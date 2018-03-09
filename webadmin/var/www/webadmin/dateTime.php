<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Date/Time";

include "inc/header.php";

//Save the date/time settings if the SaveDateTime
if (isset($_POST['SaveDateTime'])) {
	if (isset($_POST['timezone'])) {
        	$tz = $_POST['timezone'];
	        setTimeZone($tz);
	}

	if (isset($_POST['timeserver'])) {
		$ts = $_POST['timeserver'];
		setTimeServer($ts);
	}
	echo "<div class=\"alert alert-success\">Configuration saved.</div>";
}

?>

<script>
function showErr(id, valid)
{
	if (valid || document.getElementById(id).value == "")
	{
		document.getElementById(id).style.borderColor = "";
		document.getElementById(id).style.backgroundColor = "";
	}
	else
	{
		document.getElementById(id).style.borderColor = "#a94442";
		document.getElementById(id).style.backgroundColor = "#f2dede";
	}
}
function enableButton(id, enable)
{
	document.getElementById(id).disabled = !enable;
}

function validateTimeserver()
{
	var validTimeserver = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(document.getElementById("timeserver").value);
	showErr("timeserver", validTimeserver);
	enableButton("SaveDateTime", validTimeserver);
}
</script>

<h2>Date/Time</h2>

<div class="row">
	<div class="col-xs-12 col-sm-6 col-md-4">

		<hr>

		<form action="dateTime.php" method="post" name="DateTimeSettings" id="DateTimeSettings">
			<input type="hidden" name="userAction" value="DateTime">

			<span class="label label-default">Current Time</span>
			<span class="description">Current time on the NetBoot/SUS/LDAP Proxy server</span>
			<span><?php print getLocalTime();?></span>
			<br>

			<span class="label label-default">Current Time Zone</span>
			<span class="description">Current time zone on the NetBoot/SUS/LDAP Proxy server</span>
			<span>
				<select class="form-control input-sm" id="timezone" name="timezone" onClick="validateTimeserver();" onChange="validateTimeserver();">
					<?php echo getSystemTimeZoneMenu();?>
				</select>
			</span>

			<span class="label label-default">Network Time Server</span>
			<span class="description">Server to use to synchronize the date/time (e.g. "pool.ntp.org")</span>
			<input type="text" name="timeserver" id="timeserver" class="form-control input-sm" value="<?php echo getCurrentTimeServer();?>" onClick="validateTimeserver();" onKeyUp="validateTimeserver();" />

			<br>

			<input type="submit" class="btn btn-primary" value="Save" name="SaveDateTime" id="SaveDateTime" disabled="disabled" />

		</form>

		<br>
		<hr>
		<br>
		<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>