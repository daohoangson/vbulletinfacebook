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
######################################## CONFIGURATION SECTION ########################################
if (true) {
	//need these templates (mostly) all the time
	$fb_globaltemplates[] = 'fb_footer';
	$fb_globaltemplates[] = 'fbb_usercp_navbar_bottom';
	$fb_globaltemplates[] = 'fbb_navbar_button';
	$fb_globaltemplates[] = 'fbb_fbml_forums_stat';
	$fb_globaltemplates[] = 'fbb_fbml_profile';
	$fb_globaltemplates[] = 'fbb_fbml_profile_postbit';
	$fb_globaltemplates[] = 'fbb_new_post_options';
	$fb_globaltemplates[] = 'fbb_postbit_controls';
	$fb_globaltemplates[] = 'fb_script_common';
}
if (THIS_SCRIPT == 'profile') {
	$fb_globaltemplates[] = 'fbb_profile';
	$fb_globaltemplates[] = 'fb_permissionbit';
	$fb_globaltemplates[] = 'fb_page_permissionbit';
	$fb_globaltemplates[] = 'fbb_options';
	$fb_globaltemplates[] = 'fbb_modifyavatar';
	$fb_globaltemplates[] = 'fb_invite';
}
if (THIS_SCRIPT == 'register') {
	$fb_globaltemplates[] = 'fbb_register';
}
if (THIS_SCRIPT == 'showthread') {
	$fb_globaltemplates[] = 'fbb_new_post_options_tricky';
	$fb_globaltemplates[] = 'fb_memberinfo_imdetail_simple';
}
if (THIS_SCRIPT == 'showpost') {
	$fb_globaltemplates[] = 'fb_memberinfo_imdetail_simple';
}
if (THIS_SCRIPT == 'member') {
	$fb_globaltemplates[] = 'fb_memberinfo_imdetail';
	$fb_globaltemplates[] = 'fbb_memberinfo_block_recent';
	$fb_globaltemplates[] = 'fb_memberinfo_block_postbit';
}
if (THIS_SCRIPT == 'album') {
	$fb_globaltemplates[] = 'fbb_album_editpictures_options';
	$fb_globaltemplates[] = 'fbb_new_post_options_tricky';
}
if (THIS_SCRIPT == 'facebook') {
	$fb_globaltemplates[] = 'fbb_landing_page';
}

//GLOBALTEMPLATES WILL BE MERGED BELOW

