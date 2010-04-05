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
if (isset($vbulletin->fbb['runtime']['fbb_redirect_login']) AND $vbulletin->fbb['runtime']['fbb_redirect_login']) {
	if (!empty($this)) {
		$this->load_style();
	}

	//Wow, we got the cute little note from our brother at global_start!
	//the user has been logged in automatically, just display a nice notice to him/her right now
	//so cooool!
	$redirect_phrase = iif(
		is_string($vbulletin->fbb['runtime']['fbb_redirect_login'])
		,$vbulletin->fbb['runtime']['fbb_redirect_login']
		,'fbb_redirect_login'
	);

	eval(standard_redirect(fetch_error($redirect_phrase,$vbulletin->userinfo['username']),true));
}
?>