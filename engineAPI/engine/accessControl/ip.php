<?php

global $accessControl;
$accessControl['IP'] = "accessControl_ip_checkIPAddr";

function accessControl_ip_checkIPAddr($value,$state=FALSE) {
	
	$remoteAddr = array();
	$remoteAddr = explode(".",$_SERVER['REMOTE_ADDR']);
	
	$ipFound = FALSE;
	
	$ipFound = userInfoIPRangeCheck($value);
	
	if ($state === FALSE && $ipFound === TRUE) {
		// IP in range(s)
		// Deny IPs in the Range(s)
		return(FALSE);
	}
	
	if ($state === TRUE && $ipFound === TRUE) {		
		// IP in range(s)
		// Allow only IPs in the range(s)
		return(TRUE);
	}
	
	if ($state === FALSE && $ipFound === FALSE) {
		// IP Not in range(s)
		// Deny only IPs in the range(s)
		return(FALSE);
	}
	
	if ($state === TRUE && $ipFound === FALSE) {
		// IP NOT in range(s)
		// Allow IP not in range(s)
		return(FALSE);
	}
	
	return(null);
}

?>