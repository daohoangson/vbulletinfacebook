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
if (
	$vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->userinfo['fbuid']
) {	
	//copied from hook_album_picture_complete.php
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page!
	
	if ($albuminfo['userid'] == $vbulletin->userinfo['userid']) {
		//auto turn off sharing with this user picture
		$vbulletin->fbb['runtime']['off_checkboxes']['actions__comment_picture__share_if'] = true;
	}
	if (isset($vbulletin->fbb['runtime']['picturecomment_posters'][$vbulletin->userinfo['userid']])) {
		//auto turn off sharing with commented picture
		$vbulletin->fbb['runtime']['off_checkboxes']['actions__comment_picture__share_if'] = true;
	}
	
	$action = 'comment_picture';
	$action_dos_str = '';
	$fbb_newpost_form_template = 'fbb_new_post_options_tricky';
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}
?>