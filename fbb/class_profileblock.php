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
/**
* Profile Block for recent Facebook posts
**/
class vB_ProfileBlock_FacebookRecent extends vB_ProfileBlock
{
	/**
	* The name of the template to be used for the block
	*
	* @var string
	*/
	var $template_name = 'fbb_memberinfo_block_recent';
	
	/**
	* Sets/Fetches the default options for the block
	*
	*/
	function fetch_default_options()
	{
		$this->option_defaults = array(
			'limit' => 50,
			'display_all' => false,
		);
	}

	/**
	* Whether to return an empty wrapper if there is no content in the blocks
	*
	* @return bool
	*/
	function confirm_empty_wrap()
	{
		return false;
	}

	/**
	* Should we actually display anything?
	*
	* @return	bool
	*/
	function confirm_display()
	{
		return true;
	}

	/**
	* Prepare any data needed for the output
	*
	* @param	string	The id of the block
	* @param	array	Options specific to the block
	*/
	function prepare_output($id = '', $options = array())
	{
		global $vbulletin, $vbphrase;
		
		if ($vbulletin->fbb['runtime']['vb4']) {
			$this->template_name = str_replace('fbb_','fbb_vb4_',$this->template_name);
		}
	
		if (is_array($options))
		{
			$options = array_merge($this->option_defaults, $options);
		}
		else
		{
			$options = $this->option_defaults;
		}

		$posts_str = '';
		
		$fboptions = unserialize($this->profile->userinfo['fboptions']);
		
		if (empty($fboptions['recentposts'])
			OR $fboptions['recentposts']['cached_time'] < TIMENOW - $this->registry->fbb['config']['display_profileblock_cache_timeout'])
		{
			$fboptions['recentposts'] = array(
				'cached_time' => TIMENOW,
				'posts' => array(),
			);
		
			if ($this->profile->userinfo['fbuid'] AND $this->profile->userinfo['fbssk']) {
				fb_forcessk($this->profile->userinfo);
				$from_fb = fb_stream_get(
					null
					, $this->profile->userinfo['fbuid']
					, 0
					, 0
					, $options['limit']
					, 'profiles');
				$posts_from_fb = $from_fb['posts'];
				/*
				$posts_from_fb = fb_fql_query("
					SELECT
						created_time
						, message
						, attachment
						, comments 
						, likes 
						, permalink 
					FROM stream
					WHERE
						source_id = {$this->profile->userinfo['fbuid']}
						" . iif($options['display_all'],'',"AND actor_id  = {$this->profile->userinfo['fbuid']}") . "
					LIMIT {$options['limit']}
				");
				*/
				
				$profiles_tmp = array();
				if (!empty($from_fb['profiles'])) {
					foreach ($from_fb['profiles'] as $profile) {
						$profiles_tmp[$profile['id']] = $profile;
					}
				}
				
				$posts_tmp = array();
				if (!empty($posts_from_fb)) {
					foreach ($posts_from_fb as $post) {
						$post_tmp = array(
							'created_time' => $post['created_time'],
							'actor_id' => $post['actor_id'],
							'message' => $post['message'],
							'attachment' => array(),
							'comments' => array(
								'count' => $post['comments']['count'],
							),
							'likes' => array(
								'href' => $post['likes']['href'],
								'count' => $post['likes']['count'],
							),
							'permalink' => $post['permalink'],
						);
						
						if (!empty($post['attachment'])) {
							$post_tmp['attachment'] = array(
								'icon' => $post['attachment']['icon'],
								'media' => $post['attachment']['media'],
								'href' => $post['attachment']['href'],
								'name' => $post['attachment']['name'],
								'description' => $post['attachment']['description'],
								'fb_object_type' => $post['attachment']['fb_object_type'],
							);
						}
						
						if (!empty($profiles_tmp[$post['actor_id']])) {
							$post_tmp['actor'] = array(
								'id' => $profiles_tmp[$post['actor_id']]['id'],
								'url' => $profiles_tmp[$post['actor_id']]['url'],
								'name' => $profiles_tmp[$post['actor_id']]['name'],
							);
						}
						
						$posts_tmp[] = $post_tmp;
					}
				}
				$fboptions['recentposts']['posts'] = $posts_tmp;
				
				// caching procedure
				$this->profile->userinfo['fboptions'] = serialize($fboptions);
				$vbulletin->db->query("
					UPDATE `" . TABLE_PREFIX . "user`
					SET fboptions = '" . $vbulletin->db->escape_string($this->profile->userinfo['fboptions']) . "'
					WHERE userid = {$this->profile->userinfo['userid']}
				");
				
				DEVDEBUG('QUERIED RECENT POSTS');
			}
		} else {
			DEVDEBUG('SKIPPED LOADING RECENT POSTS');
		}
		
		$posts = $fboptions['recentposts']['posts'];

		if (!empty($posts) AND is_array($posts)) {
			foreach ($posts as $post) {
				$post['created_time_str'] = vbdate($vbulletin->options['dateformat'] . ', ' . $vbulletin->options['timeformat'], $post['created_time']);
				
				switch ($post['attachment']['fb_object_type']) {
					case 'photo':
						$post['message'] .= ' (<a href="' . $post['attachment']['href'] . '" title="' . htmlspecialchars($post['attachment']['media'][0]['alt']) . '">Photo</a>)';
						break;
					case 'album':
						$post['message'] = $post['attachment']['description'] . ' (Photo Album: <a href="' . $post['attachment']['href'] . '" title="' . htmlspecialchars($post['attachment']['name']) . '">' . $post['attachment']['name'] . '</a>)';
						break;
					default:
						if ($post['message']) {
							if ($post['attachment']['name']) {
								$post['message'] .= ' (<a href="' . $post['attachment']['href'] . '" title="' . htmlspecialchars(strip_tags($post['attachment']['description'])) . '">' . $post['attachment']['name'] . '</a>)';
							}
						} else {
							if ($post['message'] OR $post['attachment']['description']) {
								$post['message'] = '<strong><a href="' . $post['attachment']['href'] . '">' . $post['attachment']['name'] . '</a></strong> ' . $post['attachment']['description'];
							}
						}
						break;
				}
				
				if (!$post['message']) continue; //skip because there is nothing to display
				
				if ($post['actor_id'] != $this->profile->userinfo['fbuid']) {
					if (!$options['display_all']) continue; //skip posts from other user
					if (!empty($post['actor'])) {
						$post['actor_str'] = '<a href="' . $post['actor']['url'] . '">' . $post['actor']['name'] . '</a>';
					} else {
						continue; //skip because of missing data
					}
				}
				
				if ($post['attachment']['icon'] == 'http://api.facebook.com/images/icons/hidden.gif') {
					unset($post['attachment']['icon']);
				}
			
				$bgclass = exec_switch_bg();
				
				if ($this->registry->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('fbb_memberinfo_block_postbit');
						$templater->register('bgclass', $bgclass);
						$templater->register('post', $post);
					$posts_str .= $templater->render();
				} else {
					eval('$posts_str .= "' . fetch_template('fbb_memberinfo_block_postbit') . '";');
				}
			}
		}

		$this->block_data['posts'] = $posts_str;
	}
}
?>