<?php

session_start();

$noAuthURL="index.php";

if (!($_SESSION['isAuthUser'])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";
	
	function ldapExec($cmd) {
		return shell_exec("sudo /bin/sh scripts/ldapHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}

	if (isset($_POST['service'])) {
		if ($_POST['service'] == "enable") {
			$conf->setSetting("ldapproxy", "enabled");
		} else {
			$conf->setSetting("ldapproxy", "disabled");
		}
	}

	if (isset($_POST['slapd'])) {
		if ($_POST['slapd'] == "enable") {
			ldapExec("enableproxy");
		} else {
			ldapExec("disableproxy");
		}
	}

	if (isset($_POST['dashboard'])) {
		if ($_POST['dashboard'] == "true") {
			$conf->setSetting("showproxy", "true");
		} else {
			$conf->setSetting("showproxy", "false");
		}
	}

}
?>