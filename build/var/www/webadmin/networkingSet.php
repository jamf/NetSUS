<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

if (isset($_POST['hostname']))
{
	setHostName($_POST['hostname']);
}

$status = "";
if (isset($_POST['nettype']))
{
	$type = $_POST['nettype'];
	if ($type == "dhcp")
	{
		suExec("setdhcp");
	}
	else // static
	{
		//address netmask gateway
		suExec("setip ".$_POST['ip']." ".$_POST['netmask']." ".$_POST['gateway']);
		suExec("setdns ".$_POST['dns1']." ".$_POST['dns2']);
	}
	$status = "<p><b>Configuration saved!</b></p>";
}

$type = getNetType();
$dns = getCurrentNameServers();

$jsscriptfiles = "<script type=\"text/javascript\" src=\"scripts/networkingSet.js\"></script>";
$onloadjs = "onLoadStaticOptionsToggle('$type');";
$title = "Network Configuration";
include "inc/header.php";
?>
<center><?= $status ?>
<form action="networkingSet.php" method="post" name="networkingSet"><br />
<br />
<table style="border: 0px;" class="formLabel">
	<tr>
		<td style="text-align: right;"><label for="hostname">Hostname:</label>
		</td>
		<td><input type="text" name="hostname" id="hostname"
			value="<?= getCurrentHostname(); ?>" /></td>
	</tr>
	<tr>
		<td style="text-align: right;">Type:</td>
		<td><label for="dhcp">DHCP</label><input type="radio" name="nettype"
			value="dhcp" id="dhcp"
			<?= ($type=="dhcp"?" checked=\"checked\"":"") ?>
			onclick="disableStaticOptions(true);" /> <label for="static">Static</label><input
			type="radio" name="nettype" value="static" id="static"
			<?= ($type=="static"?" checked=\"checked\"":"") ?>
			onclick="disableStaticOptions(false);" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td></td>
	</tr>
	<tr>
		<td>Static Options</td>
		<td></td>
	</tr>
	<tr>
		<td colspan="2">
		<hr />
		</td>
	</tr>
	<tr>
		<td style="text-align: right;"><label for="ip"
			style="text-align: right;">IP:</label></td>
		<td><input type="text" name="ip" id="ip"
			value="<?= getCurrentIP(); ?>" /></td>
	</tr>
	<tr>
		<td style="text-align: right;"><label for="netmask">Netmask:</label></td>
		<td><input type="text" name="netmask" id="netmask"
			value="<?= getCurrentNetmask(); ?>" /></td>
	</tr>
	<tr>
		<td style="text-align: right;"><label for="netmask">Gateway:</label></td>
		<td><input type="text" name="gateway" id="gateway"
			value="<?= getCurrentGateway(); ?>" /></td>
	</tr>
	<tr>
		<td style="text-align: right;"><label for="dns1">DNS Server 1:</label>
		</td>
		<td><input type="text" name="dns1" id="dns1" value="<?= $dns[0]; ?>" />
		</td>
	</tr>
	<tr>
		<td style="text-align: right;"><label for="dns2">DNS Server 2:</label>
		</td>
		<td><input type="text" name="dns2" id="dns2" value="<?= $dns[1]; ?>" />
		</td>
	</tr>
</table>
<br />
<br />
<input type="submit" value="Save Network Configuration" /></form>
</center>
<?php
include "inc/footer.php";        
?>

</body>
</html>