$fbb = array(
	'config' => array(
		'apikey' => $vbulletin->options['fbb_apikey'],
		'secret' => $vbulletin->options['fbb_secret'],
		
		'auto_login' => $vbulletin->options['fbb_template_auto_login']?true:false,
		'activated_system_wide' => $vbulletin->options['fbb_activated_system_wide']?true:false,
		'cookie_last_checked_name' => 'fbb_last_checked',
		'cookie_last_checked_timeout' => 6*60*60, //six hours before checking again
		
		'register_on_the_fly' => array(), //place holder, will be processed below
		
		'auto_register' => $vbulletin->options['fbb_template_auto_register']?true:false,
		'accept_proxied_email' => $vbulletin->options['fbb_auto_register_accept_proxied_email']?true:false,
		'auto_register_usergroupid' => $vbulletin->options['fbb_auto_register_usergroupid'],
		
		'post_reply_notification_old_posts_limit' => $vbulletin->options['fbb_post_reply_notification_old_posts_limit'],
		'comment_as_reply' => intval($vbulletin->options['fbb_comment_as_reply']),
		'comment_as_reply_get_name' => $vbulletin->options['fbb_comment_as_reply_get_name']?true:false,
		'comment_as_reply_max' => $vbulletin->options['fbb_comment_as_reply_max'],
		
		'avatar_enabled' => $vbulletin->options['fbb_sync_avatar_source']?true:false,
		'sync_avatar_source' => $vbulletin->options['fbb_sync_avatar_source']?$vbulletin->options['fbb_sync_avatar_source']:'pic_big',
		'sync_avatar_cache_timeout' => $vbulletin->options['fbb_sync_avatar_cache_timeout'],
		'sync_avatar_max_perpage' => $vbulletin->options['fbb_sync_avatar_max_perpage'],

		'display_elements' => array(
			'navbar_connect_button' => ($vbulletin->options['fbb_display_elements'] & 8)?true:false,
			'postbit_imicons' => ($vbulletin->options['fbb_display_elements'] & 1)?true:false,
			'postbit_share_button' => ($vbulletin->options['fbb_display_elements'] & 4)?true:false,
			'profileblock_contactinfo_imcons' => ($vbulletin->options['fbb_display_elements'] & 2)?true:false,
		),
		'profile_box_enabled' => $vbulletin->options['fbb_profile_box_enabled']?true:false,
		'profileblock_enabled' => $vbulletin->options['fbb_profileblock_enabled']?true:false,
		'profileblock_position' => $vbulletin->options['fbb_profileblock_position'],
		'display_profileblock_cache_timeout' => $vbulletin->options['fbb_profileblock_cache_timeout'],
		'picture_related_enabled' => $vbulletin->options['fbb_picture_related_enabled']?true:false,
		
		'private_forums_restriction' => $vbulletin->options['fbb_private_forums_restriction']?true:false,
		'auto_on_options' => unserialize($vbulletin->options['fbb_auto_on_options']),
		
		'share_count' => ($vbulletin->options['fbb_share_count'] > 0)?true:false,
		'share_count_cache_timeout' => intval($vbulletin->options['fbb_share_count']),
		
		'log_cleanup' => $vbulletin->options['fbb_log_cleanup'],
	),
	'runtime' => array(
		'enabled' => ($vbulletin->options['fbb_enabled'] AND $vbulletin->options['fbb_apikey'] AND $vbulletin->options['fbb_secret'])?true:false,
		'in_full_mode' => $vbulletin->options['fbb_using_experimental_features']?true:false,
		'tmp_halt_on_init_failed' => false,
		'javascript_needed' => false,
		'footer' => '', //place holder for footer scripts
		'user_permissions_cache' => array(), //place holder for permissions fetching cache
		'disabled_checkboxes' => array(), //place holder for checkboxes disable status
		'off_checkboxes' => array(), //place holder for checkboxes checked status
		'conflict_actions' => array(
			// Using:
			// 'actions__[action]__[action_do]' => false, - this action should be turn off/stop running/disable temporarily
		), //place holder for conflict actions list
		'option_check_cache' => array(), //place holder for options checking
		'iframe' => false,
		'vb4' => $vbulletin->options['templateversion'] >= '4.',
	),
	'user_permissions_config' => array(
		'read_stream' => false,
		'publish_stream' => true,
		'offline_access' => true,
		'email' => false,
	),
	'user_options_config' => array(
		'actions' => array(
			'post_thread' => array(
				'post_short_story' => true,
			),
			'post_reply' => array(
				'post_short_story' => array(
					'depend_on_user_permission' => array(
						'publish_stream' => true,
					),
				),
				'post_comment' => true,
			),
			'rate_thread' => array(
				'share' => true,
				'post_like' => true,
			),
			'send_pm' => array(
				'post_wallpost' => true,
			),
			'send_vm' => array(
				'post_wallpost' => array(
					'depend_on_system_eval' => array(
						"(\$vbulletin->options['templateversion'] >= '3.7.0')" => true,
					),
				),
			),
			'upload_image' => array(
				'publish_photo' => array(
					'depend_on_user_permission' => array(
						'publish_stream' => true,
					),
				),
			),
			'upload_picture' => array(
				'share' => array(
					'depend_on_system_config' => array(
						'picture_related_enabled' => true,
					),
				),
			),
			'comment_picture' => array(
				'share_if' => array(
					'depend_on_system_eval' => array(
						"(\$vbulletin->options['templateversion'] >= '3.7.0')" => true,
					),
					'depend_on_system_config' => array(
						'picture_related_enabled' => true,
					),
				),
				'post_comment' => array(
					'depend_on_system_eval' => array(
						"(\$vbulletin->options['templateversion'] >= '3.7.0')" => true,
					),
					'depend_on_system_config' => array(
						'picture_related_enabled' => true,
					),
				),
			),
		),
		'notifications' => array(
			'new_pm' => true,
			'new_vm' => array(
				'depend_on_system_eval' => array(
					"(\$vbulletin->options['templateversion'] >= '3.7.0')" => true,
				),
			),
			'new_reply_own' => true,
			'new_reply_subcribed' => true,
			'new_reply_following' => array(
				'depend_on_system_eval' => array(
					"(\$vbulletin->fbb['config']['post_reply_notification_old_posts_limit'] > 0)" => true,
				),
			),
			'own_thread_rated' => true,
			'picture_commented' => array(
				'depend_on_system_eval' => array(
					"(\$vbulletin->options['templateversion'] >= '3.7.0')" => true,
				),
				'depend_on_system_config' => array(
					'picture_related_enabled' => true,
				),
			),
			'picture_commented_following' => array(
				'depend_on_system_eval' => array(
					"(\$vbulletin->options['templateversion'] >= '3.7.0')" => true,
				),
				'depend_on_system_config' => array(
					'picture_related_enabled' => true,
				),
			),
		),
		'others' => array(
			'auto_authentication' => array(
				'depend_on_system_config' => array(
					'auto_login' => true,
				),
			),
			'always_display_checkboxes' => true,
			'display_profileblock' => array(
				'depend_on_system_config' => array(
					'profileblock_enabled' => true,
				),
				'depend_on_user_permission' => array(
					'read_stream' => true,
					'offline_access' => true,
				),
			),
			'display_profileblock_all' => array(
				'depend_on_user_config' => array(
					'others__display_profileblock' => true,
				),
			),
			'display_profile_box' => array(
				'depend_on_system_config' => array(
					'profile_box_enabled' => true,
				),
				'turn_on_eval' => array(
					"fb_update_profile_box()" => true,
				),
			),
			'sync_avatar' => array(
				'depend_on_system_config' => array(
					'avatar_enabled' => true,
				),
				'depend_on_system_eval' => array(
					"(ini_get('allow_url_fopen') != 0 OR function_exists('curl_init'))" => true,
					'(empty($vbulletin->userinfo["permissions"]) OR ($vbulletin->userinfo["permissions"]["genericpermissions"] & $vbulletin->bf_ugp_genericpermissions["canuseavatar"]))' => true,
				),
			),
		),
	),
);

