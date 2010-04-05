<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.4
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 01:32 Apr 01st, 2010
|| #################################################################### ||
\*======================================================================*/
if (VB_PRODUCT == 'vbcms') {
	//different environment...
	//we need the global variables
	//so we declare them here
	global $vbulletin;
}

if (($vbulletin->fbb['runtime']['enabled'] OR $vbulletin->debug)
	AND strpos($_SERVER['HTTP_USER_AGENT'],'facebook.com') !== false
) {
	//help the publisher in Facebook fetch the appropriate data
	//the request is coming from Facebook's servers
	//we will see what is requested...
	$vbulletin->fbb['runtime']['isFacebookRequest'] = true;
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->fbb['config']['auto_login']
	AND
	(
		(
			THIS_SCRIPT == 'ajax'
			AND $_REQUEST['do'] == 'fbb_fetch_userinfo'
		)
		OR 
		(
			THIS_SCRIPT == 'login'
			AND $_REQUEST['do'] == 'login'
			AND isset($_REQUEST['fbb'])
		)
		OR 
		(
			/* Provide additional check for registering page */
			THIS_SCRIPT == 'register'
			AND $_REQUEST['do'] == 'signup'
			AND isset($_REQUEST['fbb'])
		)
		OR 
		(
			/* With our pages, try our best!!! */
			THIS_SCRIPT == 'facebook'
			AND !$vbulletin->userinfo['userid']
		)
	)
) 
{
	require_once(DIR . '/fbb/functions.php');
	
	$vbuser = array('userid' => -1);
	$fbuid = fb_users_getLoggedInUser();
	if (!fb_isErrorCode($fbuid)) {
		$user = $vbulletin->db->query_first("
			SELECT userid, usergroupid, membergroupids, infractiongroupids, username, password, salt, fbuid, fboptions
			FROM `" . TABLE_PREFIX . "user`
			WHERE fbuid = '$fbuid'
		");
		if ($user['userid'] > 0
			AND 
			(
				fb_quick_options_check('others__auto_authentication',$user['fboptions']) //on ajax request, return positive if user enable option
				//with these scripts, return positive asap
				OR THIS_SCRIPT == 'login'
				OR THIS_SCRIPT == 'register'
				OR THIS_SCRIPT == 'facebook'
				
			)
		) {
			$vbuser = $user;
		}
	}
	
	if (THIS_SCRIPT == 'ajax') {
		//step 4
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('userinfo');
		
		foreach (array('userid','username') as $key)
			$xml->add_tag($key,$vbuser[$key]);
		
		$xml->close_group();
		$xml->print_xml(true);
	} else if ($vbuser['userid'] > 0) {
		//final step
		require_once(DIR . '/includes/functions_login.php');
		
		//store phrases
		$vbphrase_store = $vbphrase;
		unset($vbphrase);
		
		//get userinfo
		$vbulletin->userinfo = $vbulletin->db->query_first("
			SELECT userid, usergroupid, membergroupids, infractiongroupids, username, password, salt 
			FROM " . TABLE_PREFIX . "user 
			WHERE userid = $vbuser[userid]
		");
		//set cookies
		vbsetcookie('userid', $vbulletin->userinfo['userid'], true, true, true);
		vbsetcookie('password', md5($vbulletin->userinfo['password'] . COOKIE_SALT), true, true, true);
		//process session
		$vbulletin->session->created = false;
		process_new_login('', true, '');
		
		//reset phrases
		$vbphrase = $vbphrase_store;
		unset($vbphrase_store);
		
		$vbulletin->url = $_SERVER["HTTP_REFERER"];
		if (strpos($vbulletin->url,'register.php') !== false
			OR strpos($vbulletin->url,'login.php') !== false
			OR strpos($vbulletin->url,'facebook.php') !== false
		) {
			$vbulletin->url = $vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome'] . '.php';
		}
		
		//if we redirect right now, the page can not be render properly
		//so... leave the easy (and honor) job for our script at global_setup_complete hook
		$vbulletin->fbb['runtime']['fbb_redirect_login'] = true; 
	}
	
	if (!$vbulletin->fbb['runtime']['fbb_redirect_login']
		AND THIS_SCRIPT != 'register'
		AND THIS_SCRIPT != 'facebook') {
		//find a way for member to join our forum
		//redirect to register page is a choice!
		$vbulletin->url = $vbulletin->options['bburl'] . '/register.php?do=signup&fbb';
		$vbulletin->fbb['runtime']['fbb_redirect_login'] = 'fbb_redirect_register_now'; 
	}
}

if ($vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->fbb['config']['auto_login']
	AND THIS_SCRIPT == 'login'
	AND $_REQUEST['do'] == 'logout'
) {
	//I don't want to write this part but I have to
	//when user logout of vBulletin, they should be logout of Facebook also, to prevent auto-login later...
	if ($vbulletin->userinfo['fbuid']
		AND fb_quick_options_check('others__auto_authentication')) {
		require_once(DIR . '/fbb/functions.php');
		
		fb_embed_javascript("
			FB.ensureInit(
				function() {
					FB.Connect.logout();
				}
			);
			
			delete_cookie('{$vbulletin->fbb['config']['cookie_last_checked_name']}');  //options changed or something
		");
	}
} else if ($vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->fbb['config']['auto_login']) {	
	/*
		What we are trying to do here is:
		
		AUTO LOGIN USING FACEBOOK SESSION
		
		Process flow:
			1. Guest user detected
			2. Turn on the Facebook javascript code to track him/her, display Connect button
				2.1. Not a Facebook Connected user, process to 3
				2.2. Is a Facebook Connected user, process to 4
			3. [from 2.1] <do nothing>
			4. [from 2.2] Javascript code check if the Facebook Account is connected to a vBulletin Account
				4.1. Not connected to any vBulletin Account, display a Login button, process to 5
				4.2. Connected to a vBulletin Account, process to 6
			5. [from 4.1] 
				5.1. Trigger the variable to display the Login button in a appropriate place
				5.2. Use a cookie flag to prevent future check in 4
				5.3. Finish
			6. [from 4.2] Javascript code redirect browser to special login processing page, finish
	*/
	
	$vbulletin->input->clean_array_gpc('c', array(
		$vbulletin->fbb['config']['cookie_last_checked_name'] => TYPE_UINT,
	));
	$last_checked = $vbulletin->GPC[$vbulletin->fbb['config']['cookie_last_checked_name']];
	
	$bypass_cookie_last_checked = false;
	
	if (
		/* Provide additional check for registering page */
			THIS_SCRIPT == 'register'
			AND $_REQUEST['do'] == 'signup'
			AND isset($_REQUEST['fbb'])
	) {
		$bypass_cookie_last_checked = true;
		
		fb_embed_javascript("
			//bypass cookie check
			delete_cookie('{$vbulletin->fbb['config']['cookie_last_checked_name']}');
		");
	}

	if (!$vbulletin->userinfo['userid'] 
		AND 
			(
				TIMENOW - $last_checked > $vbulletin->fbb['config']['cookie_last_checked_timeout']
				OR $bypass_cookie_last_checked
			)
	) {
		require_once(DIR . '/fbb/functions.php');
		
		fb_embed_javascript("
			var current_unix_timestamp = " . TIMENOW . ";
			var last_unix_timestamp = fetch_cookie('{$vbulletin->fbb['config']['cookie_last_checked_name']}'); //using vBulletin's function

			if (last_unix_timestamp == null || current_unix_timestamp - last_unix_timestamp > {$vbulletin->fbb['config']['cookie_last_checked_timeout']})
			{
				//do the check every 6 hours 
				//actually we do not need to check cookie in javascript because PHP did it before but... who knows?
				//I want to make sure about the performance of the system ;)
				
				YAHOO.util.Connect.asyncRequest(
					'POST',
					'{$vbulletin->options['bburl']}/ajax.php?do=fbb_fetch_userinfo',
					{
						success: function(o) {
							if(o.responseXML){
								var a = o.responseXML.getElementsByTagName('userid');
								var userid =  a[0].firstChild.nodeValue;
								" . iif($vbulletin->debug,"alert('Hi friend, your (auto recognized) userid = ' + userid);","") . "
								if (userid != '-1' && userid != '0' && userid > 0) {
									//if someone wants to play around, oh, it's me who always play around this thing! Haha
									//use vBulletin's function again
									delete_cookie('{$vbulletin->fbb['config']['cookie_last_checked_name']}'); 
									
									window.location = '{$vbulletin->options['bburl']}/login.php?do=login&fbb';
									
									return;
								}
							}
							
							//prevent future check by setting a flag in browser's cookie
							//use vBulletin's function
							var expDate=new Date();
							expDate.setDate(expDate.getDate()+14);
							set_cookie('{$vbulletin->fbb['config']['cookie_last_checked_name']}', current_unix_timestamp, expDate);
						}
						,failure: function(o) {
							//do nothing
						}
					}
					,SESSIONURL+'ajax=1&'
				);
			}
		");
	}
}

if ($vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->fbb['config']['display_elements']['navbar_connect_button']
	AND ($vbulletin->fbb['config']['auto_register'] OR $vbulletin->fbb['config']['auto_login'])
	AND !$vbulletin->userinfo['userid']) {
	if ($vbulletin->fbb['runtime']['vb4']) {
		if (!defined('VB_PRODUCT')) {
			//Added our template immediately inside Forums
			$template_hook['navbar_end'] .= vB_Template::create('fbb_vb4_navbar_button')->render();
		} else {
			//Walk through, add it later with others
			$vbulletin->fbb['runtime']['navbar_button_needed'] = true;
		}
	} else {
		eval('$template_hook["navbar_buttons_right"] .= "' . fetch_template('fbb_navbar_button') . '";');
	}
}

if ($vbulletin->fbb['runtime']['enabled']) {
	init_startup_additional_task();
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fb_footer');
		$vbulletin->fbb['runtime']['footer'] = $templater->render();
	} else {
		eval('$vbulletin->fbb["runtime"]["footer"] = "' . fetch_template('fb_footer') . '";');
	}
}
?>