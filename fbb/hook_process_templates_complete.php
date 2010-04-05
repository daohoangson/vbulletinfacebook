<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.5
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 13:30 Mar 31th, 2010
|| #################################################################### ||
\*======================================================================*/
if ($vbulletin->fbb['runtime']['navbar_button_needed']) {
	switch (VB_PRODUCT) {
		case 'vbcms':
			$template_hook['vbcms_navbar_end'] .= vB_Template::create('fbb_vb4_navbar_button')->render();
			break;
		case 'vbblog':
			$template_hook['blog_navbar_end'] .= vB_Template::create('fbb_vb4_navbar_button')->render();
			break;
		default:
			$handled = false;
			
			($hook = vBulletinHook::fetch_hook('fbb_navbar_button')) ? eval($hook) : false;
			
			if (!$handled) $template_hook['navbar_end'] .= vB_Template::create('fbb_vb4_navbar_button')->render();
			break;
	}
}

if ($vbulletin->fbb['runtime']['enabled']) {
	if ($vbulletin->fbb['runtime']['vb4']) {
		$vbulletin->fbb['runtime']['footer'] = vB_Template::create('fb_footer')->render();
	} else {
		eval('$vbulletin->fbb["runtime"]["footer"] = "' . fetch_template('fb_footer') . '";');
	}
}
?>