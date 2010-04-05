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
init_startup_additional_task();
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
			// Provide additional check for registering page
			THIS_SCRIPT == 'register'
			AND $_REQUEST['do'] == 'signup'
			AND isset($_REQUEST['fbb'])
		)
		OR 
		(
			// With our pages, try our best!!!
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
		} else {
			if ($vbulletin->fbb['config']['auto_login'] AND !empty($vbulletin->fbb['config']['register_on_the_fly'])) {
				//REGISTER ON THE FLY - AWESOME FEATURE, ADDED IN 3.3
				//pardon me, I feel so high today, don't know why!!!!
				$fields = array(
					'uid',
					'first_name',
					'last_name',
					'name',
					'pic_big',
					'timezone',
					'birthday_date',
					'sex',
					'proxied_email',
					'profile_url',
					'username',
					'website',
				);
				$fbuserinfo = fb_fetch_userinfo($fbuid,$fields);
				$map = array(
					'[username]' => $fbuserinfo['username'],
					'[uid]' => $fbuserinfo['uid'],
					'[fname]' => $fbuserinfo['first_name'],
					'[lname]' => $fbuserinfo['last_name'],
					'[name]' => $fbuserinfo['name']
				);

				$good_usernames = array();
				foreach ($vbulletin->fbb['config']['register_on_the_fly'] as $pattern) {
					$username = $pattern;
					foreach ($map as $fbkey => $replacement) {
						if (strpos($username,$fbkey) !== false) {
							if (empty($replacement)) {
								$username = ''; //one key failed, don't use this pattern
							} else {
								$username = str_replace($fbkey,$replacement,$username);
							}
						}
					}
					if (!empty($username)) $good_usernames[strtolower($username)] = $vbulletin->db->escape_string($username);
				}
				if (!empty($good_usernames)) {
					//we have a few good usernames here, check to see if any of them is available to register
					$registereds = $vbulletin->db->query_read("
						SELECT username
						FROM `" . TABLE_PREFIX . "user`
						WHERE username IN ('" . implode("','",$good_usernames) . "')
					");
					while ($registered = $vbulletin->db->fetch_array($registereds)) {
						unset($good_usernames[strtolower($registered['username'])]);
					}
				}
				if (!empty($good_usernames)) {
					//huray! After the check, we still have good username(s) left. Go register the first one!
					//most of the lines are copied over from hook_register_start.php
					$lucky_username = array_shift($good_usernames);
					$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_ARRAY);
					$userdata->set('fbuid', $fbuid);
					$userdata->set('username', $lucky_username);
					$userdata->set('password', $fbuserinfo['uid'] . '@facebook');
					$userdata->set('email', $fbuserinfo['proxied_email']);
					if ($vbulletin->options['moderatenewmembers']) {
						$newusergroupid = 4;
					} else {
						$newusergroupid = $vbulletin->fbb['config']['auto_register_usergroupid']?$vbulletin->fbb['config']['auto_register_usergroupid']:2;
					}
					// set usergroupid
					$userdata->set('usergroupid', $newusergroupid);
					
					// set user title
					$userdata->set_usertitle('', false, $vbulletin->usergroupcache["$newusergroupid"], false, false);
					
					// set profile fields
					$userfields_stupid_reference_for_nothing = array();
					$customfields = $userdata->set_userfields($userfields_stupid_reference_for_nothing, true, 'auto_register');
					
					// set birthday
					$userdata->set('showbirthday', 2);
					$birthday = explode('/',$fbuserinfo['birthday_date']);
					if (count($birthday) == 3) {
						$userdata->set('birthday', array(
							'day'   => $birthday[1],
							'month' => $birthday[0],
							'year'  => $birthday[2]
						));
					} else {
						$userdata->set('birthday', array(
							'day'   => 1,
							'month' => 1,
							'year'  => 1970
						));
					}
					
					//referrer
					if ($vbulletin->GPC[COOKIE_PREFIX . 'referrerid']) {
						// in case user visited a referrerid URI
						if ($referrername = $db->query_first_slave("SELECT username FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC[COOKIE_PREFIX . 'referrerid'])) {
							// fortunately we found the username
							// set referrer now
							$userdata->set('referrerid', $referrername['username']);
						}
					}
					
					//set homepage
					if ($fbuserinfo['profile_url']) {
						$userdata->set('homepage', $fbuserinfo['profile_url']);
					}
					
					// set time options
					$userdata->set('timezoneoffset', $fbuserinfo['timezone']);
					
					// register IP address
					$userdata->set('ipaddress', IPADDRESS);
					
					if (empty($userdata->errors)) {
						// set fboptions
						
						$fboptions = fb_get_default_options_set();
						$userdata->set('fboptions',serialize($fboptions));
					}
					
					/* Conflict!!! */
					/* No Spam! */ $vbulletin->options['nospam_onoff'] = false;
					/* vMail */ $vbulletin->options['nlp_vmail_active'] = false;
					/* Intro On Register */ $vbulletin->options['app_min'] = 0;
					
					($hook = vBulletinHook::fetch_hook('register_addmember_process')) ? eval($hook) : false;

					$userdata->pre_save();
					
					if (empty($userdata->errors)) {
						// save the data
						$vbulletin->userinfo['userid']
							= $userid
							= $userdata->save();
							
						if ($userid) {
							$userinfo = fetch_userinfo($userid);
							$userdata_rank =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
							$userdata_rank->set_existing($userinfo);
							$userdata_rank->set('posts', 0);
							$userdata_rank->save();
							
							fb_connect_orphan_posts($userinfo,$userinfo['fbuid']);

							// force a new session to prevent potential issues with guests from the same IP, see bug #2459
							require_once(DIR . '/includes/functions_login.php');
							$vbulletin->session->created = false;
							process_new_login('', false, '');

							// send new user email
							if ($vbulletin->options['newuseremail'] != '')
							{
								$username = $vbulletin->GPC['username'];
								$email = $vbulletin->GPC['email'];

								if ($birthday = $userdata->fetch_field('birthday'))
								{
									$bday = explode('-', $birthday);
									$year = vbdate('Y', TIMENOW, false, false);
									$month = vbdate('n', TIMENOW, false, false);
									$day = vbdate('j', TIMENOW, false, false);
									if ($year > $bday[2] AND $bday[2] > 1901 AND $bday[2] != '0000')
									{
										require_once(DIR . '/includes/functions_misc.php');
										$vbulletin->options['calformat1'] = mktimefix($vbulletin->options['calformat1'], $bday[2]);
										if ($bday[2] >= 1970)
										{
											$yearpass = $bday[2];
										}
										else
										{
											// day of the week patterns repeat every 28 years, so
											// find the first year >= 1970 that has this pattern
											$yearpass = $bday[2] + 28 * ceil((1970 - $bday[2]) / 28);
										}
										$birthday = vbdate($vbulletin->options['calformat1'], mktime(0, 0, 0, $bday[0], $bday[1], $yearpass), false, true, false);
									}
									else
									{
										// lets send a valid year as some PHP3 don't like year to be 0
										$birthday = vbdate($vbulletin->options['calformat2'], mktime(0, 0, 0, $bday[0], $bday[1], 1992), false, true, false);
									}

									if ($birthday == '')
									{
										// Should not happen; fallback for win32 bug regarding mktime and dates < 1970
										if ($bday[2] == '0000')
										{
											$birthday = "$bday[0]-$bday[1]";
										}
										else
										{
											$birthday = "$bday[0]-$bday[1]-$bday[2]";
										}
									}
								}

								if ($userdata->fetch_field('referrerid') AND $vbulletin->GPC['referrername'])
								{
									$referrer = unhtmlspecialchars($vbulletin->GPC['referrername']);
								}
								else
								{
									$referrer = $vbphrase['n_a'];
								}
								$ipaddress = IPADDRESS;

								eval(fetch_email_phrases('newuser', 0));

								$newemails = explode(' ', $vbulletin->options['newuseremail']);
								foreach ($newemails AS $toemail)
								{
									if (trim($toemail))
									{
										vbmail($toemail, $subject, $message);
									}
								}
							}

							$username = htmlspecialchars_uni($vbulletin->GPC['username']);
							$email = htmlspecialchars_uni($vbulletin->GPC['email']);

							if ($newusergroupid == 2 OR $newusergroupid == $vbulletin->fbb['config']['auto_register_usergroupid']) {
								if ($vbulletin->options['welcomemail'])
								{
									eval(fetch_email_phrases('welcomemail'));
									vbmail($email, $subject, $message);
								}
							}

							($hook = vBulletinHook::fetch_hook('register_addmember_complete')) ? eval($hook) : false;

							$vbuser = $vbulletin->userinfo;
						}
					} else {
						//debugging stuff
						//var_dump($userdata->errors);exit;
					}
				}
			}
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
	}
	
	if ($vbuser['userid'] > 0 AND $vbulletin->userinfo['userid'] != $vbuser['userid']) {
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
		if (strpos($vbulletin->url,'register') !== false
			OR strpos($vbulletin->url,'login') !== false
			OR strpos($vbulletin->url,'facebook') !== false
		) {
			$vbulletin->url = $vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome'] . '.php';
		}
		
		//if we redirect right now, the page can not be render properly
		//so... leave the easy (and honor) job for our script at global_setup_complete hook
		$vbulletin->fbb['runtime']['fbb_redirect_login'] = true; 
	}
	
	if (THIS_SCRIPT == 'ajax') {
		// step 4 (part 2, finish it!)
		$xml->print_xml(true);	
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
	// What we are trying to do here is:
	
	// AUTO LOGIN USING FACEBOOK SESSION
	
	// Process flow:
		// 1. Guest user detected
		// 2. Turn on the Facebook javascript code to track him/her, display Connect button
			// 2.1. Not a Facebook Connected user, process to 3
			// 2.2. Is a Facebook Connected user, process to 4
		// 3. [from 2.1] <do nothing>
		// 4. [from 2.2] Javascript code check if the Facebook Account is connected to a vBulletin Account
			// 4.1. Not connected to any vBulletin Account, display a Login button, process to 5
			// 4.2. Connected to a vBulletin Account, process to 6
		// 5. [from 4.1] 
			// 5.1. Trigger the variable to display the Login button in a appropriate place
			// 5.2. Use a cookie flag to prevent future check in 4
			// 5.3. Finish
		// 6. [from 4.2] Javascript code redirect browser to special login processing page, finish
	
	$vbulletin->input->clean_array_gpc('c', array(
		$vbulletin->fbb['config']['cookie_last_checked_name'] => TYPE_UINT,
	));
	$last_checked = $vbulletin->GPC[$vbulletin->fbb['config']['cookie_last_checked_name']];
	
	$bypass_cookie_last_checked = false;
	
	if (
		// Provide additional check for registering page
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
									
									//window.location = '{$vbulletin->options['bburl']}/login.php?do=login&fbb';
									window.location.reload();
									
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
	$vbulletin->fbb['runtime']['navbar_button_needed'] = true;
	if ($vbulletin->fbb['config']['activated_system_wide']) {
		$vbulletin->fbb['runtime']['javascript_needed'] = true;
	}
}

if ($vbulletin->fbb['runtime']['navbar_button_needed'] AND !$vbulletin->fbb['runtime']['vb4']) {
	eval('$template_hook["navbar_buttons_right"] .= "' . fetch_template('fbb_navbar_button') . '";');
}
?>