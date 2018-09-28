<?php

if( isset( $FUNCTIONS ) ) {
	return;
}

$FUNCTIONS=1;

function suExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/adminHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

function getCurrentHostname() {
	return shell_exec("/bin/hostname");
}

function getCurrentWebUser() {
	global $admin_username;
	if (isset($_SESSION['username']))
		return $_SESSION['username'];
	return $admin_username;
}

function getSSHstatus() {
	if (trim(suExec("getSSHstatus")) == "true") {
		return true;
	} else {
		return false;
	}
}

function getFirewallstatus() {
	if (trim(suExec("getFirewallstatus")) == "true") {
		return true;
	} else {
		return false;
	}
}

?>