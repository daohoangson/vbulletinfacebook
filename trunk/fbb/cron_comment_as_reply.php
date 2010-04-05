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
// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
if (!is_object($vbulletin->db))
{
	exit;
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
if (empty($style)) {
	$db =& $vbulletin->db;
	
	$vbphrase_store = $vbphrase;
	unset($vbphrase);
	
	require_once(DIR . '/global.php');
	
	$vbphrase = $vbphrase_store;
}

if (!$vbulletin->fbb['config']['comment_as_reply']) {
	exit;
}

require_once(DIR . '/fbb/functions.php');

$log_info = array();
$threads = array();
$threadids = array();
$threadids_synced = array();
$fbusers = array();
$fbcomments = array();
$fbfromids = array();
$fbcommentids = array();
$newpostids = array();
$thread_lastpost_limit = TIMENOW - $vbulletin->fbb['config']['comment_as_reply_max'];
$thread_lastcomment_sync_limit = TIMENOW - $vbulletin->fbb['config']['comment_as_reply'];

$thread_lastpost_limit = TIMENOW - 6*60*60;
$thread_lastcomment_sync_limit = TIMENOW;

$threads_from_db = $vbulletin->db->query_read("
	SELECT thread.*
		, user.userid, user.fbuid, user.fbssk
	FROM `" . TABLE_PREFIX . "thread` AS thread
	INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = thread.postuserid)
	WHERE 
		thread.fbpid NOT IN ('0','-1','-2','-3')
		AND thread.lastpost > $thread_lastpost_limit
		AND thread.fblastcomment_sync < $thread_lastcomment_sync_limit
		AND user.fbuid <> '0'
		AND user.fbssk <> ''
");
while ($thread = $vbulletin->db->fetch_array($threads_from_db)) {
	if ($thread['fbpid'] == fb_generateNonCode()) continue;
	
	$threads[$thread['fbuid']][$thread['fbpid']] = array(
		'threadid' => $thread['threadid'],
		'fbpid' => $thread['fbpid'],
		'fblastcomment' => intval($thread['fblastcomment']),
	);
	$fbusers[$thread['fbuid']] = array(
		'userid' => $thread['userid'],
		'fbuid' => $thread['fbuid'],
		'fbssk' => $thread['fbssk'],
	);
	
	$threadids[] = $thread['threadid'];
}
$vbulletin->db->free_result($threads_from_db);

if (!empty($threads)) {
	foreach ($threads as $fbuid => $user_threads) {
		fb_forcessk($fbusers[$fbuid]);
		
		$fblastcomment = 0;
		foreach ($user_threads as $thread) {
			if ($fblastcomment == 0) {
				$fblastcomment = $thread['fblastcomment'];
			} else {
				$fblastcomment = min($fblastcomment,$thread['fblastcomment']);
			}
		}
		
		$comments_from_fb = fb_fql_query("
			SELECT 
				post_id
				,id,fromid,username
				,time,text
			FROM comment
			WHERE post_id IN ('" . implode("','",array_keys($user_threads)) . "')
				AND time > $fblastcomment
		");
		
		if (!empty($comments_from_fb)) {
			foreach ($comments_from_fb as $comment) {
				if ($comment['time'] <= $user_threads[$comment['post_id']]['fblastcomment']) continue; 
				
				$fbcomments[$fbuid][$comment['post_id']][$comment['id']] = $comment;
				$fbfromids[] = $comment['fromid'];
				$fbcommentids[$comment['id']] = array('fbuid' => $fbuid, 'fbpid' => $comment['post_id']);
				
				$threadids_synced[$user_threads[$comment['post_id']]['threadid']][] = $comment['time'];
			}
		}
	}
	
	if ($vbulletin->fbb['config']['comment_as_reply_get_name'] AND !empty($fbfromids)) {
		$users_from_fb = fb_fql_query("
			SELECT 
				name
				,uid
			FROM user
			WHERE uid IN ('" . implode("','",$fbfromids) . "')
		");
		
		if (!empty($users_from_fb)) {
			foreach ($users_from_fb as $fbuser) {
				$fbusers[$fbuser['uid']]['fbuid'] = $fbuser['uid'];
				$fbusers[$fbuser['uid']]['username'] = $fbuser['name'];
			}
		}
	}
	
	if (!empty($fbfromids)) {
		$users_from_db = $vbulletin->db->query_read("
			SELECT userid, username, fbuid
			FROM `" . TABLE_PREFIX . "user` 
			WHERE fbuid IN ('" . implode("','",$fbfromids) . "')
		");
		while ($user = $vbulletin->db->fetch_array($users_from_db)) {
			$fbusers[$user['fbuid']]['fbuid'] = $user['fbuid'];
			$fbusers[$user['fbuid']]['userid'] = $user['userid'];
			$fbusers[$user['fbuid']]['username'] = $user['username'];
		}
		$vbulletin->db->free_result($users_from_db);
	}

	if (!empty($fbcommentids)) {
		$synched_from_db = $vbulletin->db->query_read("
			SELECT postid, fbuid, fbcid
			FROM `" . TABLE_PREFIX . "post`
			WHERE fbcid IN ('" . implode("','",array_keys($fbcommentids)) . "')
		");
		while ($synced = $vbulletin->db->fetch_array($synched_from_db)) {
			$tmp = $fbcommentids[$synced['fbcid']];
			if (count($tmp) == 2) {
				unset($fbcomments[$tmp['fbuid']][$tmp['fbpid']][$synced['fbcid']]);
				if (empty($fbcomments[$tmp['fbuid']][$tmp['fbpid']])) unset($fbcomments[$tmp['fbuid']][$tmp['fbpid']]);
				if (empty($fbcomments[$tmp['fbuid']])) unset($fbcomments[$tmp['fbuid']]);
				
				echo 'Found synced post:  <a href="', fb_getLink($synced['postid'],'post'), '">#', $synced['postid'], '</a><br/>';
			}
		}
		$vbulletin->db->free_result($synched_from_db);
	}

	$userinfo_store = $vbulletin->userinfo;
	
	if (!empty($fbcomments)) {
		foreach ($fbcomments as $poster_fbuid => $user_posts) {
			$newpost = array();
			foreach ($user_posts as $fbpid => $user_comments) {
				foreach ($user_comments as $fbcid => $comment) {
					$threadinfo =& $threads[$poster_fbuid][$fbpid];
					$foruminfo = $vbulletin->forumcache[$threadinfo['forumid']];
					$userinfo =& $fbusers[$comment['fromid']];
					
					$vbulletin->userinfo = $userinfo; //mapping
					
					$newpost['message'] = $comment['text'];
					$newpost['title'] = '';
					$newpost['iconid'] = '';
					$newpost['dateline'] = $comment['time'];
					
					$dataman =& datamanager_init('Post', $vbulletin, ERRTYPE_ARRAY, 'threadpost');
					
					$dataman->set_info('is_automated', true);
					
					$dataman->set_info('preview', 0);
					$dataman->set_info('parseurl', 1);
					$dataman->set_info('forum', $foruminfo);
					$dataman->set_info('thread', $threadinfo);
					// set options
					$dataman->set('showsignature', 1);
					$dataman->set('allowsmilie', 1);

					// set data
					if (!$userinfo['userid']) $userinfo['userid'] = 0;
					$dataman->setr('userid', $userinfo['userid']);
					if ($userinfo['userid'] == 0) {
						if (!$userinfo['username']) $userinfo['username'] = 'Facebook User';
						$dataman->setr('username', $userinfo['username']);
					}
					$dataman->setr('title', $newpost['title']);
					$dataman->setr('pagetext', $newpost['message']);
					$dataman->setr('iconid', $newpost['iconid']);
					$dataman->set('dateline',$newpost['dateline']);
					
					$dataman->set('fbuid',$userinfo['fbuid']);
					$dataman->set('fbcid',$comment['id']);

					$dataman->set('visible', 1);

					$dataman->setr('parentid', $threadinfo['firstpostid']);
					$dataman->setr('threadid', $threadinfo['threadid']);

					$errors = array();
					
					$dataman->pre_save();
					
					if (empty($dataman->errors)) {
						$id = $dataman->save();
						
						echo 'Added new post:  <a href="', fb_getLink($id,'post'), '">#', $id, '</a><br/>';
						
						$newpostids[] = $id;
					} else {
						echo 'Error adding comment (', $comment['text'], '): ', implode('; ',$dataman->errors);
						exit;
					}
					
					unset($dataman);
				}
			}
		}
	}
	
	$vbulletin->userinfo = $userinfo_store;
}

if (!empty($threadids_synced)) {
	foreach ($threadids_synced as $threadid => $times) {
		$lastcomment = $times[0];
		foreach ($times as $time) $lastcomment = max($lastcomment,$time);
		$vbulletin->db->query("
			UPDATE `" . TABLE_PREFIX . "thread`
			SET fblastcomment_sync = " . TIMENOW . "
				,fblastcomment = $lastcomment
			WHERE threadid = $threadid
		");
	}
}

if (!empty($threadids)) {
	$tmp = array();
	foreach ($threadids as $threadid) {
		if (!isset($threadids_synced[$threadid])) {
			$tmp[] = $threadid;
		}
	}
	
	if (!empty($tmp)) {
		$vbulletin->db->query("
			UPDATE `" . TABLE_PREFIX . "thread`
			SET fblastcomment_sync = " . TIMENOW . "
			WHERE threadid IN (" . implode(',',$tmp) . ")
		");
	}
}

if ($newpostids) {
	log_cron_action(implode(', ',$newpostids), $nextitem, 1);
}
?>