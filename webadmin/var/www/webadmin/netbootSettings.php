<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "NetBoot";

include "inc/header.php";

// Helper Function
function netbootExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/netbootHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$conf_error = false;
$subnet_error = false;

// Default Image
$default_image = $conf->getSetting("netbootimage");

// Image List
$enabled_images = array();
$netbootdir = "/srv/NetBoot/NetBootSP0";
$netbootdirlist = array_diff(scandir($netbootdir), array("..", "."));
foreach($netbootdirlist as $key) {
	if (is_dir($netbootdir."/".$key) && file_exists($netbootdir."/".$key."/i386/booter")) {
		$nbi_info = json_decode(trim(netbootExec("getNBImageInfo \"".$key."\"")));
		if ($nbi_info->IsEnabled) {
			array_push($enabled_images, $key);
		}
	}
}

// Service Status
$dhcp_running = (trim(netbootExec("getdhcpstatus")) === "true");
if ($dhcp_running) {
	netbootExec("stopdhcp");
	netbootExec("startbsdp");
}

/* if (isset($_POST['addsubnet'])) {
	$conf->addSubnet(getNetAddress($_POST['subnet'], $_POST['netmask']), $_POST['netmask']);
	$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
	$nbsubnets = "";
	foreach($conf->getSubnets() as $key => $value) {
		$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
	}
	$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
	suExec("touchconf /var/appliance/conf/dhcpd.conf.new");
	if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE) {
		$conf_error = true;
	}
	if ($dhcp_running) {
		netbootExec("stopdhcp");
	}
	netbootExec("installdhcpdconf");
	if ($dhcp_running && in_array($default_image, $enabled_images)) {
		netbootExec("setnbimage ".$default_image);
		netbootExec("startdhcp");
	}
}

if (isset($_POST['delsubnet'])) {
	$conf->deleteSubnet($_POST['deleteSubnet'], $_POST['deleteNetmask']);
	$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
	$nbsubnets = "";
	foreach($conf->getSubnets() as $key => $value) {
		$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
	}
	$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
	suExec("touchconf /var/appliance/conf/dhcpd.conf.new");
	if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE) {
		$conf_error = true;
	}
	if ($dhcp_running) {
		netbootExec("stopdhcp");
	}
	netbootExec("installdhcpdconf");
	if ($dhcp_running && sizeof($conf->getSubnets() > 0) && in_array($default_image, $enabled_images)) {
		netbootExec("setnbimage ".$default_image);
		netbootExec("startdhcp");
	}
} */

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
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
				/* var subnet_error = <?php echo $subnet_error; ?>;
				
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
				} */

				function toggleService() {
					if ($('#netbootenabled').prop('checked')) {
						$('#netboot').removeClass('hidden');
						ajaxPost('netbootCtl.php', 'service=enable');
					} else {
						$('#netboot').addClass('hidden');
						ajaxPost('netbootCtl.php', 'service=disable');
					}
				}

				/* function toggleBSDP() {
					if ($('#pybsdp').prop('checked')) {
						$('#subnet_error').addClass('hidden');
						$('#newsubnet').prop('disabled', true);
						ajaxPost('netbootCtl.php', 'pybsdp=true');
					} else {
						if (subnet_error) {
							$('#subnet_error').removeClass('hidden');
						}
						$('#newsubnet').prop('disabled', false);
						ajaxPost('netbootCtl.php', 'pybsdp=false');
					}
				} */

				function toggleDashboard() {
					if ($('#dashboard').prop('checked')) {
						ajaxPost('netbootCtl.php', 'dashboard=true');
					} else {
						ajaxPost('netbootCtl.php', 'dashboard=false');
					}
				}

				/* function validSubnet() {
					var subnet = document.getElementById('subnet');
					var netmask = document.getElementById('netmask'); */
					// if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(subnet.value) && /^(?!127.*$).*/.test(subnet.value)) {
						/* hideError(subnet, 'subnet_label');
					} else {
						showError(subnet, 'subnet_label');
					}
					if (/^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(netmask.value)) {
						hideError(netmask, 'netmask_label');
					} else {
						showError(netmask, 'netmask_label');
					} */
					// if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(subnet.value) && /^(?!127.*$).*/.test(subnet.value) && /^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(netmask.value)) {
						/* $('#addsubnet').prop('disabled', false);
					} else {
						$('#addsubnet').prop('disabled', true);
					}
				} */

				//Ensure all inputs have values before enabling the add button
				/* $(document).ready(function () {
					$('#subnet, #netmask').focus(validSubnet);
					$('#subnet, #netmask').keyup(validSubnet);
					$('#subnet, #netmask').blur(validSubnet);
				}); */
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<div class="row">
						<div class="col-xs-10"> 
							<h2>NetBoot Server</h2>
						</div>
						<div class="col-xs-2 text-right"> 
							<input type="checkbox" id="netbootenabled" data-toggle="toggle" data-size="small" onChange="toggleService();" <?php echo ($conf->getSetting("netboot") == "enabled" ? "checked" : ""); ?>>
						</div>
					</div>
				</div>
			</nav>

			<div style="padding: 70px 20px 1px; background-color: #f9f9f9;">
				<div class="checkbox checkbox-primary">
					<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("shownetboot") == "false" ? "" : "checked"); ?>>
					<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
				</div>
			</div>

			<hr>

			<!-- <div style="padding: 16px 20px 1px;">
				<div class="checkbox checkbox-primary">
					<input name="pybsdp" id="pybsdp" class="styled" type="checkbox" value="true" onChange="toggleBSDP();" <?php echo ($conf->getSetting("pybsdp") == "true" ? "checked" : ""); ?>>
					<label><strong>Use BSDP</strong><br><span style="font-size: 75%; color: #777;">Use bsdp service to provide NetBoot information to clients.</span></label>
				</div>
			</div>

			<hr> -->

			<!-- <div style="padding: 16px 20px 1px; background-color: #f9f9f9; overflow-x: auto;">
				<div id="conf_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($conf_error ? "" : "hidden"); ?>">
					<div class="panel-body">
						<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>Unable to update dhcpd.conf.</div>
					</div>
				</div>

				<div id="subnet_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($subnet_error ? ($conf->getSetting("pybsdp") == "true" ? hidden : "") : "hidden"); ?>">
					<div class="panel-body">
						<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>Ensure you have added a Subnet that includes the IP address of the NetBoot server.</div>
					</div>
				</div>

				<div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
					<div class="row">
						<div class="col-sm-10">
							<div class="dataTables_filter">
								<h5><strong>Subnets</strong> <small>One of the subnets must include the IP address of the NetBoot server.</small></h5>
							</div>
						</div>
						<div class="col-sm-2">
							<div class="dataTables_paginate">
								<div class="btn-group">
									<button type="button" id="newsubnet" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#add-subnet-modal" <?php echo ($conf->getSetting("pybsdp") == "true" ? "disabled" : ""); ?>><span class="glyphicon glyphicon-plus"></span> Add</button>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12"> -->
							<!-- <table id="subnetTable" class="table table-hover" style="border-bottom: 1px solid #eee;"> -->
							<!-- <table id="subnetTable" class="table table-hover">
								<thead>
									<tr>
										<th>Subnet</th>
										<th>Netmask</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php foreach($conf->getSubnets() as $key => $value) { ?>
									<tr>
										<td><?php echo $value['subnet']; ?></td>
										<td><?php echo $value['netmask']; ?></td>
										<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#del-subnet-modal" onClick="$('#del-subnet-name').text('<?php echo $value['subnet']."/".$value['netmask']; ?>'); $('#deleteSubnet').val('<?php echo $value['subnet']; ?>'); $('#deleteNetmask').val('<?php echo $value['netmask']; ?>');">Delete</button></td>
									</tr>
<?php }
if (sizeof($conf->getSubnets()) == 0) { ?>
								<tr>
									<td align="center" valign="top" colspan="3" class="dataTables_empty">No data available in table</td>
								</tr>
<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<hr> -->

			<!-- <form action="netbootSettings.php" method="post" name="NetBoot" id="NetBoot"> -->

				<!-- Add Subnet Modal -->
				<!-- <div class="modal fade" id="add-subnet-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title"">Add Subnet</h3>
							</div>
							<div class="modal-body">
								<h5 id="subnet_label"><strong>Subnet</strong> <small>IP or Network address.</small></h5>
								<div class="form-group">
									<input type="text" name="subnet" id="subnet" class="form-control input-sm" value="<?php echo ($subnet_error ? $currentNetwork : ""); ?>"/>
								</div>

								<h5 id="netmask_label"><strong>Netmask</strong> <small>Subnet mask for network.</small></h5>
								<div class="form-group">
									<input type="text" name="netmask" id="netmask" class="form-control input-sm" value="<?php echo ($subnet_error ? $currentNetmask : ""); ?>"/>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
								<button type="submit" name="addsubnet" id="addsubnet" class="btn btn-primary btn-sm" disabled >Save</button>
							</div>
						</div>
					</div>
				</div> -->
				<!-- /#modal -->

				<!-- Delete Subnet Modal -->
				<!-- <div class="modal fade" id="del-subnet-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Delete <span id="del-subnet-name">Subnet</span></h3>
							</div>
							<div class="modal-body">
								<input id="deleteSubnet" name="deleteSubnet" type="hidden" value="">
								<input id="deleteNetmask" name="deleteNetmask" type="hidden" value="">
								<div class="text-muted">This action is permanent and cannot be undone.</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="delsubnet" id="delsubnet" class="btn btn-danger btn-sm">Delete</button>
							</div>
						</div>
					</div>
				</div> -->
				<!-- /.modal -->

			<!-- </form> --> <!-- end form NetBoot -->
<?php include "inc/footer.php"; ?>