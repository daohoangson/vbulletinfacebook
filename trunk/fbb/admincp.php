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
error_reporting(E_ALL & ~E_NOTICE);
define('THIS_SCRIPT', 'fbb_admin');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('logging');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
include_once('./global.php');
include_once(DIR . '/fbb/functions.php');

$processed = false;

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
if ($_REQUEST['do'] == 'revoke') {
	$vbulletin->input->clean_array_gpc('r', array(
		'userid' => TYPE_UINT,
		'permission' => TYPE_STR,
	));
	
	$userinfo = fetch_userinfo($vbulletin->GPC['userid']);

	if (!$userinfo['fbuid']) print_stop_message('fbb_no_user_found');
	
	if ($vbulletin->GPC['permission']) {
		if (!in_array($vbulletin->GPC['permission'],array_keys($vbulletin->fbb['user_permissions_config']))) {
			print_stop_message('fbb_user_permission_invalid');
		}
		
		if (fb_auth_revokeExtendedPermission($vbulletin->GPC['permission'],$userinfo['fbuid'])) {
			define('CP_REDIRECT','fbb_admin.php?do=users&userid=' . $userinfo['userid']);
			print_stop_message('fbb_permission_revoke_done');
		} else {
			print_stop_message('fbb_permission_revoke_fail');
		}
	} else {
		if (fb_auth_revokeAuthorization($userinfo['fbuid'])) {
			$vbulletin->db->query("
				UPDATE `" . TABLE_PREFIX . "user`
				SET fbuid = '0', fbssk = ''
				WHERE userid = $userinfo[userid]
			");
			
			define('CP_REDIRECT','fbb_admin.php?do=users');
			print_stop_message('fbb_permission_revoke_authorization_done');
		} else {
			print_stop_message('fbb_permission_revoke_fail');
		}
	}
}

if ($_REQUEST['do'] == 'users') {
	print_cp_header($vbphrase['fbb_user_manager']);

	//Page processing
	$query_str = '';
	$sqlcond = "WHERE user.fbuid <> '0'";
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'userid' => TYPE_UINT,
	));

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 25;
	} else {
		$query_str = "&perpage={$vbulletin->GPC['perpage']}";
	}

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM `" . TABLE_PREFIX . "user` AS user
		$sqlcond");
		
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);
	
	if ($vbulletin->GPC['userid']) {
		$user = $vbulletin->db->query_first("
			SELECT *
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid = {$vbulletin->GPC['userid']}
		");
		
		if ($user) {
			if ($user['fbuid']) {
				$fbuserinfo = fb_fetch_userinfo($user['fbuid'],array());
				$isFbConnected = fb_fetch_permission($user['fbuid']);
				foreach ($vbulletin->fbb['user_permissions_config'] as $permission => $isRequired) {
					$fbuserinfo['permissions'][$permission] = fb_fetch_permission($user['fbuid'],$permission);
				}
				
				print_table_start();
				print_table_header(construct_phrase($vbphrase['fbb_user_view'],$user['username']));
				foreach ($fbuserinfo as $key => $value) {
					if (is_string($value)) {
						switch ($key) {
							case 'profile_url': $value = "<a href=\"$value\" target=\"_blank\">$value</a>";
						}
						if (!trim($value)) $value = $vbphrase['fbb_user_view_field_not_public'];
						print_label_row($vbphrase['fbb_user_view_field_' . $key],$value);
					}
				}
				print_label_row(
					$vbphrase['fbb_permission_connected']
					,iif(
						$isFbConnected
						,$vbphrase['fbb_permissions_status_good'] 
						. construct_link_code($vbphrase['fbb_user_disconnect'],"fbb_admin.php?do=revoke&userid=$user[userid]")
						,$vbphrase['fbb_permissions_status_bad']
					)
				);
				foreach ($fbuserinfo['permissions'] as $permission => $permission_state) {
					$name = $vbphrase['fbb_permission_' . $permission];
					print_label_row(
						iif($name,$name,$permission)
						,iif(
							$permission_state
							,$vbphrase['fbb_permissions_status_good']
							,$vbphrase['fbb_permissions_status_bad']
						)
						. iif(
							$permission_state
							,construct_link_code($vbphrase['fbb_user_revoke'],"fbb_admin.php?do=revoke&userid=$user[userid]&permission=$permission")
						)	
					);
				}
				print_table_footer();
				
				$before = $vbulletin->db->query_first("
					SELECT COUNT(*) AS total
					FROM `" . TABLE_PREFIX . "user` AS user
					$sqlcond
						AND user.username < '" . $vbulletin->db->escape_string($user['username']) . "'
					ORDER BY user.username ASC
				");
				$vbulletin->GPC['pagenumber'] = floor($before['total']/$vbulletin->GPC['perpage']) + 1;
			} else {
				print_stop_message('fbb_user_x_is_not_connected',$user['username']);
			}
		} else {
			print_stop_message('no_results_matched_your_query');
		}
	}
	
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	
	$users = $vbulletin->db->query_read("SELECT *
		FROM `" . TABLE_PREFIX . "user` AS user
		$sqlcond
		ORDER BY user.username ASC
		LIMIT $startat,{$vbulletin->GPC['perpage']}");
		
	if ($vbulletin->db->num_rows($users)) {
		$location_prefix = "fbb_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=users&perpage=" . $vbulletin->GPC['perpage'] . "&page";
		//Page processing (buttons)
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='$location_prefix=1$query_str'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='$location_prefix=$prv$query_str'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='$location_prefix=$nxt$query_str'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='$location_prefix=$totalpages$query_str'\">";
		}
	
		print_table_start();
		print_table_header($vbphrase['fbb_user_manager'] . ' - Total: ' . $counter['total'] . iif($totalpages > 1," (Page: {$vbulletin->GPC['pagenumber']}/$totalpages)"),4);
		
		$headings = array();
		$headings[] = '';
		$headings[] = $vbphrase['username'];
		$headings[] = 'Facebook UID';
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);
		
		while($user = $vbulletin->db->fetch_array($users)) {
			$cell = array();
			$cell[] = $user['userid'];
			$cell[] = $user['username'];
			$cell[] = $user['fbuid'];
			$cell[] = 
				construct_link_code($vbphrase['view'],"fbb_admin.php?do=users&userid=$user[userid]");
			
			print_cells_row($cell);
		}
	
		print_table_footer(4, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	} else {
		print_stop_message('fbb_no_user_found');
	}
	print_cp_footer();
}

if ($_REQUEST['do'] == 'send_notification') {
	print_cp_header($vbphrase['fbb_send_notification']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'notification'    => TYPE_STR,
		'url'    => TYPE_STR,
		'url_text'    => TYPE_STR,
	));
	
	if ($vbulletin->GPC['url']) {
		if (!$vbulletin->GPC['url_text']) {
			$vbulletin->GPC['url_text'] = construct_phrase($vbphrase['fbb_action_link_self'],$vbulletin->options['bbtitle']);
		}
		$vbulletin->GPC['notification'] .= " <a href=\"{$vbulletin->GPC['url']}\">{$vbulletin->GPC['url_text']}</a>";
	}
	
	if ($vbulletin->GPC['notification']) {
		$fbuids_from_db = $vbulletin->db->query_read("
			SELECT fbuid
			FROM `" . TABLE_PREFIX . "user`
			WHERE fbuid <> '0'
		");
		$pack_size = 40; //Facebook suggests 50 but we will take 40 uids per time
		$packs = array();
		$counter = 0;
		$sent_counter = 0;
		while ($fbuid = $vbulletin->db->fetch_array($fbuids_from_db)) {
			$counter++;
			$i = floor($counter / $pack_size);
			$packs[$i][] = $fbuid['fbuid'];
		}

		echo 'Found ', $counter, ' Connected Users. Ready to send notifications...<br/>';
		echo 'FYI: We got ', count($packs), ' pack(s) to send. Each pack contain maximum ', $pack_size, ' user<br/>';
		
		foreach ($packs as $packid => $pack) {
			set_time_limit(30); //each pack should be process in 30 seconds or less
			
			echo 'Sending pack #', $packid, ' (' . count($pack) . ' user(s)): ', implode(', ',$pack);
			$result = fb_notifications_send(
				implode(',',$pack)
				,$vbulletin->GPC['notification']
				,'app_to_user'
			);
			
			if (fb_isErrorCode($result)) {
				echo ' - <strong>FAILED</strong>';
			} else {
				echo ' - <strong>COMPLETED</strong>';
				
				$users = array();
				$users_from_db = $vbulletin->db->query_read("
					SELECT username, fbuid
					FROM `" . TABLE_PREFIX . "user`
					WHERE fbuid IN ('" . implode("','",explode(',',$result)) . "')
				");
				while ($user = $vbulletin->db->fetch_array($users_from_db)) {
					$users[] = "$user[username] ($user[fbuid])";
				}
				echo '<br/>Sent to (' . count($users) . ' user(s)): ', implode(', ',$users);
				$sent_counter += count($users);
			}
			
			echo '<br/>';
			vbflush();
		}
		
		print_stop_message('fbb_sent_notification_to_x_users',$sent_counter);
	}

	$announcement_notifications_per_week = fb_admin_getAllocation('announcement_notifications_per_week');
	$connected_counter = $vbulletin->db->query_first("
		SELECT COUNT(*) AS total
		FROM `" . TABLE_PREFIX . "user`
		WHERE fbuid <> '0'
	");
	$connected_total = $connected_counter['total'];
	
	print_form_header('fbb_admin', 'send_notification');
	print_table_header($vbphrase['fbb_send_notification']);
	print_description_row($vbphrase['fbb_send_notification_desc']);
	print_label_row($vbphrase['fbb_announcement_notifications_per_week'],$announcement_notifications_per_week);
	print_label_row($vbphrase['fbb_connected_total'],$connected_total);
	print_textarea_row($vbphrase['message'],'notification');
	print_input_row($vbphrase['fbb_notification_url'],'url',$vbulletin->options['bburl']);
	print_input_row($vbphrase['fbb_notification_url_text'],'url_text',construct_phrase($vbphrase['fbb_action_link_self'],$vbulletin->options['bbtitle']));

	print_submit_row($vbphrase['go']);
	
	print_cp_footer();
}

if ($_REQUEST['do'] == 'migration') {
	print_cp_header($vbphrase['fbb_migration']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'source' => TYPE_STR,
		'turn_on' => TYPE_UINT,
	));
	
	$check_facebook_connect = $vbulletin->db->query_first("SHOW TABLES LIKE '" . TABLE_PREFIX . "fbuser'");
	$check_vbnexus = $vbulletin->db->query_first("SHOW TABLES LIKE '" . TABLE_PREFIX . "vbnexus_nonvbuser'");
	$supported_list = array(
		'' => $vbphrase['please_select_one'],
	);
	if ($check_facebook_connect) {
		$supported_list['facebook_connect'] = 'Facebook Connect';
	}
	if ($check_vbnexus) {
		$supported_list['vbnexus'] = 'vB Nexus';
	}
	
	if ($vbulletin->GPC['source'] AND isset($supported_list[$vbulletin->GPC['source']])) {
		$migrated_counter = 0;
		
		switch ($vbulletin->GPC['source']) {
			case 'facebook_connect':
				$vbuid_field = 'userid';
				$fbuid_field = 'fbuid';
				$query_table = 'fbuser';
				$query_where = '';
				break;
			case 'vbnexus':
				$vbuid_field = 'userid';
				$fbuid_field = 'nonvbid';
				$query_table = 'vbnexus_nonvbuser';
				$query_where = "AND source.type = '1'";
				break;
		}
		$source_users_result = $vbulletin->db->query_read("
			SELECT
				source.$vbuid_field AS userid
				,user.username AS username
				,source.$fbuid_field AS fbuid
			FROM `" . TABLE_PREFIX . "$query_table` AS source
			INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = source.$vbuid_field)
			WHERE 1=1
				$query_where
		");
		$fboptions = array();
		if ($vbulletin->GPC['turn_on']) {
			require_once(DIR . '/fbb/hook_init_startup.php');
			init_startup_additional_task();
			$fboptions = fb_get_default_options_set();
		}
		$fboptions_str = $vbulletin->db->escape_string(serialize($fboptions));
		
		while ($source_user = $vbulletin->db->fetch_array($source_users_result)) {
			echo $source_user['username'], '; ';

			$vbulletin->db->query_write("
				UPDATE `" . TABLE_PREFIX . "user`
				SET fbuid = '$source_user[fbuid]'
					,fboptions = '$fboptions_str'
				WHERE userid = $source_user[userid]
			");
			
			$migrated_counter++;
			
			if ($migrated_counter % 20 == 0) vbflush();
		}
		unset($source_user);
		$vbulletin->db->free_result($source_users_result);
		
		print_stop_message('fbb_migrated_x_users',$migrated_counter);
	}
	
	print_form_header('fbb_admin', 'migration');
	print_table_header($vbphrase['fbb_migration']);
	print_description_row($vbphrase['fbb_migration_desc']);
	print_select_row($vbphrase['fbb_migration_supported']
		,'source'
		,$supported_list
	);
	print_select_row($vbphrase['fbb_migration_turn_on']
		,'turn_on'
		,array(
			'1' => $vbphrase['yes'],
			'0' => $vbphrase['no'],
		)
		,1
	);
	print_submit_row($vbphrase['go']);
	
	print_cp_footer();
}

if ($_REQUEST['do'] == 'log') {
	print_cp_header($vbphrase['fbb_log']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'username'   => TYPE_STR,
		'function'   => TYPE_STR,
		'startdate'  => TYPE_UNIXTIME,
		'enddate'    => TYPE_UNIXTIME,
		'istartdate' => TYPE_UINT,
		'ienddate'   => TYPE_UINT,
	));
	
	$functionlist = array(
		'' => $vbphrase['please_select_one'],
		FBF_feed_deactivateTemplateBundleByID => $vbphrase['fbb_log_feed_deactivateTemplateBundleByID'],
		FBF_feed_registerTemplateBundle => $vbphrase['fbb_log_feed_registerTemplateBundle'],
		FBF_auth_revokeAuthorization => $vbphrase['fbb_log_auth_revokeAuthorization'],
		FBF_auth_revokeextendedpermission => $vbphrase['fbb_log_auth_revokeExtendedPermission'],
		FBF_fql_query => $vbphrase['fbb_log_fql_query'],
		FBF_profile_setfbml => $vbphrase['fbb_log_profile_setfbml'],
		FBF_notifications_sendmail => $vbphrase['fbb_log_notifications_sendmail'],
		FBF_notifications_send => $vbphrase['fbb_log_notifications_send'],
		FBF_stream_get => $vbphrase['fbb_log_stream_get'],
		FBF_stream_addlike => $vbphrase['fbb_log_stream_addlike'],
		FBF_stream_addcomment => $vbphrase['fbb_log_stream_addcomment'],
		FBF_stream_publish => $vbphrase['fbb_log_stream_publish'],
		FBF_publish_shortstory => $vbphrase['fbb_log_publish_shortstory'],
		FBF_send_oneline => $vbphrase['fbb_log_send_oneline'],
		FBF_photos_upload => $vbphrase['fbb_log_photos_upload'],
		FBF_photos_addtag => $vbphrase['fbb_log_photos_addtag'],
	);
	
	$sqlconds = array();
	$hook_query_fields = $hook_query_joins = '';
	$search_params = '';

	if ($vbulletin->GPC['perpage'] < 1) {
		$vbulletin->GPC['perpage'] = 15;
	}

	if ($vbulletin->GPC['username']) {
		$usernames = explode(',',$vbulletin->GPC['username']);
		$usernames_query = array();
		foreach ($usernames as $username) {
			$username = trim($username);
			if ($username) {
				$usernames_query[] = "'" . $vbulletin->db->escape_string($username) . "'";
			}
		}
		$sqlconds_userids = array();
		$usernames = array();
		if (count($usernames_query)) {
			$users = $vbulletin->db->query_read("
				SELECT userid, username
				FROM `" . TABLE_PREFIX . "user`
				WHERE username IN (" . implode(',',$usernames_query) . ")
			");
			
			while ($user = $vbulletin->db->fetch_array($users)) {
				$sqlconds_userids[] = 'fbb_log.userid = ' . $user['userid'];
				$usernames[] = $user['username'];
			}
			
			if (count($usernames)) {
				$sqlconds[] = '(' . implode(' OR ',$sqlconds_userids) . ')';
			}
		}
		$vbulletin->GPC['username'] = implode(', ',$usernames);
		$search_params .= '&username=' . urlencode($vbulletin->GPC['username']);
	}
	
	if ($vbulletin->GPC['function'] AND !empty($functionlist[$vbulletin->GPC['function']])) {
		$sqlconds[] = 'fbb_log.function = \'' . $vbulletin->GPC['function'] . '\'';
	}

	if ($vbulletin->GPC['startdate'] OR $vbulletin->GPC['istartdate']) {
		if (!$vbulletin->GPC['istartdate']) $vbulletin->GPC['istartdate'] = $vbulletin->GPC['startdate'];
		$sqlconds[] = "fbb_log.timeline >= " . $vbulletin->GPC['istartdate'];
		$search_params .= '&istartdate=' . $vbulletin->GPC['startdate'];
	}

	if ($vbulletin->GPC['enddate'] OR $vbulletin->GPC['ienddate']) {
		if (!$vbulletin->GPC['ienddate']) $vbulletin->GPC['ienddate'] = $vbulletin->GPC['enddate'];
 		$sqlconds[] = "fbb_log.timeline <= " . $vbulletin->GPC['ienddate'];
		$search_params .= '&ienddate=' . $vbulletin->GPC['ienddate'];
	}
	
	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "fbb_log AS fbb_log
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
	");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	if ($vbulletin->GPC['pagenumber'] < 1) {
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	
	$logs = $db->query_read("
		SELECT fbb_log.*, user.username
			$hook_query_fields
		FROM " . TABLE_PREFIX . "fbb_log AS fbb_log
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = fbb_log.userid)
		$hook_join_fields
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
		ORDER BY timeline DESC
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");
	
	if ($db->num_rows($logs)) {
		$location_prefix = "fbb_admin.php?" . $vbulletin->session->vars['sessionurl'] . "do=log{$search_params}&perpage=" . $vbulletin->GPC['perpage'] . "&page";
		
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='$location_prefix=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='$location_prefix=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='$location_prefix=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='$location_prefix=$totalpages'\">";
		}
		
		print_form_header('fbb_admin', 'log');
		print_table_header(construct_phrase($vbphrase['fbb_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), 6);

		$headings = array();
		$headings[] = $vbphrase['id'];
		$headings[] = $vbphrase['username'];
		$headings[] = $vbphrase['date'];
		$headings[] = $vbphrase['action'];
		$headings[] = $vbphrase['info'];
		print_cells_row($headings, 1);

		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = $log['logid'];
			$cell[] = $log['username'];
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['timeline']) . '</span>';
			
			$action_str = $info_str = array();
			$info_separator = '<br/>';

			switch ($log['function']) {
				case FBF_feed_deactivateTemplateBundleByID: 
					$action_str[] = $vbphrase['fbb_log_feed_deactivateTemplateBundleByID'];
					$action_str[] = $vbphrase['fbb_log_tbid'] . ': ' . $log['data'];
					$info_str[] = $log['result'];
					break;
				case FBF_feed_registerTemplateBundle: 
					$action_str[] = $vbphrase['fbb_log_feed_registerTemplateBundle'];
					$info_str[] = $vbphrase['fbb_log_tbid'] . ': ' . $log['result'];
					break;
				case FBF_auth_revokeAuthorization: 
					$action_str[] = $vbphrase['fbb_log_auth_revokeAuthorization'];
					$action_str[] = 'Facebook UID: ' . fb_log_getUID($log);
					$info_str[] = iif($log['result'] == '1',$vbphrase['fbb_log_result_ok'],$vbphrase['fbb_log_result_failed']);
					break;
				case FBF_auth_revokeextendedpermission: 
					$action_str[] = $vbphrase['fbb_log_auth_revokeExtendedPermission'];
					$logdata = unserialize($log['data']);
					$action_str[] = 'Extended Permission: ' . $logdata[0];
					unset($logdata);
					$info_str[] = iif($log['result'] == '1',$vbphrase['fbb_log_result_ok'],$vbphrase['fbb_log_result_failed']);
					break;
				case FBF_fql_query: 
					$action_str[] = $vbphrase['fbb_log_fql_query'];
					$action_str[] = '<span class="smallfont">' . $log['data'] . '</span>';
					$rows_count = 0;
					if (is_numeric($log['result'])) {
						$rows_count = $log['result'];
					} else if (is_string($log['result'])) {
						$tmp = unserialize($log['result']); //original script store the whole result in the database. Oops!
						$rows_count = count($tmp);
						unset($tmp);
					}
					$info_str[] = $vbphrase['fbb_log_fql_query_rows'] . ': ' . $rows_count;
					break;
				case FBF_profile_setfbml: 
					$action_str[] = $vbphrase['fbb_log_profile_setfbml'];
					$action_str[] = 'Facebook UID: ' . fb_log_getUID($log);
					$info_str[] = iif($log['result'] == '1',$vbphrase['fbb_log_result_ok'],$vbphrase['fbb_log_result_failed']);
					break;
				case FBF_notifications_sendmail: 
					$action_str[] = $vbphrase['fbb_log_notifications_sendmail'];
					$tmp = unserialize($log['data']);
					if (!empty($tmp)) {
						$recipients = $tmp[0]; //old script
						unset($tmp);
					} else {
						$recipients = $log['data']; //new one
					}
					$action_str[] = 'Facebook UID(s): ' . $recipients;
					
					if (!empty($log['result'])) {
						$tmp = explode(',',$log['result']);
						foreach ($tmp as $fbuid) {
							$info_str[] = $fbuid;
						}
						$info_separator = ', ';
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_notifications_send: 
					$action_str[] = $vbphrase['fbb_log_notifications_send'];
					$logdata = unserialize($log['data']);
					$action_str[] = "Facebook UID: $logdata[to_ids]";
					$action_str[] = "($logdata[type]) $logdata[notification]";
					unset($logdata);
					
					if (!empty($log['result'])) {
						$tmp = explode(',',$log['result']);
						foreach ($tmp as $fbuid) {
							$info_str[] = $fbuid;
						}
						$info_separator = ', ';
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_stream_get: 
					$action_str[] = $vbphrase['fbb_log_stream_get'];
					$logdata = unserialize($log['data']);
					if ($logdata[1]) {
						$action_str[] = 'Facebook UID: ' . $logdata[1];
					}
					unset($logdata);
					
					$tmp = unserialize($log['result']);
					if (!empty($tmp['posts'])) {
						$posts = array();
						foreach ($tmp['posts'] as $post) {
							$posts[] = $post['permalink'];
						}
						unset($tmp);
					} else {
						$posts = $tmp;
						unset($tmp);
					}
					if (!empty($posts)) {
						$i = 0;
						foreach ($posts as $post) {
							if ($post) {
								$info_str[] = '<a href="' . $post . '">Post #' . ++$i . '</a>';
							}
						}
						$info_separator = ', ';
					} 
					if (count($info_str) == 0) {
						$info_str[] = $vbphrase['fbb_log_stream_no_post'];
					}
					break;
				case FBF_stream_addlike: 
					$action_str[] = $vbphrase['fbb_log_stream_addlike'];
					$logdata = unserialize($log['data']);
					$action_str[] = 'Facebook PID: ' . $logdata[0];
					unset($logdata);
					$info_str[] = iif($log['result'] == '1',$vbphrase['fbb_log_result_ok'],$vbphrase['fbb_log_result_failed']);
					break;
				case FBF_stream_addcomment: 
					$action_str[] = $vbphrase['fbb_log_stream_addcomment'];
					$logdata = unserialize($log['data']);
					$action_str[] = 'Facebook PID: ' . $logdata['post_id']; //the data is prepared manually
					unset($logdata);
					
					if (!fb_isErrorCode($log['result'])) {
						$info_str[] = 'Facebook CID: ' . $log['result'];
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_stream_publish: 
					$action_str[] = $vbphrase['fbb_log_stream_publish'];
					if (!fb_isErrorCode($log['result'])) {
						$info_str[] = 'Facebook PID: ' . $log['result'];
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_publish_shortstory: 
					$action_str[] = $vbphrase['fbb_log_publish_shortstory'];
					if ($log['result'] == 'a:1:{i:0;s:1:"1";}') {
						$info_str[] = $vbphrase['fbb_log_result_ok'];
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_send_oneline: 
					$action_str[] = $vbphrase['fbb_log_send_oneline'];
					if ($log['result'] == 'a:1:{i:0;s:1:"1";}') {
						$info_str[] = $vbphrase['fbb_log_result_ok'];
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_photos_upload: 
					$action_str[] = $vbphrase['fbb_log_photos_upload'];
					if (!fb_isErrorCode($log['result'])) {
						$tmp = unserialize($log['result']);
						$info_str[] = '<a href="' . $tmp['link'] . '">Uploaded Photo</a>';
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
				case FBF_photos_addtag: 
					$action_str[] = $vbphrase['fbb_log_photos_addtag'];
					if ($log['result'] === '1') {
						$info_str[] = $vbphrase['fbb_log_result_ok'];
					} else {
						$info_str[] = $vbphrase['fbb_log_result_failed'];
					}
					break;
			}

			$cell[] = implode('<br/>',$action_str);
			
			if (!$log['is_exception']) {
				$cell[] = implode($info_separator,$info_str);
			} else {
				$cell[] .= 'Exception: ' . $log['result'];
			}
			
			print_cells_row($cell);
		}

		print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	} else {
		print_stop_message('no_results_matched_your_query');
	}
	
	print_form_header('fbb_admin', 'log');
	print_table_header($vbphrase['fbb_log']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', $vbulletin->GPC['perpage']);
	print_input_row($vbphrase['show_only_entries_generated_by_username'], 'username', $vbulletin->GPC['username']);
	print_select_row($vbphrase['show_only_entries_by_function'], 'function', $functionlist, $vbulletin->GPC['function']);
	print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
	print_time_row($vbphrase['end_date'], 'enddate', 0, 0);
	print_submit_row($vbphrase['view'], 0);
	
	print_cp_footer();
}
?>