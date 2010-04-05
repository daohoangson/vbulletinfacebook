<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 2.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 01:03 Oct 20th, 2009
|| # HP is so in love with ASUS!!!
|| #################################################################### ||
\*======================================================================*/
if ($vbulletin->fbb['runtime']['enabled']
	//system enabled
	AND $vbulletin->fbb['config']['avatar_enabled']
	//avatar feature enabled
	AND $vbulletin->userinfo['fbuid']
	//this user is connected to Facebook
	//let's start!
) {
	$fboptions = unserialize($vbulletin->userinfo['fboptions']);
	$new_value = null;
	
	if (fb_quick_options_check('others__sync_avatar',$fboptions)) {
		//currently enabled
		if ($vbulletin->GPC['avatarid'] != -2) {
			//but not anymore
			$new_value = false;
		}
	} else {
		//currently disabled
		if ($vbulletin->GPC['avatarid'] == -2) {
			//but now, enabled!
			$new_value = true;
		}
	}
	
	if ($new_value !== null) {
		require_once(DIR . '/fbb/functions.php');
	
		$scopes = array();
		if ($new_value) {
			fb_turn_on_option('others__sync_avatar',$fboptions,$scopes);
		} else {
			fb_turn_off_option('others__sync_avatar',$fboptions,$scopes);
		}
		
		$vbulletin->userinfo['fboptions'] = serialize($fboptions);
		$vbulletin->fbb['runtime']['fboptions_changed'] = true;
	}

	if (fb_quick_options_check('others__sync_avatar',false,true,false,false,true)) {
		require_once(DIR . '/fbb/functions.php');
	
		$vbulletin->GPC['avatarid'] = 0;
		$vbulletin->GPC['avatarurl'] = '';
		$avatar_source = $vbulletin->fbb['config']['sync_avatar_source'];
		
		$fbuserinfo = fb_fetch_userinfo($vbulletin->userinfo['fbuid'],array($avatar_source));
		if ($fbuserinfo[$avatar_source]) {
			if (
				$fbuserinfo[$avatar_source] != $fboptions['sync_avatar_last_url']
				OR THIS_SCRIPT == 'profile' //if the requested script is profile, reload anyway
			) {
				DEVDEBUG("AVATAR SOURCE ($avatar_source) = " . $fbuserinfo[$avatar_source]);
				$vbulletin->GPC['avatarurl'] = $fbuserinfo[$avatar_source];
				
				$fboptions['sync_avatar_timeline'] = TIMENOW;
				$fboptions['sync_avatar_last_url'] = $fbuserinfo[$avatar_source];
				$vbulletin->userinfo['fboptions'] = serialize($fboptions);
				$vbulletin->fbb['runtime']['fboptions_changed'] = true;
			} else {
				DEVDEBUG("AVATAR SOURCE UNCHANGED");
				
				$fboptions['sync_avatar_timeline'] = TIMENOW;
				$vbulletin->userinfo['fboptions'] = serialize($fboptions);
				$vbulletin->fbb['runtime']['fboptions_changed'] = true;
			}
		} else {
			DEVDEBUG("AVATAR SOURCE ($avatar_source) = NOT FOUND");
			$errors_found = true; //for use in our simulator function
			if (THIS_SCRIPT == 'profile')
				eval(standard_error(fetch_error('fbb_unable_to_access_facebook_avatar')));
		}
	} else {
		if ($vbulletin->GPC['avatarid'] == -2) {
			$errors_found = true; //for use in our simulator function
			if (THIS_SCRIPT == 'profile')
				eval(standard_error(fetch_error('fbb_sync_avatar_unavailable')));
		}
	}
}
?>