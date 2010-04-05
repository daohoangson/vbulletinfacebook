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
if ($vbulletin->fbb['runtime']['enabled']) {
	$show['fbb_fun'] = $vbulletin->fbb['runtime']['in_full_mode'];
	$show['fbb_invite'] = true;

	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create('fbb_vb4_usercp_navbar_bottom');
			$templater->register('show', $show);
			$templater->register('navclass', $navclass);
		//Bug fixed by robwoelich@vBulletin.org on 07th Feb, 2010
		$template_hook['usercp_navbar_bottom'] .= $templater->render();
	} else {
		eval('$template_hook["usercp_navbar_bottom"] .= "' . fetch_template('fbb_usercp_navbar_bottom') . '";');
	}
}
?>