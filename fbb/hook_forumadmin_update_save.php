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
if (!empty($vbulletin->GPC['forum']['fbtarget_id']))
{
	if (empty($vbulletin->GPC['forum']['fbuid'])) {
		print_stop_message('fbb_forum_publish_require_fbuid');
	}
	
	$page_owner = $vbulletin->db->query_first("
		SELECT userid, username, fbuid, fbssk
		FROM `" . TABLE_PREFIX . "user`
		WHERE fbuid = '{$vbulletin->GPC['forum']['fbuid']}'
	");
	
	if (!$page_owner) {
		print_stop_message('fbb_no_user_found');
	}
	
	if (empty($page_owner['fbssk'])) {
		print_stop_message('fbb_forum_publish_require_offline_access',$page_owner['username']);
	}
}
?>