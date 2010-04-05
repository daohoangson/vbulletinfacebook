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
	AND !empty($blocklist)
	AND $userinfo['fbuid']
	AND fb_quick_options_check('others__display_profileblock',unserialize($userinfo['fboptions']),true,$userinfo)
) {	
	require_once(DIR . '/fbb/class_profileblock.php');
	
	$blocklist['facebook_recent'] = array(
		'class' => 'FacebookRecent',
		'title' => $vbphrase['fbb_profileblock_recent'],
		'hook_location' => iif($vbulletin->fbb['config']['profileblock_position'] == 'center','profile_left_last','profile_right_last'),
		'options' => array(
			'display_all' => fb_quick_options_check('others__display_profileblock_all',unserialize($userinfo['fboptions']),true,$userinfo),
		),
	);
}
?>