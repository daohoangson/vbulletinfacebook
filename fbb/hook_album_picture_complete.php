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
	$vbulletin->fbb['runtime']['enabled']
	AND $vbulletin->userinfo['fbuid']
	AND $editorid
	AND $picturecomment_form
) {	
	$vbulletin->fbb['runtime']['javascript_needed'] = true; //we will need Facebook Account information in the next page!
	
	if ($albuminfo['userid'] == $vbulletin->userinfo['userid']) {
		//auto turn off sharing with this user picture
		$vbulletin->fbb['runtime']['off_checkboxes']['actions__comment_picture__share_if'] = true;
	}
	if (isset($vbulletin->fbb['runtime']['picturecomment_posters'][$vbulletin->userinfo['userid']])) {
		//auto turn off sharing with commented picture
		$vbulletin->fbb['runtime']['off_checkboxes']['actions__comment_picture__share_if'] = true;
	}
	
	$action = 'comment_picture';
	$action_dos_str = '';
	$fbb_newpost_form_template = 'fbb_new_post_options_tricky';
	include(DIR . '/fbb/hook_newpost_form_complete.php');
	
	if ($action_dos_str) {
		//inject our stuff into the correct template
		if ($vbulletin->fbb['runtime']['vb4']) {
			$templater = vB_Template::create('picturecomment_form');
				$templater->register('albuminfo', $albuminfo);
				$templater->register('allowed_bbcode', $allowed_bbcode);
				$templater->register('editorid', $editorid);
				$templater->register('group', $group);
				$templater->register('messagearea', $messagearea);
				$templater->register('messagestats', $messagestats);
				$templater->register('pictureinfo', $pictureinfo);
				$templater->register('vBeditTemplate', $vBeditTemplate);
			$picturecomment_form = $templater->render();
		} else {
			eval('$picturecomment_form = "' . fetch_template('picturecomment_form') . '";');
		}
		
		//update some dependent variables
		$show['picturecomment_options'] = ($picturecomment_form OR $picturecommentbits);

		if ($vbulletin->fbb['runtime']['vb4']) {
			$templater = vB_Template::create('picturecomment_commentarea');
				$templater->register('messagestats', $messagestats);
				$templater->register('pagenav', $pagenav);
				$templater->register('picturecommentbits', $picturecommentbits);
				$templater->register('picturecomment_form', $picturecomment_form);
				$templater->register('pictureinfo', $pictureinfo);
			$picturecomment_commentarea = $templater->render();
		} else {
			eval('$picturecomment_commentarea = "' . fetch_template('picturecomment_commentarea') . '";');
			eval('$picturecomment_css = "' . fetch_template('picturecomment_css') . '";');
		}
	}
}
?>