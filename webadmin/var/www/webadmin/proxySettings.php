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
				var proxies = <?php echo sizeof($conf->getProxies()); ?>;

				function toggleService() {
					if ($('#proxyenabled').prop('checked')) {
						$('#ldapproxy').removeClass('hidden');
						ajaxPost('proxyCtl.php', 'service=enable');
						if (proxies > 0) {
							ajaxPost('proxyCtl.php', 'slapd=enable');
						} else {
							$('#slapd_info').removeClass('hidden');
						}
					} else {
						$('#ldapproxy').addClass('hidden');
						ajaxPost('proxyCtl.php', 'service=disable');
						ajaxPost('proxyCtl.php', 'slapd=disable');
						$('#slapd_info').addClass('hidden');
						$('#slapd_error').addClass('hidden');
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

					<div id="slapd_info" class="panel panel-default <?php echo ($conf->getSetting("ldapproxy") != "enabled" || sizeof($conf->getProxies()) > 0 ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-info"><span class="glyphicon glyphicon-info-sign" style="padding-right: 12px;"></span>LDAP service will start when a proxy configuration is added.</div>
						</div>
					</div>

					<div id="slapd_error" class="panel panel-default <?php echo (!$ldap_running && $conf->getSetting("ldapproxy") == "enabled" && sizeof($conf->getProxies()) > 0 ? "" : "hidden"); ?>">
						<div class="panel-body">
							<div class="text-danger"><span class="glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>Error in proxy configuration.</div>
						</div>
					</div>

					<hr>

					<div style="padding-top: 9px;" class="checkbox checkbox-primary">
						<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showproxy") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>