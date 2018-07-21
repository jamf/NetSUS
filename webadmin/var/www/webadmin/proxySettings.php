<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "LDAP Proxy";

include "inc/header.php";

// Helper Function
function ldapExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/ldapHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$ldap_running = (trim(ldapExec("getldapproxystatus")) === "true");

?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
				function toggleService() {
					if ($('#proxyenabled').prop('checked')) {
						$('#ldapproxy').removeClass('hidden');
						ajaxPost('proxyCtl.php', 'service=enable');
					} else {
						$('#ldapproxy').addClass('hidden');
						ajaxPost('proxyCtl.php', 'service=disable');
					}
				}

				function toggleDashboard() {
					if ($('#dashboard').prop('checked')) {
						ajaxPost('proxyCtl.php', 'dashboard=true');
					} else {
						ajaxPost('proxyCtl.php', 'dashboard=false');
					}
				}
			</script>

			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
			<div class="row">
				<div class="col-xs-10"> 
					<h2>LDAP Proxy</h2>
				</div>
				<div class="col-xs-2 text-right"> 
					<input type="checkbox" id="proxyenabled" <?php echo ($conf->getSetting("ldapproxy") == "enabled" ? "checked" : ""); ?> data-toggle="toggle" onChange="toggleService();">
				</div>
			</div>

			<div class="row">
				<div class="col-xs-12"> 

					<hr>

					<div style="padding: 12px 0px;" class="description">LDAP PROXY DESCRIPTION</div>

					<div class="checkbox checkbox-primary" style="padding-top: 12px;">
						<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showproxy") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>