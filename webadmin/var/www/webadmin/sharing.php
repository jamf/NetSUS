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

<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
<h2>File Sharing</h2>

<div class="row">
	<div class="col-xs-12"> 

		<hr>
		<br>

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
</div>

<?php include "inc/footer.php"; ?>
