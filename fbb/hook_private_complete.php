<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.1
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 04:00 Jan 10th, 2010
|| #################################################################### ||
\*======================================================================*/
if ($vbulletin->fbb['runtime']['vb4']) {
	$templatename = $page_templater->get_template_name();
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $templatename == 'pm_newpm'
	AND $vbulletin->userinfo['fbuid']
) {	
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page!
	
	$action = 'send_pm';
	$action_dos_str = '';
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $templatename == 'pm_showpm'
	AND $vbulletin->userinfo['fbuid']
	AND $show['quickreply']
	AND $messagearea
) {	
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page!
	
	$action = 'send_pm';
	$action_dos_str = '';
	include(DIR . '/fbb/hook_newpost_form_complete.php');
}

if ($vbulletin->fbb['runtime']['javascript_needed']
	AND $vbulletin->fbb['runtime']['vb4']) {
	$page_templater->register('messagearea',$messagearea);
}
?>