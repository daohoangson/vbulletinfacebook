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
	AND $vbulletin->userinfo['fbuid']
) {	
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page!
	
	if (fb_quick_options_check('actions__post_thread') OR fb_quick_options_check('others__always_display_checkboxes')) {
		require_once(DIR . '/fbb/functions.php');
		
		$key_to_update = fb_getForumRestrictKey($foruminfo);
		
		if ($key_to_update) {
			foreach ($vbulletin->fbb['user_options_config']['actions']['post_thread'] AS $action_do => $config) {
				$vbulletin->fbb['runtime'][$key_to_update]['actions__post_thread__' . $action_do] = true;
			}
		}
	}
	
	$action = 'post_thread';
	$action_dos_str = '';
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}
?>