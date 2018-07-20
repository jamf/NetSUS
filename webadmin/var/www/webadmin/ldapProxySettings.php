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
				
				function toggleLdap(element) {
					if (element.checked) {
						ajaxPost("ldapProxyCtl.php", "ldapservice=start");
						$('#ldap_status').text('Running')
					} else {
						ajaxPost("ldapProxyCtl.php", "ldapservice=stop");
						$('#ldap_status').text('Not Running')
					}
				}
			</script>

			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>

			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
			<h2>LDAP Proxy</h2>

			<div class="row">
				<div class="col-xs-12"> 

					<hr>

					<div class="checkbox checkbox-primary" style="padding-top: 8px;">
						<input name="proxyenabled" id="proxyenabled" class="styled" type="checkbox" value="true" onChange="toggleService();" <?php echo ($conf->getSetting("ldapproxy") == "enabled" ? "checked" : ""); ?>>
						<label><strong>Enable LDAP Proxy</strong> <span style="font-size: 75%; color: #777;">DESCRIPTION</span></label>
					</div>

					<div class="service-settings">
						<div class="checkbox checkbox-primary">
							<input name="ldapproxystatus" id="ldapproxystatus" class="styled" type="checkbox" value="true" onChange="toggleLdap(this);" <?php echo ($ldap_running ? "checked" : ""); ?> <?php echo ($conf->getSetting("ldapproxy") == "enabled" ? "" : "disabled"); ?>>
							<label><strong>LDAP Server</strong><br><span id="ldap_status" style="font-size: 75%; color: #777;"><?php echo ($ldap_running ? "Running" : "Not Running"); ?></span></label>
						</div>
					</div>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>