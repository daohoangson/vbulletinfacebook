<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.3
|| # Coded by Dao Hoang Son
|| # Contact: daohoangson@gmail.com
|| # Check out my hompage: http://daohoangson.com
|| # Last Updated: 04:01 Apr 06th, 2010
|| #################################################################### ||
\*======================================================================*/
//adding/removing this contants should be reflexed in fbb_admin.php
//2 positions need to be updated:
//the functionlist variable (contains llst of available logging functions)
//the switch which handles the log entry display
define('FBF_feed_deactivateTemplateBundleByID',11001);
define('FBF_feed_registerTemplateBundle',11000);
define('FBF_auth_revokeextendedpermission',10001);
define('FBF_auth_revokeAuthorization',10000);
define('FBF_fql_query',9999);
define('FBF_profile_setfbml',4001);
define('FBF_notifications_sendmail',3002);
define('FBF_notifications_send',3001);
define('FBF_stream_get',2004);
define('FBF_stream_addlike',2003);
define('FBF_stream_addcomment',2002);
define('FBF_stream_publish',2001);
define('FBF_publish_shortstory',1002);
define('FBF_send_oneline',1001);
define('FBF_photos_upload',1101);
define('FBF_photos_addtag',1102);

function validate_cookie() {
	global $vbulletin;
	
	$tmp = array();
	$str = '';
	$c =& $_COOKIE;
	$prefix = $vbulletin->fbb['config']['apikey'] . '_';
	$prefix_l = strlen($prefix);
	
	foreach ($c as $key => $value) {
		if (strpos($key,$prefix) === 0) {
			//this is our key!
			$key = substr($key,$prefix_l);
			$tmp[$key] = $value;
		}
	}
	
	if (count($tmp) > 0) {
		ksort($tmp);
		
		foreach ($tmp as $key => $value) {
			$str .= $key . '=' . $value;
		}
		
		$secret = $vbulletin->fbb['config']['secret'];
		$str .= $secret;
		$str_compare = $c[$vbulletin->fbb['config']['apikey']];
		
		if (md5($str) == $str_compare) {
			//good
			return $tmp;
		} else {	
			return false;
		}
	} else {
		return false;
	}
}

function &theFacebook($apikey = false, $secret = false, $force = false) {
	global $vbulletin;
	static $facebook = null;
	
	if ($force) unset($facebook);
	
	if ($facebook === null) {
		include_once(DIR . '/fbb/fbplatform/facebook.php');
		
		if ($apikey === false) $apikey = $vbulletin->fbb['config']['apikey'];
		if ($secret === false) $secret = $vbulletin->fbb['config']['secret'];
		
		$facebook = new Facebook($apikey, $secret);

		if (!$facebook AND $vbulletin->fbb['runtime']['tmp_halt_on_init_failed']) {
			eval(standard_error(fetch_error('fbb_facebook_init_fail')));
		}
	}
	
	return $facebook;
}

function fb_users_getLoggedInUser() {
	try {
		$result =& theFacebook()->api_client->users_getLoggedInUser();
	} catch (FacebookRestClientException $e) {
		//skip logging exception for this one
	}
	
	return $result;
}

function fb_fetch_userinfo($fbuid,$fields = false) {
	$userinfo_fields = array(
		'uid'
		,'name'
		,'birthday'
		,'sex'
		,'profile_url'
	);
	if ($fields !== false) {
		foreach ($fields as $field)
			if (!in_array($field,$userinfo_fields))
				$userinfo_fields[] = $field;
	}
	
	try {
		if ($fields === false) {
			$userinfo_from_fb =& theFacebook()->api_client->users_getStandardInfo(
				$fbuid
				,$userinfo_fields
			);
		} else {
			$userinfo_from_fb =& theFacebook()->api_client->users_getInfo(
				$fbuid
				,$userinfo_fields
			);
		}
	} catch (FacebookRestClientException $e) {
		//skip
	}
	
	$userinfo = array();
	if (is_array($userinfo_from_fb)) {
		foreach ($userinfo_fields as $field) {
			$userinfo[$field] = $userinfo_from_fb[0][$field];
		}
	}
	
	return $userinfo;
}

function &fb_fetch_permission($fbuid,$permission = false) {
	/*Permissions list:
	null = check for all permissions, return an array - doesn't work anymore!
	false/default = Application Permission (connected or not)
	'email' = This permission allows an application to send email to its user.
	'read_stream' = Lets your application or site access a user's stream and display it
	'publish_stream' = Lets your application or site post content, comments, and likes to a user's profile and in the streams of the user's friends without prompting the user.
	'offline_access' = This permission grants an application access to user data when the user is offline or doesn't have an active session
	'sms' = This permission allows a mobile application to send messages to the user and respond to messages from the user via text message. 
	*/
	
	global $vbulletin;
	
	if (!isset($vbulletin->fbb['runtime']['user_permissions_cache'][$fbuid . '_' . $permission])) {
		try {
			if ($permission === false) {
				$result =& theFacebook()->api_client->users_isAppUser($fbuid);
			} else {
				$result =& theFacebook()->api_client->users_hasAppPermission($permission,$fbuid);
			}
		} catch (FacebookRestClientException $e) {
			//skip
		}
		
		$vbulletin->fbb['runtime']['user_permissions_cache'][$fbuid . '_' . $permission] = $result;
	}
	
	return ($vbulletin->fbb['runtime']['user_permissions_cache'][$fbuid . '_' . $permission]?true:false);
}

/*
This functions won't work anymore since January 5th, 2010

function &fb_feed_registerTemplateBundle($one_line_story_templates,
	$short_story_templates = array(),
	$full_story_template = null,
	$action_links = array(),
	$facebook = null) {
	
	if (count($action_links) == 0) {
		global $vbphrase, $vbulletin;
		
		$action_links[] = array(
			'text' => construct_phrase($vbphrase['fbb_action_link_self'], $vbulletin->options['bbtitle']),
			'href' => $vbulletin->options['bburl'],
		);
	}
	
	try {
		if ($facebook === null)	$facebook =& theFacebook();
		
		$tbid =& $facebook->api_client->feed_registerTemplateBundle($one_line_story_templates,
			$short_story_templates,
			$full_story_template,
			$action_links);
			
		$data = array(
			'one_line_story_templates' => $one_line_story_templates,
			'short_story_templates' => $short_story_templates,
			'action_links' => $action_links,
		); //manually collect because we change the parameters in some cases
		
		fb_log(FBF_feed_registerTemplateBundle,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_feed_registerTemplateBundle,$e,$data);
	}

	return $tbid;
}

function &fb_feed_getRegisteredTemplateBundles() {
	static $bundles = 'something-not-cool';
	
	if ($bundles === 'something-not-cool') {
		try {
			$bundles = null;
			$bundles = theFacebook()->api_client->feed_getRegisteredTemplateBundles();
		} catch (FacebookRestClientException $e) {
			//skip
		}
	}
	
	return $bundles;
}

function &fb_feed_getRegisteredTemplateBundleByID($template_bundle_id) {
	try {
		$bundle =& theFacebook()->api_client->feed_getRegisteredTemplateBundleByID($template_bundle_id);
	} catch (FacebookRestClientException $e) {
		//skip
	}

	return $bundle;
}

function &fb_feed_deactivateTemplateBundleByID($template_bundle_id) {
	try {
		$result =& theFacebook()->api_client->feed_deactivateTemplateBundleByID($template_bundle_id);
		fb_log(FBF_feed_deactivateTemplateBundleByID,$result,$template_bundle_id);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_feed_deactivateTemplateBundleByID,$e,$template_bundle_id);
	}

	return $result;
}
*/

function &fb_admin_getAllocation($integration_point_name, $uid=null) {
	try {
		$result =& theFacebook()->api_client->admin_getAllocation($integration_point_name,$uid);
	} catch (FacebookRestClientException $e) {
		//skip
	}

	return $result;
}

