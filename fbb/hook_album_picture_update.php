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
	$this_picture_is_ok = true;
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$idfield = 'attachmentid';
	} else {
		$idield = 'pictureid';
	}
	
	if ($vbulletin->GPC['pictures'][$picture[$idfield]]['delete'])
		if ($can_delete)
			$this_picture_is_ok = false; //going to be deleted
	
	$album = $vbulletin->GPC['pictures'][$picture[$idfield]]['album'];
	if (isset($destinations[$album]) AND ($album != $albuminfo['albumid']))
		$this_picture_is_ok = false; //going to be moved

	if ($this_picture_is_ok AND fb_quick_options_check('actions__upload_picture__share',false,true,false,$picture[$idfield])) {
		if (!isset($vbulletin->fbb['runtime']['pictures_to_share'])) $vbulletin->fbb['runtime']['pictures_to_share'] = array();
		
		$vbulletin->fbb['runtime']['pictures_to_share'][$picture[$idfield]] = $picture;
		
		if ($vbulletin->fbb['runtime']['vb4']) {
			$attachdata->set('fbbypass', 1);
		} else {
			$picturedata->set('fbbypass', 1);
		}
	}
}
?>