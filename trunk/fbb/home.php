<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.2.3
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 19:54 Mar 29th, 2010
|| #################################################################### ||
\*======================================================================*/
// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'facebook');
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('fbb');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/fbb/functions.php');

// #######################################################################
// ######################## START FACEBOOK CATCHING-UP ###################
// #######################################################################
if (isset($_REQUEST['removed'])) {
	$facebook =& theFacebook();
	$user = $facebook->get_loggedin_user();
	if ($user != NULL && $facebook->fb_params['uninstall'] == 1) {
		//Okie, this user removed us!
		$fbuid = $facebook->fb_params['user'];
		
		$vbulletin->db->query("
			UPDATE `" . TABLE_PREFIX . "user`
			SET fbuid = '0', fbssk = ''
			WHERE fbuid = '$fbuid'
		");
		
		echo 'Thank you, Facebook. See you later';
		exit;
	} else {
		//Hmm, trying to access this function manually huh?
		print_no_permission();
	}
}

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if (!$vbulletin->fbb['runtime']['enabled']) print_no_permission();

if ($_REQUEST['do'] == 'albumpicture' AND $_REQUEST['albumid'] AND $_REQUEST['pictureid']) {
	//this part of code will bypass all permission check by vBulletin
	//damn dangerous!

	if (in_array(
			$_SERVER['HTTP_USER_AGENT']
			,array(
				'facebookexternalhit/1.0 (+http://www.facebook.com/externalhit_uatext.php)' //posting as wall post via Publisher
				,'facebookplatform/1.0 (+http://developers.facebook.com)' //image in attachment in a post
			)
		)
		OR (strpos($_SERVER['HTTP_USER_AGENT'],'debug') !== false)
	) {
		//it should be strpos or something that just matched but we don't wanna be fooled so... let it be!
		
		$vbulletin->input->clean_array_gpc('r', array(
			'pictureid' => TYPE_UINT,
			'albumid'   => TYPE_UINT,
			'thumb'     => TYPE_BOOL
		));
		
		if ($vbulletin->fbb['runtime']['vb4']) {
			//Code copied from attachment.php
			require_once(DIR . '/packages/vbattach/attach.php');
			
			if (!($attach =& vB_Attachment_Display_Single_Library::fetch_library(
				$vbulletin
				, null
				, $vbulletin->GPC['thumb']
				, $vbulletin->GPC['pictureid']
			))) {
				eval(standard_error(fetch_error('invalidid', $vbphrase['attachment'], $vbulletin->options['contactuslink'])));
			}
			$attach->verify_attachment();
			$attachmentinfo = $attach->fetch_attachmentinfo();
			// this convoluted mess sets the $threadinfo/$foruminfo arrays for the session.inthread and session.inforum values
			if ($browsinginfo = $attach->fetch_browsinginfo())
			{
				foreach ($browsinginfo AS $arrayname => $values)
				{
					$$arrayname = array();
					foreach ($values AS $index => $value)
					{
						$$arrayname[$$index] = $value;
					}
				}
			}
			
			$imageinfo = $attachmentinfo;
			$imageinfo['pictureid'] = $attachmentinfo['attachmentid'];
		} else {
			/*
			$imageinfo = $db->query_first_slave("
				SELECT picture.pictureid, picture.userid, picture.extension, picture.idhash, picture.state, picture.fbbypass, 
					albumpicture.dateline, album.state AS albumstate, profileblockprivacy.requirement AS privacy_requirement,
					" . ($vbulletin->GPC['thumb'] ?
						"picture.thumbnail AS filedata, picture.thumbnail_filesize AS filesize" :
						'picture.filedata, picture.filesize'
					) . "
				FROM " . TABLE_PREFIX . "albumpicture AS albumpicture
				INNER JOIN " . TABLE_PREFIX . "picture AS picture ON (albumpicture.pictureid = picture.pictureid)
				INNER JOIN " . TABLE_PREFIX . "album AS album ON (albumpicture.albumid = album.albumid)
				LEFT JOIN " . TABLE_PREFIX . "profileblockprivacy AS profileblockprivacy ON
					(profileblockprivacy.userid = picture.userid AND profileblockprivacy.blockid = 'albums')
				WHERE albumpicture.albumid = " . $vbulletin->GPC['albumid'] . " AND albumpicture.pictureid = " . $vbulletin->GPC['pictureid']
			);
			*/
			$imageinfo = $db->query_first_slave("
				SELECT picture.pictureid, picture.userid, picture.extension, picture.idhash, picture.state, picture.fbbypass, 
					albumpicture.dateline, album.state AS albumstate,
					" . ($vbulletin->GPC['thumb'] ?
						"picture.thumbnail AS filedata, picture.thumbnail_filesize AS filesize" :
						'picture.filedata, picture.filesize'
					) . "
				FROM " . TABLE_PREFIX . "albumpicture AS albumpicture
				INNER JOIN " . TABLE_PREFIX . "picture AS picture ON (albumpicture.pictureid = picture.pictureid)
				INNER JOIN " . TABLE_PREFIX . "album AS album ON (albumpicture.albumid = album.albumid)
				WHERE albumpicture.albumid = " . $vbulletin->GPC['albumid'] . " AND albumpicture.pictureid = " . $vbulletin->GPC['pictureid']
			);
		}
		
		if ($imageinfo['fbbypass'])
		{
			header('Cache-control: max-age=31536000');
			header('Expires: ' . gmdate('D, d M Y H:i:s', (TIMENOW + 31536000)) . ' GMT');
			header('Content-disposition: inline; filename=' . "user$imageinfo[userid]_pic$imageinfo[pictureid]_$imageinfo[dateline]" . ($vbulletin->GPC['thumb'] ? '_thumb' : '') . ".$imageinfo[extension]");
			header('Content-transfer-encoding: binary');
			if ($imageinfo['filesize'])
			{
				header('Content-Length: ' . $imageinfo['filesize']);
			}
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $imageinfo['dateline']) . ' GMT');
			header('ETag: "' . $imageinfo['dateline'] . '-' . $imageinfo['pictureid'] . ($vbulletin->GPC['thumb'] ? '-thumb' : '') . '"');

			if ($imageinfo['extension'] == 'jpg' OR $imageinfo['extension'] == 'jpeg')
			{
				header('Content-type: image/jpeg');
			}
			else if ($imageinfo['extension'] == 'png')
			{
				header('Content-type: image/png');
			}
			else
			{
				header('Content-type: image/gif');
			}

			if ($vbulletin->fbb['runtime']['vb4']) {
				if ($vbulletin->options['attachfile']) {
					require_once(DIR . '/includes/functions_file.php');
					if ($vbulletin->GPC['thumb'])
					{
						$attachpath = fetch_attachment_path($attachmentinfo['uploader'], $attachmentinfo['filedataid'], true);
					}
					else
					{
						$attachpath = fetch_attachment_path($attachmentinfo['uploader'], $attachmentinfo['filedataid']);
					}
					@readfile($attachpath);
				} else {
					$attachmentdata = $db->query_first_slave("
						SELECT " . (!empty($vbulletin->GPC['thumb']) ? 'thumbnail' : 'filedata') . " AS filedata
						FROM " . TABLE_PREFIX . "filedata
						WHERE filedataid = $attachmentinfo[filedataid]
					");
					echo $attachmentdata['filedata'];
				}
			} else {
				if ($vbulletin->options['album_dataloc'] == 'db')
				{
					echo $imageinfo['filedata'];
				}
				else
				{
					@readfile(fetch_picture_fs_path($imageinfo, $vbulletin->GPC['thumb']));
				}
			}
		} else {
			echo 'Oops, picture not found?!';
		}
	} else {
		echo 'Hey, something went terriblely wrong. Contact Administrator please...';
	}
	exit;
}

