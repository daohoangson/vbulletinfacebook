<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.3
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 19:54 Mar 29th, 2010
|| #################################################################### ||
\*======================================================================*/
if (
	$vbulletin->fbb['runtime']['enabled']
	AND 
		(
			$vbulletin->fbb['config']['auto_register']
			/* OR $vbulletin->fbb['config']['auto_login'] - removed to prevent security breach */
		)
	AND $_REQUEST['do'] == 'signup'
	AND isset($_REQUEST['fbb'])
) {	
	require_once(DIR . '/fbb/functions.php');
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we need them now!
	
	// BOF - Copied from register.php 
	if (!$vbulletin->options['allowregistration'])
	{
		eval(standard_error(fetch_error('noregister')));
	}

	// check for multireg
	if ($vbulletin->userinfo['userid'] AND !$vbulletin->options['allowmultiregs'])
	{
		eval(standard_error(fetch_error('alreadyregistered', $vbulletin->userinfo['username'], $vbulletin->session->vars['sessionurl'])));
	}
	// EOF - Copied from register.php 
	
	$vbulletin->input->clean_array_gpc('p', array(
		'username'            => TYPE_STR,
		'userfield'            => TYPE_ARRAY,
		'email'               => TYPE_STR,
		'emailconfirm'        => TYPE_STR,
		'password'            => TYPE_STR,
		'passwordconfirm'     => TYPE_STR,
	));
	
	if (!empty($vbulletin->GPC['username'])) {
		$fbuid = fb_users_getLoggedInUser();
		if (!fb_isErrorCode($fbuid)) {
		
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
			
			// init user datamanager class
			$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_ARRAY);
			
			// set fbuid
			if ($other_user = $vbulletin->db->query_first("
				SELECT userid
				FROM `" . TABLE_PREFIX . "user`
				WHERE fbuid = $fbuid
			")) {
				$userdata->error('fbb_connected_to_other_account',$vbulletin->options['bbtitle']);
			} else {
				$userdata->set('fbuid', $fbuid);
			}
			
			$userdata->set('username', $vbulletin->GPC['username']);
			
			// set password
			if ($vbulletin->GPC['password'] OR $vbulletin->GPC['passwordconfirm']) {
				// guest entered passwords, proceed with standard procedure
				// check for matching passwords
				if ($vbulletin->GPC['password'] != $vbulletin->GPC['passwordconfirm'])
				{
					$userdata->error('passwordmismatch');
				}
				$userdata->set('password', $vbulletin->GPC['password']);
			} else {
				$userdata->set('password', $fbuserinfo['uid'] . '@facebook');
			}
			
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
			$customfields = $userdata->set_userfields($vbulletin->GPC['userfield'], true, 'register');
			
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
			DEVDEBUG("Birthday = $birthday[1]-$birthday[0]-$birthday[2] ($fbuserinfo[birthday_date])");
			
			//set email
			if ($vbulletin->GPC['email'] OR $vbulletin->GPC['emailconfirm']) {
				//guest specified some email addresses
				//use standard procedure from register.php
				//...
			} else {
				//no email found
				//use facebook email address (if admin allows it)
				if ($vbulletin->fbb['config']['accept_proxied_email']) {
					$email = $fbuserinfo['proxied_email']; //apps+$$$$.$$$$.****@proxymail.facebook.com
					
					// Synchronize for other plugins/products
					$vbulletin->GPC['email'] = $email;
					$vbulletin->GPC['emailconfirm'] = $email;
				}
			}
			if ($vbulletin->GPC['email'] != $vbulletin->GPC['emailconfirm'])
			{
				if ($vbulletin->fbb['config']['accept_proxied_email']) {
					$userdata->error('fbb_emailmismatch');
				} else {
					$userdata->error('emailmismatch');
				}
			}
			$userdata->set('email', $vbulletin->GPC['email']);
			
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
			
			($hook = vBulletinHook::fetch_hook('register_addmember_process')) ? eval($hook) : false;

			$userdata->pre_save();
			
			// check for errors
			if (!empty($userdata->errors)) {
				$errorlist = '';
				foreach ($userdata->errors AS $index => $error)
				{
					$errorlist .= "<li>$error</li>";
				}

				$username = htmlspecialchars_uni($vbulletin->GPC['username']);
				$show['errors'] = true;
			} else {
				$show['errors'] = false;

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

					$fbb_url = $vbulletin->options['bburl'] . '/profile.php?do=fbb&justadded=1';
					$vbulletin->url = $fbb_url;

					if ($vbulletin->options['moderatenewmembers'])
					{
						eval(standard_error(fetch_error('moderateuser', $username, $vbulletin->options['forumhome'], $vbulletin->session->vars['sessionurl_q']), '', false));
					}
					else
					{
						eval(print_standard_redirect(fetch_error('fbb_registration_complete',$username,$vbulletin->session->vars['sessionurl'],$vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome'] . '.php'), false, true));
						//eval(standard_error(fetch_error('registration_complete', $username, $vbulletin->session->vars['sessionurl'], $vbulletin->options['bburl'] . '/' . $vbulletin->options['forumhome'] . '.php'), '', false));
					}
				}
			}
		}
	}
	
	// required fields
	// copied from register.php
	$customfields_profile = '';
	$customfields_option = '';
	
	$profilefields = $vbulletin->db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "profilefield
		WHERE editable > 0 AND required <> 0 AND required <> 2
		ORDER BY displayorder
	");
	while ($profilefield = $vbulletin->db->fetch_array($profilefields))
	{
		$profilefieldname = "field$profilefield[profilefieldid]";
		$optionalname = $profilefieldname . '_opt';
		$optionalfield = '';
		$optional = '';
		$profilefield['title'] = $vbphrase[$profilefieldname . '_title'];
		$profilefield['description'] = $vbphrase[$profilefieldname . '_desc'];
		if (!$errorlist)
		{
			unset($vbulletin->userinfo["$profilefieldname"]);
		}
		elseif (isset($vbulletin->GPC['userfield']["$profilefieldname"]))
		{
			$vbulletin->userinfo["$profilefieldname"] = $vbulletin->GPC['userfield']["$profilefieldname"];
		}

		$custom_field_holder = '';

		if ($profilefield['type'] == 'input')
		{
			if ($profilefield['data'] !== '')
			{
				$vbulletin->userinfo["$profilefieldname"] = $profilefield['data'];
			}
			else
			{
				$vbulletin->userinfo["$profilefieldname"] = htmlspecialchars_uni($vbulletin->userinfo["$profilefieldname"]);
			}
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('userfield_textbox');
					$templater->register('profilefield', $profilefield);
					$templater->register('profilefieldname', $profilefieldname);
				$custom_field_holder = $templater->render();
			} else {
				eval('$custom_field_holder = "' . fetch_template('userfield_textbox') . '";');
			}
		}
		else if ($profilefield['type'] == 'textarea')
		{
			if ($profilefield['data'] !== '')
			{
				$vbulletin->userinfo["$profilefieldname"] = $profilefield['data'];
			}
			else
			{
				$vbulletin->userinfo["$profilefieldname"] = htmlspecialchars_uni($vbulletin->userinfo["$profilefieldname"]);
			}
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('userfield_textarea');
					$templater->register('profilefield', $profilefield);
					$templater->register('profilefieldname', $profilefieldname);
				$custom_field_holder = $templater->render();
			} else {
				eval('$custom_field_holder = "' . fetch_template('userfield_textarea') . '";');
			}
		}
		else if ($profilefield['type'] == 'select')
		{
			$data = unserialize($profilefield['data']);
			$selectbits = '';
			foreach ($data AS $key => $val)
			{
				$key++;
				$selected = '';
				if (isset($vbulletin->userinfo["$profilefieldname"]))
				{
					if (trim($val) == $vbulletin->userinfo["$profilefieldname"])
					{
						$selected = 'selected="selected"';
						$foundselect = 1;
					}
				}
				else if ($profilefield['def'] AND $key == 1)
				{
					$selected = 'selected="selected"';
					$foundselect = 1;
				}

				if ($vbulletin->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('userfield_select_option');
						$templater->register('key', $key);
						$templater->register('selected', $selected);
						$templater->register('val', $val);
					$selectbits .= $templater->render();
				} else {
					eval('$selectbits .= "' . fetch_template('userfield_select_option') . '";');
				}
			}

			if ($profilefield['optional'])
			{
				if (!$foundselect AND $vbulletin->userinfo["$profilefieldname"])
				{
					$optional = htmlspecialchars_uni($vbulletin->userinfo["$profilefieldname"]);
				}
				
				if ($vbulletin->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('userfield_optional_input');
						$templater->register('optional', $optional);
						$templater->register('optionalname', $optionalname);
						$templater->register('profilefield', $profilefield);
						$templater->register('tabindex', $tabindex);
					$optionalfield = $templater->render();
				} else {
					eval('$optionalfield = "' . fetch_template('userfield_optional_input') . '";');
				}
			}
			if (!$foundselect)
			{
				$selected = 'selected="selected"';
			}
			else
			{
				$selected = '';
			}
			$show['noemptyoption'] = iif($profilefield['def'] != 2, true, false);
			
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('userfield_select');
					$templater->register('optionalfield', $optionalfield);
					$templater->register('profilefield', $profilefield);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('selectbits', $selectbits);
					$templater->register('selected', $selected);
				$custom_field_holder = $templater->render();
			} else {
				eval('$custom_field_holder = "' . fetch_template('userfield_select') . '";');
			}
		}
		else if ($profilefield['type'] == 'radio')
		{
			$data = unserialize($profilefield['data']);
			$radiobits = '';
			$foundfield = 0;
			$perline = 0;
			$unclosedtr = true;

			foreach ($data AS $key => $val)
			{
				$key++;
				$checked = '';
				if (!$vbulletin->userinfo["$profilefieldname"] AND $key == 1 AND $profilefield['def'] == 1)
				{
					$checked = 'checked="checked"';
				}
				else if (trim($val) == $vbulletin->userinfo["$profilefieldname"])
				{
					$checked = 'checked="checked"';
					$foundfield = 1;
				}
				if ($perline == 0)
				{
					$radiobits .= '<tr>';
				}
				
				if ($vbulletin->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('userfield_radio_option');
						$templater->register('checked', $checked);
						$templater->register('key', $key);
						$templater->register('profilefieldname', $profilefieldname);
						$templater->register('val', $val);
					$radiobits .= $templater->render();
				} else {
					eval('$radiobits .= "' . fetch_template('userfield_radio_option') . '";');
				}
				
				$perline++;
				if ($profilefield['perline'] > 0 AND $perline >= $profilefield['perline'])
				{
					$radiobits .= '</tr>';
					$perline = 0;
					$unclosedtr = false;
				}
			}
			if ($unclosedtr)
			{
				$radiobits .= '</tr>';
			}
			if ($profilefield['optional'])
			{
				if (!$foundfield AND $vbulletin->userinfo["$profilefieldname"])
				{
					$optional = htmlspecialchars_uni($vbulletin->userinfo["$profilefieldname"]);
				}
				
				if ($vbulletin->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('userfield_optional_input');
						$templater->register('optional', $optional);
						$templater->register('optionalname', $optionalname);
						$templater->register('profilefield', $profilefield);
						$templater->register('tabindex', $tabindex);
					$optionalfield = $templater->render();
				} else {
					eval('$optionalfield = "' . fetch_template('userfield_optional_input') . '";');
				}
			}
			
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('userfield_radio');
					$templater->register('optionalfield', $optionalfield);
					$templater->register('profilefield', $profilefield);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('radiobits', $radiobits);
				$custom_field_holder = $templater->render();
			} else {
				eval('$custom_field_holder = "' . fetch_template('userfield_radio') . '";');
			}
		}
		else if ($profilefield['type'] == 'checkbox')
		{
			$data = unserialize($profilefield['data']);
			$radiobits = '';
			$perline = 0;
			$unclosedtr = true;
			foreach ($data AS $key => $val)
			{
				if ($vbulletin->userinfo["$profilefieldname"] & pow(2,$key))
				{
					$checked = 'checked="checked"';
				}
				else
				{
					$checked = '';
				}
				$key++;
				if ($perline == 0)
				{
					$radiobits .= '<tr>';
				}
				
				if ($vbulletin->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('userfield_checkbox_option');
						$templater->register('checked', $checked);
						$templater->register('key', $key);
						$templater->register('profilefieldname', $profilefieldname);
						$templater->register('val', $val);
					$radiobits .= $templater->render();
				} else {
					eval('$radiobits .= "' . fetch_template('userfield_checkbox_option') . '";');
				}
				
				$perline++;
				if ($profilefield['perline'] > 0 AND $perline >= $profilefield['perline'])
				{
					$radiobits .= '</tr>';
					$perline = 0;
					$unclosedtr = false;
				}
			}
			if ($unclosedtr)
			{
				$radiobits .= '</tr>';
			}
			
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('userfield_radio');
					$templater->register('optionalfield', $optionalfield);
					$templater->register('profilefield', $profilefield);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('radiobits', $radiobits);
				$custom_field_holder = $templater->render();
			} else {
				eval('$custom_field_holder = "' . fetch_template('userfield_radio') . '";');
			}
		}
		else if ($profilefield['type'] == 'select_multiple')
		{
			$data = unserialize($profilefield['data']);
			$selectbits = '';
			$selected = '';

			if ($profilefield['height'] == 0)
			{
				$profilefield['height'] = count($data);
			}

			foreach ($data AS $key => $val)
			{
				if ($vbulletin->userinfo["$profilefieldname"] & pow(2, $key))
				{
					$selected = 'selected="selected"';
				}
				else
				{
					$selected = '';
				}
				$key++;
				
				if ($vbulletin->fbb['runtime']['vb4']) {
					$templater = vB_Template::create('userfield_select_option');
						$templater->register('key', $key);
						$templater->register('selected', $selected);
						$templater->register('val', $val);
					$selectbits .= $templater->render();
				} else {
					eval('$selectbits .= "' . fetch_template('userfield_select_option') . '";');
				}
			}
			
			if ($vbulletin->fbb['runtime']['vb4']) {
				$templater = vB_Template::create('userfield_select_multiple');
					$templater->register('profilefield', $profilefield);
					$templater->register('profilefieldname', $profilefieldname);
					$templater->register('selectbits', $selectbits);
				$custom_field_holder = $templater->render();
			} else {
				eval('$custom_field_holder = "' . fetch_template('userfield_select_multiple') . '";');
			}
		}

		if ($profilefield['required'] == 2)
		{
			// this will never happen because we restricted the condition for database query
			// not required to be filled in but still show
			$profile_variable =& $customfields_other;
		}
		else // required to be filled in
		{
			if ($profilefield['form'])
			{
				$profile_variable =& $customfields_option;
			}
			else
			{
				$profile_variable =& $customfields_profile;
			}
		}
		
		//new in 4.0.2
		if ($vbulletin->options['templateversion'] >= '4.0.2') {
			$templater = vB_Template::create('userfield_wrapper');
				$templater->register('custom_field_holder', $custom_field_holder);
				$templater->register('profilefield', $profilefield);
			$profile_variable .= $templater->render();
		} else {
			$profile_variable .= $custom_field_holder;
		}
	}
	// eof - required field
	
	if (fb_is_proxied_email($email)) {
		$email = '';
	}
	$email_is_required = !$vbulletin->fbb['config']['accept_proxied_email'];
	
	//Load script
	$connected_phrase = construct_phrase(
		$vbphrase['fbb_thank_choose_an_username']
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
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$navbar = render_navbar_template(construct_navbits(
			array(
				'facebook.php' => $vbphrase['fbb'],
				'register.php?do=signup&fbb' => $vbphrase['register'],
			)
		));
	
		$templater = vB_Template::create('fbb_vb4_register');
			$templater->register_page_templates();
			$templater->register('navbar', $navbar);
			$templater->register('birthdayfields', $birthdayfields);
			$templater->register('checkedoff', $checkedoff);
			$templater->register('customfields_option', $customfields_option);
			$templater->register('customfields_other', $customfields_other);
			$templater->register('customfields_profile', $customfields_profile);
			$templater->register('day', $day);
			$templater->register('email', $email);
			$templater->register('emailconfirm', $emailconfirm);
			$templater->register('errorlist', $errorlist);
			$templater->register('human_verify', $human_verify);
			$templater->register('month', $month);
			$templater->register('parentemail', $parentemail);
			$templater->register('password', $password);
			$templater->register('passwordconfirm', $passwordconfirm);
			$templater->register('referrername', $referrername);
			$templater->register('timezoneoptions', $timezoneoptions);
			$templater->register('url', $url);
			$templater->register('username', $username);
			$templater->register('year', $year);
			$templater->register('fb_script_common', $fb_script_common);
		print_output($templater->render());
	} else {
		eval('print_output("' . fetch_template('fbb_register') . '");');
	}
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $_REQUEST['do'] == 'fbb_reclaim'
) {
	if ($vbulletin->userinfo['userid']) { //safe procedure, user logged in so he/she shouldn't see this page anymore. We don't want to generate the password again
		$vbulletin->url = $vbulletin->options['bburl'];
		eval(standard_error(fetch_error('fbb_reclamation_redirecting')));
	}

	require_once(DIR . '/fbb/functions.php');

	$vbulletin->input->clean_array_gpc('r', array(
		'u' => TYPE_STR,
		'h' => TYPE_STR,
	));
	
	$facebook =& theFacebook();
	if ($facebook->verify_account_reclamation($vbulletin->GPC['u'],$vbulletin->GPC['h'])) {
		$userinfo = $vbulletin->db->query_first("
			SELECT *
			FROM `" . TABLE_PREFIX . "user` 
			WHERE fbuid = {$vbulletin->GPC['u']}
		");
		
		if ($userinfo['userid']) {
			//user found
			$userdata =& datamanager_init('User', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing($userinfo);
			
			$new_password = '';
			//srand($vbulletin->GPC['u']); //each Facebook userid will generate exactly the same password everytime. Just a safe procedure... - REMOVED!
			for ($i = 0; $i < 10; $i++) {
				if (rand(0,5) > 2) {
					$new_password .= chr(rand(65,90)); // A-Z
				} else {
					$new_password .= chr(rand(48,57)); // 0-9
				}
			}
			
			$userdata->set('password',$new_password);
			
			$userdata->pre_save();
			
			if (empty($userdata->errors)) {
				//password changed
				$userdata->save();
				
				$fbuid = $vbulletin->GPC['u'];
				$ip_address = $_SERVER['REMOTE_ADDR'];
				eval(fetch_email_phrases('fbb_reclamation'));
				vbmail($userinfo['email'], $subject, $message);

				eval(standard_error(fetch_error('fbb_reclamation_succeed',$vbulletin->options['bbtitle'],$userinfo['username'],$new_password),'',false));
			} else {
				//password changed fail
				eval(standard_error(fetch_error('fbb_reclamation_fail_unknown',$vbulletin->options['bbtitle'])));
			}
		} else {
			//user not found
			eval(standard_error(fetch_error('fbb_reclamation_fail_user_not_found')));
		}
	} else {
		eval(standard_error(fetch_error('fbb_reclamation_fail_invalid')));
	}
}
?>