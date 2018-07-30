<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "File Sharing";

include "inc/header.php";

// Helper Functions
function shareExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/shareHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$dhcp_running = (trim(shareExec("getdhcpstatus")) === "true");

$smb_running = (trim(shareExec("getsmbstatus")) === "true");
$smb_conns = trim(shareExec("smbconns"));

$afp_running = (trim(shareExec("getafpstatus")) === "true");
$afp_conns = trim(shareExec("afpconns"));
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
				function toggleService() {
					if ($('#sharingenabled').prop('checked')) {
						$('#sharing').removeClass('hidden');
						$('#smbstatus').prop('disabled', false);
						$('#afpstatus').prop('disabled', false);
						ajaxPost('sharingCtl.php', 'service=enable');
					} else {
						$('#sharing').addClass('hidden');
						$('#smbstatus').prop('disabled', true);
						$('#afpstatus').prop('disabled', true);
						ajaxPost('sharingCtl.php', 'service=disable');
					}
				}

				function toggleDashboard() {
					if ($('#sharingdashboard').prop('checked')) {
						ajaxPost('sharingCtl.php', 'dashboard=true');
					} else {
						ajaxPost('sharingCtl.php', 'dashboard=false');
					}
				}

				function toggleSMB(element) {
					var smb_conns = document.getElementById("smb_conns");
					if (element.checked) {
						ajaxPost("sharingCtl.php", "smb=enable");
						var connections = parseInt(ajaxPost('sharingCtl.php', 'smbconns'));
						smb_conns.innerText = "Number of users connected: " + connections;
					} else {
						var connections = parseInt(ajaxPost('sharingCtl.php', 'smbconns'));
						if (connections > 0) {
							if (connections == 1) {
								message = 'is 1 user';
							} else {
								message = 'are ' + connections + ' users';
							}
							document.getElementById('smb-message').innerText = message;
							$('#smb-warning').modal('show');
							element.checked = true;
						} else {
							ajaxPost("sharingCtl.php", "smb=disable");
							smb_conns.innerText = "File Sharing: Off";
						}
					}
				}

				function toggleAFP(element) {
					var afp_conns = document.getElementById("afp_conns");
					if (element.checked) {
						ajaxPost("sharingCtl.php", "afp=enable");
						var connections = parseInt(ajaxPost('sharingCtl.php', 'afpconns'));
						afp_conns.innerText = "Number of users connected: " + connections;
					} else {
						var connections = parseInt(ajaxPost('sharingCtl.php', 'afpconns'));
						if (connections > 0) {
							if (connections == 1) {
								message = 'is 1 user';
							} else {
								message = 'are ' + connections + ' users';
							}
							document.getElementById('afp-message').innerText = message;
							$('#afp-warning').modal('show');
							element.checked = true;
						} else {
							ajaxPost("sharingCtl.php", "afp=disable");
							afp_conns.innerText = "File Sharing: Off";
						}
					}
				}
			</script>

			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
			<div class="row">
				<div class="col-xs-10"> 
					<h2>File Sharing</h2>
				</div>
				<div class="col-xs-2 text-right"> 
					<input type="checkbox" id="sharingenabled" <?php echo ($conf->getSetting("sharing") == "enabled" ? "checked" : ""); ?> data-toggle="toggle" onChange="toggleService();">
				</div>
			</div>

			<div class="row">
				<div class="col-xs-12"> 

					<hr>

					<div class="checkbox checkbox-primary" style="padding-top: 9px;">
						<input name="sharingdashboard" id="sharingdashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showsharing") == "false" ? "" : "checked"); ?>>
						<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
					</div>

					<div class="checkbox checkbox-primary" style="padding-top: 12px">
						<input name="smbstatus" id="smbstatus" class="styled" type="checkbox" value="true" onChange="toggleSMB(this);" <?php echo ($smb_running ? "checked" : ""); ?> <?php echo ($conf->getSetting("sharing") == "enabled" ? "" : "disabled"); ?>>
						<label><strong>Share files and folders using SMB</strong><br><span id="smb_conns" style="font-size: 75%; color: #777;"><?php echo ($smb_running ? "Number of users connected: ".$smb_conns : "SMB Sharing: Off"); ?></span></label>
					</div>

					<div class="checkbox checkbox-primary" style="padding-top: 12px">
						<input name="afpstatus" id="afpstatus" class="styled" type="checkbox" value="true" onChange="toggleAFP(this);" <?php echo ($afp_running ? "checked" : ""); ?> <?php echo ($conf->getSetting("sharing") == "enabled" ? "" : "disabled"); ?>>
						<label><strong>Share files and folders using AFP</strong><br><span id="afp_conns" style="font-size: 75%; color: #777;"><?php echo ($afp_running ? "Number of users connected: ".$afp_conns : "AFP Sharing: Off"); ?></span></label>
					</div>

					<!-- SMB Warning Modal -->
					<div class="modal fade" id="smb-warning" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h3 class="modal-title">Disable SMB</h3>
								</div>
								<div class="modal-body">
									<div style="padding: 8px 0px;">Are you sure you want to disable SMB?</div>
									<div class="text-muted" style="padding: 8px 0px;"><span class="glyphicon glyphicon-exclamation-sign"></span> There <span id="smb-message">users</span> connected to this server.</div>
								</div>
								<div class="modal-footer">
									<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
									<button type="button" data-dismiss="modal" class="btn btn-danger btn-sm pull-right" onClick="ajaxPost('sharingCtl.php', 'smb=disable'); document.getElementById('smbstatus').checked = false; smb_conns.innerText = 'File Sharing: Off';">Disable</button>
								</div>
							</div>
						</div>
					</div>
					<!-- /#modal -->

					<!-- AFP Warning Modal -->
					<div class="modal fade" id="afp-warning" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h3 class="modal-title">Disable AFP</h3>
								</div>
								<div class="modal-body">
									<div style="padding: 8px 0px;">Are you sure you want to disable AFP?</div>
									<div class="text-muted" style="padding: 8px 0px;"><span class="glyphicon glyphicon-exclamation-sign"></span> There <span id="afp-message">users</span> connected to this server.</div>
								</div>
								<div class="modal-footer">
									<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
									<button type="button" data-dismiss="modal" class="btn btn-danger btn-sm pull-right" onClick="ajaxPost('sharingCtl.php', 'afp=disable'); document.getElementById('afpstatus').checked = false; afp_conns.innerText = 'File Sharing: Off';">Disable</button>
								</div>
							</div>
						</div>
					</div>
					<!-- /#modal -->

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>