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
global $vbulletin, $vbphrase;

require_once(DIR . '/fbb/functions.php');

$always_display_checkboxes = fb_quick_options_check('others__always_display_checkboxes');

foreach ($vbulletin->fbb['user_options_config']['actions'][$action] AS $action_do => $config) {
	$option_original = $option = 'actions__' . $action . '__' . $action_do;
	
	$option_on_off = fb_quick_options_check($option);
	
	if ($fbb_form_element_id) {
		$option .= '__' . $fbb_form_element_id;
	}
	
	if ($option_on_off OR $always_display_checkboxes) {
		if (!empty($vbulletin->fbb['runtime']['disabled_checkboxes'][$option])) {
			//disabled
			//don't care about everything else anymore
			$checked[$option] = ' disabled="disabled"';
		} else {
			if (empty($_REQUEST['fbb_there_are_checkboxes'])) {
				//first time get in here
				//checkbox get the value based on user configuration
				if ($option_on_off) {
					if (!empty($vbulletin->fbb['runtime']['off_checkboxes'][$option])) {
						//checkbox is forced to be turned off
						$checked[$option] = '';
					} else {
						$checked[$option] = " checked=\"checked\"";
					}
				} else {
					$checked[$option] = '';
				}
			} else {
				//not the first time
				//checkbox get the value from the request
				if (!empty($_REQUEST[$option])) {
					$checked[$option] = " checked=\"checked\"";
				} else {
					$checked[$option] = '';
				}
			}
		}
		
		if ($vbulletin->fbb['runtime']['vb4']) {
			$action_dos_str .= "\t\t\t<li>
				<label for=\"qr_fbb_{$option}\">
					<input type=\"checkbox\" name=\"{$option}\" value=\"1\" id=\"qr_fbb_{$option}\"{$checked[$option]}/>
					" . fb_option_name($option_original) . "
				</label>
			</li>\r\n";
		} else {
			$action_dos_str .= "\t\t\t<div>
				<label for=\"qr_fbb_{$option}\">
					<input type=\"checkbox\" name=\"{$option}\" value=\"1\" id=\"qr_fbb_{$option}\"{$checked[$option]}/>
					" . fb_option_name($option_original) . "
				</label>
			</div>\r\n";
		}
	}
}

if ($action_dos_str) {
	$action_str = iif(isset($vbphrase['fbb_options_action_' . $action]),$vbphrase['fbb_options_action_' . $action],actionFromDashCode($action));
	if (empty($fbb_form_element_target)) {
		$fbb_form_element_target = '$messagearea';
	}
	
	$fbb_newpost_form_template = iif($fbb_newpost_form_template,$fbb_newpost_form_template,'fbb_new_post_options');
	if ($vbulletin->fbb['runtime']['vb4']) {
		$templater = vB_Template::create(str_replace('fbb_','fbb_vb4_',$fbb_newpost_form_template));
			$templater->register('action_str', $action_str);
			$templater->register('action_dos_str', $action_dos_str);
		eval($fbb_form_element_target . ' .= $templater->render(true);');
	} else {
		eval($fbb_form_element_target . ' .= "' . fetch_template($fbb_newpost_form_template,0,false) . '";');
	}
}
?>