/*
########################################
These methods will not work any more since January 5th, 2010
But they are kept here, a safe place, for us to remember that they once existed and worked (very well)
God bless us!
########################################

function &fb_send_oneline($template_bundle_id,$template_data = '',$target_ids = '',$body_general =' ') {
	if (!$template_bundle_id) return false;
	fb_encoding_to_Facebook($template_data);
	fb_encoding_to_Facebook($body_general);
	
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->feed_publishUserAction(
			$template_bundle_id
			,$template_data
			,$target_ids
			,$body_general
			,FacebookRestClient::STORY_SIZE_ONE_LINE
		);
		$data = array(
			'template_bundle_id' => $template_bundle_id,
			'template_data' => $template_data,
			'target_ids' => $target_ids,
			'body_general' => $body_general,
		);
		fb_log(FBF_send_oneline,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_send_oneline,$e,$data);
	}
	
	return $result;
}

function &fb_publish_shortstory_manually($template_bundle_id,$template_data = '',$target_ids = '',$body_general = '',$user_message = '') {
	if (!$template_bundle_id) return false;

	if (is_array($template_data)) {
		$template_data_str = json_encode($template_data);
	} else {
		$template_data_str = $template_data;
	}
	
	if (is_array($target_ids)) {
		$target_ids_str = json_encode($target_ids);
	} else {
		$target_ids_str = $target_ids;
	}
	
	fb_embed_javascript("FB.Connect.showFeedDialog(
		'$template_bundle_id'
		," . ($template_data_str?$template_data_str:"''") . "
		," . ($target_ids_str?$target_ids_str:"''") . "
		,'$body_general'
		,null
		,FB.RequireConnect.promptConnect
		,function() {}
		,null
		,{value: '$user_message'}
	);");
	
	return fb_generateNonCode();
}

function &fb_publish_shortstory($template_bundle_id,$template_data = '',$target_ids = '',$body_general = '',$user_message = '') {
	if (!$template_bundle_id) return false;
	fb_encoding_to_Facebook($template_data);
	fb_encoding_to_Facebook($body_general);
	fb_encoding_to_Facebook($user_message);

	$published = false;
	if (fb_fetch_permission($vbulletin->userinfo['fbuid'],'publish_stream')) {
		//automatically publish
		try {
			fb_forcessk();
			$published =& theFacebook()->api_client->feed_publishUserAction(
				$template_bundle_id
				,$template_data
				,$target_ids
				,$body_general
				,FacebookRestClient::STORY_SIZE_SHORT
				,$user_message
			);
			$data = array(
				'template_bundle_id' => $template_bundle_id,
				'template_data' => $template_data,
				'target_ids' => $target_ids,
				'body_general' => $body_general,
				'user_message' => $user_message,
			);
			fb_log(FBF_publish_shortstory,$published,$data);
		} catch (FacebookRestClientException $e) {
			fb_log_exception(FBF_publish_shortstory,$e,$data);
		}
	} 

	if (!fb_isErrorCode($published)) {
		//wow, cool! Automatically sent
		return $published;
	} else {
		//fallback to manual mode
		return fb_publish_shortstory_manually($template_bundle_id,$template_data,$target_ids,$body_general,$user_message);
	}
}
*/

function &fb_stream_get($viewer_id = null, $source_ids = null, $start_time = 0, $end_time = 0, $limit = 30, $filter_key = '') {
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->stream_get($viewer_id = null,
			$source_ids,
			$start_time,
			$end_time,
			$limit,
			$filter_key
		);
		
		fb_encoding_to_vBulletin($result);
		
		$data = func_get_args();
		$posts = array();
		if (!empty($result['posts'])) {
			foreach ($result['posts'] as $post) {
				$posts[] = $post['permalink'];
			}
		}
		fb_log(FBF_stream_get,$posts,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_stream_get,$e,$data);
	}
	
	return $result;
}

function &fb_stream_publish($message, $attachment = null, $action_links = null, $target_id = null, $uid = null, $auto_fallback = true, $extra = array()) {
	//Charset dealing
	fb_encoding_to_Facebook($message);
	fb_encoding_to_Facebook($attachment);
	fb_encoding_to_Facebook($action_links);
	
	$data = array(
		'message' => $message,
		//'attachment' => $attachment,
		//'action_links' => $action_links,
		'target_id' => $target_id,
		'uid' => $uid,
	);
	
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->stream_publish(
			$message
			, $attachment
			, $action_links
			, $target_id
			, $uid
		);
		
		
		fb_log(FBF_stream_publish,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_stream_publish,$e,$data);
	}
	
	if (!fb_isErrorCode($result)) {
		//wow, cool! Automatically sent
		return $result;
	} else if ($auto_fallback) {
		//fallback to manual mode
		if (
				$uid === null
				OR $uid == $GLOBALS['vbulletin']->userinfo['fbuid']
				OR $uid == theFacebook()->user
		) {
			//$message, $attachment = null, $action_links = null, $target_id = null, $uid = null
			if (is_array($attachment)) {
				$attachment_str = json_encode($attachment);
			} else {
				unset($attachment_str);
			}
			
			if (is_array($action_links)) {
				$action_links_str = json_encode($action_links);
			} else {
				unset($action_links_str);
			}
			
			if (is_array($target_ids)) {
				$target_ids_str = json_encode($target_ids);
			} else {
				$target_ids_str = $target_ids;
			}
			
			$log_id = fb_log(FBF_stream_publish,'manual publish',$data);
			
			$extra_str = '';
			if (!empty($extra)) {
				foreach ($extra as $key => $value) {
					$extra_str .= "&$key=$value";
				}
			}
			
			fb_embed_javascript("FB.Connect.streamPublish(
				'" . addslashes($message) . "'
				," . ($attachment_str?$attachment_str:'null') . "
				," . ($action_links_str?$action_links_str:'null') . "
				," . ($target_id?$target_id:'null') . "
				,'Publish to Facebook'
				,function (post_id, exception) {
					YAHOO.util.Connect.asyncRequest(
						'POST'
						,'facebook.php'
						,{
							success:function(a) {}
							,failure:function(a) {}
							,timeout:vB_Default_Timeout
							,scope:this
						}
						,SESSIONURL+'securitytoken='+SECURITYTOKEN+'&do=update_log&log_id=$log_id{$extra_str}&post_id='+(post_id != null?post_id:0)+'&exception='+exception 
					);
				}
				,true
				," . ($uid?$uid:'null') . "
			);");
			
			return 0;
		}
	}
}

function &fb_stream_addcomment($post_id, $comment, $uid = null) {
	fb_encoding_to_Facebook($comment);

	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->stream_addComment(
			$post_id
			, $comment
			, $uid
		);
		
		$data = array(
			'post_id' => $post_id,
			//'comment' => $comment,
			'uid' => $uid,
		);
		fb_log(FBF_stream_addcomment,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_stream_addcomment,$e,$data);
	}
	
	return $result;
}

function &fb_stream_addlike($post_id, $uid = null) {
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->stream_addLike(
			$post_id
			, $uid
		);
		$data = func_get_args();
		fb_log(FBF_stream_addlike,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_stream_addlike,$e,$data);
	}
	
	return $result;
}

function &fb_notifications_send($to_ids, $notification, $type, $userinfo = false) {
	global $vbulletin;
	
	fb_encoding_to_Facebook($notification);

	try {
		fb_forcessk();
		
		$changed_internally = false;
		$fb =& theFacebook();
		
		if ($userinfo !== false AND $userinfo['userid'] != $vbulletin->userinfo['userid'] AND $type == 'user_to_user') {
			//prevent sending notification on behalf of wrong user
			//switch to app_to_user mode
			$notification = $userinfo['username'] . ' ' . $notification;
			$type = 'app_to_user';
			$changed_internally = true;
		}
		if (!$fb->user AND $type == 'user_to_user') {
			//looks like it's a not-connected-yet user...
			//fall back to app_to_user mode
			if ($userinfo === false) $userinfo =& $vbulletin->userinfo;
			$notification = $userinfo['username'] . ' ' . $notification;
			$type = 'app_to_user';
			$changed_internally = true;
		}
		
		$result =& theFacebook()->api_client->notifications_send(
			$to_ids
			, $notification
			, $type
		);
		
		$data = array(
			'to_ids' => $to_ids,
			'notification' => $notification,
			'type' => $type,
			'changed_internally' => $changed_internally,
		); //rebuild data to log because maybe we changed it before
		
		fb_log(FBF_notifications_send,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_notifications_send,$e,$data);
	}
	
	return $result;
}

