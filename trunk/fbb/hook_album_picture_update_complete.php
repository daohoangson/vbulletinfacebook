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
	!empty($vbulletin->fbb['runtime']['pictures_to_share'])
) {
	require_once(DIR . '/fbb/functions.php');

	$message = '';
	$attachment = array(
		'name' => $albuminfo['title'],
		'href' => $vbulletin->options['bburl'] . '/album.php?albumid=' . $albuminfo['albumid'],
		'description' => $albuminfo['description'],
		'media' => array(),
	);
	$action_links = array(
		array(
			'href' => $vbulletin->options['bburl'],
			'text' => construct_phrase($vbphrase['fbb_action_link_self'],$vbulletin->options['bbtitle']),
		),
	);
	
	if (count($vbulletin->fbb['runtime']['pictures_to_share']) > 3) {
		$vbulletin->fbb['runtime']['pictures_to_share'] = array_rand($vbulletin->fbb['runtime']['pictures_to_share'],3);
	}
	
	if ($vbulletin->fbb['runtime']['vb4']) {
		$idfield = 'attachmentid';
		$tablename = 'attachment';
	} else {
		$idfield = 'pictureid';
		$tablename = 'picture';
	}
	
	foreach ($vbulletin->fbb['runtime']['pictures_to_share'] AS $pictureid => $picture) {
		$media_item = array(
			'type' => 'image',
			'src' => $vbulletin->options['bburl'] . '/facebook.php?do=albumpicture&albumid=' . $albuminfo['albumid'] . '&pictureid=' . $picture[$idfield],
			'href' => $vbulletin->options['bburl'] . '/album.php?albumid=' . $albuminfo['albumid'] . '&' . $idfield . '=' . $picture[$idfield],
		);
		
		$attachment['media'][] = $media_item;
	}
	
	$result = fb_stream_publish(
		$message
		, $attachment
		, $action_links
	);
	
	if (!fb_isErrorCode($result)) {
		$vbulletin->db->query("
			UPDATE `" . TABLE_PREFIX . $tablename . "`
			SET fbpid = '" . $vbulletin->db->escape_string($result) . "'
			WHERE $idfield IN (" . implode(',',array_keys($vbulletin->fbb['runtime']['pictures_to_share'])) . ")
		");
	}
}
?>