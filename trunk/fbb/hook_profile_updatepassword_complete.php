<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 19:05 Jan 22nd, 2010
|| #################################################################### ||
\*======================================================================*/
if ($userdata->user['email']
	AND $userdata->existing['fbuid'] 
	AND ($userdata->existing['fbemail'] OR fb_is_proxied_email($userdata->existing['email']))
) {
	$userdata->set('fbemail',0);
}
?>