<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.4
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 01:08 Apr 01st, 2010
|| #################################################################### ||
\*======================================================================*/
if (
	$vbulletin->fbb['runtime']['enabled']
	AND $_REQUEST['do'] == 'updatefbboptions'
) {
	$vbulletin->input->clean_array_gpc('r', array(
		'type' => TYPE_STR,
		'action' => TYPE_STR,
		'action_do' => TYPE_STR,
		'notification' => TYPE_STR,
		'other' => TYPE_STR,
		'onoff' => TYPE_UINT,
	));

	require_once(DIR . '/fbb/functions.php');
	
	$fboptions = unserialize($vbulletin->userinfo['fboptions']);
	$processed = array();
	$processed_code = array();
	$url_hash = '';
	
	if ($vbulletin->GPC['type'] == 'actions') {
		$action = $vbulletin->GPC['action'];
		$action_do = $vbulletin->GPC['action_do'];
		if ($vbulletin->GPC['onoff'] == 1) {
			/*
			$fboptions['actions'][$action][$action_do] = 1;
			if (isset($vbulletin->fbb['user_options_config']['actions'][$action][$action_do]['conflict_auto_turnoff'])) {
				if (is_array($vbulletin->fbb['user_options_config']['actions'][$action][$action_do]['conflict_auto_turnoff'])) {
					foreach ($vbulletin->fbb['user_options_config']['actions'][$action][$action_do]['conflict_auto_turnoff'] as $action_do_turnoff) {
						unset($fboptions['actions'][$action][$action_do_turnoff]);
						$processed_code[] = 'actions__' . $action;
					}
				}
			}
			*/
			fb_turn_on_option('actions__' . $action . '__' . $action_do,$fboptions,$processed_code);
		} else {
			//unset($fboptions['actions'][$action][$action_do]);
			fb_turn_off_option('actions__' . $action . '__' . $action_do,$fboptions,$processed_code);
		}
		
		$processed[] = iif(isset($vbphrase['fbb_options_action_' . $action]),$vbphrase['fbb_options_action_' . $action],actionFromDashCode($action))
			. ' - ' 
			. iif(isset($vbphrase['fbb_options_do_' . $action_do]),$vbphrase['fbb_options_do_' . $action_do],actionFromDashCode($action_do));
		$url_hash = 'actions__' . $action;
	} else if ($vbulletin->GPC['type'] == 'notifications') {
		$notification = $vbulletin->GPC['notification'];
		if ($vbulletin->GPC['onoff'] == 1) {
			//$fboptions['notifications'][$notification] = 1;
			fb_turn_on_option('notifications__' . $notification,$fboptions,$processed_code);
		} else {
			//unset($fboptions['notifications'][$notification]);
			fb_turn_off_option('notifications__' . $notification,$fboptions,$processed_code);
		}
		
		$processed[] = iif(isset($vbphrase['fbb_options_notification_' . $notification]),$vbphrase['fbb_options_notification_' . $notification],actionFromDashCode($notification));
		$url_hash = 'notifications__' . $notification;
	} else if ($vbulletin->GPC['type'] == 'others') {
		$other = $vbulletin->GPC['other'];
		if ($vbulletin->GPC['onoff'] == 1) {
			//$fboptions['others'][$other] = 1;
			fb_turn_on_option('others__' . $other,$fboptions,$processed_code);
		} else {
			//unset($fboptions['others'][$other]);
			fb_turn_off_option('others__' . $other,$fboptions,$processed_code);
		}
		
		$processed[] = iif(isset($vbphrase['fbb_options_other_' . $other]),$vbphrase['fbb_options_other_' . $other],actionFromDashCode($other));
		$url_hash = 'others__' . $other;
	}

	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);
	$vbulletin->userinfo['fboptions'] = serialize($fboptions);
	$userdata->set('fboptions',$vbulletin->userinfo['fboptions']);
	
	if ($userdata->save()) {
		$message = fetch_error('fbb_saved_options_x',implode(', ',$processed));
	} else {
		$message = fetch_error('fbb_failed_saving_options_x',implode(', ',$processed));
	}
	
	if ($vbulletin->GPC['ajax']) {
		$scope = '';
		foreach ($processed_code as $code) {
			if ($scope == '') {
				$scope = $code;
				continue;
			}
			
			$scope_parts = explode('__',$scope);
			$code_parts = explode('__',$code);
			$tmp_parts = array();
			
			for ($i = 0; $i < min(count($scope_parts),count($code_parts)); $i++) {
				if ($scope_parts[$i] == $code_parts[$i]) {
					$tmp_parts[] = $scope_parts[$i];
				} else {
					break;
				}
			}
			
			$scope = implode('__',$tmp_parts);
		}
		
		if ($scope == '') {
			$eid = 'all';
		} else {
			$eid = $scope;
		}
		$options_html = trim(fb_render_options_html($scope,$fboptions));
	
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('response');
		
		$xml->add_tag('html_eid',$eid);
		$xml->add_tag('html',$options_html);
		if (fb_found_messages('changing_options')) {
			$xml->add_tag('message',implode("\r\n",fb_get_messages('changing_options')));
		}
		
		$xml->close_group();
		$xml->print_xml(true);
	}
	/*
	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=fbb' . iif($url_hash,'#' . $url_hash);
	
	eval(print_standard_redirect($message,false));
	*/
	$_REQUEST['do'] = 'fbb';
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND 
		(
			$_REQUEST['do'] == 'fbb'
			OR $_REQUEST['do'] == 'fbb_pages'
		)
) {	
	//we will need facebook (very cool) javascript for this page
	//so... Turn on our trigger!
	require_once(DIR . '/fbb/functions.php');
	$vbulletin->fbb['runtime']['javascript_needed'] = true;

	// draw cp nav bar
	construct_usercp_nav('fbb');
	
	$isFbConnected = false;
	$fbuserinfo = array();
	if ($vbulletin->userinfo['fbuid'] > 0) {
		$isFbConnected = fb_fetch_permission($vbulletin->userinfo['fbuid']);
	}
	if ($isFbConnected) {
		//Profile info
		$fbuserinfo = fb_fetch_userinfo($vbulletin->userinfo['fbuid']);
		
		switch ($_REQUEST['do']) {
			case 'fbb':
				//Facebook Bridge options
				$options_html = fb_render_options_html();
				break;
			case 'fbb_pages':
				$pages_html = '';
				foreach ($vbulletin->forumcache as $forum) {
					if ($forum['fbuid'] == $vbulletin->userinfo['fbuid']) {
						$pageid = $forum['fbtarget_id'];
						
						$page_title = 'Page #' . $forum['fbtarget_id'];
						$page_permission = iif($forum['fbtarget_id'] == $forum['fbtarget_id_permission_granted'],fb_fetch_permission($pageid,'publish_stream'),false);
						if (!$page_permission) {
							$fbuserinfo['page_permissions_missing'][] = $forum['fbtarget_id'];
						}
						
						if ($vbulletin->fbb['runtime']['vb4']) {
							$templater = vB_Template::create('fb_page_permissionbit');
								$templater->register('pageid', $pageid);
								$templater->register('page_title', $page_title);
								$templater->register('page_permission', $page_permission);
							$pages_html = $templater->render();
						} else {
							eval('$pages_html .= "' . fetch_template('fb_page_permissionbit') . '";');
						}
					}
				}
				if (!empty($fbuserinfo['page_permissions_missing'])) {
					$fbuserinfo['page_permissions_missing_str'] = implode(',',$fbuserinfo['page_permissions_missing']);
				}
				break;
		} 

		//Permissions
		$permissions_html = '';
		foreach ($vbulletin->fbb['user_permissions_config'] as $permission => $isRequired) {
			$fbuserinfo['permissions'][$permission] = fb_fetch_permission($vbulletin->userinfo['fbuid'],$permission);
			
			if (!$fbuserinfo['permissions'][$permission] AND $isRequired) {
				$fbuserinfo['permissions_missing'][] = $permission;
			}
			
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('fb_permissionbit');
					$templater->register('permission', $permission);
					$templater->register('fbuserinfo', $fbuserinfo);
				$permissions_html .= $templater->render();
			} else {
				eval('$permissions_html .= "' . fetch_template('fb_permissionbit') . '";');
			}
		}
		//Missing permissions
		if ($_REQUEST['justadded'] == 1 AND is_array($fbuserinfo['permissions_missing'])) {
			$fbuserinfo['permissions_missing_str'] = implode(',',$fbuserinfo['permissions_missing']);
		}
	}
	
	//Load script
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fb_script_profile');
			$templater->register('fbuserinfo', $fbuserinfo);
		$fb_script_profile = $templater->render();
	} else {
		eval('$fb_script_profile = "' . fetch_template('fb_script_profile') . '";');
	}
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$page_templater = vB_Template::create('fbb_vb4_profile');
		$page_templater->register('isFbConnected', $isFbConnected);
		$page_templater->register('fbuserinfo', $fbuserinfo);
		$page_templater->register('permissions_html', $permissions_html);
		$page_templater->register('pages_html', $pages_html);
		$page_templater->register('options_html', $options_html);
		$page_templater->register('fb_script_profile', $fb_script_profile);
	} else {
		$templatename = 'fbb_profile';
	}
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $_REQUEST['do'] == 'updatefbb'
) {
	require_once(DIR . '/fbb/functions.php');
	
	$facebook_response = validate_cookie();
	
	if ($facebook_response === false OR !is_array($facebook_response)) {
		eval(standard_error(fetch_error('fbb_facebook_response_invalid')));
	}
	
	$messages = array();

	$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
	$userdata->set_existing($vbulletin->userinfo);
	
	//fbuid
	$adding = false;
	if ($vbulletin->userinfo['fbuid'] != $facebook_response['user']) {
		$other_user = $vbulletin->db->query_first("
			SELECT userid
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid <> {$vbulletin->userinfo['userid']}
				AND fbuid = {$facebook_response['user']}
		");
		if ($other_user['userid'] > 0) {
			//Oops!
			
			$vbulletin->fbb['runtime']['javascript_needed'] = true; //wake up the Facebook's stuff...
			
			eval(standard_error(fetch_error('fbb_connected_to_other_account'
				,$vbulletin->options['bbtitle']
				,$vbulletin->options['bburl'] . '/profile.php?do=fbb'
			)));
		}
		
		$userdata->set('fbuid',$facebook_response['user']);
		if (!$vbulletin->userinfo['fbuid'])	$adding = true;
		$vbulletin->userinfo['fbuid'] = $facebook_response['user'];
		
		if ($adding) {
			//populate default options
			$fboptions = fb_get_default_options_set();
			$userdata->set('fboptions',serialize($fboptions));
		}
		
		$messages[] = fetch_error('fbb_connected_to_your_account');
		
		if (fb_connect_orphan_posts($vbulletin->userinfo,$vbulletin->userinfo['fbuid'])) {
			$messages[] = fetch_error('fbb_connected_orphan_posts',$vbulletin->options['bbtitle']);
		}
	}
	
	//offline access permission
	if ($facebook_response['expires'] === '0'
		AND $vbulletin->userinfo['fbssk'] != $facebook_response['session_key']) {
		$userdata->set('fbssk',$facebook_response['session_key']);
	}
	
	$vbulletin->input->clean_array_gpc('r', array(
		'permitted'            => TYPE_STR
	));
	$permitted_permissions = explode(',',$vbulletin->GPC['permitted']);
	
	foreach ($permitted_permissions as $permitted) {
		$permitted = trim($permitted);
		
		$tmp = $vbphrase['fbb_saved_' . $permitted . '_permission'];
		if ($tmp) $messages[] = $tmp;
		
		switch ($permitted) {
			case 'publish_stream':
				//store pages' permissions
				$pageids = array();
				foreach ($vbulletin->forumcache as $forum) {
					if ($forum['fbuid'] == $vbulletin->userinfo['fbuid']) {
						$pageids[$forum['fbtarget_id']] = false;
					}
				}
				if (count($pageids)) {
					$page_perms = fb_fql_query("
						SELECT uid, publish_stream
						from permissions
						where uid IN (" . implode(',',array_keys($pageids)) . ")
					");
					
					if (!empty($page_perms) AND is_array($page_perms)) {
						foreach ($page_perms as $page_perm) {
							if ($page_perm['publish_stream']) {
								$pageids[$page_perm['uid']] = true;
							}
						}
					}
					
					foreach ($pageids as $pageid => $permission_granted) {
						if ($permission_granted) {
							$tmp = $pageid;
						} else {
							$tmp = '';
						}
						
						// Bug found by sNator@vBulletin.org
						// Fixed on March 30th, 2010
						$vbulletin->db->query_write("
							UPDATE `" . TABLE_PREFIX . "forum`
							SET fbtarget_id_permission_granted = '$tmp'
							WHERE fbtarget_id = '$pageid'
						");
					}
					
					require_once(DIR . '/includes/adminfunctions.php');
					build_forum_permissions(false);
				}
				break;
			case 'email':
				//update real email if found
				$users_from_fb = fb_fql_query("
					SELECT uid, email
					FROM user
					WHERE uid = '{$vbulletin->userinfo['fbuid']}'
				");
				$user_from_fb = array_shift($users_from_fb);
				if ($user_from_fb['uid'] == $vbulletin->userinfo['fbuid']) {
					if (fb_is_proxied_email($vbulletin->userinfo['email']) OR $vbulletin->userinfo['fbemail']) {
						$userdata->set('email',$user_from_fb['email']);
						$email_updated = true;
						
						if (!fb_is_proxied_email($user_from_fb['email'])) {
							$userdata->set('fbemail',1);
						}
					}
				}
				break;
		}
	}
	
	//save
	$userdata->save();
	
	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=fbb' . ($adding?'&justadded=1':'');
	
	if (count($messages) == 0) {
		eval(print_standard_redirect('fbb_profile_updated'));
	} else {
		eval(print_standard_redirect(implode('<br/>',$messages),false,true)); //force our messages to be displayed
	}
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $_REQUEST['do'] == 'byefbb'
) {
	require_once(DIR . '/fbb/functions.php');

	$vbulletin->db->query("
		UPDATE `" . TABLE_PREFIX . "user`
		SET fbuid = '0', fbssk = ''
		WHERE userid = {$vbulletin->userinfo['userid']}
	");
	
	fb_auth_revokeAuthorization();
	
	$vbulletin->url = 'profile.php?' . $vbulletin->session->vars['sessionurl'] . 'do=fbb';
	
	eval(print_standard_redirect('fbb_profile_disconnected',true,true));
}

#################### EXTRA FUNCTIONS ####################
if ($_REQUEST['do'] == 'fbb_invited') {
	$invited_count = 0;
	if (is_array($_POST['ids'])) {
		foreach ($_POST['ids'] as $fbuid) {
			$invitelog = array(
				'timeline' => TIMENOW,
				'userid' => $vbulletin->userinfo['userid'],
				'invited_fbuid' => $fbuid,
			);
			$vbulletin->db->query_write(fetch_query_sql($invitelog,'fbb_invited'));
			
			$invited_count++;
		}
	}
	
	$vbulletin->url = 'profile.php?do=fbb';
	if ($invited_count > 0) {
		eval(print_standard_redirect(fetch_error('fbb_x_invited',$invited_count),false,true));
	} else {
		eval(print_standard_redirect(fetch_error('fbb_0_invited'),false,true));
	}
}

if ($_REQUEST['do'] == 'fbb_invite') {	
	//we will need facebook (very cool) javascript for this page
	//so... Turn on our trigger!
	require_once(DIR . '/fbb/functions.php');
	$vbulletin->fbb['runtime']['javascript_needed'] = true;

	// draw cp nav bar
	construct_usercp_nav('fbb_invite');
	
	$isFbConnected = false;
	$fbuserinfo = array();
	if ($vbulletin->userinfo['fbuid'] > 0) {
		try {
			$isFbConnected = fb_fetch_permission($vbulletin->userinfo['fbuid']);
		} catch (FacebookRestClientException $e) {
		}
	}
	
	if ($isFbConnected) {
		//all good
		$fb_type = preg_replace('/[^a-z0-9 ]/i','',$vbulletin->options['bbtitle']);
		$fb_content = construct_phrase(
			$vbphrase['fbb_invite_content']
			,"<fb:name uid='{$vbulletin->userinfo['fbuid']}' useyou='false' />"
			,$vbulletin->options['bbtitle']
			,$vbphrase['register']
		) . "<fb:req-choice url=\"{$vbulletin->options[bburl]}/register.php?do=signup&fbb\" label=\"$vbphrase[register]\" />";
		$fb_actiontext = construct_phrase(
			$vbphrase['fbb_invite_friends']
			,$vbulletin->options['bbtitle']
		);
		
		$exclude_ids = array();
		//Exclude Facebook UID of invited users
		$invited_from_db = $vbulletin->db->query_read("
			SELECT invited_fbuid
			FROM `" . TABLE_PREFIX . "fbb_invited`
			WHERE userid = {$vbulletin->userinfo['userid']}
				AND timeline > " . (TIMENOW - 3*24*60*60) . "
		");
		while ($invited = $vbulletin->db->fetch_array($invited_from_db)) {
			$exclude_ids[] = $invited['invited_fbuid'];
		} 
		$vbulletin->db->free_result($invited_from_db);
		//Exclude Facebook UID of connected users
		$connected_from_db = $vbulletin->db->query_read("
			SELECT fbuid
			FROM `" . TABLE_PREFIX . "user`
			WHERE fbuid <> '0'
		");
		while ($connected = $vbulletin->db->fetch_array($connected_from_db)) {
			$exclude_ids[] = $connected['fbuid'];
		}
		$vbulletin->db->free_result($connected_from_db);
		$fb_exclude_ids = implode(',',array_unique($exclude_ids));
		
		$fb_content = htmlentities($fb_content);
		$fb_actiontext = htmlentities($fb_actiontext);
	} else {
		$vbulletin->url = 'profile.php?do=fbb';
		eval(standard_error(fetch_error('fbb_not_connected')));
	}
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$page_templater = vB_Template::create('fb_invite');
		$page_templater->register('fb_type', $fb_type);
		$page_templater->register('fb_content', $fb_content);
		$page_templater->register('fb_exclude_ids', $fb_exclude_ids);
		$page_templater->register('fb_actiontext', $fb_actiontext);
	} else {
		$templatename = 'fb_invite';
	}
}

if ($vbulletin->fbb['runtime']['in_full_mode']) {
	if (
		$vbulletin->fbb['runtime']['enabled']
		AND $_REQUEST['do'] == 'fun'
		AND $vbulletin->userinfo['fbuid']
	) {
		require_once(DIR . '/fbb/functions.php');
		include_once(DIR . '/fbb/functions_image.php');

		if ($_REQUEST['list'] == 'fan') {
			$fans_query = $vbulletin->db->query("
				SELECT
					user.userid
					,user.fbuid
					,user.username
					,user.usertitle
					,user.avatarrevision
					,avatar.filedata
					,avatar.filename
					,COUNT(*) as count
					,user.posts
				FROM `" . TABLE_PREFIX . "post` as post
				INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = post.userid)
				LEFT JOIN `" . TABLE_PREFIX . "customavatar` AS avatar ON (avatar.userid = post.userid AND avatar.visible = 1)
				WHERE post.userid != {$vbulletin->userinfo['userid']}
				AND post.threadid IN (
					SELECT threadid
					FROM `" . TABLE_PREFIX . "thread`
					WHERE postuserid = {$vbulletin->userinfo['userid']}
				)
				GROUP BY user.userid
				ORDER BY count DESC
				LIMIT 10
			");
			$fans = array();
			$fbuids = array();
			$fbtags = array();
			while ($fan = $vbulletin->db->fetch_array($fans_query)) {
				$fans[] = $fan;
				if ($fan['fbuid'])
					$fbuids[] = "'" . $fan['fbuid'] . "'";
			}
			$vbulletin->db->free_result($fans_query);
			
			$fbusers = array();
			if (count($fbuids)) {
				$fbusers_query = fb_fql_query("SELECT uid, name, pic_small FROM user WHERE uid IN (" . implode(',',$fbuids) . ")");
				foreach ($fbusers_query as $fbuser) {
					$fbusers[$fbuser['uid']] = $fbuser;
				}
				unset($fbusers_query);
			}
		
			$photo_w = $photo_h = 600;
			$im = @imagecreatetruecolor($photo_w, $photo_h) or die('Cannot Initialize new GD image stream');
			$background_color = $white = imagecolorallocate($im,255,255,255);
			$text_color = $black = imagecolorallocate($im,0,0,0);
			$font = DIR . '/fbb/fonts/arial.ttf';
			
			imagefilledrectangle($im, 0, 0, 599, 599, $background_color); //background

			$photo_caption = construct_phrase($vbphrase['fbb_profile_fun_list_fan_header'],$vbulletin->userinfo['username']);
			draw_ttftext($im,18,0,11,20,$black,$font,$photo_caption,$grey);
			
			imagerectangle($im, 0, 25, 599, 599, $black);
			$display_area_x = 0;
			$display_area_y = 25;
			$display_area_w = 599 - 0 + 1;
			$display_area_h = 599 - 25 + 1;
			
			$column = 2;
			$row = 5;
			$spacing = 10;
			$w = floor(($display_area_w - 2*$spacing)/$column) - floor($spacing/$column*($column-1));
			$h = floor(($display_area_h - 2*$spacing)/$row) - floor($spacing/$row*($row-1));
			
			$count = 0;
			foreach ($fans as $fan) {
				$x = $display_area_x + $spacing + ($count % $column) * ($w + $spacing);
				$y = $display_area_y + $spacing + floor($count / $column) * ($h + $spacing);
				
				$avatar_area_x = $x + $spacing;
				$avatar_area_y = $y + $spacing;
				$avatar_area_w = min(75,$w - 2*$spacing);
				$avatar_area_h = $h - 2*$spacing;
				
				$info_area_x = $x + $spacing + $avatar_area_w + $spacing;
				$info_area_y = $avatar_area_y;
				$info_area_w = $w - 3*$spacing - $avatar_w;
				$info_area_h = $avatar_area_h;
				
				imagerectangle($im, $x, $y, $x + $w, $y + $h, $black);
				
				// Start dealing with avatar, ew...
				draw_user_avatar($im, $fan, $avatar_area_x, $avatar_area_y, $avatar_area_w, $avatar_area_h, $fbusers);
				// Wow, done with avatar!
				
				$text_width_limit = $info_area_w - 2*$spacing;
				draw_ttftext($im,15,0,$info_area_x + $spacing, $info_area_y + $spacing + 20,$black,$font,($count + 1) . '. ' . $fan['username'],$gray,$text_width_limit);
				draw_ttftext($im,10,0,$info_area_x + $spacing, $info_area_y + $spacing + 40,$black,$font,$fan['usertitle'],$gray,$text_width_limit);
				draw_ttftext($im,10,0,$info_area_x + $spacing, $info_area_y + $spacing + 60,$black,$font,$vbphrase['posts'] . ': ' . $fan['posts'],$gray,$text_width_limit);
				
				//tagging
				$fbtags[] = array(
					'x' => round(($avatar_area_w/2 + $avatar_area_x)/$photo_w*100,2),
					'y' => round(($avatar_area_h/2 + $avatar_area_y)/$photo_h*100,2),
					'tag_uid' => iif($fan['fbuid'],$fan['fbuid'],''),
					'tag_text' => iif($fan['fbuid'],'',$fan['username']),
				);
				
				$count++;
			}
			
			if ($vbulletin->options['safeupload']) {
				$tmp_path = $vbulletin->options['tmppath'] . '/' . md5(uniqid(microtime()) . $vbulletin->userinfo['userid']);
			} else {
				$tmp_path = tempnam(ini_get('upload_tmp_dir'), 'fbb');
			}
			
			imagepng($im,$tmp_path);

			$fbphoto = fb_photos_upload(
				$tmp_path
				, null
				, construct_phrase(
					$vbphrase['fbb_profile_fun_list_fan_caption']
					,$vbulletin->options['bbtitle']
					,$vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome'] . '.php'
				)
			);
			
			@unlink($tmp_path);
			
			$vbulletin->url = $vbulletin->options['bburl'] . '/profile.php?do=fbb';
			
			if (is_array($fbphoto) AND $fbphoto['link']) {
				if (count($fbtags))
					fb_photos_addTag($fbphoto['pid'], '', '', '', '', $fbtags);
			
				$vbulletin->url = $fbphoto['link'];
				
				eval(print_standard_redirect(fetch_error('fbb_published_photo'),false,true));
			} else {
				eval(standard_error(fetch_error('fbb_published_photo'),'',false));
			}
			
		}
	}
}
?>