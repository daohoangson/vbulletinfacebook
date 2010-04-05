<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 2.1
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 03:28 Oct 16th, 2009
|| # HP is so in love with ASUS!!!
|| #################################################################### ||
\*======================================================================*/
if (
	$vbulletin->fbb['runtime']['enabled']
	AND $type == 'thread'
	AND $vbulletin->userinfo['userid']
) {
	require_once(DIR . '/fbb/functions.php');

	if (fb_getForumRestrictKey($foruminfo) == 'disabled_checkboxes') {
		//well, skip everything...
	} else {
		if (fb_quick_options_check('actions__post_thread__post_short_story',false,false,false,true)) {
			//well, mark the thread to trigger publishing action (in showthread)
			$dataman->set('fbpid', -3);
		} else if (fb_quick_options_check('actions__post_thread__post_one_line',false,false,false,true)) {
			//well, mark the thread to trigger publishing action (in showthread)
			$dataman->set('fbpid', -2);
		}
	}
}
?>