<?php

session_start();

$noAuthURL="index.php";

if (!($_SESSION['isAuthUser'])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";
	
	function netbootExec($cmd) {
		return shell_exec("sudo /bin/sh scripts/netbootHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}

	if (isset($_POST['service'])) {
		if ($_POST['service'] == "enable") {
			$conf->setSetting("netboot", "enabled");
		} else {
			$conf->setSetting("netboot", "disabled");
		}
	}

	if (isset($_POST['dashboard'])) {
		if ($_POST['dashboard'] == "true") {
			$conf->setSetting("shownetboot", "true");
		} else {
			$conf->setSetting("shownetboot", "false");
		}
	}

}
?>