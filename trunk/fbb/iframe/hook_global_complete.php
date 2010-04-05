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
if (!empty($vbulletin->fbb['runtime']['iframe'])) {
	function fb_iframe_url_replace_callback($matches) {
		global $vbulletin;
	
		$isOurUrl = false;
		$url = $matches[2];
		
		if (substr($url,0,7) != 'http://') {
			//relative url
			$isOurUrl = true;
		} else if (strpos($url,$vbulletin->options['bburl']) === 0) {
			//absolute url
			$isOurUrl = true;
		}
		
		if ($isOurUrl) {
			if (strpos($url,'?') !== false) {
				$url .= '&';
			} else {
				$url .= '?';
			}
			
			$url .= 'fbiframe=1';
		}
		
		return "<a$matches[1]href=\"$url\"";
	}

	$output = preg_replace_callback(
		'/<a([^>]+)href="([^"]+)"/i'
		,'fb_iframe_url_replace_callback'
		,$output
	);
}
?>