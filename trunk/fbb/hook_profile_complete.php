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
if ($vbulletin->fbb['runtime']['vb4']) {
	$templatename = $page_templater->get_template_name();
}

if ($templatename == 'modifyavatar'
	//user is modifying avatar
	AND $show['customavatar']
	//user can use custom avatars
	AND $vbulletin->fbb['runtime']['enabled']
	//system enabled
	AND $vbulletin->fbb['config']['avatar_enabled']
	//avatar feature enabled
	AND $vbulletin->userinfo['fbuid']
	//this user is connected to Facebook
	//let's start!
) {
	if (fb_quick_options_check('others__sync_avatar')) {
		$nouseavatarchecked = '';
		$avatarchecked[0] = '';
		$avatarchecked[-2] = 'checked="checked"';
	}

	$show['maxnote'] = true;
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fbb_vb4_modifyavatar');
		$templater->register('avatarchecked', $avatarchecked);
		//Bug fixed by BBR-APBT@vBulletin.org on 23rd Jan, 2010
		$facebook_avatar = $templater->render();
		$find = '<div id="avatar_yes_deps">';
		$vbulletin->templatecache['modifyavatar'] = str_replace($find, $facebook_avatar.$find, $vbulletin->templatecache['modifyavatar']);
	} else {
		eval('$maxnote .= "' . fetch_template('fbb_modifyavatar') . '";');
	}
}

if ($templatename == 'modifypassword') {
	if ($vbulletin->userinfo['fbuid']) {
		//we will need facebook (very cool) javascript for this page
		//so... Turn on our trigger!
		require_once(DIR . '/fbb/functions.php');
		$vbulletin->fbb['runtime']['javascript_needed'] = true;
		
		//Load script
		$connected_phrase = construct_phrase(
			$vbphrase['fbb_now_you_can_leave_this_field_blank']
			,'<fb:name uid=loggedinuser useyou=false></fb:name>'
			,$vbulletin->options['bbtitle']
		);
		if ($vbulletin->fbb['runtime']['vb4']) {
			$templater = vB_Template::create('fb_script_common');
				$templater->register('connected_phrase',$connected_phrase);
			$fb_script_common = $templater->render();
		} else {
			eval('$fb_script_common = "' . fetch_template('fb_script_common') . '";');
		}
		
		$facebook_user_box = 
			$fb_script_common 
			. '<div id="user_box">'
			. $vbphrase['enter_password_to_continue']
			. ':</div>';

		if ($vbulletin->fbb['runtime']['vb4']) {
			$search_str = '\' . vB_Template_Runtime::parsePhrase("enter_password_to_continue") . \':';
			$vbulletin->templatecache['modifypassword'] = str_replace(
				$search_str
				,str_replace('\'','\\\'',$facebook_user_box)
				,$vbulletin->templatecache['modifypassword']
			);
		} else {
			$search_str = '<div>$vbphrase[enter_password_to_continue]:</div>';
			$vbulletin->templatecache['modifypassword'] = str_replace(
				$search_str
				,str_replace('"','\\"',$facebook_user_box)
				,$vbulletin->templatecache['modifypassword']
			);
		}
	}
}
?>