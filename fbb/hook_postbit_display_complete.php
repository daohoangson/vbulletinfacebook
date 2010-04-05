<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.0
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 22:42 Jan 07th, 2010
|| #################################################################### ||
\*======================================================================*/
if ($this->registry->fbb['runtime']['enabled']
	AND $this->registry->fbb['config']['display_elements']['postbit_share_button']
	AND !empty($thread) AND !$thread['isdeleted'] AND $thread['open']
	AND !empty($forum)
	AND !$post['isdeleted']
) {
	require_once(DIR . '/fbb/functions.php');
	
	if (fb_getForumRestrictKey($forum,true) == '') {
		
		global $ids;

		if ($this->registry->fbb['config']['share_count'] AND empty($this->registry->fbb['runtime']['fbb_links_queried'])) {
			$this->registry->fbb['runtime']['fbb_links_queried'] = true; //marked as work done!
			
			//ONE TIME query database for ALL posts
			if (!$ids) {
				//showpost
				$postids_tmp = array($post['postid']);
			} else {
				//showthread
				//use the original $ids
				if ($this->registry->fbb['runtime']['vb4']) {
					$postids_tmp = $ids;
				} else {
					$postids_tmp = array();
					foreach (explode(',',$ids) as $postid_tmp) {
						if ($postid_tmp) {
							$postids_tmp[] = $postid_tmp;
						}
					}
				}
			}
			
			if (count($postids_tmp)) {
				$links_from_db_result = $this->registry->db->query_read_slave("
					SELECT *
					FROM `" . TABLE_PREFIX . "fbb_links`
					WHERE postid IN (" . implode(",",$postids_tmp) . ")
				");
				
				$ids_need_update = $postids_tmp;
				while ($link = $this->registry->db->fetch_array($links_from_db_result)) {
					$urls[$link['url']] = $link;
					
					if ($link['updated'] > TIMENOW - $this->registry->fbb['config']['share_count_cache_timeout']) {
						//Still up-to-date, skip updating
						$key_tmp = array_search($link['postid'],$ids_need_update);
						if ($key_tmp !== false) {
							unset($ids_need_update[$key_tmp]);
						}
					}
				}
				
				DEVDEBUG('Preparing to update link statistic for postid: ' . implode(', ',$ids_need_update));
				
				$urls_need_update = array();
				if (count($ids_need_update)) {
					foreach ($ids_need_update as $postid_tmp) {
						$urls_need_update[$postid_tmp] = fb_getLink($postid_tmp);
					}
					
					if (count($urls_need_update)) {
						$links_from_fb = fb_fql_query("
							SELECT share_count, like_count, comment_count, url 
							FROM link_stat 
							WHERE url IN ('" . implode("','",$urls_need_update) . "')
						");

						if (is_array($links_from_fb)) {
							foreach ($links_from_fb as $link) {
								if (!empty($urls[$link['url']])) {
									$link['linkid'] = $urls[$link['url']]['linkid']; //mark as existed, update instead of insert later
								}
								$link['updated'] = TIMENOW;
								$urls[$link['url']] = $link;
							}
						}
						
						foreach ($urls_need_update as $postid_tmp => $url) {
							if (empty($urls[$url])) {
								//prevent duplicate requests
								$urls[$url] = array(
									'url' => $url,
									'updated' => TIMENOW,
									'share_count' => 0,
									'like_count' => 0,
									'comment_count' => 0,
								);
							}
							
							if ($urls[$url]['updated'] == TIMENOW) {
								$link = $urls[$url];
								$link['postid'] = $postid_tmp;
								if ($link['linkid']) {
									$this->registry->db->query_write(fetch_query_sql($link,'fbb_links','WHERE linkid = ' . $link['linkid']));
								} else {
									$this->registry->db->query_write(fetch_query_sql($link,'fbb_links'));
								}
							}
						}
					}
				}
				
				$this->registry->fbb['runtime']['share_urls'] = $urls;
				unset($urls,$links_from_db_result,$ids_need_update,$urls_need_update,$links_from_fb,$link);
			}
		}
	
		$url_raw = fb_getLink($post['postid']);
		$url_encoded = urlencode($url_raw); 
		$title_encoded = urlencode(iif($post['title'],$post['title'],$thread['title']));
		$show['share_count'] = $this->registry->fbb['config']['share_count'];
		if ($this->registry->fbb['config']['share_count']) {
			$link_statistic = array(
				'share_count' => vb_number_format($this->registry->fbb['runtime']['share_urls'][$url_raw]['share_count']),
				'like_count' => vb_number_format($this->registry->fbb['runtime']['share_urls'][$url_raw]['like_count']),
				'comment_count' => vb_number_format($this->registry->fbb['runtime']['share_urls'][$url_raw]['comment_count']),
				'updated' => vbdate(
					$this->registry->options['dateformat'] . ' ' . $this->registry->options['timeformat']
					,$this->registry->fbb['runtime']['share_urls'][$url_raw]['updated']
				),
			);
		}
		
		if ($this->registry->fbb['runtime']['vb4']) {
			$templater = vB_Template::create('fbb_vb4_postbit_controls');
				$templater->register('post', $post);
				$templater->register('url_encoded', $url_encoded);
				$templater->register('title_encoded', $title_encoded);
				$templater->register('show', $show);
				$templater->register('link_statistic', $link_statistic);
			$template_hook['postbit_controls'] .= $templater->render();
		} else {
			eval('$template_hook["postbit_controls"] .= "' . fetch_template('fbb_postbit_controls') . '";');
		}
	}
}
?>