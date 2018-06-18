<?php

session_start();

$noAuthURL="index.php";
if (!($_SESSION['isAuthUser']))
{
	echo "Not authorized - please log in";
}
else
{

	include "inc/config.php";
	include "inc/functions.php";
	

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