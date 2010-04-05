<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 2.2.4
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 18:13 Oct 25th, 2009
|| # HP is so in love with ASUS!!!
|| #################################################################### ||
\*======================================================================*/
if (
	$this->registry->fbb['runtime']['enabled']
	AND $post['fbuid']
) {
	require_once(DIR . '/fbb/functions.php');
	
	if (!isset($this->registry->fbb['runtime']['sync_ed_avatar_' . $post['userid']])) {
		$this->registry->fbb['runtime']['sync_ed_avatar_' . $post['userid']] = fb_sync_avatar($post);
	}
	
	if (!empty($this->registry->fbb['runtime']['sync_ed_avatar_' . $post['userid']])) {
		foreach ($this->registry->fbb['runtime']['sync_ed_avatar_' . $post['userid']] as $key => $value) {
			$post[$key] = $value;
		}
	}
}
?>