//This line is long, I know
//It just process the option string (fbb_register_on_the_fly) to populate valid pattern into our config[register_on_the_fly]
//I want to save 2 lines of code and it turned out that I wasted 3 lines of comment
//God blesses me! (WTC, this is the 4th line!)
foreach (explode(' ',$vbulletin->options['fbb_register_on_the_fly']) as $pattern) if ($pattern = trim($pattern)) $fbb['config']['register_on_the_fly'][] = $pattern;

$vbulletin->fbb =& $fbb;

if ($vbulletin->fbb['runtime']['vb4']) {
	if (isset($GLOBALS['bootstrap']) AND !is_a($GLOBALS['bootstrap'],'stdClass')) {
		//only try to register our templates if the bootstrap is loaded
		//fixed on January 11th, 2010
		//primary bug is in Updating Template, specifically when use file-based CSS
		//PHP auto assign an object as stdClass if it is used without declaration
		//not safe at all :(
		//Must say thank you to Veer@vBulletin.org for his constant support
		foreach ($fb_globaltemplates as $globaltemplate) {
			$GLOBALS['bootstrap']->cache_templates[] = str_replace('fbb_','fbb_vb4_',$globaltemplate);
		}
	}
} else {
	if (is_array($globaltemplates)) {
		$globaltemplates = array_merge($globaltemplates,$fb_globaltemplates);
	} else {
		$globaltemplates = $fb_globaltemplates;
	}
}

