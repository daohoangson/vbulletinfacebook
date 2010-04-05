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
	$vbulletin->fbb['runtime']['enabled']
	AND $userinfo['fbuid']
	AND ($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canviewmembers'])
	AND $vbulletin->fbb['config']['display_elements']['postbit_imicons']
) {
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fb_memberinfo_imdetail_simple');
			$templater->register('userinfo', $userinfo);
		$fbb_imdetail = $templater->render();
	} else {
		eval('$fbb_imdetail = "' . fetch_template('fb_memberinfo_imdetail_simple') . '";');
	}
	
	$userinfo['icqicon'] = $fbb_imdetail . $userinfo['icqicon'];
	$userinfo['showicq'] = true;
	$show['hasimicons'] = true;
}
?>