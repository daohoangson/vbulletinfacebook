<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 19:06 Jan 22nd, 2010
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

//Update Facebook Profiles' FBML
$lastactivity_threshold = 0;
$lastactivity_threshold = TIMENOW - 2*60*60; //2 hours

$users_from_db = $vbulletin->db->query_read("
	SELECT user.*
	FROM " . TABLE_PREFIX . "user AS user
	WHERE user.fboptions LIKE '%s:19:\"display_profile_box\";i:1%'
		AND user.lastactivity > $lastactivity_threshold
	ORDER BY user.lastpost DESC
	LIMIT 25;
");

while ($user = $vbulletin->db->fetch_array($users_from_db)) {
	if (fb_update_profile_box($user)) {
		$log_info[] = $user['username'];
	}
}

if (rand(1,10) < 5) { 
	// 50% chance to run this script
	fb_update_profile_box_static_parts();
	$log_info[] = 'Statistics';
}

if ($vbulletin->options['fbb_log_cleanup']) {
	//sometimes our init code won't be run so we need to use direct option array from vBulletin
	$timelimit = TIMENOW - $vbulletin->options['fbb_log_cleanup'] * 24 * 60 * 60;
	$vbulletin->db->query("
		DELETE FROM `" . TABLE_PREFIX . "fbb_log`
		WHERE timeline < $timelimit
	");
	$log_info[] = 'YAFB\'s log';
}

if ($log_info) {
	log_cron_action(implode(', ',$log_info), $nextitem, 1);
}
?>