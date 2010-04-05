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
	!$this->condition
	AND $this->registry->fbb['runtime']['enabled']
) {	
	global $vbphrase;
	require_once(DIR . '/fbb/functions.php');

	$stars = intval($this->fetch_field('vote'));
	
	//try to post short story
	if ($this->registry->userinfo['fbuid'] AND fb_quick_options_check('actions__rate_thread__share')) {
		$firstpost = $this->registry->db->query_first("
			SELECT *
			FROM `" . TABLE_PREFIX . "post`
			WHERE postid = " . intval($threadinfo['firstpostid']) . "
		");
		
		//build description
		$description = getWords($firstpost['pagetext']);
	
		$message = construct_phrase($vbphrase['fbb_template_rate_thread_message' . iif($stars <= 1,'_singular','_plural')]
			,$this->registry->options['bbtitle']
			,$stars
			,$threadinfo['title']
		);
		$attachment = array(
			'name' => $threadinfo['title'],
			'href' => fb_getLink($threadinfo['threadid'],'thread'),
			'description' => $description
		);
		$action_links = array(
			array(
				'href' => fb_getLink($threadinfo['threadid'],'thread'),
				'text' => $vbphrase['fbb_template_rate_thread_check'],
			),
			array(
				'href' => $this->registry->options['bburl'],
				'text' => construct_phrase($vbphrase['fbb_action_link_self'],$this->registry->options['bbtitle']),
			),
		);
		
		$fbpid = fb_stream_publish($message,$attachment,$action_links);
	}
	
	//try to like post
	if ($this->registry->userinfo['fbuid'] 
		AND $threadinfo['fbpid']
		AND fb_quick_options_check('actions__rate_thread__post_like')) {
		fb_stream_addlike($threadinfo['fbpid'],$this->registry->userinfo['fbuid'] );
	}
		
	//try to send notifications
	$threadposter = $this->registry->db->query_first("
		SELECT userid, username, fbuid, fboptions
		FROM `" . TABLE_PREFIX . "user`
		WHERE userid = $threadinfo[postuserid]
	");
	
	if ($threadposter['fbuid'] AND fb_quick_options_check('notifications__own_thread_rated',unserialize($threadposter['fboptions']))) {
		$notification = construct_phrase(
			$vbphrase['fbb_template_rate_thread_notification' . iif($stars <= 1,'_singular','_plural')]
			,$this->registry->userinfo['username']
			,$stars
			,fb_getLink($threadinfo['threadid'],'thread')
		);
		fb_notifications_send($threadposter['fbuid'], $notification, 'user_to_user');
	}
}
?>