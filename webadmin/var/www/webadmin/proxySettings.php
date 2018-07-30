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
$hostname = trim(getCurrentHostname());
if (trim(suExec("getSSLstatus")) == "true") {
	$ldap_url = "ldaps://".$hostname.":636/";
} else {
	$ldap_url = "ldap://".$hostname.":389/";
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
				var ldap_url = '<?php echo $ldap_url; ?>';
				var proxies = <?php echo sizeof($conf->getProxies()); ?>;

				function toggleService() {
					if ($('#proxyenabled').prop('checked')) {
						$('#ldapproxy').removeClass('hidden');
						ajaxPost('proxyCtl.php', 'service=enable');
						if (proxies > 0) {
							$('#slapd_status').text('Available on your network at ' + ldap_url);
							ajaxPost('proxyCtl.php', 'slapd=enable');
						} else {
							$('#slapd_info').removeClass('hidden');
							$('#slapd_status').text('LDAP: Not Running');
						}
					} else {
						$('#ldapproxy').addClass('hidden');
						ajaxPost('proxyCtl.php', 'service=disable');
						ajaxPost('proxyCtl.php', 'slapd=disable');
						$('#slapd_status').text('LDAP: Off');
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

					<hr>

					<div id="slapd_status" style="padding-top: 12px;" class="description"><?php echo ($ldap_running ? "Available on your network at ".$ldap_url : ($conf->getSetting("ldapproxy") == "enabled" ? "LDAP: Not Running" : "LDAP: Off")); ?></div>

					<div id="slapd_info" class="<?php echo ($conf->getSetting("ldapproxy") != "enabled" || sizeof($conf->getProxies()) > 0 ? "hidden" : ""); ?>">
						<div class="text-info"><span class="glyphicon glyphicon-info-sign"></span> LDAP service will start when a proxy configuration is added.</div>
					</div>

					<div id="slapd_error" class="<?php echo (!$ldap_running && $conf->getSetting("ldapproxy") == "enabled" && sizeof($conf->getProxies()) > 0 ? "" : "hidden"); ?>">
						<div class="text-danger"><span class="glyphicon glyphicon-exclamation-sign"></span> Error in proxy configuration.</div>
					</div>

					<div style="padding-top: 12px;" class="checkbox checkbox-primary">
						<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showproxy") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>