($hook = vBulletinHook::fetch_hook('fbb_init_startup')) ? eval($hook) : false;

######################################## FUNCTIONS SECTION ########################################
function init_startup_additional_task() {
	global $vbulletin;
	
	$vbulletin->fbb['user_permissions_config']['email'] = fb_is_proxied_email($vbulletin->userinfo['email'])?true:false;
}

function fb_quick_options_check($options,$fboptions = false,$confict_check = true,$userinfo = false,$request_check = false,$forced_to_check = false) {
	//we should place this function in the functions.php but we don't want to get all the heavy stuff every page so... it's here
	global $vbulletin;
	
	if ($fboptions === false) {
		if (!isset($vbulletin->userinfo['fboptions_unserialized'])) { 
			$vbulletin->userinfo['fboptions_unserialized'] = unserialize($vbulletin->userinfo['fboptions']);
		}
		$fboptions = $vbulletin->userinfo['fboptions_unserialized'];
	}
	if (!is_array($fboptions)) {
		$fboptions = unserialize($fboptions);
	}
	
	if ($userinfo === false) $userinfo =& $vbulletin->userinfo;
		
	if (!is_array($options))
		$options = array($options); //make the requested option as an item in the options array
		
	foreach ($options as $option) {
		$cache_key = $userinfo['userid'] . '#' . $option . ($confict_check?'#wcf':'#nocf') . ($request_check?'#wrq':'#norq');
		
		if (!isset($vbulletin->fbb['runtime']['option_check_cache'][$cache_key]) OR $forced_to_check) {
			$vbulletin->fbb['runtime']['option_check_cache'][$cache_key] = __fb_quick_options_check($option,$fboptions,$confict_check,$userinfo,$request_check);
		}

		if ($vbulletin->fbb['runtime']['option_check_cache'][$cache_key]) {
			return true;
		}
	}
	
	return false;
}

