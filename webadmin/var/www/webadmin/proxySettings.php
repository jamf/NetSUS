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

if (!empty($_POST['enableproxy'])) {
	ldapExec("enableproxy");
}

$ldap_running = (trim(ldapExec("getldapproxystatus")) === "true");
if ($conf->getSetting("ldapproxy") == "enabled" && sizeof($conf->getProxies()) > 0 && !$ldap_running) {
	$slapd_error = "The LDAP service is not running. <a href=\"\" onClick=\"enableProxy();\">Click here to start it</a>.";
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
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

				function enableProxy() {
					$('#enableproxy').val('true');
					$('#LDAPProxy').submit();
				}
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<div class="row">
						<div class="col-xs-10">
							<h2>LDAP Proxy</h2>
						</div>
						<div class="col-xs-2 text-right">
							<input type="checkbox" id="proxyenabled" data-toggle="toggle" data-size="small" onChange="toggleService();" <?php echo ($conf->getSetting("ldapproxy") == "enabled" ? "checked" : ""); ?>>
						</div>
					</div>
				</div>
			</nav>

			<form action="proxySettings.php" method="post" name="LDAPProxy" id="LDAPProxy">

				<div style="padding: 70px 20px 1px; background-color: #f9f9f9;">
					<div id="slapd_info" style="margin-top: 9px; margin-bottom: 17px;" class="panel panel-primary <?php echo ($conf->getSetting("ldapproxy") != "enabled" || sizeof($conf->getProxies()) > 0 ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-info glyphicon glyphicon-info-sign" style="padding-right: 12px;"></span>LDAP service will start when a proxy configuration is added.</div>
						</div>
					</div>

					<div id="slapd_error" style="margin-top: 9px; margin-bottom: 17px; border-color: #d43f3a;" class="panel panel-danger <?php echo (empty($slapd_error) ? "hidden" : ""); ?>">
						<div class="panel-body">
							<input type="hidden" id="enableproxy" name="enableproxy" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span><?php echo $slapd_error; ?></div>
						</div>
					</div>

					<div class="checkbox checkbox-primary">
						<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showproxy") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>
				</div>

				<hr>

			</form> <!-- end form LDAPProxy -->
<?php include "inc/footer.php"; ?>