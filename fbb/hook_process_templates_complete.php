<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 19:06 Jan 22nd, 2010
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
	}
}
?>