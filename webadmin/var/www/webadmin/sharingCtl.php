<?php

session_start();

if (!($_SESSION["isAuthUser"])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";

	function shareExec($cmd) {
		return shell_exec("sudo /bin/sh scripts/shareHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}

	if (isset($_POST['enablesmb'])) {
		$enablesmb = explode(":", $_POST['enablesmb']);
		shareExec("addSMBshare \"".$enablesmb[0]."\" \"".$enablesmb[1]."\" ".$enablesmb[2]." ".$enablesmb[3]);
	}

	if (isset($_POST['disablesmb'])) {
		shareExec("delSMBshare ".$_POST['disablesmb']);
	}

	if (isset($_POST['enableafp'])) {
		$enableafp = explode(":", $_POST['enableafp']);
		shareExec("addAFPshare \"".$enableafp[0]."\" \"".$enableafp[1]."\" ".$enableafp[2]." ".$enableafp[3]);
	}

	if (isset($_POST['disableafp'])) {
		shareExec("delAFPshare ".$_POST['disableafp']);
	}

	if (isset($_POST['enablehttp'])) {
		$enablehttp = explode(":", $_POST['enablehttp']);
		shareExec("addHTTPshare \"".$enablehttp[0]."\" \"".$enablehttp[1]."\"");
	}

	if (isset($_POST['disablehttp'])) {
		shareExec("delHTTPshare ".$_POST['disablehttp']);
	}

}

?>