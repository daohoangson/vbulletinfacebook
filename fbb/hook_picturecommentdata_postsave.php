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
	!$this->condition
	AND $this->registry->fbb['runtime']['enabled']
) {	
	global $vbphrase, $albuminfo;
	require_once(DIR . '/fbb/functions.php');

	$this_fbuid = $this->registry->userinfo['fbuid'];
	$actiondo_share_if = fb_quick_options_check('actions__comment_picture__share_if',false,true,false,true);
	$actiondo_post_comment = fb_quick_options_check('actions__comment_picture__post_comment',false,true,false,true);
	$target_userinfo =& $this->info['pictureuser'];
	$target_fbuid = $this->info['pictureuser']['fbuid'];
	$comment_pagetext = getWords($this->fetch_field('pagetext'));
	
	if ($this->registry->fbb['runtime']['vb4']) {
		$fbb_pictureinfo = $this->registry->db->query_first("
			SELECT attachmentid, filedataid, caption, fbbypass, fbpid
			FROM `" . TABLE_PREFIX . "attachment`
			WHERE filedataid = " . $this->fetch_field('filedataid') . "
		");
		
		$picture_url = $this->registry->options['bburl'] . '/album.php?albumid=' . $albuminfo['albumid'] . '&attachmentid=' . $fbb_pictureinfo['attachmentid'];
		$fb_picture_url = $this->registry->options['bburl'] . '/facebook.php?do=albumpicture&albumid=' . $albuminfo['albumid'] . '&pictureid=' . $fbb_pictureinfo['attachmentid'];
	} else {
		$fbb_pictureinfo = $this->registry->db->query_first("
			SELECT pictureid, caption, fbbypass, fbpid
			FROM `" . TABLE_PREFIX . "picture`
			WHERE pictureid = " . $this->fetch_field('pictureid') . "
		");
		
		$picture_url = $this->registry->options['bburl'] . '/album.php?albumid=' . $albuminfo['albumid'] . '&pictureid=' . $fbb_pictureinfo['pictureid'];
		$fb_picture_url = $this->registry->options['bburl'] . '/facebook.php?do=albumpicture&albumid=' . $albuminfo['albumid'] . '&pictureid=' . $fbb_pictureinfo['pictureid'];
	}
	
	if ($fbb_pictureinfo) {
		//try to share
		if ($this_fbuid AND $actiondo_share_if AND $fbb_pictureinfo['fbbypass']) {
			$message = construct_phrase($vbphrase['fbb_template_comment_picture_message']
				,$fbb_pictureinfo['caption']
				,iif($albuminfo,$albuminfo['title'],'-')
				,$target_userinfo['username']
				,$this->registry->options['bbtitle']
				,$comment_pagetext
			);
			$attachment = array(
				'name' => $fbb_pictureinfo['caption'],
				'href' => $picture_url,
				'description' => construct_phrase($vbphrase['fbb_template_comment_picture_description']
					,$fbb_pictureinfo['caption']
					,iif($albuminfo,$albuminfo['title'],'-')
					,$target_userinfo['username']
					,$this->registry->options['bbtitle']
					,$comment_pagetext
				),
				'media' => array(
					array(
						'type' => 'image',
						'src' => $fb_picture_url,
						'href' => $picture_url,
					),
				),
			);
			$action_links = array(
				array(
					'href' => $this->registry->options['bburl'] . '/album.php?albumid=' . $albuminfo['albumid'],
					'text' => $albuminfo['title'],
				),
				array(
					'href' => $this->registry->options['bburl'],
					'text' => construct_phrase($vbphrase['fbb_action_link_self'],$this->registry->options['bbtitle']),
				),
			);
			
			$fbpid = fb_stream_publish($message,$attachment,$action_links);
		}
		
		//try to comment
		if ($this_fbuid AND $actiondo_post_comment AND $fbb_pictureinfo['fbpid']) {
			$commentid = fb_stream_addcomment($fbb_pictureinfo['fbpid'],$comment_pagetext);
			if (!fb_isErrorCode($commentid)) {
				$commented = true;
			}
		}
		
		//try to send notifications
		$fbuids = array();
		$postfbuid = $target_fbuid;
		$users = array();
		$users[$target_userinfo['userid']] = $target_userinfo;
		
		$posters_result = $this->registry->db->query("
			SELECT user.userid, user.fbuid, user.fbssk, user.fboptions, picturecomment.dateline
			FROM `" . TABLE_PREFIX . "picturecomment` AS picturecomment
			INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = picturecomment.postuserid)
			WHERE 
				" 
				. iif(
					$this->registry->fbb['runtime']['vb4']
					,"picturecomment.filedataid = $fbb_pictureinfo[filedataid]"
					,"picturecomment.pictureid = $fbb_pictureinfo[pictureid]"
				) . "
				AND user.fbuid <> '0'
			ORDER BY picturecomment.dateline
			LIMIT 25
		");
		while ($poster = $this->registry->db->fetch_array($posters_result)) {
			$users[$poster['userid']] = $poster;
		}
		
		//check for comment in the newsfeed (if available)
		//we don't want somebody get multiple notifications
		$skip_fbuids = array();
		if ($fbb_pictureinfo['fbpid'] AND $fbb_pictureinfo['fbpid'] != '-1' AND $commented) {
			$commented_fbps = fb_fql_query("
				SELECT fromid 
				FROM comment
				WHERE post_id = '$fbb_pictureinfo[fbpid]'
			");
			
			if (is_array($commented_fbps)) 
				foreach ($commented_fbps as $commented_fbp) {
					if (!in_array($commented_fbp['fromid'],$skip_fbuids)) {
						$skip_fbuids[] = $commented_fbp['fromid'];
					}
				}
		}
		
		foreach ($users as $user) {
			if ($user['fbuid'] AND !in_array($user['fbuid'],$skip_fbuids)) {
				if (
					$user['userid'] == $this->registry->userinfo['userid']
					OR 
					(
						$commented
						AND $user['userid'] == $target_userinfo['userid']
					)
				) {
					//don't send notification to the logged user. Obvious!
					//don't send notification to the owner because comment posted
				} else {
					$fboptions = unserialize($user['fboptions']);
					$notification_types = array();
					if ($user['userid'] == $target_userinfo['userid']) {
						$notification_types[] = 'notifications__picture_commented';
					} else {
						$notification_types[] = 'notifications__picture_commented_following';
					}
					
					if (fb_quick_options_check($notification_types,$fboptions,true,$user)) {
						$fbuids[] = $user['fbuid'];
					}
				}
			}
		}
		
		if ($target_fbuid AND $notification_picture_commented) {
			$notification = construct_phrase(
				$vbphrase['fbb_template_comment_picture_notification']
				,$fbb_pictureinfo['caption']
				,iif($albuminfo,$albuminfo['title'],'-')
				,iif($target_fbuid == 0,'-',$target_fbuid)
				,$picture_url
			);
			fb_notifications_send($target_fbuid, $notification, 'user_to_user');
		}
		
		if (count($fbuids) > 0) {
			$to_ids = implode(',',$fbuids);
			$notification = construct_phrase(
				$vbphrase['fbb_template_comment_picture_notification' . iif($postfbuid == 0,'_no_owner')]
				,$fbb_pictureinfo['caption']
				,iif($albuminfo,$albuminfo['title'],'-')
				,iif($target_fbuid == 0,$target_userinfo['username'],$target_fbuid)
				,$picture_url
			);
			
			fb_notifications_send($to_ids, $notification, 'user_to_user');
		}
	}
}
?>