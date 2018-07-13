<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "File Sharing";

include "inc/header.php";

$smb_running = (trim(suExec("getsmbstatus")) === "true");
$smb_conns = trim(suExec("smbconns"));

$afp_running = (trim(suExec("getafpstatus")) === "true");
$afp_conns = trim(suExec("afpconns"));

$file_shares = array();
$smb_str = trim(suExec("getSMBshares"));
if ($smb_str != "") {
	foreach(explode("\n", $smb_str) as $value) {
		$share = explode(":", $value);
		$file_shares[$share[1]] = array();
		$file_shares[$share[1]]["name"] = $share[0];
		$file_shares[$share[1]]["smb"] = true;
		$file_shares[$share[1]]["afp"] = false;
	}
}
$afp_str = trim(suExec("getAFPshares"));
if ($afp_str != "") {
	foreach(explode("\n", $afp_str) as $value) {
		$share = explode(":", $value);
		if (isset($file_shares[$share[1]])) {
			$file_shares[$share[1]]["afp"] = true;
		} else {
			$file_shares[$share[1]] = array();
			$file_shares[$share[1]]["name"] = $share[0];
			$file_shares[$share[1]]["smb"] = false;
			$file_shares[$share[1]]["afp"] = true;
		}
	}
}
?>

<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>

<script type="text/javascript">
function toggleSMB(element) {
	var smb_conns = document.getElementById("smb_conns");
	if (element.checked) {
		ajaxPost("ajax.php", "smb=enable");
		var connections = parseInt(ajaxPost('ajax.php', 'smbconns'));
		smb_conns.innerText = "Number of users connected: " + connections;
	} else {
		var connections = parseInt(ajaxPost('ajax.php', 'smbconns'));
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
			ajaxPost("ajax.php", "smb=disable");
			smb_conns.innerText = "File Sharing: Off";
		}
	}
}

function toggleAFP(element) {
	var afp_conns = document.getElementById("afp_conns");
	if (element.checked) {
		ajaxPost("ajax.php", "afp=enable");
		var connections = parseInt(ajaxPost('ajax.php', 'afpconns'));
		afp_conns.innerText = "Number of users connected: " + connections;
	} else {
		var connections = parseInt(ajaxPost('ajax.php', 'afpconns'));
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
			ajaxPost("ajax.php", "afp=disable");
			afp_conns.innerText = "File Sharing: Off";
		}
	}
}
</script>

<script type="text/javascript">
$(document).ready(function(){
	$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
		localStorage.setItem('activeShareTab', $(e.target).attr('href'));
	});
	var activeShareTab = localStorage.getItem('activeShareTab');
	if(activeShareTab){
		$('#top-tabs a[href="' + activeShareTab + '"]').tab('show');
	}
});
</script>

<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
<h2>File Sharing</h2>

<div class="row">
	<div class="col-xs-12"> 

		<hr>

		<div class="checkbox checkbox-primary" style="padding-top: 8px;">
			<input name="sharing_svc" id="sharing_svc" class="styled" type="checkbox" value="true" onChange="">
			<label><strong>Enable File Sharing</strong> <span style="font-size: 75%; color: #777;">DESCRIPTION</span></label>
		</div>

		<div class="service-settings">
			<div class="checkbox checkbox-primary">
				<input name="smbstatus" id="smbstatus" class="styled" type="checkbox" value="true" onChange="toggleSMB(this);" <?php echo ($smb_running ? "checked" : ""); ?>>
				<label><strong>Share files and folders using SMB</strong><br><span id="smb_conns" style="font-size: 75%; color: #777;"><?php echo ($smb_running ? "Number of users connected: ".$smb_conns : "File Sharing: Off"); ?></span></label>
			</div>

			<br>

			<div class="checkbox checkbox-primary">
				<input name="afpstatus" id="afpstatus" class="styled" type="checkbox" value="true" onChange="toggleAFP(this);" <?php echo ($afp_running ? "checked" : ""); ?>>
				<label><strong>Share files and folders using AFP</strong><br><span id="afp_conns" style="font-size: 75%; color: #777;"><?php echo ($afp_running ? "Number of users connected: ".$afp_conns : "File Sharing: Off"); ?></span></label>
			</div>
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
						<button type="button" data-dismiss="modal" class="btn btn-danger btn-sm pull-right" onClick="ajaxPost('ajax.php', 'smb=disable'); document.getElementById('smbstatus').checked = false; smb_conns.innerText = 'File Sharing: Off';">Disable</button>
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
						<button type="button" data-dismiss="modal" class="btn btn-danger btn-sm pull-right" onClick="ajaxPost('ajax.php', 'afp=disable'); document.getElementById('afpstatus').checked = false; afp_conns.innerText = 'File Sharing: Off';">Disable</button>
					</div>
				</div>
			</div>
		</div>
		<!-- /#modal -->

	</div>
</div>

<?php include "inc/footer.php"; ?>