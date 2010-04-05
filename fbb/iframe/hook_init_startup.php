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
if ($_REQUEST['fbiframe'] == 1) {
	$vbulletin->fbb['runtime']['iframe'] = true;

	$target_styleid = 2;
	
	$_REQUEST['styleid'] = $target_styleid;
	$_GET['styleid'] = $target_styleid;
}
?>