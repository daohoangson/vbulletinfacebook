<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 2.1
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 03:28 Oct 16th, 2009
|| # HP is so in love with ASUS!!!
|| #################################################################### ||
\*======================================================================*/
if (isset($vbulletin->fbb['runtime']['isFacebookRequest'])
	AND $vbulletin->fbb['runtime']['isFacebookRequest']) {
	$userinfo = verify_id('user', $vbulletin->GPC['userid'], 1, 1, $fetch_userinfo_options);

	if ($userinfo) {
		fetch_musername($userinfo);
		$title = $userinfo['username'] . ' - ' . $vbulletin->options['bbtitle'];
		$description_array = array();
		$description_array[] = 'User Title: ' . $userinfo['usertitle'];
		$description_array[] = 'Posts: ' . vb_number_format($userinto['posts']);
		$description_array[] = 'Member Since: ' . vbdate($vbulletin->options['dateformat'],$userinfo['joindate']);
		$description = implode('. ',$description_array);
		$media = array('images' => array());
		if ($userinfo['hascustomavatar'] AND $vbulletin->options['avatarenabled'])
		{
			if ($vbulletin->options['usefileavatar'])
			{
				$avatar_url = $vbulletin->options['bburl'] . '/' . $vbulletin->options['avatarurl'] . '/avatar' . $userinfo['userid'] . '_' . $userinfo['avatarrevision'] . '.gif';
			}
			else
			{
				$avatar_url = $vbulletin->options['bburl'] . '/' . 'image.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . '&amp;dateline=' . $userinfo['avatardateline'];
			}
			$media['images'][] = array(
				'src' => $avatar_url
			);
		}

		fb_print_share_preview($title,$description,$media);
	}
}
?>