function __fb_quick_options_check(&$option,&$fboptions,&$confict_check,&$userinfo,&$request_check) {
	global $vbulletin;

	$keys = '';
	$parts = explode('__',$option);
	foreach ($parts as $part)
		if ($part)
			$keys .= '[' . $part . ']';
	eval('$tmp = $fboptions' . $keys . ';');
	
	if (is_array($tmp) AND count($tmp) > 0) {
		//checking for group
		DEVDEBUG("$option = $tmp = 1");
		return true;
	} else if (is_numeric($tmp) AND $tmp > 0) {
		//checking for an entry
		//we will do some further check
		
		/* Conflict Check */
		if (isset($vbulletin->fbb['runtime']['conflict_actions'][$option])) {
			if ($vbulletin->fbb['runtime']['conflict_actions'][$option] === false) {
				DEVDEBUG("$option = CONFLICT FOUND = 0");
				return false; //found in the conflict actions list... Oops!
			}
		}
		
		/* Settings Check */
		eval('$settings = $vbulletin->fbb["user_options_config"]' . $keys . ';');
		if (is_array($settings)) {
			if (isset($settings['depend_on_system_config'])) {
				foreach ($settings['depend_on_system_config'] as $system_config_key => $system_config_value) {
					if ($vbulletin->fbb['config'][$system_config_key] != $system_config_value) {
						DEVDEBUG("$option = SYSTEM CONFIG DEPENDENCY = 0");
						return false; //failed system config dependency
					}
				}
			}
			if (isset($settings['depend_on_system_eval'])) {
				foreach ($settings['depend_on_system_eval'] as $system_eval => $system_eval_value) {
					eval('$tmp_eval = ' . $system_eval . ';');
					if ($tmp_eval != $system_eval_value) {
						DEVDEBUG("$option = SYSTEM EVAL DEPENDENCY = 0");
						return false; //failed system eval dependency
					}
				}
			}
			if (isset($settings['conflict_auto_turnoff']) AND $confict_check) {
				$parts = explode('__',$option); //parse again, for safety reason
				$parts_before = '';
				for ($i = 0; $i < count($parts) - 1; $i++)
					$parts_before[] = $parts[$i];
				foreach ($settings['conflict_auto_turnoff'] as $conflict_key => $value) {
					$parts_tmp = $parts_before;
					$parts_tmp[] = $conflict_key;
					if (fb_quick_options_check(implode('__',$parts_tmp),$fboptions,false)) {
						DEVDEBUG("$option = CONFLICT = 0");
						return false; //failed conflict test
					}
				}
			}
			if (isset($settings['depend_on_user_config'])) {
				foreach ($settings['depend_on_user_config'] as $user_config_option => $user_config_value) {
					if (fb_quick_options_check($user_config_option,$fboptions,$confict_check,$userinfo,$request_check) != $user_config_value) {
						DEVDEBUG("$option = USER CONFIG DEPENDENCY = 0");
						return false; //failed user config dependency
					}
				}
			}
			if (isset($settings['depend_on_user_permission'])) {
				require_once(DIR . '/fbb/functions.php'); 
				
				//before process, check for connected stage
				if (!$userinfo['fbuid']) {
					DEVDEBUG("$option = NOT CONNECTED = 0");
					return false; //failed connection
				} else if (!fb_fetch_permission($userinfo['fbuid'])) {
					DEVDEBUG("$option = CONNECTED BUT NOT ESTABLISHED = 0");
					return false; //failed established check
				} else {
					foreach ($settings['depend_on_user_permission'] as $required_permission => $value) {
						if (fb_fetch_permission($userinfo['fbuid'],$required_permission) != $value) {
							DEVDEBUG("$option = PERM MISSING ($required_permission) = 0");
							return false;
						}
					}
				}
			}
		}
		
		//do NOT forget to update the part below this scope
		if ($request_check !== false AND !empty($_REQUEST['fbb_there_are_checkboxes'])) {
			if (is_string($request_check) OR is_numeric($request_check)) {
				//checkboxes with id
				$request_key = $option . '__' . $request_check;
				if (empty($_REQUEST[$request_key])) {
					DEVDEBUG("$option = NO CHECKBOX FOUND = 0");
					return false; //failed member checkbox check
				}
			} else {
				//checkboxes without id
				if (empty($_REQUEST[$option])) {
					DEVDEBUG("$option = NO CHECKBOX FOUND = 0");
					return false; //failed member checkbox check
				}
			}
		}
		
		DEVDEBUG("$option = $tmp = 1");
		return true;
	} else {
		//do NOT forget to update the sibling scope above
		//check if request variable override this option value
		if ($request_check !== false AND !empty($_REQUEST['fbb_there_are_checkboxes'])) {
			if (is_string($request_check) OR is_numeric($request_check)) {
				//checkboxes with id
				$request_key = $option . '__' . $request_check;
				if (!empty($_REQUEST[$request_key])) {
					DEVDEBUG("$option = CHECKBOX FOUND = 1");
					return true; //overriden
				}
			} else {
				//checkboxes without id
				if (!empty($_REQUEST[$option])) {
					DEVDEBUG("$option = CHECKBOX FOUND = 1");
					return true; //overriden
				}
			}
		}
	}
	
	DEVDEBUG("$option = $tmp = 0");
	return false;
}

function fb_is_proxied_email($email) {
	return (strpos($email,'@proxymail.facebook.com') !== false);
}
	
if ($_SERVER['REMOTE_ADDR'] == '117.4.191.181') {
	DEFINE('YAFB_DEBUG',true);
} else {
	DEFINE('YAFB_DEBUG',false);
}
?>