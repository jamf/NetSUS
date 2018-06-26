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
var smbConns = <?php echo $smb_conns; ?>;
var afpConns = <?php echo $afp_conns; ?>;

function toggleSMB(element) {
	var smb_conns = document.getElementById("smb_conns");
	if (element.checked) {
		ajaxPost("ajax.php", "smb=enable");
		smb_conns.innerText = "Number of users connected: " + smbConns;
	} else {
		ajaxPost("ajax.php", "smb=disable");
		smb_conns.innerText = "File Sharing: Off";
	}
}

function toggleAFP(element) {
	var afp_conns = document.getElementById("afp_conns");
	if (element.checked) {
		ajaxPost("ajax.php", "afp=enable");
		afp_conns.innerText = "Number of users connected: " + afpConns;
	} else {
		ajaxPost("ajax.php", "afp=disable");
		afp_conns.innerText = "File Sharing: Off";
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

		<ul class="nav nav-tabs nav-justified" id="top-tabs">
			<li class="active"><a class="tab-font" href="#protocols-tab" role="tab" data-toggle="tab">Protocols</a></li>
			<li><a class="tab-font" href="#shares-tab" role="tab" data-toggle="tab">Shares</a></li>
		</ul>

		<div class="tab-content">

			<div class="tab-pane active fade in" id="protocols-tab">

				<div style="padding: 8px 0px;" class="description">PROTOCOLS DESCRIPTION</div>

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

			<div class="tab-pane fade in" id="shares-tab">

				<div style="padding: 8px 0px;" class="description">SHARES DESCRIPTION</div>

				<table class="table table-striped">
					<thead>
						<tr>
							<th>SMB</th>
							<th>AFP</th>
							<th>Name</th>
							<th>Path</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($file_shares as $key => $value) { ?>
						<tr>
							<td>
								<div class="checkbox checkbox-primary" style="margin-top: 0;">
									<input type="checkbox" id="" value="smb" onChange="" <?php echo ($value["smb"] ? "checked" : ""); ?> <?php echo ($value["name"] == "NetBoot" ? "disabled" : ""); ?>/>
									<label/>
								</div>
							</td>
							<td>
								<div class="checkbox checkbox-primary" style="margin-top: 0;">
									<input type="checkbox" id="" value="smb" onChange="" <?php echo ($value["afp"] ? "checked" : ""); ?> <?php echo ($value["name"] == "NetBoot" ? "disabled" : ""); ?>/>
									<label/>
								</div>
							</td>
							<td><a href="#"><?php echo $value["name"]; ?></a></td>
							<td><?php echo $key; ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>

			</div>

		</div>

	</div>
</div>

<?php include "inc/footer.php"; ?>
