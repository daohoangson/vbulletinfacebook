<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.2 - Bugs fixed
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 14:44 Mar 20th, 2010
|| #################################################################### ||
\*======================================================================*/
if ($oldsetting['varname'] == 'fbb_apikey'
	AND $oldsetting['value'] != $newvalue
	AND $settings['fbb_apikey']
	AND $settings['fbb_secret']) 
{
	//well, the administrator changed the API key
}

if ($oldsetting['varname'] == 'fbb_share_count'
	AND $newvalue > 0) {

	if ($newvalue < 120) {
		print_stop_message('fbb_share_count_cache_timeout_too_low');
	}
	
	//we will have to create links table
	
	$vbulletin->db->hide_errors();		
	$vbulletin->db->query("
		CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "fbb_links` (
			`linkid` int(11) NOT NULL AUTO_INCREMENT,
			`postid` int(11) DEFAULT 0,
			`url` text NOT NULL,
			`share_count` int(11) DEFAULT 0,
			`comment_count` int(11) DEFAULT 0,
			`like_count` int(11) DEFAULT 0,
			`updated` int(11) NOT NULL,
			PRIMARY KEY (`linkid`),
			KEY (`postid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
	$vbulletin->db->show_errors();
}

if ($oldsetting['optioncode'] == 'fboptions') {
	if ($newvalue != 'all') {
		//"Custom Selection" selected
		$vbulletin->input->clean_array_gpc('p', array(
			'fboptions' => TYPE_ARRAY
		));
		
		$fboptions = array();
		foreach ($vbulletin->GPC['fboptions'] as $key) {
			$value = 1;
			if ($value) {
				$fboptions[$key] = 1;
			}
		}
		
		//store the options
		//can't use bitfield since the options array is expandable...
		$newvalue = serialize(array('fboptions' => $fboptions)); 
	}
}

//3.2
if ($oldsetting['varname'] == 'fbb_comment_as_reply'
	AND $newvalue > 0) {

	if ($newvalue < 900) {
		print_stop_message('fbb_comment_as_reply_timeout_too_low');
	}
	
	//we will have to create tracking field and table
	
	$vbulletin->db->hide_errors();
	$vbulletin->db->query("
		ALTER TABLE `" . TABLE_PREFIX . "post`
			ADD COLUMN `fbuid` varchar(255) DEFAULT '0'
	");
	$vbulletin->db->query("
		ALTER TABLE `" . TABLE_PREFIX . "thread`
			ADD COLUMN `fblastcomment` int(11) DEFAULT '0'
			,ADD COLUMN `fblastcomment_sync` int(11) DEFAULT '0'
	");
	$vbulletin->db->show_errors();
}
?>