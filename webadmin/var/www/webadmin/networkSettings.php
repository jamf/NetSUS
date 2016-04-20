<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Network";

include "inc/header.php";

//Save the new network settings if the "SaveNetwork" button was clicked
if (isset($_POST['SaveNetwork']))
{
	if (isset($_POST['hostname']) && isValidHostname($_POST['hostname']))
	{
		setHostName($_POST['hostname']);
	}

	if (isset($_POST['nettype']))
	{
		$type = $_POST['nettype'];
		if ($type == "dhcp")
		{
			suExec("setdhcp");
		}
		else // static
		{
			if (isValidIPAddress($_POST['ip']) && !isLoopbackAddress($_POST['ip'])
			 && getNetAddress($_POST['ip'], $_POST['netmask']) != $_POST['ip']
			 && getBcastAddress($_POST['ip'], $_POST['netmask']) != $_POST['ip']
			 && isValidNetmask($_POST['netmask']) && isValidIPAddress($_POST['gateway'])
			 && !isLoopbackAddress($_POST['gateway'])
			 && getNetAddress($_POST['gateway'], $_POST['netmask']) != $_POST['gateway']
			 && getBcastAddress($_POST['gateway'], $_POST['netmask']) != $_POST['gateway']
			 && $_POST['gateway'] != $_POST['ip'] && isValidIPAddress($_POST['dns1'])
			 && (isValidIPAddress($_POST['dns2']) || $_POST['dns2'] == ""))
			{
				//address netmask gateway
				suExec("setip ".$_POST['ip']." ".$_POST['netmask']." ".$_POST['gateway']);
				suExec("setdns ".$_POST['dns1']." ".$_POST['dns2']);
			}
		}
		echo "<div class=\"alert alert-success\">Configuration saved.</div>";
	}
}

if (isset($_POST['SSH']))
{
	if (getSSHstatus())
	{
		suExec("disableSSH");
		echo "<div class=\"alert alert-warning\">SSH Disabled.</div>";
	}
	else
	{
		suExec("enableSSH");
		echo "<div class=\"alert alert-success\">SSH Enabled.</div>";
	}
}

if (isset($_POST['Firewall']))
{
	if (getFirewallstatus())
	{
		suExec("disableFirewall");
		echo "<div class=\"alert alert-warning\">Firewall Disabled.</div>";
	}
	else
	{
		suExec("enableFirewall");
		echo "<div class=\"alert alert-success\">Firewall Enabled.</div>";
	}
}

$type = getNetType();
$dns = getCurrentNameServers();

?>

<script>
window.onload = function()
{
	document.getElementById('ip').disabled = document.getElementById('dhcp').checked;
	document.getElementById('netmask').disabled = document.getElementById('dhcp').checked;
	document.getElementById('gateway').disabled = document.getElementById('dhcp').checked;
	document.getElementById('dns1').disabled = document.getElementById('dhcp').checked;
	document.getElementById('dns2').disabled = document.getElementById('dhcp').checked;
}
</script>

<h2>Network</h2>

	<form action="networkSettings.php" method="post" name="NetworkSettings" id="NetworkSettings">

		<input type="hidden" name="userAction" value="Network">

		<div class="row">
			<div class="col-xs-12 col-md-8 col-lg-6">
				<hr>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-6 col-sm-6 col-md-4 col-lg-3">

				<label class="control-label">Hostname</label>
				<input type="text" name="hostname" id="hostname" class="form-control input-sm" value="<?php echo getCurrentHostname(); ?>" />

				<label class="control-label">Type</label>
				<!-- <select onchange="disableStaticOptions(this.value);" name="selectedNetType">
					<option name="nettype" value="dhcp" id="dhcp" <?php echo ($type=="dhcp"?" selected=\"selected\"":"") ?>>DHCP</option>
					<option name="nettype" value="static" id="static" <?php echo ($type=="static"?" selected=\"selected\"":"") ?>>Static</option>
				</select> -->

				<div class="radio radio-primary">
					<input type="radio" name="nettype" value="dhcp" id="dhcp" <?php echo ($type=="dhcp"?" checked=\"checked\"":"") ?> onclick="disableStaticOptions(this.value);" />
					<label for="dhcp">DHCP</label>
				</div>

				<div class="radio radio-primary">
					<input type="radio" name="nettype" value="static" id="static" <?php echo ($type=="static"?" checked=\"checked\"":"") ?> onclick="disableStaticOptions(this.value);" />
					<label for="static">Static</label>
				</div>

				<label class="control-label">IP Address</label>
				<input type="text" name="ip" id="ip"  class="form-control input-sm" value="<?php echo getCurrentIP(); ?>" />

				<label class="control-label">Netmask</label>
				<input type="text" name="netmask" id="netmask" class="form-control input-sm" value="<?php echo getCurrentNetmask(); ?>" />

			</div>

			<div class="col-xs-6 col-sm-6 col-md-4 col-lg-3">

				<label class="control-label">Gateway</label>
				<input type="text" name="gateway" id="gateway" class="form-control input-sm" value="<?php echo getCurrentGateway(); ?>" />


				<label class="control-label">DNS Server 1</label>
				<input type="text" name="dns1" id="dns1" class="form-control input-sm" value="<?php if (isset($dns[0])) { echo $dns[0]; } ?>" />


				<label class="control-label">DNS Server 2</label>
				<input type="text" name="dns2" id="dns2" class="form-control input-sm" value="<?php if (isset($dns[1])) { echo $dns[1]; } ?>" />
				<br>

				<input type="submit" class="btn btn-sm <?php if (getSSHstatus()) { echo 'btn-success" value="Disable'; } else { echo'btn-danger" value="Enable'; } ?> SSH" name="SSH"/>
				<br>
				<br>

				<input type="submit" class="btn btn-sm <?php if (getFirewallstatus()) { echo 'btn-success" value="Disable'; } else { echo'btn-danger" value="Enable'; } ?> Firewall" name="Firewall"/>
			</div>
		</div>

		<br>

		<div class="row">
			<div class="col-xs-12 col-md-8 col-lg-6">
				<input type="submit" class="btn btn-primary" value="Save" name="SaveNetwork"/>
				<br>
				<br>
				<hr>
				<br>
				<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">
			</div>
		</div>


	</form> <!-- end network settings form -->

<?php include "inc/footer.php"; ?>
