<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "NetBoot";

include "inc/header.php";

// Helper Function
function netbootExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/netbootHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$conf_error = false;
$subnet_error = false;

// Default Image
$default_image = $conf->getSetting("netbootimage");

// Image List
$enabled_images = array();
$netbootdir = "/srv/NetBoot/NetBootSP0";
$netbootdirlist = array_diff(scandir($netbootdir), array("..", "."));
foreach($netbootdirlist as $key) {
	if (is_dir($netbootdir."/".$key) && file_exists($netbootdir."/".$key."/i386/booter")) {
		$nbi_info = json_decode(trim(netbootExec("getNBImageInfo \"".$key."\"")));
		if ($nbi_info->IsEnabled) {
			array_push($enabled_images, $key);
		}
	}
}

// Service Status
$dhcp_running = (trim(netbootExec("getdhcpstatus")) === "true");
if ($dhcp_running) {
	netbootExec("stopdhcp");
	netbootExec("startbsdp");
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
				function toggleService() {
					if ($('#netbootenabled').prop('checked')) {
						$('#netboot').removeClass('hidden');
						ajaxPost('netbootCtl.php', 'service=enable');
					} else {
						$('#netboot').addClass('hidden');
						ajaxPost('netbootCtl.php', 'service=disable');
					}
				}

				function toggleDashboard() {
					if ($('#dashboard').prop('checked')) {
						ajaxPost('netbootCtl.php', 'dashboard=true');
					} else {
						ajaxPost('netbootCtl.php', 'dashboard=false');
					}
				}
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<div class="row">
						<div class="col-xs-10">
							<h2>NetBoot Server</h2>
						</div>
						<div class="col-xs-2 text-right">
							<input type="checkbox" id="netbootenabled" data-toggle="toggle" data-size="small" onChange="toggleService();" <?php echo ($conf->getSetting("netboot") == "enabled" ? "checked" : ""); ?>>
						</div>
					</div>
				</div>
			</nav>

			<div style="padding: 70px 20px 1px; background-color: #f9f9f9;">
				<div class="checkbox checkbox-primary">
					<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("shownetboot") == "false" ? "" : "checked"); ?>>
					<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
				</div>
			</div>

			<hr>
<?php include "inc/footer.php"; ?>