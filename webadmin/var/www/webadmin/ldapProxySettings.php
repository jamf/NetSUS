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
						$('#ldapproxystatus').prop('disabled', false);
						ajaxPost('ldapProxyCtl.php', 'ldapproxy=enable');
					} else {
						$('#ldapproxy').addClass('hidden');
						$('#ldapproxystatus').prop('disabled', true);
						ajaxPost('ldapProxyCtl.php', 'ldapproxy=disable');
					}
				}
				
				function toggleDashboard() {
					if ($('#proxydashboard').prop('checked')) {
						ajaxPost('ldapProxyCtl.php', 'showproxy=true');
					} else {
						ajaxPost('ldapProxyCtl.php', 'showproxy=false');
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

					<div class="checkbox checkbox-primary" style="padding-top: 12px;">
						<input name="proxydashboard" id="proxydashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showproxy") == "true" ? "checked" : ""); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>