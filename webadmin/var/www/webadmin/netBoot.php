<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "NetBoot Server";

include "inc/header.php";

// Helper Function
function netbootExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/netbootHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$subnet_error = false;
$nfs_error = false;
$nbi_warning = false;
$netbootdir = "/srv/NetBoot/NetBootSP0";
$default_image = $conf->getSetting("netbootimage");

/* // Start DHCP
if (!empty($_POST['startdhcp'])) {
	netbootExec("startdhcp");
} */

// Start BSDP
if (!empty($_POST['startbsdp'])) {
	netbootExec("startbsdp");
}

// Start TFTP
if (!empty($_POST['starttftp'])) {
	netbootExec("starttftp");
}

// Start NFS
if (!empty($_POST['startnfs'])) {
	netbootExec("startnfs");
}

// Start AFP
if (!empty($_POST['startafp'])) {
	netbootExec("startafp");
}

// Delete Image
if (isset($_POST['deletenbi'])) {
	netbootExec("deleteNBI \"".$netbootdir."/".$_POST['deletenbi']."\"");
}

// Save Image Settings
if (isset($_POST['savenbi'])) {
	netbootExec("setNBIproperty \"".$_POST['savenbi']."\" Name \"".$_POST['Name']."\"");
	netbootExec("setNBIproperty \"".$_POST['savenbi']."\" Description \"".$_POST['Description']."\"");
	netbootExec("setNBIproperty \"".$_POST['savenbi']."\" Type ".$_POST['Type']);
	netbootExec("setNBIproperty \"".$_POST['savenbi']."\" Index ".$_POST['Index']);
	if ($_POST['SupportsDiskless'] == "true") {
		netbootExec("setNBIproperty \"".$_POST['savenbi']."\" SupportsDiskless true");
	} else {
		netbootExec("setNBIproperty \"".$_POST['savenbi']."\" SupportsDiskless false");
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

// Subnet Check
$subnets = $conf->getSubnets();
$currentIP = trim(getCurrentIP());
$currentNetmask = trim(getCurrentNetmask());
$currentNetwork = trim(getNetAddress($currentIP, $currentNetmask));
$currentSubnet = array("subnet" => $currentNetwork, "netmask" => $currentNetmask);
if (!in_array($currentSubnet, $subnets)) {
	$subnet_error = true;
}

// Service Status
$dhcp_running = (trim(netbootExec("getdhcpstatus")) === "true");
if ($dhcp_running) {
	netbootExec("stopdhcp");
	netbootExec("startbsdp");
}
$bsdp_running = (trim(netbootExec("getbsdpstatus")) === "true");
$tftp_running = (trim(netbootExec("gettftpstatus")) === "true");
$nfs_running = (trim(netbootExec("getnfsstatus")) === "true");
$afp_running = (trim(netbootExec("getafpstatus")) === "true");

// Image List
$nbi_list = array();
$nbi_indexes = array();
$netbootdirlist = array_diff(scandir($netbootdir), array("..", "."));
foreach($netbootdirlist as $key) {
	if (is_dir($netbootdir."/".$key) && file_exists($netbootdir."/".$key."/i386/booter")) {
		$nbi_list[$key] = json_decode(trim(netbootExec("getNBImageInfo \"".$key."\"")));
		if ($key != $default_image) {
			$nbi_list[$key]->IsDefault = false;
		}
		if ($nbi_list[$key]->Type == "NFS" && !$nfs_running) {
			$nfs_error = true;
			if ($nbi_list[$key]->IsDefault) {
				$nbi_list[$key]->IsDefault = false;
				// netbootExec("stopdhcp");
				// $dhcp_running = false;
				netbootExec("setNBIproperty \"".$key."\" IsDefault false");
				$default_image = "";
				$conf->deleteSetting("netbootimage");
			}
		}
		if (isset($nbi_list[$key]->SupportsDiskless)) {
			array_push($nbi_indexes, $nbi_list[$key]->Index);
		} else {
			$nbi_warning = true;
		}
	}
}
if (!array_key_exists($default_image, $nbi_list) || $nbi_list[$default_image]->IsEnabled != true) {
	$default_image = "";
	$conf->deleteSetting("netbootimage");
	// netbootExec("stopdhcp");
	// $dhcp_running = false;
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />

			<style>
				.checkbox-error {
					margin-top: 1px;
					margin-left: -20px;
					font-size: 17px;
				}
				@media(min-width:768px) {
					.checkbox-error {
						margin-left: -5px;
					}
				}
			</style>

			<script type="text/javascript">
				/* function startDHCP() {
					$('#startdhcp').val('true');
					$('#NetBoot').submit();
				} */

				function startBSDP() {
					$('#startbsdp').val('true');
					$('#NetBoot').submit();
				}

				function startTFTP() {
					$('#starttftp').val('true');
					$('#NetBoot').submit();
				}

				function startNFS() {
					$('#startnfs').val('true');
					$('#NetBoot').submit();
				}

				function startAFP() {
					$('#startafp').val('true');
					$('#NetBoot').submit();
				}

				function showError(element, labelId = false) {
					element.parentElement.classList.add("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.add("text-danger");
					}
				}

				function hideError(element, labelId = false) {
					element.parentElement.classList.remove("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.remove("text-danger");
					}
				}

				function toggleEnabled(element) {
					if (element.checked) {
						$('input[name="Default"][value="' + element.value + '"]').prop('disabled', false);
						ajaxPost('netbootCtl.php', 'setenabled='+element.value);
					} else {
						if ($('input[name="Default"][value="' + element.value + '"]').prop('checked') == true) {
							// ajaxPost('netbootCtl.php', 'dhcp=stop');
							$('input[name="Default"][value="' + element.value + '"]').prop('checked', false);
							ajaxPost('netbootCtl.php', 'setdefaultoff='+element.value);
							$('#service_info').removeClass('hidden');
						}
						$('input[name="Default"][value="' + element.value + '"]').prop('disabled', true);
						ajaxPost('netbootCtl.php', 'setdisabled='+element.value);
					}
				}

				function toggleDefault(element) {
					var checked = element.checked;
					// ajaxPost('netbootCtl.php', 'dhcp=stop');
					elements = document.getElementsByName('Default');
					for (i = 0; i < elements.length; i++) {
						elements[i].checked = false;
						ajaxPost('netbootCtl.php', 'setdefaultoff='+elements[i].value);
					}
					element.checked = checked;
					if (checked) {
						ajaxPost('netbootCtl.php', 'setdefault='+element.value);
						// ajaxPost('netbootCtl.php', 'dhcp=start');
						$('#service_info').addClass('hidden');
					} else {
						$('#service_info').removeClass('hidden');
					}
				}

				function nbiSettings(nbi) {
					json_str = ajaxPost('netbootCtl.php', 'getnbimageinfo='+nbi);
					imageinfo = JSON.parse(json_str);
					$('#savenbi').val(nbi);
					$('#Name').val(imageinfo.Name);
					$('#Description').val(imageinfo.Description);
					$('#Type').val(imageinfo.Type);
					$('#Index').val(imageinfo.Index);
					$('#ExistingIndex').val(imageinfo.Index);
					$('#SupportsDiskless').prop('checked', imageinfo.SupportsDiskless);
					validSettings();
				}

				function validSettings() {
					var existingIndexes = [<?php echo (empty($nbi_list) ? "" : implode(', ', $nbi_indexes)); ?>];
					var ExistingIndex = document.getElementById('ExistingIndex');
					var Name = document.getElementById('Name');
					var Description = document.getElementById('Description');
					var Type = document.getElementById('Type');
					var Index = document.getElementById('Index');
					if (/^([A-Za-z0-9 ._-]){1,255}$/.test(Name.value)) {
						hideError(Name, 'Name_label');
					} else {
						showError(Name, 'Name_label');
					}
					if (/^.{0,255}$/.test(Description.value)) {
						hideError(Description, 'Description_label');
					} else {
						showError(Description, 'Description_label');
					}
					if (Type.value == "HTTP" || Type.value == "NFS") {
						hideError(Type, 'Type_label');
					} else {
						showError(Type, 'Type_label');
					}
					if (Index.value == parseInt(Index.value) && (existingIndexes.indexOf(parseInt(Index.value)) == -1 || Index.value == ExistingIndex.value) && Index.value > 0 && Index.value < 4096) {
						hideError(Index, 'Index_label');
					} else {
						showError(Index, 'Index_label');
					}
					if (/^([A-Za-z0-9 ._-]){1,255}$/.test(Name.value) && /^.{0,255}$/.test(Description.value) && (Type.value == "HTTP" || Type.value == "NFS") && Index.value == parseInt(Index.value) && (existingIndexes.indexOf(parseInt(Index.value)) == -1 || Index.value == ExistingIndex.value) && Index.value > 0 && Index.value < 4096) {
						$('#savenbi').prop('disabled', false);
					} else {
						$('#savenbi').prop('disabled', true);
					}
				}

				//Ensure all inputs have values before enabling the add button
				$(document).ready(function () {
					$('#Name, #Description, #Type, #Index').focus(validSettings);
					$('#Name, #Description, #Index').keyup(validSettings);
					$('#Type').change(validSettings);
					$('#Name, #Description, #Type, #Index').blur(validSettings);
				});
			</script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#settings').attr('onclick', 'document.location.href="netbootSettings.php"');
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description">&nbsp;</div>
					<div class="row">
						<div class="col-xs-10">
							<h2>NetBoot Server</h2>
						</div>
						<div class="col-xs-2 text-right">
							<!-- <button type="button" class="btn btn-default btn-sm" >Settings</button> -->
						</div>
					</div>
				</div>
			</nav>

			<form action="netBoot.php" method="post" name="NetBoot" id="NetBoot">

				<div style="padding: 79px 20px 1px; background-color: #f9f9f9; overflow-x: auto;">
					<!-- <div id="subnet_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($subnet_error ? ($conf->getSetting("pybsdp") == "true" ? "hidden" : "") : "hidden"); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>Ensure you have added a Subnet that includes the IP address of the NetBoot server. <a href="netbootSettings.php">Click here to resolve this</a>.</div>
						</div>
					</div> -->

					<!-- <div id="bsdp_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($conf->getSetting("pybsdp") == "true" ? ($bsdp_running ? "hidden" : "") : "hidden"); ?>"> -->
					<div id="bsdp_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($bsdp_running ? "hidden" : ""); ?>">
						<div class="panel-body">
							<input type="hidden" id="startbsdp" name="startbsdp" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The BSDP service is not running. <a href="" onClick="startBSDP();">Click here to start it</a>.</div>
						</div>
					</div>

					<!-- <div id="dhcp_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($dhcp_running || $default_image == "" || sizeof($subnets) == 0 ? "hidden" : ""); ?>">
						<div class="panel-body">
							<input type="hidden" id="startdhcp" name="startdhcp" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The DHCP service is not running. <a href="" onClick="startDHCP();">Click here to start it</a>.</div>
						</div>
					</div> -->

					<div id="tftp_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($tftp_running ? "hidden" : ""); ?>">
						<div class="panel-body">
							<input type="hidden" id="starttftp" name="starttftp" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The TFTP service is not running. <a href="" onClick="startTFTP();">Click here to start it</a>.</div>
						</div>
					</div>

					<div id="nfs_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($nfs_error ? "" : "hidden"); ?>">
						<div class="panel-body">
							<input type="hidden" id="startnfs" name="startnfs" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The NFS service is not running. <a href="" onClick="startNFS();">Click here to start it</a>.</div>
						</div>
					</div>

					<div id="afp_warning" style="margin-top: 0px; margin-bottom: 16px; border-color: #eea236;" class="panel panel-warning <?php echo ($afp_running ? "hidden" : ""); ?>">
						<div class="panel-body">
							<input type="hidden" id="startafp" name="startafp" value="">
							<div class="text-muted"><span class="text-warning glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The AFP service is not running, diskless functionality is unavailable. <a href="" onClick="startAFP();">Click here to start it</a>.</div>
						</div>
					</div>

					<div id="nbi_warning" style="margin-top: 0px; margin-bottom: 16px; border-color: #eea236;" class="panel panel-warning <?php echo ($nbi_warning ? "" : "hidden"); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-warning glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>Unable to read NBImageInfo.plist. Edit the Image to correct this issue.</div>
						</div>
					</div>

					<!-- <div id="service_info" style="margin-top: 0px; margin-bottom: 16px;" class="panel panel-primary <?php echo ($dhcp_running || $default_image != "" || $conf->getSetting("pybsdp") == "true" ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-info glyphicon glyphicon-info-sign" style="padding-right: 12px;"></span>The NetBoot service will start when the default NetBoot Image is set.</div>
						</div>
					</div> -->

					<div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
						<div class="row">
							<div class="col-sm-10">
								<div class="dataTables_filter">
									<h5><strong>Netboot Images</strong> <small>Refresh this page after uploading a NetBoot image.<br><strong>Note:</strong> The NetBoot folder name cannot contain spaces.</small></h5>
								</div>
							</div>
							<div class="col-sm-2">
								<div class="dataTables_paginate">
									<div class="btn-group">
										<button type="button" id="uploadnbi" class="btn btn-primary btn-sm" onClick="ajaxPost('sharingCtl.php', 'smb=enable'); window.location.assign('smb://smbuser@<?php echo $currentIP; ?>/NetBoot');"><span class="glyphicon glyphicon-plus"></span> Upload</button>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-12">
								<table id="nbiTable" class="table table-hover">
									<thead>
										<tr>
											<th>Enable</th>
											<th>Default</th>
											<th>Image</th>
											<th>Name</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
<?php $i = 0;
foreach($nbi_list as $key => $value) { ?>
										<tr>
											<td>
<?php if ($nbi_list[$key]->Type == "NFS" && !$nfs_running) { ?>
												<div class="checkbox checkbox-danger checkbox-inline">
													<span class="text-danger glyphicon glyphicon-exclamation-sign checkbox-error"></span>
												</div>
<?php } elseif (isset($value->SupportsDiskless)) { ?>
												<div class="checkbox checkbox-primary checkbox-inline">
													<input type="checkbox" name="Enabled" id="Enabled[<?php echo $i; ?>]" value="<?php echo $key ?>" onChange="toggleEnabled(this);" <?php echo ($value->IsEnabled == "1" ? "checked" : ""); ?>/>
													<label/>
												</div>
<?php } else { ?>
												<div class="checkbox checkbox-danger checkbox-inline">
													<a href="#nbi-settings" data-toggle="modal" onClick="nbiSettings('<?php echo $key; ?>');"><span class="text-warning glyphicon glyphicon-exclamation-sign checkbox-error"></span></a>
												</div>
<?php } ?>
											</td>
											<td>
												<div class="checkbox checkbox-primary checkbox-inline">
													<input type="checkbox" name="Default" id="Default[<?php echo $i; ?>]" value="<?php echo $key ?>" onChange="toggleDefault(this);" <?php echo ($value->IsEnabled == "1" ? ($value->IsDefault == "1" ? "checked" : ($nbi_list[$key]->Type == "NFS" && !$nfs_running ? "disabled" : "")) : "disabled"); ?>/>
													<label/>
												</div>
											</td>
											<td><a data-toggle="modal" href="#nbi-settings" onClick="nbiSettings('<?php echo $key; ?>');"><?php echo $key; ?></a></td>
											<td><?php echo (isset($value->Name) ? $value->Name : ""); ?></td>
											<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#del-nbi-modal" onClick="$('#del-nbi-name').text('<?php echo $key; ?>'); $('#deletenbi').val('<?php echo $key; ?>');">Delete</button></td>
										</tr>
<?php $i++;
}
if (sizeof($nbi_list) == 0) { ?>
									<tr>
										<td align="center" valign="top" colspan="6" class="dataTables_empty">No data available in table</td>
									</tr>
<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<hr>

				<!-- Image Settings Modal -->
				<div class="modal fade" id="nbi-settings" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Image Settings</h3>
							</div>
							<div class="modal-body">
								<h5 id="Name_label"><strong>Name</strong> <small>This name identifies the image in the Startup Disk preferences pane on client computers.</small></h5>
								<div class="form-group">
									<input type="text" name="Name" id="Name" class="form-control input-sm" placeholder="[Required]" />
								</div>

								<h5 id="Description_label"><strong>Description</strong> <small>Notes or other information to help you characterize the image.</small></h5>
								<div class="form-group">
									<input type="text" name="Description" id="Description" class="form-control input-sm" placeholder="[Optional]" />
								</div>

								<h5 id="Type_label"><strong>Availability</strong> <small>By default, images are available over HTTP.</small></h5>
								<select id="Type" name="Type" class="form-control input-sm">
									<option value="HTTP">HTTP</option>
									<option value="NFS">NFS</option>
								</select>

								<h5 id="Index_label"><strong>Image Index</strong> <small>The image index can be used to distribute load across multiple servers.</small></h5>
								<div class="form-group">
									<input type="hidden" name="ExistingIndex" id="ExistingIndex" value=""/>
									<input type="text" name="Index" id="Index" class="form-control input-sm" placeholder="[1-4095]" value=""/>
								</div>

								<div class="checkbox checkbox-primary checkbox-inline" style="padding-top: 4px;">
									<input name="SupportsDiskless" id="SupportsDiskless" class="styled" type="checkbox" value="true">
									<label><strong>Diskless</strong> <span style="font-size: 75%; color: #777;">Make this image available for diskless booting</span></label>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="savenbi" id="savenbi" class="btn btn-primary btn-sm" value="" disabled>Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /#modal -->

				<!-- Delete Image Modal -->
				<div class="modal fade" id="del-nbi-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Delete <span id="del-nbi-name">Image</span></h3>
							</div>
							<div class="modal-body">
								<div class="text-muted">This action is permanent and cannot be undone.</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="deletenbi" id="deletenbi" class="btn btn-danger btn-sm" value="">Delete</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

			</form> <!-- end form NetBoot -->
<?php include "inc/footer.php"; ?>