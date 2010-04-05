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
if (
	$vbulletin->fbb['runtime']['enabled']
	AND $type != 'thread'
) {
	require_once(DIR . '/fbb/functions.php');

	$commented = false;
	
	list($fb_title,$description,$fb_media) = fb_getPostInfo($post,$threadinfo,$foruminfo);
	$comment =& $description;
	$template_data = array(
		'post_title' => $post['title'],
		'post_url' => fb_getLink($post['postid'],'post'),
		'thread_title' => $threadinfo['title'],
		'thread_url' => fb_getLink($threadinfo['threadid'],'thread'),
		'forum_title' => $foruminfo['title'],
		'forum_url' => fb_getLink($foruminfo['forumid'],'forum'),
		'description' => $comment,
	);
	
	//get images
	if (is_array($fb_media['images'])) {
		for ($i = 0; $i < min(3,count($fb_media['images'])); $i++) {
			$template_data['images'][] = array(
				'src' => $fb_media['images'][$i]['src'],
				'href' => $template_data['post_url'],
			);
		}
	}

	if (fb_getForumRestrictKey($foruminfo) == 'disabled_checkboxes') {
		//well, skip everything...
	} else {
		if ($vbulletin->userinfo['fbuid']) {
			if ($threadinfo['fbpid'] != ''
				AND $threadinfo['fbpid'] != '0'
				AND $threadinfo['fbpid'] != '-1'
				AND $threadinfo['fbpid'] != '-2'
				AND fb_quick_options_check('actions__post_reply__post_comment',false,true,false,true)
			) {	
				//try to comment
				$commentid = fb_stream_addcomment($threadinfo['fbpid'],$comment);
				if (!fb_isErrorCode($commentid)) {
					$vbulletin->db->query("
						UPDATE `" . TABLE_PREFIX . "post`
						SET fbcid = '$commentid'
						WHERE postid = $post[postid]
					");
					
					$commented = true;
				}
			}
			
			if (fb_quick_options_check('actions__post_reply__post_short_story',false,true,false,true)
				AND !($commented AND $threadinfo['postuserid'] == $vbulletin->userinfo['userid']) //skip if commented on a post on member's own wall
			) {
				$message = construct_phrase($vbphrase['fbb_template_post_reply_message']
					,$vbulletin->userinfo['username']
					,$template_data['post_title']
					,$template_data['post_url']
					,$template_data['thread_title']
					,$template_data['thread_url']
					,$template_data['forum_title']
					,$template_data['forum_url']);
				$attachment = array(
					'name' => $template_data['post_title'],
					'href' => $template_data['post_url'],
					'caption' => construct_phrase(
						$vbphrase['fbb_template_post_reply_caption']
						,'{*actor*}'
						,$template_data['post_title']
						,$template_data['post_url']
						,$template_data['thread_title']
						,$template_data['thread_url']
						,$template_data['forum_title']
						,$template_data['forum_url']
					),
					'description' => construct_phrase(
						$vbphrase['fbb_template_post_reply_description']
						,$template_data['description']
						,$template_data['post_title']
						,$template_data['post_url']
						,$template_data['thread_title']
						,$template_data['thread_url']
						,$template_data['forum_title']
						,$template_data['forum_url']
					),
				);
				if (isset($template_data['images'])) {
					$attachment['media'] = array();
					foreach ($template_data['images'] as $image) {
						$attachment['media'][] = array(
							'type' => 'image',
							'src' => $image['src'],
							'href' => $image['href'],
						);
					}
				}
				$action_links = array(
					array(
						'href' => $template_data['thread_url'],
						'text' => $template_data['thread_title'],
					),
					array(
						'href' => $vbulletin->options['bburl'],
						'text' => construct_phrase($vbphrase['fbb_action_link_self'],$vbulletin->options['bbtitle']),
					),
				);
				
				$fbpid = fb_stream_publish($message,$attachment,$action_links);
			}
		}
	}
	
	//Notifications
	$fbuids = array();
	$postfbuid = 0;
	$users = array();
	
	if ($vbulletin->fbb['config']['post_reply_notification_old_posts_limit'] > 0) {
		$posters_result = $vbulletin->db->query("
			SELECT user.userid, user.fbuid, user.fbssk, user.fboptions, post.dateline, 1 AS isFollowing
			FROM `" . TABLE_PREFIX . "post` AS post
			INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = post.userid)
			WHERE post.threadid = $threadinfo[threadid]
				AND user.fbuid <> '0'
			ORDER BY post.dateline
			LIMIT {$vbulletin->fbb['config']['post_reply_notification_old_posts_limit']}
		");
		while ($poster = $vbulletin->db->fetch_array($posters_result)) {
			$users[$poster['userid']] = $poster;
		}
	}
	
	//Injected our script to a hook named "newpost_notification_message" and store the subscribers information
	//We will grab that information here
	if (isset($vbulletin->fbb['runtime']['subscriber_' . $threadinfo['threadid']]) AND is_array($vbulletin->fbb['runtime']['subscriber_' . $threadinfo['threadid']])) {
		foreach ($vbulletin->fbb['runtime']['subscriber_' . $threadinfo['threadid']] AS $subcriberid => $subcriber) {
			$users[$subcriber['userid']] = $subcriber;
		}
	}
	
	if (!isset($users[$threadinfo['postuserid']])) {
		$users[$threadinfo['postuserid']] = $vbulletin->db->query_first("
			SELECT userid, fbuid, fbssk, fboptions 
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid = $threadinfo[postuserid]
		");
	}
	
	//check for comment in the newsfeed (if available)
	//we don't want somebody get multiple notifications
	$skip_fbuids = array();
	if ($threadinfo['fbpid'] AND $threadinfo['fbpid'] != '-1' AND $commented) {
		$commented_fbps = fb_fql_query("
			SELECT fromid 
			FROM comment
			WHERE post_id = '$threadinfo[fbpid]'
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
				$user['userid'] == $vbulletin->userinfo['userid']
				OR
				(
					$commented
					AND $user['userid'] == $threadinfo['postuserid']
				)
			) {
				//don't send notification to the logged user. Obvious!
				//don't send notification to the thread poster because comment posted
			} else {
				$fboptions = unserialize($user['fboptions']);
				$notification_types = array();
				if ($user['userid'] == $threadinfo['postuserid']) {
					$notification_types[] = 'notifications__new_reply_own';
				} else if ($user['isFollowing']) {
					$notification_types[] = 'notifications__new_reply_following';
				} else if ($user['isSubcriber']) {
					$notification_types[] = 'notifications__new_reply_subcribed';
				}
				
				if (fb_quick_options_check($notification_types,$fboptions,true,$user)) {
					$fbuids[] = $user['fbuid'];
				}
			}
			
			if ($user['userid'] == $threadinfo['postuserid'])
				$postfbuid = $user['fbuid'];
		}
	}
	
	if ($vbulletin->userinfo['userid'] == $threadinfo['postuserid']
		AND $vbulletin->userinfo['fbuid'])
		$postfbuid = $vbulletin->userinfo['fbuid'];

	if (count($fbuids) > 0) {
		$to_ids = implode(',',$fbuids);
		$notification = construct_phrase(
			$vbphrase['fbb_template_post_reply_notification' . iif($postfbuid == 0,'_no_owner')]
			,$template_data['post_url']
			,$template_data['post_title']
			,$template_data['thread_url']
			,$template_data['thread_title']
			,iif($postfbuid == 0,$threadinfo['postusername'],$postfbuid)
			,$vbulletin->userinfo['username']
		);
		fb_notifications_send($to_ids, $notification, 'user_to_user');
	}
}

if (
	$vbulletin->fbb['runtime']['enabled']
	AND $type == 'thread'
) {
	//the main thread processing code is placed in hook_newpost_process 
	//the publishing stuff is placed in showthread_complete
	//here is the publishing to Facebook Page (which is configurable via AdminCP > Forum Manager)
	if ($foruminfo['fbtarget_id'] AND $foruminfo['fbuid']) {
		require_once(DIR . '/fbb/functions.php');
		
		$post['username'] = $vbulletin->userinfo['username']; //manually pass username into array
		$post['pagetext'] = $post['message']; //manually pass pagetext into array 
		list($title,$description,$media) = fb_getPostInfo($post,$threadinfo,$foruminfo);
		
		$message = construct_phrase(
			$vbphrase['fbb_page_template_post_thread_message']
			,$vbulletin->userinfo['username']
			,$title
			,fb_getLink($threadinfo['threadid'],'thread')
			,$foruminfo['title']
			,fb_getLink($foruminfo['forumid'],'forum')
		);
		$attachment = array(
			'name' => $title,
			'href' => fb_getLink($threadinfo['threadid'],'thread'),
			'description' => construct_phrase(
				$vbphrase['fbb_page_template_post_thread_description']
				,$description
				,$title
				,fb_getLink($threadinfo['threadid'],'thread')
				,$foruminfo['title']
				,fb_getLink($foruminfo['forumid'],'forum')
			),
		);
		if (isset($media['images'])) {
			$attachment['media'] = array();
			for($i = 0; $i < min(3,count($media['images'])); $i++) {
				$image = $media['images'][$i];
				
				$attachment['media'][] = array(
					'type' => 'image',
					'src' => $image['src'],
					'href' => fb_getLink($threadinfo['threadid'],'thread'),
				);
			}
		}
		$action_links = array(
			array(
				'href' => fb_getLink($foruminfo['forumid'],'forum'),
				'text' => $foruminfo['title'],
			),
		);
		
		if ($foruminfo['fbtarget_id'] == $foruminfo['fbtarget_id_permission_granted']) {
			//posting on behalf of the Page itself
			//Posts where the actor is a page cannot also include a target_id
			fb_stream_publish($message,$attachment,$action_links,null,$foruminfo['fbtarget_id'],false); 
		} else {
			//posting on behalf of the Page Owner
			fb_stream_publish($message,$attachment,$action_links,$foruminfo['fbtarget_id'],$foruminfo['fbuid'],false);
		}
	}
}
?>