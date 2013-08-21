<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Network";

include "inc/header.php";

//Save the new network settings if the "SaveNetwork" button was clicked
if (isset($_POST['SaveNetwork']))
{
	if (isset($_POST['hostname']))
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
			//address netmask gateway
			suExec("setip ".$_POST['ip']." ".$_POST['netmask']." ".$_POST['gateway']);
			suExec("setdns ".$_POST['dns1']." ".$_POST['dns2']);
		}
		echo "<div class=\"successMessage\">Configuration saved.</div>";
	}
}

if (isset($_POST['SSH']))
{

	suExec("enableSSH");
	echo "<div class=\"successMessage\">SSH Enabled.</div>";

}

$type = getNetType();
$dns = getCurrentNameServers();

?>

<script>
window.onload=disableStaticOptions("<?php echo $type ?>");
</script>

<h2>Network</h2>

<div id="form-wrapper">

	<form action="networkSettings.php" method="post" name="NetworkSettings" id="NetworkSettings">

		<div id="form-inside">
			<input type="hidden" name="userAction" value="Network">

			<span class="label">Hostname</span>
			<input type="text" name="hostname" id="hostname" value="<?php echo getCurrentHostname(); ?>" />
			<br>

			<span class="label">Type</span>
<!-- 			<select onchange="disableStaticOptions(this.value);" name="selectedNetType">
				<option name="nettype" value="dhcp" id="dhcp" <?php echo ($type=="dhcp"?" selected=\"selected\"":"") ?>>DHCP</option>
				<option name="nettype" value="static" id="static" <?php echo ($type=="static"?" selected=\"selected\"":"") ?>>Static</option>
			</select> -->


			<span class="label"><input type="radio" name="nettype" value="dhcp" id="dhcp" <?php echo ($type=="dhcp"?" checked=\"checked\"":"") ?> onclick="disableStaticOptions(this.value);" />DHCP</span>
			<span class="label"><input type="radio" name="nettype" value="static" id="static" <?php echo ($type=="static"?" checked=\"checked\"":"") ?> onclick="disableStaticOptions(this.value);" />Static</span>


			<span class="label">IP Address</span>
			<input type="text" name="ip" id="ip" value="<?php echo getCurrentIP(); ?>" />
			<br>

			<span class="label">Netmask</span>
			<input type="text" name="netmask" id="netmask" value="<?php echo getCurrentNetmask(); ?>" />
			<br>

			<span class="label">Gateway</span>
			<input type="text" name="gateway" id="gateway" value="<?php echo getCurrentGateway(); ?>" />
			<br>

			<span class="label">DNS Server 1</span>
			<input type="text" name="dns1" id="dns1" value="<?php echo $dns[0]; ?>" />
			<br>

			<span class="label">DNS Server 2</span>
			<input type="text" name="dns2" id="dns2" value="<?php echo $dns[1]; ?>" />
			<br>
			
			<input type="submit" class="insideActionButton" value="Save" name="SaveNetwork"/>
			<br>
			<br>
			<input type="submit" class="insideActionButton" value="Enable SSH" name="SSH"/>

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='settings.php'">

			</div>

		</div>

	</form> <!-- end network settings form -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