function &fb_notifications_sendEmail($recipients, $subject, $text, $fbml) {
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->notifications_sendEmail($recipients, $subject, $text, $fbml);

		fb_log(FBF_notifications_sendmail,$result,$recipients);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_notifications_sendmail,$result,$recipients);
	}
	
	return $result;
}

function &fb_profile_setFBML($markup, $uid=null, $profile='', $profile_action='', $mobile_profile='', $profile_main='') {
	fb_encoding_to_Facebook($profile);
	fb_encoding_to_Facebook($profile_main);
	
	try {		
		$result =& theFacebook()->api_client->profile_setFBML($markup, $uid, $profile, $profile_action, $mobile_profile, $profile_main);
		
		fb_log(FBF_profile_setfbml,$result,$uid);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_profile_setfbml,$e,$uid);
	}
	
	return $result;
}

function &fb_fbml_setRefHandle($handle, $fbml) {
	fb_encoding_to_Facebook($fbml);
	
	try {
		$result =& theFacebook()->api_client->fbml_setRefHandle($handle, $fbml);
	} catch (FacebookRestClientException $e) {
		//skip
	}
	
	return $result;
}

function &fb_fql_query($query) {
	try {
		$result =& theFacebook()->api_client->fql_query($query);
		fb_log(FBF_fql_query,count($result),$query);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_fql_query,$e,$query);
	}
	
	fb_encoding_to_vBulletin($result);
	
	return $result;
}

function &fb_auth_revokeAuthorization($uid = null) {
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->auth_revokeAuthorization($uid);
		fb_log(FBF_auth_revokeAuthorization,$result,$uid);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_auth_revokeAuthorization,$e,$uid);
	}
	
	return $result;
}

function &fb_auth_revokeExtendedPermission($perm, $uid=null) {
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->auth_revokeExtendedPermission($perm, $uid);
		$data = func_get_args();
		fb_log(FBF_auth_revokeextendedpermission,$result,$data);
		
		if ($result == '1') {
			global $vbulletin;
			$vbulletin->fbb['runtime']['user_permissions_cache'] = array(); //remove cached permission information
		}
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_auth_revokeextendedpermission,$e,$data);
	}
	
	return $result;
}

function fb_photos_upload($file, $aid=null, $caption=null, $uid=null) {
	fb_encoding_to_Facebook($caption);
	
	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->photos_upload($file,$aid,$caption,$uid);
		fb_log(FBF_photos_upload,$result,$_SERVER['HTTP_REFERER']);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_photos_upload,$e,$_SERVER['HTTP_REFERER']);
	}
	
	return $result;
}

function &fb_photos_addTag($pid, $tag_uid, $tag_text, $x, $y, $tags, $owner_uid=0) {
	fb_encoding_to_Facebook($tag_text);
	fb_encoding_to_Facebook($tags);

	try {
		fb_forcessk();
		$result =& theFacebook()->api_client->photos_addTag($pid, $tag_uid, $tag_text, $x, $y, $tags, $owner_uid);
		$data = func_get_args();
		fb_log(FBF_photos_addtag,$result,$data);
	} catch (FacebookRestClientException $e) {
		fb_log_exception(FBF_photos_addtag,$e,$data);
	}
	
	return $result;
}

/* SUPPORT */
/*
This function is deprecated
function fb_e_x_c_e_p_t_i_o_n($e) {
	global $vbulletin;
	//DEVDEBUG("Facebook Exception: " . $e->getMessage());
	$f = fopen(DIR . '/fbb/fbb.log','a');
	fwrite($f,date('H:i dmY|') . $_SERVER['REQUEST_URI'] . '|userid=' . $vbulletin->userinfo['userid'] . '~' . $e->getMessage() . "\r\n");
	fclose($f);
}
*/
function fb_log_exception($function,$e,$data = false,$fbuid = false) {
	return fb_log($function,$e->getMessage(),$data,$fbuid,true);
}

function fb_log($function,$result,$data = false,$fbuid = false,$isException = false) {
	global $vbulletin;
	
	if ($fbuid === false OR $fbuid === null) {
		$fb =& theFacebook();
		$fbuid = $fb->user . ':' . $fb->api_client->session_key;
	}
	
	$log_entry = array(
		'timeline' => TIMENOW,
		'userid' => $vbulletin->userinfo['userid'],
		'fbuid' => $fbuid,
		'function' => iif(is_numeric($function),$function,0),
		'result' => iif(is_array($result),serialize($result),$result),
		'data' => iif(is_array($data),serialize($data),$data),
		'is_exception' => iif($isException,1,0),
	);
	
	$vbulletin->db->query_write(fetch_query_sql($log_entry,'fbb_log'));
	
	return $vbulletin->db->insert_id();
}

function fb_log_getUID($log) {
	//extract the fbuid from data but sometime it's null so we will try to get from the fbuid field
	if (!empty($log['data'])) {
		return $log['data'];
	} else if (!empty($log['fbuid'])) {
		$parts = explode(':',$log['fbuid']);
		return $parts[0];
	} else if (!empty($log['userid'])) {
		global $vbulletin;
		
		$user = $vbulletin->db->query_first("
			SELECT fbuid
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid = $log[userid]
		");
		
		return $user['fbuid'];
	}
	
	return 'N/A';
}

function fb_forcessk($userinfo = false) {
	global $vbulletin;
	$fb =& theFacebook();
	if ($userinfo === false) $userinfo =& $vbulletin->userinfo;
	
	if (
		(!$fb->user AND !$fb->api_client->session_key)
		OR ($userinfo['fbuid'] AND $fb->user != $userinfo['fbuid'])
	) {
		if ($userinfo['fbuid'] AND $userinfo['fbssk']) {
			//$fb->set_user($vbulletin->userinfo['fbuid'],$vbulletin->userinfo['fbssk']);
			$fb->user = $userinfo['fbuid'];
			$fb->api_client->session_key = $userinfo['fbssk'];
			$fb->session_expires = 0;
		}
	}
}

function fb_isErrorCode($code) {
	if (
		$code === null
		OR $code === false
		OR $code === ''
		OR (is_numeric($code) AND $code < 1000)
	) {
		DEVDEBUG("$code IS an Error Code");
		return true;
	}
	DEVDEBUG("$code is NOT an Error Code");
}

function fb_generateNonCode() {
	return '123456789_xXx_987654321';
}

function fb_encoding_mbstring(&$data,$original_charset,$target_charset) {
	if (is_string($data)) {
		$data = mb_convert_encoding($data,$target_charset,$original_charset);
	} else if (is_array($data)) {
		foreach (array_keys($data) as $key) {
			fb_encoding_mbstring($data[$key],$original_charset,$target_charset); //call this function itself to process array, recursively
		}
	}
}

function fb_encoding_iconv(&$data,$original_charset,$target_charset) {
	if (is_string($data)) {
		$data = iconv($original_charset,$target_charset,$data);
	} else if (is_array($data)) {
		foreach (array_keys($data) as $key) {
			fb_encoding_iconv($data[$key],$original_charset,$target_charset); //call this function itself to process array, recursively
		}
	}
}

function fb_encoding_iso_utf(&$data,$toUTF8 = true) {
	if (is_string($data)) {
		if ($toUTF8) {
			$data = utf8_encode($data);
		} else {
			$data = utf8_decode($data);
		}
	} else if (is_array($data)) {
		foreach (array_keys($data) as $key) {
			fb_encoding_iso_utf($data[$key],$toUTF8); //call this function itself to process array, recursively
		}
	}
}

function fb_html_entity_decode(&$data,$charset) {
	if (is_string($data)) {
		//added @ to prevent warning message with unsupported charset
		//hmmm
		$data = @html_entity_decode($data,ENT_COMPAT,$charset);
	} else if (is_array($data)) {
		foreach (array_keys($data) as $key) {
			fb_html_entity_decode($data[$key],$charset); //call this function itself to process array, recursively
		}
	}
}

function fb_encoding(&$data,$toFacebook = true) {
	//need somebody to tell me why this function doesn't work as expected
	//eliminated some parts to improve performance
	//hmm
	global $vbulletin;
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$vbulletin_charset = strtoupper(vB_Template_Runtime::fetchStylevar("charset"));
	} else {
		global $stylevar;
		$vbulletin_charset = strtoupper($stylevar['charset']);
	}
	$facebook_prefered_charset = 'UTF-8';
	
	//$data_original = $data;
	if ($toFacebook) {
		$original_charset = $vbulletin_charset;
		$target_charset = $facebook_prefered_charset;
		
		fb_html_entity_decode($data,$vbulletin_charset);
	} else {
		$original_charset = $facebook_prefered_charset;
		$target_charset = $vbulletin_charset;
	}
	
	$function = '';
	if ($original_charset != $target_charset OR false) {
		//temporary disable this whole scope!
		if (strtoupper($stylevar['charset']) == 'ISO-8859-1') {
			fb_encoding_iso_utf($data,$toFacebook);
			$function = 'fb_encoding_iso_utf';
		} else if (function_exists('mb_convert_encoding')) {
			fb_encoding_mbstring($data,$original_charset,$target_charset);
			$function = 'fb_encoding_mbstring';
		} else if (function_exists('iconv')) {
			fb_encoding_iconv($data,$original_charset,$target_charset);
			$function = 'fb_encoding_iconv';
		} else {
			DEVDEBUG('SKIPPED fb_encoding: no converter available');
		}
	} else {
		DEVDEBUG('SKIPPED fb_encoding: matched charset');
	}
}

