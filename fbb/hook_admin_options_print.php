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
switch ($setting['optioncode']) {
	case 'usergroupid':
		$usergrouplist = array();
		foreach ($vbulletin->usergroupcache AS $usergroup) {
			$usergrouplist["$usergroup[usergroupid]"] = $usergroup['title'];
		}

		print_select_row($description, $name, $usergrouplist, $setting['value']);
		
		$handled = true;
		break;
	case 'fb_avatar_sources':
		$sources = array('' => $vbphrase['setting_fbb_sync_avatar_source_no']);

		$source_keys = array(
			'pic_small',
			'pic',
			'pic_big',
			'pic_square',
			/*
			'pic_small_with_logo',
			'pic_with_logo',
			'pic_big_with_logo',
			'pic_square_with_logo',
			*/
		);
		
		foreach ($source_keys as $key) {
			$sources[$key] = $vbphrase['setting_fbb_sync_avatar_source_' . $key];
		}
		
		print_select_row($description, $name, $sources, $setting['value']);
		
		$handled = true;
		break;
		
	case 'profileblock_positions':
		$positions = array(
			'center' => $vbphrase['setting_fbb_profileblock_center'],
			'right' => $vbphrase['setting_fbb_profileblock_right'],
		);
		
		print_select_row($description, $name, $positions, $setting['value']);
		
		$handled = true;
		break;
		
	case 'fboptions':
		require_once(DIR . '/fbb/hook_init_startup.php');
		init_startup_additional_task();
		require_once(DIR . '/fbb/functions.php');
		
		$old_setting = unserialize($setting['value']);
		if (empty($old_setting)) {
			//all
			$isAll = true;
		} else {
			$isAll = false;
			$old_options = array_keys($old_setting['fboptions']);
		}
		
		$all_or_not = array(
			'all' => $vbphrase['setting_fbb_auto_on_options_all'],
			'custom' => $vbphrase['setting_fbb_auto_on_options_custom'],
		);
		print_select_row($description, $name, $all_or_not, iif($isAll,'all','custom'));
		
		//script below is similar to the scope in fb_get_default_options_set (fbb/functions.php)
		$fboptions = array();
		foreach ($vbulletin->fbb['user_options_config']['actions'] as $action => $action_dos) {
			$action_dos_array = array();
			foreach ($action_dos as $action_do => $action_do_config) {
				if (empty($action_do_config['depend_on_user_permission'])) {
					$option = 'actions__' . $action . '__' . $action_do;
					$action_dos_array[$option] = fb_option_name($option);
				}
			}
			if (!empty($action_dos_array)) {
				$fboptions[fb_option_name('actions__' . $action)] = $action_dos_array;
			}
		}
		foreach (array('notifications','others') as $category) {
			$category_array = array();
			foreach ($vbulletin->fbb['user_options_config'][$category] as $item => $item_config) {
				if (empty($item_config['depend_on_user_permission'])) {
					$option = $category . '__' . $item;
					$category_array[$option] = fb_option_name($option);
				}
			}
			if (!empty($category_array)) {
				$fboptions[fb_option_name($category)] = $category_array;
			}
		}
		
		print_select_row('<div class="smallfont">' . $vbphrase['setting_fbb_auto_on_options_desc_2nd'] . '</div>','fboptions[]',$fboptions,$old_options,false,10,true);
		
		$handled = true;
	
		break;
	//3.3
	case 'fb_username_patterns':
		print_input_row($description, $name, $setting['value']);
		$handled = true;
		break;
}
?>