<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.0
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 22:42 Jan 07th, 2010
|| #################################################################### ||
\*======================================================================*/
if (
	$this->registry->fbb['runtime']['enabled']
	AND $this->template_name == 'memberinfo_block_contactinfo'
	AND $userinfo['fbuid']
	AND $this->registry->fbb['config']['display_elements']['profileblock_contactinfo_imcons']
) {
	if ($this->registry->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fb_memberinfo_imdetail');
			$templater->register('userinfo', $userinfo);
			$templater->register('prepared', $prepared);
		$fbb_imdetail = $templater->render();
	} else {
		eval('$fbb_imdetail = "' . fetch_template('fb_memberinfo_imdetail') . '";');
	}

	$block_data['imbits'] .= $fbb_imdetail;
	
	$prepared['hasimdetails'] = true;
}

if ($this->registry->fbb['runtime']['enabled']
	AND $this->template_name == 'memberinfo_block_visitormessaging'
	AND $this->registry->userinfo['fbuid']
	AND $this->block_data['messagearea']
) {
	$this->registry->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page!
	
	if ($this->profile->userinfo['userid'] == $this->registry->userinfo['userid']) {
		//auto turn off posting wall post if on self-profile
		$this->registry->fbb['runtime']['off_checkboxes']['actions__send_vm__post_wallpost'] = true;
	}
	
	$action = 'send_vm';
	$action_dos_str = '';
	$fbb_form_element_target = '$block_data[\'messagearea\']';
	
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}
?>