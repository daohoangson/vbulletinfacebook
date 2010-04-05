<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 2.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 23:53 Oct 19th, 2009
|| # HP is so in love with ASUS!!!
|| #################################################################### ||
\*======================================================================*/
if (
	!$this->condition
	AND $this->registry->fbb['runtime']['enabled']
	AND is_array($this->info['recipients'])
) {	
	global $vbphrase;
	require_once(DIR . '/fbb/functions.php');

	$fromuser = fetch_userinfo($fromuserid);
	$tousers =& $this->info['recipients'];
	
	$fromfbuid = $fromuser['fbuid'];
	$fromfboptions = unserialize($fromuser['fboptions']);
	$tofbuids = array();
	foreach ($tousers as $touser) {
		if ($touser['fbuid']) {
			$tofbuids[$touser['fbuid']] = unserialize($touser['fboptions']);
		}
	}
	
	$skip_fbuids = array();
	
	//try to post wall posts
	if ($fromuser['userid'] == $this->registry->userinfo['userid']) {
		//skip auto sent PMs
		if ($fromfbuid AND fb_quick_options_check('actions__send_pm__post_wallpost',$fromfboptions,true,$fromuser,true)
			AND count($tofbuids) > 0) {
			$message = construct_phrase($vbphrase['fbb_template_send_pm_message']
				,$this->registry->options['bbtitle']
			);
			$action_links = array(
				array(
					'href' => $this->registry->options['bburl'] . '/private.php',
					'text' => $vbphrase['fbb_template_send_pm_read_now'],
				),
				array(
					'href' => $this->registry->options['bburl'],
					'text' => construct_phrase($vbphrase['fbb_action_link_self'],$this->registry->options['bbtitle']),
				),
			);
			
			foreach ($tofbuids as $tofbuid => $fboptions) {
				$fbpid = fb_stream_publish($message,array(),$action_links,$tofbuid);
				
				if (!fb_isErrorCode($fbpid)) {
					$skip_fbuids[] = $tofbuid;
				}
			}
		}
	}
	
	if (count($tofbuids) != count($skip_fbuids)) {
		$to_ids = array();
		
		foreach ($tofbuids as $tofbuid => $fboptions) {
			if (!in_array($tofbuid,$skip_fbuids)
				AND fb_quick_options_check('notifications__new_pm',$fboptions)) {
				$to_ids[] = $tofbuid;
			}
		}
		
		if (count($to_ids) > 0) {
			//send notifications
			$notification = construct_phrase(
				$vbphrase['fbb_template_send_pm_notification']
				,$fromuser['username']
				,$this->registry->options['bburl'] . '/private.php'
				,unhtmlspecialchars($this->fetch_field('title'))
			);
			fb_notifications_send(implode(',',$to_ids), $notification, 'user_to_user', $fromuser);
		}
	}
}
?>