function fb_encoding_to_Facebook(&$data) {
	//a wrapper for fb_encoding
	fb_encoding($data,true);
}

function fb_encoding_to_vBulletin(&$data) {
	//a wrapper for fb_encoding
	fb_encoding($data,false);
}

/* SYSTEM */

function fb_embed_javascript($jscode) {
	global $vbulletin;
	
	if (!isset($vbulletin->fbb['runtime']['javascript_code'])) 
		$vbulletin->fbb['runtime']['javascript_code'] = '';
	
	$vbulletin->fbb['runtime']['javascript_code'] .= $jscode;
}

function strip_bbcode_quick($message) {
	//return preg_replace('/\[\/?[a-zA-Z0-9\=\,\.\"\']+?\]/','',$message); - our dirty way
	
	//this is vBulletin way (we still use the fast and dirty one)
	//return strip_bbcode($message, true /* strip quote */, true /* fast & dirty */);
	
	//this is another way which can get more benefit (a little bit slower)
	global $vbulletin;
	require_once(DIR . '/includes/class_bbcode_alt.php');
	$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
	$plaintext_parser->set_parsing_language($vbulletin->userinfo['languageid']);
	$message = $plaintext_parser->parse($message, 'privatemessage');
	
	return $message;
}

function getWords($message,$upper_limit = 75) {
	global $vbulletin;
	
	$message = strip_quotes($message);
	
	$message = preg_replace('/\[img\](.*?)\[\/img\]/i','',$message); //remove [IMG] tags
	
	require_once(DIR . '/includes/class_bbcode_alt.php');
	$plaintext_parser = new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
	$plaintext_parser->set_parsing_language($vbulletin->userinfo['languageid']);
	$message = $plaintext_parser->parse($message, 'privatemessage');
	
	$message = trim($message);
	
	if (strlen($message) > $upper_limit * 4) {
		$words = explode(' ',$message);
		$message = '';
		$word_limit = min($upper_limit,count($words));
		for ($i = 0; $i < $word_limit; $i++) 
			$message .= $words[$i] . ' ';
		if ($word_limit < count($words)) 
			$message .= '...';
	}
	
	return $message;
}

