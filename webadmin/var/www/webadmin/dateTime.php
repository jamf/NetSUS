<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Date/Time";

// Script setup.
ini_set('error_reporting', E_ALL);
date_default_timezone_set('UTC');
include 'inc/parser.php';
$local_file = 'images/timezone/tz_map.png';
list($map_width, $map_height) = getimagesize($local_file);
$timezones = timezone_picker_parse_files($map_width, $map_height, 'images/timezone/tz_world.txt', 'images/timezone/tz_islands.txt');

include "inc/header.php";

//Save the date/time settings if the SaveDateTime
if (isset($_POST['savetimeserver'])) {
 	suExec("settimeserver ".$_POST['timeserver']);
}
if (isset($_POST['savetime'])) {
 	suExec("setlocaltime ".$_POST['localtime']);
}
if (isset($_POST['savetimezone'])) {
 	suExec("settimezone ".$_POST['timezone']);
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

$currentServer = trim(suExec("gettimeserver"));
$currentTime = trim(suExec("getlocaltime"));
$currentZone = trim(suExec("gettimezone"));
?>
			<link rel="stylesheet" href="theme/bootstrap-datetimepicker.css" />

			<script type="text/javascript" src="scripts/moment/moment.min.js"></script>
			<script type="text/javascript" src="scripts/bootstrap/transition.js"></script>
			<script type="text/javascript" src="scripts/bootstrap/collapse.js"></script>
			<script type="text/javascript" src="scripts/datetimepicker/bootstrap-datetimepicker.min.js"></script>

			<script type="text/javascript">
				$(function () {
					$('#settime').datetimepicker({
						format: 'ddd MMM DD HH:mm:ss YYYY',
					});
				});
			</script>

			<style>
				#timezone-picker {
					text-align: center;
					overflow-x: auto;
					white-space: nowrap;
					padding-bottom: 8px;
				}
				#timezone-picker div,
				#timezone-picker map {
					margin: auto;
				}
			</style>

			<script type="text/javascript" src="scripts/timezonepicker/jquery.maphilight.min.js"></script>
			<script type="text/javascript" src="scripts/timezonepicker/jquery.timezone-picker.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#timezone-image').timezonePicker({
						target: '#timezone-menu'
					});
					$('#timezone-menu option[value="<?php echo $currentZone; ?>"]').prop('selected', true);
				});
			</script>

			<script type="text/javascript">
				function showError(element, labelId = false) {
					element.parentElement.classList.add("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.add("text-danger");
					}
				}

				function hideError(element, labelId = false) {
					element.parentElement.classList.remove("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.remove("text-danger");
					}
				}

				function validTimeserver() {
					var timeserver = document.getElementById('timeserver');
					if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(timeserver.value)) {
						hideError(timeserver, 'timeserver_label');
						$('#savetimeserver').prop('disabled', false);
					} else {
						showError(timeserver, 'timeserver_label');
						$('#savetimeserver').prop('disabled', true);
					}
				}

				function validTime() {
					var localtime = document.getElementById('localtime');
					if (Date.parse(localtime.value)) {
						hideError(localtime, 'localtime_label');
						$('#savetime').prop('disabled', false);
					} else {
						showError(localtime, 'localtime_label');
						$('#savetime').prop('disabled', true);
					}
				}
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2>Date/Time</h2>
				</div>
			</nav>

			<form action="dateTime.php" method="post" name="DateTime" id="DateTime">

				<div style="padding: 70px 20px 16px; background-color: #f9f9f9;">
					<h5 id="timeserver_label"><strong>Network Time Server</strong> <small>Server to use to synchronize the date/time (e.g. "pool.ntp.org").</small></h5>
					<div class="input-group has-feedback">
						<input type="text" name="timeserver" id="timeserver" class="form-control input-sm" value="<?php echo $currentServer;?>" onFocus="validTimeserver();" onKeyUp="validTimeserver();" onBlur="validTimeserver();"/>
						<span class="input-group-btn">
							<button type="submit" name="savetimeserver" id="savetimeserver" class="btn btn-primary btn-sm" disabled>Save</button>
						</span>
					</div>
				</div>

				<hr>

				<div style="padding: 6px 20px 1px;">
					<h5 id="localtime_label"><strong>Current Time</strong> <small>Current time on the NetSUS server.</small></h5>
					<div class="form-group has-feedback">
						<div class="input-group has-feedback date" id="settime" name="settime">
							<span class="input-group-addon input-sm" style="color: #555; background-color: #eee; border: 1px solid #ccc; border-right: 0;">
								<span class="glyphicon glyphicon-calendar"></span>
							</span>
							<input type="text" id="localtime" name="localtime" class="form-control input-sm" value="<?php echo $currentTime; ?>" onFocus="validTime();" onKeyUp="validTime();" onBlur="validTime();"/>
							<span class="input-group-btn">
								<button type="submit" name="savetime" id="savetime" class="btn btn-primary btn-sm" disabled>Save</button>
							</span>
						</div>
					</div>
				</div>

				<hr>

				<div style="padding: 6px 20px 16px; background-color: #f9f9f9;">
					<h5 id="timezone_label"><strong>Current Time Zone</strong> <small>Current time zone on the NetSUS server.</small></h5>
					<div id="timezone-picker">
						<img id="timezone-image" src="<?php print $local_file; ?>" width="<?php print $map_width; ?>" height="<?php print $map_height; ?>" usemap="#timezone-map" />
						<img class="timezone-pin" src="images/timezone/pin.png" style="padding-top: 4px;" />
						<map name="timezone-map" id="timezone-map">
<?php foreach ($timezones as $timezone_name => $timezone) {
foreach ($timezone['polys'] as $coords) { ?>
							<area data-timezone="<?php print $timezone_name; ?>" data-country="<?php print $timezone['country']; ?>" data-pin="<?php print implode(',', $timezone['pin']); ?>" data-offset="<?php print $timezone['offset']; ?>" shape="poly" coords="<?php print implode(',', $coords); ?>" />
<?php }
foreach ($timezone['rects'] as $coords) { ?>
							<area data-timezone="<?php print $timezone_name; ?>" data-country="<?php print $timezone['country']; ?>" data-pin="<?php print implode(',', $timezone['pin']); ?>" data-offset="<?php print $timezone['offset']; ?>" shape="rect" coords="<?php print implode(',', $coords); ?>" />
<?php }
} ?>
						</map>
					</div>
					<button type="submit" name="savetimezone" id="savetimezone" class="btn btn-primary btn-sm pull-right">Save</button>
					<div style="margin-right: 51px;">
						<select id="timezone-menu" name="timezone" class="form-control input-sm">
							<option value="">Select...</option>
<?php foreach (DateTimeZone::listIdentifiers() as $identifier) { ?>
							<option value="<?php echo $identifier; ?>"><?php echo $identifier; ?></option>
<?php } ?>
						</select>
					</div>
				</div>

				<hr>

			</form> <!-- end form DateTime -->
<?php include "inc/footer.php"; ?>