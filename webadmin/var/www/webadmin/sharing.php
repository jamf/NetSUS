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

<div class="description">&nbsp;</div>

<h2>File Sharing</h2>

<div class="row">
	<div class="col-xs-12"> 

		<form action="SUS.php" method="post" name="SUS" id="SUS">

			<hr>

			<div style="padding: 12px 0px;" class="description">FILE SHARING DESCRIPTION</div>

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

		</form>

	</div>
</div>

<?php include "inc/footer.php"; ?>