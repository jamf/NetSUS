<?php

session_start();

$noAuthURL="index.php";

if (!($_SESSION['isAuthUser'])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";

	if (isset($_POST['webadminuser'])) {
		if ($_SESSION['username'] == $conf->getSetting("webadminuser")) {
			$_SESSION['username'] = $_POST['webadminuser'];
		}
		$conf->setSetting("webadminuser", $_POST['webadminuser']);
		if ($_POST['webadminuser'] != "webadmin") {
			$conf->changedPass("webaccount");
		}
	}

	if (isset($_POST['confirmold'])) {
		if (hash("sha256", $_POST['confirmold']) == $conf->getSetting("webadminpass")) {
			echo "true";
		} else {
			echo "false";
		}
	}

	if (isset($_POST['webadminpass'])) {
		$conf->setSetting("webadminpass", hash("sha256", $_POST['webadminpass']));
		if ($_POST['webadminpass'] != "webadmin") {
			$conf->changedPass("webaccount");
		}
	}

	if (isset($_POST['ldapserver'])) {
		if ($_POST['ldapserver'] == "") {
			$conf->deleteSetting("ldapserver");
		} else {
			$conf->setSetting("ldapserver", $_POST['ldapserver']);
		}
	}

	if (isset($_POST['ldapdomain'])) {
		if ($_POST['ldapdomain'] == "") {
			$conf->deleteSetting("ldapdomain");
		} else {
			$conf->setSetting("ldapdomain", $_POST['ldapdomain']);
		}
	}

	if (isset($_POST['ldapbase'])) {
		if ($_POST['ldapbase'] == "") {
			$conf->deleteSetting("ldapbase");
		} else {
			$conf->setSetting("ldapbase", $_POST['ldapbase']);
		}
	}

}
?>