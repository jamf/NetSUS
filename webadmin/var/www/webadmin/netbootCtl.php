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
			if ($conf->getSetting("netbootengine") == "pybsdp") {
				netbootExec("startbsdp");
			} else {
				if ($conf->getSetting("netbootimage") != "" && sizeof($conf->getSubnets() > 0)) {
					netbootExec("startdhcp");
				}
			}
			netbootExec("starttftp");
			netbootExec("startnfs");
			netbootExec("startafp");
		} else {
			$conf->setSetting("netboot", "disabled");
			netbootExec("stopbsdp");
			netbootExec("stopdhcp");
			netbootExec("stoptftp");
			netbootExec("stopnfs");
			if ($conf->getSetting("sharing") != "enabled") {
				netbootExec("stopafp");
				netbootExec("stopsmb");
			}
		}
	}

	if (isset($_POST['dashboard'])) {
		if ($_POST['dashboard'] == "true") {
			$conf->setSetting("shownetboot", "true");
		} else {
			$conf->setSetting("shownetboot", "false");
		}
	}

	if (isset($_POST['engine'])) {
		if ($_POST['engine'] == "pybsdp") {
			netbootExec("stopdhcp");
			netbootExec("startbsdp");
			$conf->setSetting("netbootengine", "pybsdp");
		} else {
			netbootExec("stopbsdp");
			if ($conf->getSetting("netbootimage") != "" && sizeof($conf->getSubnets() > 0)) {
				netbootExec("startdhcp");
			}
			$conf->setSetting("netbootengine", "dhcpd");
		}
	}

	if (isset($_POST['setnbimage'])) {
		netbootExec("setnbimage \"".$_POST['setnbimage']."\"");
	}

	if (isset($_POST['bsdp'])) {
		if ($_POST['bsdp'] == "start") {
			netbootExec("startbsdp");
		} else {
			netbootExec("stopbsdp");
		}
	}

	if (isset($_POST['dhcp'])) {
		if ($_POST['dhcp'] == "start") {
			netbootExec("startdhcp");
		} else {
			netbootExec("stopdhcp");
		}
	}

	if (isset($_POST['tftp'])) {
		if ($_POST['tftp'] == "start") {
			netbootExec("starttftp");
		} else {
			netbootExec("stoptftp");
		}
	}

	if (isset($_POST['nfs'])) {
		if ($_POST['nfs'] == "start") {
			netbootExec("startnfs");
		} else {
			netbootExec("stopnfs");
		}
	}

	if (isset($_POST['afp'])) {
		if ($_POST['afp'] == "start") {
			netbootExec("startafp");
		} else {
			netbootExec("stopafp");
		}
	}

	if (isset($_POST['smb'])) {
		if ($_POST['smb'] == "enable") {
			netbootExec("startsmb");
		} else {
			netbootExec("stopsmb");
		}
	}

	if (isset($_POST['getnbimageinfo'])) {
		echo trim(netbootExec("getNBImageInfo \"".$_POST['getnbimageinfo']."\""));
	}

	if (isset($_POST['setenabled'])) {
		netbootExec("setNBIproperty \"".$_POST['setenabled']."\" IsEnabled true");
	}

	if (isset($_POST['setdisabled'])) {
		netbootExec("setNBIproperty \"".$_POST['setdisabled']."\" IsEnabled false");
	}

	if (isset($_POST['setdefault'])) {
		netbootExec("setNBIproperty \"".$_POST['setdefault']."\" IsDefault true");
		$conf->setSetting("netbootimage", $_POST['setdefault']);
		netbootExec("setnbimage \"".$_POST['setdefault']."\"");
	}

	if (isset($_POST['setdefaultoff'])) {
		netbootExec("setNBIproperty \"".$_POST['setdefaultoff']."\" IsDefault false");
		$conf->deleteSetting("netbootimage");
	}

}
?>