<?php
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
}
?>

<form action="admin.php" method="post" name="DateTimeSettings" id="DateTimeSettings">
<input type="hidden" name="userAction" value="DateTime">
<table style="border: 0px;" class="formLabel">
        <tr>
		<td style="text-align: right;">Current Time: </td>
		<td><?print getLocalTime();?></td>
	</tr>
	<tr>
                <td style="text-align: right;"><label for="timezone">Current Time Zone: </label></td>
		<td><?getSystemTimeZoneMenu();?></td>
	</tr>
	<tr>
		<td style="text-align: right;"><label for="timeserver">Network Time Server:</label></td>
		<td><input type="text" name="timeserver" id="timeserver" value="<?=getCurrentTimeServer();?>" /></td>
	</tr>
</table>
<br>
<input type="submit" value="Save Date/Time Configuration" name="SaveDateTime"/>
</form>
