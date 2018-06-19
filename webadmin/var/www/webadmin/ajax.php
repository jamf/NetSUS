<?php

session_start();

$noAuthURL="index.php";

if (!($_SESSION['isAuthUser'])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";
	
	if (isset($_POST["getconns"])) {
		$afpconns = trim(suExec("afpconns"));
		$smbconns = trim(suExec("smbconns"));
		$allconns = $afpconns + $smbconns;
		echo $allconns;
	}

	if (isset($_POST["restart"])) {
		// Unset all of the session variables.
		$_SESSION = array();
		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		// Destroy the session.
		session_destroy();
		// Finally, restart
		suExec("restart");
	}

	if (isset($_POST['shutdown'])) {
		// Unset all of the session variables.
		$_SESSION = array();
		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		// Destroy the session.
		session_destroy();
		// Finally, shut down
		suExec("shutdown");
	}

	if (isset($_POST['disablegui'])) {
		$conf->setSetting("webadmingui", "Disabled");
		// Unset all of the session variables.
		$_SESSION = array();
		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		// Finally, destroy the session.
		session_destroy();
	}

	if (isset($_POST['NetBootImage']) && $_GET['service'] = "NetBoot")
	{
		$wasrunning = getNetBootStatus();
		$nbi = $_POST['NetBootImage'];
		if ($nbi != "")
		{
			$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
			$nbsubnets = "";
			foreach($conf->getSubnets() as $key => $value)
			{
				$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
			}
			$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
			suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
			if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
			{
				echo "<div class=\"errorMessage\">ERROR: Unable to update dhcpd.conf</div>";
			 
			}
			suExec("disablenetboot");
			suExec("installdhcpdconf");
		
			if ($wasrunning || isset($_POST['enablenetboot']))
			{
				suExec("setnbimages ".$nbi);
			}
			$conf->setSetting("netbootimage", $nbi);
		}
	}

}
?>