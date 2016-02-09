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
	echo "<div class=\"alert alert-success alert-margin-top\">Configuration saved.</div>";
}

?>

<h2>Date/Time</h2>

<ul class="nav nav-tabs"></ul>

<div id="form-wrapper">

	<form action="dateTime.php" method="post" name="DateTimeSettings" id="DateTimeSettings">
		<input type="hidden" name="userAction" value="DateTime">

		<span class="label label-short">Current Time</span>
		<span class="description">Current time on the NetBoot/SUS/LDAP Proxy server</span>
		<span><?php print getLocalTime();?></span>
		<br>

		<span class="label label-short">Current Time Zone</span>
		<span class="description">Current time zone on the NetBoot/SUS/LDAP Proxy server</span>
		<span><?php echo getSystemTimeZoneMenu();?></span>
		<br>

		<span class="label label-short">Network Time Server</span>
		<span class="description">Server to use to synchronize the date/time (e.g. "pool.ntp.org")</span>
		<input type="text" name="timeserver" id="timeserver" value="<?php echo getCurrentTimeServer();?>" />

		<br>
		<br>

		<input type="submit" class="btn btn-sm btn-primary" value="Save" name="SaveDateTime"/>

	</form>

	<br>

	<ul class="nav nav-tabs"></ul>

	<br>

	<input type="button" id="back-button" name="action" class="btn btn-sm btn-primary" value="Back" onclick="document.location.href='settings.php'">

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>