if ($_REQUEST['do'] == 'update_log') {
	$vbulletin->input->clean_array_gpc('p', array(
		'log_id' => TYPE_UINT,
		'post_id' => TYPE_STR,
		'exception' => TYPE_STR,
		'threadid' => TYPE_UINT,
	));
	
	if ($vbulletin->GPC['log_id'] > 0) {
		if (strpos($vbulletin->GPC['post_id'],'_') !== false) {
			$log_entry = array(
				'result' => $vbulletin->GPC['post_id'],
			);
		} else {
			$log_entry = array(
				'result' => $vbulletin->GPC['exception'],
				'is_exception' => 1,
			);
		}
		
		$vbulletin->db->query_write(fetch_query_sql($log_entry,'fbb_log',"WHERE logid = {$vbulletin->GPC['log_id']}"));
		
		if (empty($log_entry['is_exception'])) {
			if ($vbulletin->GPC['threadid']) {
				//update Facebook PostID for the target thread (which has just been published to Facebook)
				$vbulletin->db->query("
					UPDATE `" . TABLE_PREFIX . "thread`
					SET fbpid = '{$vbulletin->GPC['post_id']}'
					WHERE threadid = " . intval($vbulletin->GPC['threadid']) . "
				");
			}
			
			($hook = vBulletinHook::fetch_hook('fbb_update_log')) ? eval($hook) : false;
		}
	}
	
	exit;
}

if (empty($_REQUEST['do'])) {
	if ($vbulletin->fbb['config']['auto_register']
		OR $vbulletin->fbb['config']['auto_login']) {
		$vbulletin->fbb['runtime']['javascript_needed'] = true; //we need them now!
		
		//Load script
		$connected_phrase = construct_phrase(
			$vbphrase['fbb_thank_choose_an_action']
			,'<fb:name uid=loggedinuser useyou=false></fb:name>'
			,$vbulletin->options['bbtitle']
		);
		if ($vbulletin->fbb['runtime']['vb4']) {
			$templater = vB_Template::create('fb_script_common');
				$templater->register('connected_phrase',$connected_phrase);
			$fb_script_common = $templater->render();
		} else {
			eval('$fb_script_common = "' . fetch_template('fb_script_common') . '";');
		}

		if ($vbulletin->fbb['runtime']['vb4']) {
			$navbar = render_navbar_template(construct_navbits(
				array(
					'facebook.php' => $vbphrase['fbb'],
				)
			));
			
			$templater = vB_Template::create('fbb_vb4_landing_page');
				$templater->register_page_templates();
				$templater->register('navbar', $navbar);
				$templater->register('fb_script_common', $fb_script_common);
			print_output($templater->render());
		} else {
			eval('print_output("' . fetch_template('fbb_landing_page') . '");');
		}
	} else {
		print_no_permission();
	}
}
?>