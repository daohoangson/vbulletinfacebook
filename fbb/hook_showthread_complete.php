<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.2 - Bugs fixed
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 14:44 Mar 20th, 2010
|| #################################################################### ||
\*======================================================================*/
if (
	$vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->userinfo['fbuid']
	AND $threadinfo['postuserid'] == $vbulletin->userinfo['userid']
	AND ($threadinfo['fbpid'] == -2 OR $threadinfo['fbpid'] == -3)
) {	
	require_once(DIR . '/fbb/functions.php');

	$firstpost = $vbulletin->db->query_first("
		SELECT *
		FROM `" . TABLE_PREFIX . "post`
		WHERE postid = " . intval($threadinfo['firstpostid']) . "
	");
	
	list($fb_title,$description,$fb_media) = fb_getPostInfo($firstpost,$threadinfo,$foruminfo);
	$template_data = array(
		'post_title' => $threadinfo['title'],
		'post_url' => fb_getLink($threadinfo['firstpostid'],'post'),
		'thread_title' => $threadinfo['title'],
		'thread_url' => fb_getLink($threadinfo['threadid'],'thread'),
		'forum_title' => $foruminfo['title'],
		'forum_url' => fb_getLink($foruminfo['forumid'],'forum'),
		'description' => $description,
	);
	
	//get images
	if (is_array($fb_media['images'])) {
		for ($i = 0; $i < min(3,count($fb_media['images'])); $i++) {
			$template_data['images'][] = array(
				'src' => $fb_media['images'][$i]['src'],
				'href' => $template_data['thread_url'],
			);
		}
	}

	if ($threadinfo['fbpid'] == -2) {
		$fbpid = 0;
	} else if ($threadinfo['fbpid'] == -3) {
		$message = construct_phrase($vbphrase['fbb_template_post_thread_message']
			,$vbulletin->userinfo['username']
			,$template_data['thread_title']
			,$template_data['thread_url']
			,$template_data['forum_title']
			,$template_data['forum_url']);
		$attachment = array(
			'name' => $template_data['thread_title'],
			'href' => $template_data['thread_url'],
			'caption' => construct_phrase(
				$vbphrase['fbb_template_post_thread_caption']
				,'{*actor*}'
				,$template_data['thread_title']
				,$template_data['thread_url']
				,$template_data['forum_title']
				,$template_data['forum_url']
			),
			'description' => construct_phrase(
				$vbphrase['fbb_template_post_thread_description']
				,$template_data['description']
				,$template_data['thread_title']
				,$template_data['thread_url']
				,$template_data['forum_title']
				,$template_data['forum_url']
			),
		);
		if (isset($template_data['images'])) {
			$attachment['media'] = array();
			foreach ($template_data['images'] as $image) {
				$attachment['media'][] = array(
					'type' => 'image',
					'src' => $image['src'],
					'href' => $image['href'],
				);
			}
		}
		$action_links = array(
			array(
				'href' => $template_data['forum_url'],
				'text' => $template_data['forum_title'],
			),
			array(
				'href' => $vbulletin->options['bburl'],
				'text' => construct_phrase($vbphrase['fbb_action_link_self'],$vbulletin->options['bbtitle']),
			),
		);
		
		$fbpid = fb_stream_publish($message,$attachment,$action_links,null,null,true,array('threadid' => $threadinfo['threadid']));
		
		if (fb_isErrorCode($fbpid)) {
			$fbpid = 0;
		}
	}
	
	$vbulletin->db->query("
		UPDATE `" . TABLE_PREFIX . "thread`
		SET fbpid = '$fbpid'
		WHERE threadid = " . intval($threadinfo['threadid']) . "
	");
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->userinfo['fbuid']
	AND $show['quickreply']
) {	
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page (in case member quick replies)
	
	//this scope is the same as the scope in hook_newthread_form_complete.php
	if (fb_quick_options_check('actions__post_reply') OR fb_quick_options_check('others__always_display_checkboxes')) {
		require_once(DIR . '/fbb/functions.php');
		
		$key_to_update = fb_getForumRestrictKey($foruminfo);
		
		if ($key_to_update) {
			foreach ($vbulletin->fbb['user_options_config']['actions']['post_reply'] AS $action_do => $config) {
				$vbulletin->fbb['runtime'][$key_to_update]['actions__post_reply__' . $action_do] = true;
			}
		}
	}
	
	$action = 'post_reply';
	$action_dos_str = '';
	$fbb_newpost_form_template = 'fbb_new_post_options_tricky';
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}
?>