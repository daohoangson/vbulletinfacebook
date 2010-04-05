<?php
/*======================================================================*\
|| #################################################################### ||
|| # Yay! Another Facebook Bridge 3.1
|| # Coded by SonDH
|| # Contact: daohoangson@gmail.com
|| # Check out my page: http://facebook.com/sondh
|| # Last Updated: 04:00 Jan 10th, 2010
|| #################################################################### ||
\*======================================================================*/
function draw_ttftext(&$im, $size, $angle, $x, $y, $color, $fontfile, $text, $shadow = false, $width_limit = false) {
	if (function_exists('imagettftext') AND $fontfile) {
		if ($width_limit !== false) {
			do {
				$box = imagettfbbox($size,$angle,$fontfile,$text);
				$text_width = $box[2] - $box[0];
				if ($text_width > $width_limit) 
					$text = substr($text,0,-1);
			} while ($text_width > $width_limit);
		}
	
		if ($shadow) draw_ttftext($im, $size, $angle, $x + 1, $y + 1, $shadow, $fontfile, $text);
		return imagettftext($im, $size, $angle, $x, $y, $color, $fontfile, $text);
	} else {
		if (imagestring($im,floor(min($size,15)/3),$x,$y - $size - 2,$text,$color)) {
			return true;
		}
	}
}

function draw_user_avatar(&$im, &$user, &$avatar_area_x, &$avatar_area_y, &$avatar_area_w, &$avatar_area_h, $fbusers = array()) {
	global $vbulletin;
	
	$avatar_path = '';
			
	if ($user['fbuid'] AND isset($fbusers[$user['fbuid']])) {
		if ($fbusers[$user['fbuid']]['name'])
			$user['username'] = $fbusers[$user['fbuid']]['name'];
		if ($fbusers[$user['fbuid']]['pic_small']) {
			$user['fb_avatar'] = $fbusers[$user['fbuid']]['pic_small'];
			$tmp = file_get_contents($fbusers[$user['fbuid']]['pic_small']);
			if ($tmp) {
				if ($vbulletin->options['safeupload']) {
					$avatar_path = $vbulletin->options['tmppath'] . '/' . md5(uniqid(microtime()) . $vbulletin->userinfo['userid']);
				} else {
					$avatar_path = tempnam(ini_get('upload_tmp_dir'), 'fbb');
				}
				
				$filenum = @fopen($avatar_path, 'wb');
				@fwrite($filenum, $tmp);
				@fclose($filenum);
				$avatar_ext = 'jpg';
				unset($tmp);
			}
		}
	}
	if (!$avatar_path) {
		if ($vbulletin->options['usefileavatar']) {
			$avatar_path = DIR . '/' . $vbulletin->options['avatarpath'] . '/avatar' . $user['userid'] . '_' . $user['avatarrevision'] . '.gif';
			$avatar_ext = 'gif';
		} else {
			if ($vbulletin->options['safeupload']) {
				$avatar_path = $vbulletin->options['tmppath'] . '/' . md5(uniqid(microtime()) . $vbulletin->userinfo['userid']);
			} else {
				$avatar_path = tempnam(ini_get('upload_tmp_dir'), 'fbb');
			}

			$filenum = @fopen($avatar_path, 'wb');
			@fwrite($filenum, $user['filedata']);
			@fclose($filenum);
			$avatar_ext = trim(substr(strrchr(strtolower($user['filename']), '.'), 1));
		}
	}
	unset($user['filedata']); //clean up memory
	
	if (file_exists($avatar_path)) {
		$avatarinfo = getimagesize($avatar_path);

		$avatar = null;
		if ($avatarinfo) {
			switch($avatarinfo[2]) {
				case 1:
					if (function_exists('imagecreatefromgif')) $avatar = @imagecreatefromgif($avatar_path);
					break;
				case 2:
					if (function_exists('imagecreatefromjpeg')) $avatar = @imagecreatefromjpeg($avatar_path);
					break;
				case 3:
					if (function_exists('imagecreatefrompng')) $avatar = @imagecreatefrompng($avatar_path);
					break;
				case 15:
					if (function_exists('imagecreatefromwbmp')) $avatar = @imagecreatefromwbmp($avatar_path);
					break;
				case 16:
					if (function_exists('imagecreatefromxbm')) $avatar = @imagecreatefromxbm($avatar_path);
					break;
			}
		}
		if ($avatar) {
			$real_w = $avatarinfo[0];
			$real_h = $avatarinfo[1];
			
			if ($real_w/$real_h > $avatar_area_w/$avatar_area_h) {
				$old_h = $avatar_area_h;
				$avatar_area_h = $real_h/$real_w*$avatar_area_w;
				$avatar_area_y = $avatar_area_y + floor(($old_h - $avatar_area_h)/2);
			} else if ($real_w/$real_h < $avatar_area_w/$avatar_area_h) {
				$old_w = $avatar_area_w;
				$avatar_area_w = $real_w/$real_h*$avatar_area_h;
				$avatar_area_x = $avatar_area_x + floor(($old_w - $avatar_area_w)/2);
			}
			
			imagecopyresampled (
				$im
				,$avatar
				,$avatar_area_x
				,$avatar_area_y
				,0 
				,0
				,$avatar_area_w
				,$avatar_area_h
				,$real_w
				,$real_h
			);
		}
		
		@unlink($avatar_path);
	}
	
}
?>