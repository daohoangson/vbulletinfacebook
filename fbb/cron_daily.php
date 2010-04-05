<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 21:13 Jan 22nd, 2010
|| #################################################################### ||
\*======================================================================*/
// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
if (!is_object($vbulletin->db))
{
	exit;
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
if (empty($style)) {
	$db =& $vbulletin->db;
	
	$vbphrase_store = $vbphrase;
	unset($vbphrase);
	
	require_once(DIR . '/global.php');
	
	$vbphrase = $vbphrase_store;
}
require_once(DIR . '/fbb/functions.php');

$log_info = array();

$users_from_db = $vbulletin->db->query_read("
	SELECT user.userid, user.email, user.fbuid, user.fbemail
	FROM " . TABLE_PREFIX . "user AS user
	WHERE user.fbemail = 1
		AND user.fbuid <> '0'
");

$users = array();
$need2update = array();
while ($user = $vbulletin->db->fetch_array($users_from_db)) {
	$users[$user['fbuid']] = $user;
}

if (!empty($users)) {
	$users_from_fb = fb_fql_query("
		SELECT uid, email
		FROM user
		WHERE uid IN (" . implode(',',array_keys($users)) . ")
	");
	
	foreach ($users_from_fb as $fbuser) {
		$vbuser =& $users[$fbuser['uid']];
		if ($fbuser['email'] AND $vbuser['email'] != $fbuser['email']) {
			$need2update[$vbuser['userid']] = $fbuser['email'];
		}
	}
}

if (!empty($need2update)) {
	foreach ($need2update as $userid => $email) {
		$vbulletin->db->query("
			UPDATE `" . TABLE_PREFIX . "user`
			SET email = '" . $email . "'
			WHERE userid = $userid
		");
		$log_info[] = $email;
	}
}

if ($log_info)
{
	log_cron_action(implode(', ',$log_info), $nextitem, 1);
}
?>