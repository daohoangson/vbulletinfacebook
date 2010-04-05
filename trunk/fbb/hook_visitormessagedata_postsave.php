<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 1.9.9
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 17:26 Oct 11th, 2009
|| # HP is so in love with ASUS!!!
|| #################################################################### ||
\*======================================================================*/
if (
	!$this->condition
	AND $this->registry->fbb['runtime']['enabled']
	AND is_array($this->info['profileuser'])
) {	
	global $vbphrase;
	require_once(DIR . '/fbb/functions.php');
	
	$message_pagetext = getWords($this->fetch_field('pagetext'));
	$wallpost_sent = false;
	
	//try to post wall posts
	if ($this->registry->userinfo['fbuid'] 
		AND $this->info['profileuser']['fbuid']
		AND fb_quick_options_check('actions__send_vm__post_wallpost',false,true,false,true)
	) {
		$message = construct_phrase($vbphrase['fbb_template_send_vm_message']
			,$this->registry->options['bbtitle']
			,$message_pagetext
		);
		$action_links = array(
			array(
				'href' => $this->registry->options['bburl'] . '/member.php?u=' . $this->info['profileuser']['userid'] . '&tab=visitor_messaging',
				'text' => $vbphrase['view_profile'],
			),
			array(
				'href' => $this->registry->options['bburl'],
				'text' => construct_phrase($vbphrase['fbb_action_link_self'],$this->registry->options['bbtitle']),
			),
		);
		
		$fbpid = fb_stream_publish($message,array(),$action_links,$this->info['profileuser']['fbuid']);
		
		if (!fb_isErrorCode($fbpid)) {
			$wallpost_sent = true;
		}
	}
	
	if (!$wallpost_sent) {
		//only send notification if no wallpost sent
		if ($this->info['profileuser']['fbuid']
			AND $this->info['profileuser']['fbuid'] != $this->registry->userinfo['fbuid']
			AND fb_quick_options_check('notifications__new_vm',unserialize($this->info['profileuser']['fboptions']))) {
			//send notification
			$notification = construct_phrase(
				$vbphrase['fbb_template_send_vm_notification']
				,$this->registry->options['bbtitle']
				,$message_pagetext
				,$this->registry->options['bburl'] . '/member.php?u=' . $this->info['profileuser']['userid'] . '&tab=visitor_messaging'
			);
			fb_notifications_send($this->info['profileuser']['fbuid'], $notification, 'user_to_user', $fromuser);
		}
	}
}
?>