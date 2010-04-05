<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 19:05 Jan 22nd, 2010
|| #################################################################### ||
\*======================================================================*/
if ($userdata->existing['fbuid'] 
	AND empty($vbulletin->GPC['currentpassword'])
) {
	require_once(DIR . '/fbb/functions.php');
	
	$facebook_response = validate_cookie();
	if ($userdata->existing['fbuid'] == $facebook_response['user']) {
		$fake_password = 'afakepassword';
		$vbulletin->GPC['currentpassword'] = $fake_password;
		$vbulletin->GPC['currentpassword_md5'] = md5($fake_password);
		$vbulletin->userinfo['password'] = md5(md5($fake_password) . $vbulletin->userinfo['salt']);
		
		$check_password = $userdata->hash_password($userdata->verify_md5($vbulletin->GPC['currentpassword_md5']) ? $vbulletin->GPC['currentpassword_md5'] : $vbulletin->GPC['currentpassword'], $vbulletin->userinfo['salt']);
	}
}
?>