function fb_getPostInfo($postinfo,$threadinfo,$foruminfo) {
	global $vbulletin, $vbphrase;

	$title = construct_phrase(
		$vbphrase['fbb_template_shared_title']
		, getWords($postinfo['title'],10)
		, $postinfo['username']
		, $vbulletin->options['bbtitle']
	);
	
	//build description
	if (isset($postinfo['pagetext'])) {
		$description = getWords($postinfo['pagetext']);
	} else {
		$description = getWords($postinfo['message']);
	}
	
	$media = array('images' => array());

	//get images
	preg_match_all('/\[img\](.*?)\[\/img\]/i',$postinfo['pagetext'],$matches,PREG_PATTERN_ORDER );
	if (is_array($matches[1]) AND count($matches[1]) > 0) {
		for ($i = 0; $i < count($matches[1]); $i++) {
			$media['images'][] = array(
				'src' => $matches[1][$i],
			);
		}
	}
	
	//BOF - process attachments
	$parseAttachments = true;
	
	if ($parseAttachments AND $postinfo['attach']) {
		//check for attachment permission
		//scripts copied from attachment.php
		$guestinfo = array(
			'userid' => 0,
			'usergroupid' => 1,
		);
	
		$guest_forumperms = fetch_permissions($foruminfo['forumid'], 0, $guestinfo);

		# Block attachments belonging to soft deleted/moderated posts and threads
		if (in_array($postinfo['visible'],array(0,2)) OR in_array($threadinfo['visible'],array(0,2))) {
			$parseAttachments = false;
		}

		$viewpermission = (($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment']));
		$viewthumbpermission = (($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment']) OR ($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['canseethumbnails']));

		if (!($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) 
			OR !($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']) 
			OR !($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) 
		) {
			$parseAttachments = false;
		}
		else if (!$viewthumbpermission) {
			$parseAttachments = false;
		}
	}
	
	if ($vbulletin->fbb['runtime']['vb4']) $parseAttachments = false; //temporary disable this feature under vBulletin 4
	
	if ($parseAttachments) {
		if (/*$postinfo['attach'] AND*/ empty($postinfo['attachments'])) {
			//copied from showpost.php
			if ($vbulletin->fbb['runtime']['vb4']) {
				require_once(DIR . '/includes/class_bootstrap_framework.php');
				require_once(DIR . '/vb/types.php');
				vB_Bootstrap_Framework::init();
				$types = vB_Types::instance();
				$contenttypeid = $types->getContentTypeID('vBForum_Post');
				
				$attachments = $vbulletin->db->query_read_slave("
					SELECT
						fd.thumbnail_dateline, fd.filesize, IF(fd.thumbnail_filesize > 0, 1, 0) AS hasthumbnail, fd.thumbnail_filesize,
						a.dateline, a.state, a.attachmentid, a.counter, a.contentid AS postid, a.filename,
						type.contenttypes
					FROM " . TABLE_PREFIX . "attachment AS a
					INNER JOIN " . TABLE_PREFIX . "filedata AS fd ON (a.filedataid = fd.filedataid)
					LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS type ON (fd.extension = type.extension)
					WHERE
						a.contentid = $postinfo[postid]
							AND
						a.contenttypeid = $contenttypeid
					ORDER BY a.attachmentid
				");

				while ($attachment = $vbulletin->db->fetch_array($attachments)) {
					$attachment['visible'] = $attachment['state'] == 'visible'?1:0;
					$content = @unserialize($attachment['contenttypes']);
					$attachment['newwindow'] = $content["$contenttypeid"]['n'];
					$postinfo['attachments']["$attachment[attachmentid]"] = $attachment;
				}
			} else {
				$attachments = $vbulletin->db->query_read_slave("
					SELECT dateline, thumbnail_dateline, filename, filesize, visible, attachmentid, counter,
						postid, IF(thumbnail_filesize > 0, 1, 0) AS hasthumbnail, thumbnail_filesize,
						attachmenttype.thumbnail AS build_thumbnail, attachmenttype.newwindow
					FROM " . TABLE_PREFIX . "attachment
					LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS attachmenttype USING (extension)
					WHERE postid = $postinfo[postid]
					ORDER BY attachmentid
				");
				
				while ($attachment = $vbulletin->db->fetch_array($attachments)) {
					if (!$attachment['build_thumbnail']) {
						$attachment['hasthumbnail'] = false;
					}
					$postinfo['attachments']["$attachment[attachmentid]"] = $attachment;
				}
			}
			$vbulletin->db->free_result($attachments);
		}
		
		if (!empty($postinfo['attachments'])) {
			foreach ($postinfo['attachments'] as $attachmentid => $attachment) {
				if ($attachment['visible'] AND $attachment['hasthumbnail']) {
					$media['images'][] = array(
						'src' => "{$vbulletin->options['bburl']}/attachment.php?$session[sessionurl]attachmentid=$attachment[attachmentid]&stc=1&thumb=1&d=$attachment[thumbnail_dateline]",
					);
				}
			}
		}
	}
	//EOF - process attachments
	
	return array($title,$description,$media);
}

function actionFromDashCode($code) {
	$action = ucwords(implode(' ',explode('_',$code)));
	return $action;
}

function fb_render_options_html($scope = false,$fboptions = false) {
	global $vbulletin, $vbphrase;
	
	$actions = $notifications = '';
	
	if ($fboptions === false)
		$fboptions = unserialize($vbulletin->userinfo['fboptions']);
	
	if ($scope !== false) {
		$skip_amap = true;
	}
	$scope_parts = explode('__',$scope);
	$do_parts = array();
	$keys = '';
	$scope_level = iif($skip_amap,0,-1);
	for ($i = 0; $i < count($scope_parts); $i++) {
		if ($scope_parts[$i]) //check for empty string;
		{
			$keys .= '[' . $scope_parts[$i] . ']';
			$scope_level++;
		}
	}
	eval('$do_parts' . $keys . ' = $vbulletin->fbb["user_options_config"]' . $keys . ';');

	if (isset($do_parts['actions'])) {
		foreach ($do_parts['actions'] as $action => $action_dos) {
			$actions_tmp = '';
			
			$actions_tmp .= iif($scope_level < 2,'<li>' 
				. '<a name="actions__' . $action . '"></a>'
				. fb_option_name('actions__' . $action)
				. iif($vbulletin->fbb['runtime']['vb4'],'<div class="description">')
				. '<ul id="actions__' . $action . iif($vbulletin->fbb['runtime']['vb4'],'" class="checkradio group full','') . '">'
			);
			
			$action_dos_count = 0;
			foreach ($action_dos as $action_do => $action_do_config) {
				//currently we don't use the $action_do_config, yet
				$option = 'actions__' . $action . '__' . $action_do;
				
				if (fb_option_available($option)) {
					$actions_tmp .= iif($scope_level < 3,'<li id="' . $option . '">')
						. fb_option_name($option);
					$actions_tmp .= ' - ';
					$action_do_onoff = fb_quick_options_check($option,$fboptions);
					$action_do_url = 'profile.php?do=updatefbboptions&type=actions&action=' . $action . '&action_do=' . $action_do . '&onoff=' . iif($action_do_onoff,'0','1');
					$actions_tmp .= '<a id="' . $option . '_link" href="' . $action_do_url . '" onclick="return change_option(this);">' . iif($action_do_onoff,$vbphrase['fbb_options_action_do_on'],$vbphrase['fbb_options_action_do_off']) . '</a>';
					$actions_tmp .= iif($scope_level < 3,'</li>');
					
					$action_dos_count++;
				}
			}
			
			$actions_tmp .= iif($scope_level < 2,'</ul>'
				. iif($vbulletin->fbb['runtime']['vb4'],'</div>')
				. '</li>'
			);
			
			if ($action_dos_count) {
				$actions .= $actions_tmp; //add this whole action into the output
			}
		}
	}

	foreach (array('notifications','others') as $type) {
		if (isset($do_parts[$type])) {
			foreach ($do_parts[$type] as $item => $item_config) {
				//currently we don't use the $item_config, yet
				$option = $type . '__' . $item;
				$type_tmp = '';
				$type_single = substr($type,0,-1); //remove the "s"
				
				if (fb_option_available($option)) {
					$type_tmp .= iif($scope_level < 2,'<li id="' . $option . '">')
						. '<a name="' . $option . '"></a>'
						. fb_option_name($option);
					$type_tmp .= ' - ';
					$item_onoff = fb_quick_options_check($option,$fboptions);
					$item_url = "profile.php?do=updatefbboptions&type=$type&$type_single=$item&onoff=" . iif($item_onoff,'0','1');
					$type_tmp .= '<a id="' . $option . '_link" href="' . $item_url . '" onclick="return change_option(this);">' . iif($item_onoff,$vbphrase['fbb_options_' . $type_single . '_on'],$vbphrase['fbb_options_' . $type_single . '_off']) . '</a>';
					$type_tmp .= iif($scope_level < 2,'</li>');
				}
				
				if ($type_tmp) {
					eval('$' . $type . ' .= $type_tmp;');
				}
			}
		}
	}
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fbb_vb4_options');
			$templater->register('scope_level', $scope_level);
			$templater->register('actions', $actions);
			$templater->register('notifications', $notifications);
			$templater->register('others', $others);
		$result = $templater->render();
	} else {
		eval('$result = "' . fetch_template('fbb_options') . '";');
	}
	
	return $result;
}

function fb_option2key($option) {
	$keys = '';
	$parts = explode('__',$option);
	foreach ($parts as $part)
		if ($part)
			$keys .= '[' . $part . ']';
	
	return $keys;
}

function fb_option_name($option) {
	global $vbphrase;
	
	$parts = explode('__',$option);
	
	switch ($parts[0]) {
		case 'actions':
			if (count($parts) == 3) {
				$action_do = $parts[2];
				$name = iif(isset($vbphrase['fbb_options_do_' . $action_do]),$vbphrase['fbb_options_do_' . $action_do],actionFromDashCode($action_do));
			} else if (count($parts) == 2) {
				$action = $parts[1];
				$name = iif(isset($vbphrase['fbb_options_action_' . $action]),$vbphrase['fbb_options_action_' . $action],actionFromDashCode($action));
			}
			break;
		case'notifications':
			if (count($parts) == 2) {
				$notification = $parts[1];
				$name = iif(isset($vbphrase['fbb_options_notification_' . $notification]),$vbphrase['fbb_options_notification_' . $notification],actionFromDashCode($notification));
			} else {
				$name = iif(isset($vbphrase['fbb_options_notifications']),$vbphrase['fbb_options_notifications'],actionFromDashCode('notifications'));
			}
			break;
		case 'others':
			if (count($parts) == 2) {
				$other = $parts[1];
				$name = iif(isset($vbphrase['fbb_options_other_' . $other]),$vbphrase['fbb_options_other_' . $other],actionFromDashCode($other));
			} else {
				$name = iif(isset($vbphrase['fbb_options_others']),$vbphrase['fbb_options_others'],actionFromDashCode('others'));
			}
			break;
		default:
			$name = array();
			foreach ($parts as $part) {
				$name[] = actionFromDashCode($part);
			}
			$name = implode(' - ',$name);
	}
	
	return $name;
}

function fb_permission_name($permission) {
	global $vbphrase;
	
	$name = iif(isset($vbphrase['fbb_permission_' . $permission]),$vbphrase['fbb_permission_' . $permission],actionFromDashCode($permission));
	
	return $name;
}

function fb_option_available($option,$settings = null,$send_message = false) {
	global $vbulletin, $vbphrase;
	
	if ($settings === null) {
		$keys = fb_option2key($option);
		eval('$settings = $vbulletin->fbb["user_options_config"]' . $keys . ';');
	}
	
	if (is_array($settings)) {
		if (isset($settings['depend_on_system_config'])) {
			foreach ($settings['depend_on_system_config'] as $system_config_key => $system_config_value) {
				if ($vbulletin->fbb['config'][$system_config_key] != $system_config_value) {
					if ($send_message)
						fb_message('changing_options',construct_phrase(
							$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
							, fb_option_name($option)
							, $vbphrase['fbb_option_changing_error_system_config']
						));
					DEVDEBUG('$vbulletin->fbb[\'config\'][' . $system_config_key . '] FAIL');
					return false;
				}
			}
		}
		
		if (isset($settings['depend_on_system_eval'])) {
			foreach ($settings['depend_on_system_eval'] as $system_eval => $system_eval_value) {
				eval('$tmp_eval = ' . $system_eval . ';');
				if ($tmp_eval != $system_eval_value) {
					if ($send_message)
						fb_message('changing_options',construct_phrase(
							$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
							, fb_option_name($option)
							, $vbphrase['fbb_option_changing_error_system_config']
						));
					DEVDEBUG($system_eval . ' FAIL (' . $tmp_eval . ')');
					return false;
				}
			}
		}
	}
	
	return true;
}

function fb_turn_on_option($option,&$fboptions,&$scopes,$userinfo = false) {
	global $vbulletin, $vbphrase;
	
	$keys = fb_option2key($option);
	eval('$fboptions' . $keys . ' = 1;');
	DEVDEBUG('$fboptions' . $keys . ' = 1;');
	
	eval('$settings = $vbulletin->fbb["user_options_config"]' . $keys . ';');
	if (is_array($settings)) {
		/*
		if (isset($settings['depend_on_system_config'])) {
			foreach ($settings['depend_on_system_config'] as $system_config_key => $system_config_value) {
				if ($vbulletin->fbb['config'][$system_config_key] != $system_config_value) {
					eval('unset($fboptions' . $keys . ');'); //failed system config dependency
					fb_message('changing_options',construct_phrase(
						$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
						, fb_option_name($option)
						, $vbphrase['fbb_option_changing_error_system_config']
					));
				}
			}
		}
		
		if (isset($settings['depend_on_system_eval'])) {
			foreach ($settings['depend_on_system_eval'] as $system_eval => $system_eval_value) {
				eval('$tmp_eval = ' . $system_eval . ';');
				if ($tmp_eval != $system_eval_value) {
					eval('unset($fboptions' . $keys . ');'); //failed system eval dependency
					fb_message('changing_options',construct_phrase(
						$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
						, fb_option_name($option)
						, $vbphrase['fbb_option_changing_error_system_config']
					));
				}
			}
		}
		*/
		
		if (!fb_option_available($option,$settings,true)) {
			eval('unset($fboptions' . $keys . ');'); //failed on some system configuration
		}
		
		if (isset($settings['conflict_auto_turnoff'])) {
			$parts = explode('__',$option); //parse again, for safety reason
			$parts_before = '';
			for ($i = 0; $i < count($parts) - 1; $i++)
				$parts_before[] = $parts[$i];
			foreach ($settings['conflict_auto_turnoff'] as $conflict_key => $value) {
				$parts_tmp = $parts_before;
				$parts_tmp[] = $conflict_key;
				//turn off conflict items now
				if (fb_turn_off_option(implode('__',$parts_tmp),$fboptions,$scope)) {
					fb_message('changing_options',construct_phrase(
						$vbphrase['fbb_option_changing_message_turned_off_x']
						, fb_option_name(implode('__',$parts_tmp))
					));
				}
			}

			$scopes[] = implode('__',$parts_before);
		}
		
		if (isset($settings['depend_on_user_config'])) {
			foreach ($settings['depend_on_user_config'] as $user_config_option => $user_config_value) {
				if (fb_quick_options_check($user_config_option,$fboptions,true,$userinfo) != $user_config_value) {
					eval('unset($fboptions' . $keys . ');'); //failed user config dependency
					fb_message('changing_options',construct_phrase(
						$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
						, fb_option_name($option)
						, construct_phrase(
							$vbphrase['fbb_option_changing_error_user_config']
							,fb_option_name($user_config_option)
						)
					));
				}
			}
		}
		
		if (isset($settings['depend_on_user_permission'])) {
			if ($userinfo === false) $userinfo =& $vbulletin->userinfo;
			
			//before process, check for connected stage
			if (!$userinfo['fbuid']) {
				eval('unset($fboptions' . $keys . ');'); //failed connected 
				fb_message('changing_options',construct_phrase(
					$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
					, fb_option_name($option)
					, $vbphrase['fbb_option_changing_error_not_connected']
				));
			} else if (!fb_fetch_permission($userinfo['fbuid'])) {
				eval('unset($fboptions' . $keys . ');'); //failed established
				fb_message('changing_options',construct_phrase(
					$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
					, fb_option_name($option)
					, $vbphrase['fbb_option_changing_error_not_established']
				));
			} else {
				foreach ($settings['depend_on_user_permission'] as $required_permission => $value) {
					if (fb_fetch_permission($userinfo['fbuid'],$required_permission) != $value) {
						eval('unset($fboptions' . $keys . ');'); //failed permission check
						fb_message('changing_options',construct_phrase(
							$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
							, fb_option_name($option)
							, construct_phrase(
								$vbphrase['fbb_option_changing_error_permission_missing']
								,fb_permission_name($required_permission)
							)
						));
					}
				}
			}
		}
		
		if (isset($settings['turn_on_eval'])) {
			foreach ($settings['turn_on_eval'] as $eval_str => $eval_value) {
				eval('$tmp_eval = ' . $eval_str . ';');
				DEVDEBUG('$tmp_eval = ' . $eval_str . ';');
				DEVDEBUG('$tmp_eval = ' . $tmp_eval);
				if ($tmp_eval != $eval_value) {
					eval('unset($fboptions' . $keys . ');'); //failed turn on eval procedure
					fb_message('changing_options',construct_phrase(
						$vbphrase['fbb_option_changing_error_turn_on_x_because_y']
						, fb_option_name($option)
						, $vbphrase['fbb_option_changing_error_unable']
					));
				}
			}
		}
	}

	$scopes[] = $option;
}

function fb_turn_off_option($option,&$fboptions,&$scopes) {
	global $vbulletin;
	
	$keys = '';
	$parts = explode('__',$option);
	foreach ($parts as $part)
		if ($part)
			$keys .= '[' . $part . ']';
	eval('$tmp = $fboptions' . $keys . ';');
	
	if ($tmp) {
		eval('unset($fboptions' . $keys . ');');
		DEVDEBUG('unset($fboptions' . $keys . ');');

		$scopes[] = $option;
		return true;
	}
	
	return false;
}

function fb_get_default_options_set() {
	global $vbulletin;
	
	static $default_fboptions = null;
	
	if ($default_fboptions === null) {
		$fboptions = array();
		$scopes = array(); //just want to please the functions, we don't use it here
		
		if (!empty($vbulletin->fbb['config']['auto_on_options']) AND isset($vbulletin->fbb['config']['auto_on_options']['fboptions'])) {
			//we can just use the preset data, permissions will be checked later
			//but since we have a lot of time with this function so... do it here
			foreach ($vbulletin->fbb['config']['auto_on_options']['fboptions'] as $option => $value) {
				fb_turn_on_option($option,$fboptions,$scopes);
			}
		} else {
			foreach ($vbulletin->fbb['user_options_config']['actions'] as $action => $action_dos) {
				foreach ($action_dos as $action_do => $action_do_config) {
					if (empty($action_do_config['depend_on_user_permission'])) {
						fb_turn_on_option('actions__' . $action . '__' . $action_do,$fboptions,$scopes);
					}
				}
			}
			foreach (array('notifications','others') as $category) {
				foreach ($vbulletin->fbb['user_options_config'][$category] as $item => $item_config) {
					if (empty($item_config['depend_on_user_permission'])) {
						fb_turn_on_option($category . '__' . $item,$fboptions,$scopes);
					}
				}
			}
		}
		
		$default_fboptions = $fboptions;
	}
	
	return $default_fboptions;
}

function fb_found_messages($type) {
	global $vbulletin;
	
	$tmp =& $vbulletin->fbb['runtime']['messages_' . $type];
	
	if (isset($tmp)
		AND is_array($tmp)
		AND count($tmp) > 0)
	{
		return true;
	} else {
		return false;
	}
}

function fb_get_messages($type) {
	global $vbulletin;
	
	$tmp = $vbulletin->fbb['runtime']['messages_' . $type];
	unset($vbulletin->fbb['runtime']['message_' . $type]); //clear message stage
	return $tmp;
}

function fb_message($type,$message,$message_id = null) {
	global $vbulletin;
	
	$tmp =& $vbulletin->fbb['runtime']['messages_' . $type];
	
	if (!isset($tmp)) $tmp = array();
	
	if ($message_id) {
		$tmp[$message_id] = $message;
	} else {
		$tmp[] = $message;
	}
}

/* ACTIONS */

function fb_sync_avatar($userinfo, $update_database = true) {
	global $vbulletin;
	static $sync_ed_counter = 0;
	
	$return = array();
	
	if ($vbulletin->fbb['config']['sync_avatar_max_perpage'] == 0 OR $sync_ed_counter < $vbulletin->fbb['config']['sync_avatar_max_perpage']) {
		if ($userinfo['fbuid']) {
			$fboptions = unserialize($userinfo['fboptions']);
			
			if (fb_quick_options_check('others__sync_avatar',$fboptions)) {
				//user enabled sync_avatar
				if ($fboptions['sync_avatar_timeline'] < TIMENOW - $vbulletin->fbb['config']['sync_avatar_cache_timeout']) {
					//the avatar is old enough, update the new one

					/* Context stuff */
					$correct_userinfo = $vbulletin->userinfo;
					$vbulletin->userinfo = $userinfo;
					$permissions = cache_permissions($vbulletin->userinfo);
					$correct_flag = $vbulletin->fbb['runtime']['fboptions_changed'];
					unset($vbulletin->fbb['runtime']['fboptions_changed']);
					$correct_conflict_actions = $vbulletin->fbb['runtime']['conflict_actions'];
					$vbulletin->fbb['runtime']['conflict_actions']['actions__upload_image__publish_photo'] = false;
					/* Context stuff */
					
					$errors_found = false;
					
					$vbulletin->GPC['avatarid'] = -2;
					$vbulletin->GPC['avatarurl'] = '';
					
					($hook = vBulletinHook::fetch_hook('profile_updateavatar_start')) ? eval($hook) : false;

					if ($vbulletin->GPC['avatarid'] == 0 
						AND ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar'])
						AND $vbulletin->GPC['avatarurl']
					) {
						//$vbulletin->input->clean_gpc('f', 'upload', TYPE_FILE);

						// begin custom avatar code
						require_once(DIR . '/includes/class_upload.php');
						require_once(DIR . '/includes/class_image.php');

						$upload = new vB_Upload_Userpic($vbulletin);

						$upload->data =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
						$upload->image =& vB_Image::fetch_library($vbulletin);
						$upload->maxwidth = $vbulletin->userinfo['permissions']['avatarmaxwidth'];
						$upload->maxheight = $vbulletin->userinfo['permissions']['avatarmaxheight'];
						$upload->maxuploadsize = $vbulletin->userinfo['permissions']['avatarmaxsize'];
						$upload->allowanimation = ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateavatar']) ? true : false;

						if (!$upload->process_upload($vbulletin->GPC['avatarurl']))
						{
							//eval(standard_error($upload->fetch_error()));
							$errors_found = true;
						}
					} else {
						//$errors_found = true;
					}
					
					if ($errors_found == false) {
						// init user data manager
						$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
						$userdata->set_existing($vbulletin->userinfo);

						($hook = vBulletinHook::fetch_hook('profile_updateavatar_complete')) ? eval($hook) : false;

						$userdata->save();
					}
					
					if ($errors_found == false AND $upload) {
						$return = array(
							'hascustomavatar' => 1,
							'avatarrevision' => $upload->data->fetch_field('avatarrevision'),
							'avatardateline' => $upload->data->fetch_field('dateline'),
							'avwidth' => $upload->data->fetch_field('width'),
							'avheight' => $upload->data->fetch_field('height'),
						);
						DEVDEBUG('UPDATED AVATAR FROM FACEBOOK. USERID = ' . $vbulletin->userinfo['userid']);
						
						$sync_ed_counter++;
						DEVDEBUG('$sync_ed_counter = ' . $sync_ed_counter);
					}
					
					/* Context stuff */
					$vbulletin->userinfo = $correct_userinfo;
					$vbulletin->fbb['runtime']['fboptions_changed'] = $correct_flag;
					$vbulletin->fbb['runtime']['conflict_actions'] = $correct_conflict_actions;
					/* Context stuff */
				}
				DEVDEBUG('SKIPPED UPDATEING AVATAR (NOT OLD ENOUGH)');
			}
		}
	} else {
		DEVDEBUG('SKIPPED UPDATEING AVATAR (REACHED LIMIT)');
	}
	
	return $return;
}

function fb_update_profile_box($userinfo = false) {
	global $vbulletin, $vbphrase, $stylevar;

	if ($userinfo === false) $userinfo = $vbulletin->userinfo;
	if (!$userinfo['fbuid']) return false;
	
	$userinfo['joindate_str'] = date('r',$userinfo['joindate']);
	$userinfo['lastactivity_str'] = date('r',$userinfo['lastactivity']);
	$userinfo['posts_str'] = vb_number_format($userinfo['posts']);
	fetch_musername($userinfo);
	
	$guestforumids = fb_getGuestForumIDs();
	
	$user_posts = '';
	if (is_array($guestforumids) AND count($guestforumids)) {
		$posts_from_db = $vbulletin->db->query_read("
			SELECT post.*
				, thread.title AS thread_title
				, thread.iconid AS thread_iconid
				, thread.pollid AS thread_pollid
			FROM `" . TABLE_PREFIX . "post` AS post
			INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)
			WHERE post.userid = $userinfo[userid]
				AND thread.forumid IN (" . implode(',',$guestforumids) . ")
			GROUP BY threadid
			ORDER BY dateline DESC
			LIMIT 3
		");
		while ($postinfo = $vbulletin->db->fetch_array($posts_from_db)) {
			$postinfo['dateline_str'] = date('r',$postinfo['dateline']);
			$postinfo['description_cutoff'] = getWords($postinfo['pagetext']);
			$postinfo['description_cutoff'] = str_replace('"','',$postinfo['description_cutoff']);
			$postinfo['description_cutoff'] = preg_replace('/(\r\n|\r|\n)/',' ',$postinfo['description_cutoff']);
			
			if (!isset($vbulletin->iconcache)) {
				$iconcache_from_db = $vbulletin->db->query_first("
					SELECT *
					FROM `" . TABLE_PREFIX . "datastore`
					WHERE title = 'iconcache'
				");
				$vbulletin->iconcache = unserialize($iconcache_from_db['data']);
			}
			
			// show poll icon
			if ($postinfo['thread_pollid'] != 0)
			{
				$thread['thread_iconpath'] = "$stylevar[imgdir_misc]/poll_posticon.gif";
				$thread['thread_icontitle'] = $vbphrase['poll'];
			}
			// get icon from icon cache
			else if ($postinfo['thread_iconid'])
			{
				$postinfo['thread_iconpath'] = $vbulletin->iconcache["$postinfo[thread_iconid]"]['iconpath'];
				$postinfo['thread_icontitle'] = $vbulletin->iconcache["$postinfo[thread_iconid]"]['title'];
			}
			// show default icon
			else
			{
				$postinfo['thread_iconpath'] = $vbulletin->options['showdeficon'];
				$postinfo['thread_icontitle'] = '';
			}

			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('fbb_fbml_profile_postbit');
					$templater->register('postinfo', $postinfo);
				$user_posts = $templater->render();
			} else {
				eval('$user_posts .= "' . fetch_template('fbb_fbml_profile_postbit') . '";');
			}
		}
		$vbulletin->db->free_result($posts_from_db);
	}
	
	static $hook_code = false;
	if ($hook_code === false)
	{
		$hook_code = vBulletinHook::fetch_hook('fbb_update_profile_box');
	}
	if ($hook_code)
	{
		eval($hook_code);
	}
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fbb_fbml_profile');
			$templater->register('userinfo', $userinfo);
			$templater->register('user_posts', $user_posts);
			$templater->register('template_hook', $template_hook);
		$profile_fbml = $templater->render();
	} else {
		eval('$profile_fbml = "' . fetch_template('fbb_fbml_profile') . '";');
	}
	
	$result = fb_profile_setFBML(NULL,$userinfo['fbuid'],$profile_fbml,NULL,NULL,$profile_fbml);
	
	if (!fb_isErrorCode($result) OR $result == '1') {
		return true;
	} else {
		return false;
	}
}

function fb_update_profile_box_static_parts() {
	global $vbulletin, $vbphrase, $stylevar, $show;
	
	/* Code copied from index.php */
	// ### BOARD STATISTICS #################################################

	// get total threads & posts from the forumcache
	$totalthreads = 0;
	$totalposts = 0;
	
	if (!isset($vbulletin->forumcache)) {
		$forumcache_from_db = $vbulletin->db->query_first("
			SELECT *
			FROM `" . TABLE_PREFIX . "datastore`
			WHERE title = 'forumcache'
		");
		$vbulletin->forumcache = unserialize($forumcache_from_db['data']);
	}
	cache_ordered_forums(1);
	if (is_array($vbulletin->forumcache))
	{
		foreach ($vbulletin->forumcache AS $forum)
		{
			$totalthreads += $forum['threadcount'];
			$totalposts += $forum['replycount'];
		}
	}
	$totalthreads = vb_number_format($totalthreads);
	$totalposts = vb_number_format($totalposts);

	if (!isset($vbulletin->userstats)) {
		$userstats_from_db = $vbulletin->db->query_first("
			SELECT *
			FROM `" . TABLE_PREFIX . "datastore`
			WHERE title = 'userstats'
		");
		$vbulletin->userstats = unserialize($userstats_from_db['data']);
	}
	// get total members and newest member from template
	$numbermembers = vb_number_format($vbulletin->userstats['numbermembers']);
	$newusername = $vbulletin->userstats['newusername'];
	$newuserid = $vbulletin->userstats['newuserid'];
	$activemembers = vb_number_format($vbulletin->userstats['activemembers']);
	$show['activemembers'] = ($vbulletin->options['activememberdays'] > 0 AND ($vbulletin->options['activememberoptions'] & 2)) ? true : false;
	
	($hook = vBulletinHook::fetch_hook('fbb_update_profile_box_forums_stat')) ? eval($hook) : false;
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fbb_fbml_forums_stat');
			$templater->register('totalthreads', $totalthreads);
			$templater->register('totalposts', $totalposts);
			$templater->register('numbermembers', $numbermembers);
			$templater->register('activemembers', $activemembers);
			$templater->register('template_hook', $template_hook);
		$user_posts = $templater->render();
	} else {
		eval('$fbml_forums_stat = "' . fetch_template('fbb_fbml_forums_stat') . '";');
	}
	
	fb_fbml_setRefHandle('forums_stat',$fbml_forums_stat);
}

function fb_connect_orphan_posts($userinfo, $fbuid) {
	global $vbulletin;
	
	$posts = array();
	
	$posts_from_db = false;
	$vbulletin->db->hide_errors();
	$posts_from_db = $vbulletin->db->query_read("
		SELECT postid
		FROM `" . TABLE_PREFIX . "post`
		WHERE userid = 0 AND fbuid = '$fbuid'
	");
	$vbulletin->db->show_errors();
	
	if ($posts_from_db) {
		while ($post = $vbulletin->db->fetch_array($posts_from_db)) {
			$posts[$post['postid']] = $post;
		}
		$vbulletin->db->free_result($posts_from_db);
		
		if (!empty($posts)) {
			$vbulletin->db->query("
				UPDATE `" . TABLE_PREFIX . "post`
				SET userid = $userinfo[userid]
					,username = '" . $vbulletin->db->escape_string($userinfo['username']) . "'
				WHERE postid IN (" . implode(',',array_keys($posts)) . ")
			");
			
			//Update posts count
			$forums = $vbulletin->db->query_read("
				SELECT forumid
				FROM " . TABLE_PREFIX . "forum AS forum
				WHERE (forum.options & " . $vbulletin->bf_misc_forumoptions['countposts'] . ")
			");
			$gotforums = '';
			while ($forum = $vbulletin->db->fetch_array($forums)) {
				$gotforums .= ',' . $forum['forumid'];
			}
			$vbulletin->db->free_result($forums);
			
			$totalposts = $vbulletin->db->query_first("
				SELECT COUNT(*) AS posts
				FROM " . TABLE_PREFIX . "post AS post
				INNER JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = post.threadid)
				WHERE post.userid = $userinfo[userid]
					AND thread.forumid IN (0$gotforums)
					AND thread.visible = 1
					AND post.visible = 1
			");

			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
			$userdm->set_existing($userinfo);
			$userdm->set('posts', $totalposts['posts']);
			$userdm->set_ladder_usertitle($totalposts['posts']);
			$userdm->save();
			unset($userdm);
		}
	}
	
	return count($posts);
}

/* SHARING SPECIFIC FUNCTIONS */

function fb_getLink($id,$type = 'post') {
	global $vbulletin;
	
	switch ($type) {
		case 'post':
			if ($vbulletin->fbb['runtime']['vb4']) {
				$url = 'showpost.php?p=' . $id;
			} else {
				$url = 'showthread.php?p=' . $id;
			}
			break;
		case 'thread':
			$url = 'showthread.php?t=' . $id;
			break;
		case 'forum':
			$url = 'forumdisplay.php?f=' . $id;
			break;
	}
	
	return $vbulletin->options['bburl'] . '/' . $url;
}

/* HELPERS */

function fb_getForumRestrictKey($foruminfo, $check_guest_only = false) {
	global $vbulletin;

	if (!$check_guest_only) {
		if (!empty($foruminfo['fbb_allow_to_post'])) {
			switch ($foruminfo['fbb_allow_to_post']) {
				case 'allow': 
					return '';
				case 'disallow': 
					if ($vbulletin->fbb['config']['private_forums_restriction']) {
						return 'disabled_checkboxes';
					} else {
						return 'off_checkboxes';
					}
				case 'turn_off':
					return 'off_checkboxes';
				case 'disable':
					return 'disabled_checkboxes';
			}
		}
	}
	
	$guestinfo = array(
		'userid' => 0,
		'usergroupid' => 1,
	);
	
	$guest_forumperms = fetch_permissions($foruminfo['forumid'], 0, $guestinfo);

	if (!($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) 
		OR !($guest_forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads'])
	) {
		//forum can not be viewed by guest
		if ($vbulletin->fbb['config']['private_forums_restriction']) {
			return 'disabled_checkboxes';
		} else {
			return 'off_checkboxes';
		}
	}
	
	return '';
}

function fb_getGuestForumIDs() {
	global $vbulletin;
	static $guestforumids = null;
	
	if ($guestforumids === null) {
		$guestforumids = array();
		
		foreach ($vbulletin->forumcache as $forum) {
			if (fb_getForumRestrictKey($forum) == '') {
				$guestforumids[] = $forum['forumid'];
			}
		}
	}
	
	return $guestforumids;
}

function fb_print_share_preview($title, $description, $media = array()) {
	global $vboptions, $stylevar;
	
	$title = str_replace('"','',$title);
	$description = str_replace('"','',strip_tags($description));
	$media_meta_tags_array = array();
	$content_array = array();
	if (!empty($media)) {
		if (!empty($media['images'])) {
			foreach ($media['images'] as $image) {
				//$media_meta_tags_array[] = '<link rel="image_src" href="' . $image['src'] .  '" />';
				$content_array[] = '<img src="' . $image['src'] . '" />';
			}
		}
	}
	$media_meta_tags = implode("\r\n",$media_meta_tags_array);
	$content = implode("<br/>",$content_array);
	
	echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="$stylevar[textdirection]" lang="$stylevar[languagecode]">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=$stylevar[charset]" />
<meta name="generator" content="vBulletin $vboptions[templateversion] - YAFB" />
<meta name="title" content="$title" />
<meta name="description" content="$description" />
$media_meta_tags
<title>$title</title>
</head>
<body>
</body>
This content is prepared specially for Facebook's crawlers, if you can read this paragraph, please contact $vboptions[bbtitle]'s Staff or YAFB's developer (via email address: daohoangson@gmail.com)<br/>
We are so sorry for this inconvenience...
$content
</html>
EOF;
	exit; //stop the excution
}
?>