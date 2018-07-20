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

	if (isset($_POST['ldapproxy'])) {
		if ($_POST['ldapproxy'] == "enable") {
			$conf->setSetting("ldapproxy", "enabled");
		} else {
			$conf->setSetting("ldapproxy", "disabled");
		}
	}

	if (isset($_POST['ldapservice'])) {
		if ($_POST['ldapservice'] == "start") {
			ldapExec("enableproxy");
		} else {
			ldapExec("disableproxy");
		}
	}

}
?>