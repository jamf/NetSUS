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

$dhcp_running = (trim(netbootExec("getdhcpstatus")) === "true");
$tftp_running = (trim(netbootExec("gettftpstatus")) === "true");
$nfs_running = (trim(netbootExec("getnfsstatus")) === "true");
$afp_running = (trim(netbootExec("getafpstatus")) === "true");

?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
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
							<h2>NetBoot</h2>
						</div>
						<div class="col-xs-2 text-right"> 
							<input type="checkbox" id="netbootenabled" <?php echo ($conf->getSetting("netboot") == "enabled" ? "checked" : ""); ?> data-toggle="toggle" onChange="toggleService();">
						</div>
					</div>
				</div>
			</nav>

			<form action="netbootSettings.php" method="post" name="NetBoot" id="NetBoot">

				<div style="padding: 70px 20px 1px; background-color: #f9f9f9;">
					<div class="checkbox checkbox-primary">
						<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("shownetboot") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>
				</div>

				<hr>

			</form> <!-- end form NetBoot -->
<?php include "inc/footer.php"; ?>