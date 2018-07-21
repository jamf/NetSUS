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

			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
			<div class="row">
				<div class="col-xs-10"> 
					<h2>NetBoot</h2>
				</div>
				<div class="col-xs-2 text-right"> 
					<input type="checkbox" id="netbootenabled" <?php echo ($conf->getSetting("netboot") == "enabled" ? "checked" : ""); ?> data-toggle="toggle" onChange="toggleService();">
				</div>
			</div>

			<div class="row">
				<div class="col-xs-12"> 

					<hr>

					<div style="padding: 12px 0px;" class="description">NETBOOT DESCRIPTION</div>

					<div class="checkbox checkbox-primary" style="padding-top: 12px;">
						<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("shownetboot") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>