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
if (isset($this->registry->fbb['runtime']['actions__upload_image__publish_photo'])) {
	$tmp_path = $this->registry->fbb['runtime']['actions__upload_image__publish_photo']['location'];
	
	require_once(DIR . '/includes/class_image.php');
	$image =& vB_Image::fetch_library($this->registry);
	$imageinfo = $image->fetch_image_info($tmp_path);
	if ($imageinfo[0] > 604 OR $imageinfo[1] > 604)
	{
		//scale image for Facebook best display
		
		$filename = 'file.' . ($imageinfo[2] == 'JPEG' ? 'jpg' : strtolower($imageinfo[2]));
		$thumbnail = $image->fetch_thumbnail($filename, $tmp_path, 604, 604);
		if ($thumbnail['filedata'])
		{
			@unlink($tmp_path);
			
			if ($this->registry->options['safeupload']) {
				$tmp_path = $this->registry->options['tmppath'] . '/' . md5(uniqid(microtime()) . $this->registry->userinfo['userid']);
			} else {
				$tmp_path = tempnam(ini_get('upload_tmp_dir'), 'fbb');
			}
			
			$filenum = @fopen($tmp_path, 'wb');
			@fwrite($filenum, $thumbnail['filedata']);
			@fclose($filenum);
		}
	}
	
	if (file_exists($tmp_path)) {
		require_once(DIR . '/fbb/functions.php');
		global $vbphrase;
		
		$caption_phrase_name = 'fbb_template_upload_image_caption';
		if (isset($this->registry->fbb['runtime']['actions__upload_image__publish_photo']['type'])) {
			$caption_phrase_name .= '_' . $this->registry->fbb['runtime']['actions__upload_image__publish_photo']['type'];
		}
		
		fb_photos_upload(
			$tmp_path
			, null
			, construct_phrase($vbphrase[$caption_phrase_name]
				,$this->registry->options['bbtitle']
				,$this->registry->options['bburl'] . '/member.php?u=' . $this->registry->userinfo['userid']
			)
		);
		
		@unlink($tmp_path);
	}
}
?>