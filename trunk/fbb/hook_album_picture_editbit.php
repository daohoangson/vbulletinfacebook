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
	AND $albuminfo['userid'] == $vbulletin->userinfo['userid']
) {	
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page (in case member quick replies)
	
	$turn_off_now = false;
	//Prevent posting private album pictures
	if ($albuminfo['state'] != 'public') {
		$turn_off_now = true;
	}
	//Prevent reposting old pictures
	if ($picture['dateline'] < TIMENOW - 60) {
		$turn_off_now = true;
	}
	if (!isset($picture['fbbypass'])) {
		//this won't work because lack of field in mysql query
		$turn_off_now = true;
	}
	
	$action = 'upload_picture';
	$action_dos_str = '';
	$fbb_newpost_form_template = 'fbb_album_editpictures_options';
	$fbb_form_element_target = '$picture[caption]';
	if ($vbulletin->fbb['runtime']['vb4']) {
		$fbb_form_element_id = $picture['attachmentid'];
	} else {
		$fbb_form_element_id = $picture['pictureid'];
	}
	if ($turn_off_now) {
		$vbulletin->fbb['runtime']['off_checkboxes']['actions__upload_picture__share__' . $fbb_form_element_id] = true;
	}
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}
?>