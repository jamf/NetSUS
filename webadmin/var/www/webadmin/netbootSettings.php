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

// Subnets
$subnets = $conf->getSubnets();

// Network Interfaces
$ifaces = array();
$ifaces_str = trim(suExec("getifaces"));
foreach (explode(" ", $ifaces_str) as $iface) {
	$ipaddr = trim(suExec("getipaddr ".$iface));
	$netmask = trim(suExec("getmask ".$iface));
	$netaddr = trim(getNetAddress($ipaddr, $netmask));
	$ifaces[$iface]['subnet'] = trim(getNetAddress($ipaddr, $netmask));
	$ifaces[$iface]['netmask'] = $netmask;
}

// Default Network Interface
if (!array_key_exists($conf->getSetting("netbootiface"), $ifaces)) {
	$network = reset($ifaces);
	$conf->setSetting("netbootiface", key($ifaces));
	$conf->addSubnet($network['subnet'], $network['netmask']);
}

// Set Network Interface
if (isset($_POST['apply_iface']) && array_key_exists($conf->getSetting("netbootiface"), $ifaces)) {
	$iface = $_POST['netboot_iface'];
	$conf->setSetting("netbootiface", $iface);
	$conf->addSubnet($ifaces[$iface]['subnet'], $ifaces[$iface]['netmask']);
}

// Add Subnet
if (isset($_POST['addsubnet'])) {
	$conf->addSubnet(getNetAddress($_POST['subnet'], $_POST['netmask']), $_POST['netmask']);
}

// Delete Subnet
if (isset($_POST['delsubnet'])) {
	$conf->deleteSubnet($_POST['deleteSubnet'], $_POST['deleteNetmask']);
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

// NetBoot Engine
$nbengine = $conf->getSetting("netbootengine");
if (empty($nbengine)) {
	$nbengine = "pybsdp";
	$conf->setSetting("netbootengine", $nbengine);
}

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

// To Do:
// Set Enabled if only one image is found
// Set Default Image if only one image is found
// Set Engine to DHCP if only one image is found

// Default Image
$default_image = $conf->getSetting("netbootimage");
if (!in_array($default_image, $enabled_images)) {
	$conf->deleteSetting("netbootimage");
	$default_image = false;
}

// Service Status
$dhcp_running = (trim(netbootExec("getdhcpstatus")) === "true");
$bsdp_running = (trim(netbootExec("getbsdpstatus")) === "true");
// if ($dhcp_running && $nbengine == "pybsdp") {
// 	netbootExec("stopdhcp");
// 	netbootExec("startbsdp");
// }
// if ($bsdp_running && $nbengine == "dhcpd") {
// 	netbootExec("stopbsdp");
// 	netbootExec("startdhcp");
// }

$nbiface = $conf->getSetting("netbootiface");
foreach ($ifaces as $key => $value) {
	if ($key != $nbiface && in_array($value, $subnets)) {
		if (($subnet = array_search($value, $subnets)) !== false) {
			$conf->deleteSubnet($value['subnet'], $value['netmask']);
		}
	}
}

// DHCP Config
$conf_error = false;
if ($conf->getSubnets() != $subnets) {
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
}

$dhcp_subnets = array($ifaces[$nbiface]);
foreach ($conf->getSubnets() as $subnet) {
	if (!in_array($subnet, $dhcp_subnets)) {
		array_push($dhcp_subnets, $subnet);
	}
}
?>

			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
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

				function toggleService() {
					if ($('#netbootenabled').prop('checked')) {
						$('#netboot').removeClass('hidden');
						$('[name="netbootengine"]').prop('disabled', false);
						$('#netboot_iface').prop('disabled', false);
						$('#apply_iface').prop('disabled', false);
						$('#newsubnet').prop('disabled', false);
						$('[name="delete"]').prop('disabled', false);
						ajaxPost('netbootCtl.php', 'service=enable');
					} else {
						$('#netboot').addClass('hidden');
						$('[name="netbootengine"]').prop('disabled', true);
						$('#netboot_iface').prop('disabled', true);
						$('#apply_iface').prop('disabled', true);
						$('#newsubnet').prop('disabled', true);
						$('[name="delete"]').prop('disabled', true);
						ajaxPost('netbootCtl.php', 'service=disable');
					}
				}

				function toggleEngine() {
					if ($('#pybsdp').prop('checked')) {
						$('#netboot_iface').prop('disabled', true);
						$('#netboot_iface').val('all');
						$('#apply_iface').prop('disabled', true);
						$('#newsubnet').prop('disabled', true);
						$('#dhcp_subnets').addClass('hidden');
						$('#bsdp_subnets').removeClass('hidden');
						ajaxPost('netbootCtl.php', 'engine=pybsdp');
					} else {
						$('#netboot_iface').prop('disabled', false);
						$('#netboot_iface').val('<?php echo $nbiface; ?>');
						$('#apply_iface').prop('disabled', false);
						$('#newsubnet').prop('disabled', false);
						$('#bsdp_subnets').addClass('hidden');
						$('#dhcp_subnets').removeClass('hidden');
						ajaxPost('netbootCtl.php', 'engine=dhcpd');
					}
				}

				function toggleDashboard() {
					if ($('#dashboard').prop('checked')) {
						ajaxPost('netbootCtl.php', 'dashboard=true');
					} else {
						ajaxPost('netbootCtl.php', 'dashboard=false');
					}
				}

				function validSubnet() {
					var subnet = document.getElementById('subnet');
					var netmask = document.getElementById('netmask');
					if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(subnet.value) && /^(?!127.*$).*/.test(subnet.value)) {
						hideError(subnet, 'subnet_label');
					} else {
						showError(subnet, 'subnet_label');
					}
					if (/^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(netmask.value)) {
						hideError(netmask, 'netmask_label');
					} else {
						showError(netmask, 'netmask_label');
					}
					if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(subnet.value) && /^(?!127.*$).*/.test(subnet.value) && /^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(netmask.value)) {
						$('#addsubnet').prop('disabled', false);
					} else {
						$('#addsubnet').prop('disabled', true);
					}
				}

				// Ensure all inputs have values before enabling the add button
				$(document).ready(function () {
					$('#subnet, #netmask').focus(validSubnet);
					$('#subnet, #netmask').keyup(validSubnet);
					$('#subnet, #netmask').blur(validSubnet);
					$('#netboot_iface').change(function () {
						$('#apply_iface').prop('disabled', false);
					});
				});
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

			<div style="padding: 9px 20px 1px;">
				<h5><strong>NetBoot Engine</strong> <small>Service used to provide NetBoot information to clients.</small></h5>
				<div class="radio radio-primary">
					<input type="radio" id="pybsdp" name="netbootengine" value="pybsdp" onChange="toggleEngine();" <?php echo ($nbengine == "pybsdp" ? "checked" : ""); ?>>
					<label for="pybsdp"><strong>BSDP</strong> <span style="font-size: 75%; color: #777;">Supports multiple NetBoot images, but does not broadcast across subnets.</span></label>
				</div>
				<div class="radio radio-primary">
					<input type="radio" id="dhcpd" name="netbootengine" value="dhcpd" onChange="toggleEngine();" <?php echo ($nbengine == "dhcpd" ? "checked" : ""); ?>>
					<label for="dhcpd"><strong>DHCP</strong> <span style="font-size: 75%; color: #777;">Broadcasts across subnets, but only supports a single NetBoot image.</span></label>
				</div>
			</div>

			<hr>

			<form action="netbootSettings.php" method="post" name="NetBoot" id="NetBoot">

				<div style="padding: 9px 20px 1px; background-color: #f9f9f9;">
					<h5><strong>Network Interface</strong> <small>Network Interface to use for NetBoot Images (HTTP/NFS).</small></h5>
					<button type="submit" name="apply_iface" id="apply_iface" class="btn btn-primary btn-sm pull-right" disabled>Apply</button>
					<div class="form-group" style="margin-right: 55px;">
						<select id="netboot_iface" name="netboot_iface" class="form-control input-sm" <?php echo ($nbengine == "pybsdp" ? "disabled" : ""); ?>>
							<option value="all" disabled <?php echo ($nbengine == "pybsdp" ? "selected" : ""); ?>>all</option>
<?php foreach ($ifaces as $key => $value) { ?>
							<option value="<?php echo $key; ?>" <?php echo ($key == $nbiface && $nbengine == "dhcpd" ? "selected" : ""); ?>><?php echo $key; ?></option>
<?php } ?>
						</select>
					</div>

				</div>

				<hr>

				<div style="padding: 16px 20px 1px;">
					<div id="conf_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($conf_error ? "" : "hidden"); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>Unable to update dhcpd.conf.</div>
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
										<button type="button" id="newsubnet" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#add-subnet-modal" <?php echo ($nbengine == "pybsdp" ? "disabled" : ""); ?>><span class="glyphicon glyphicon-plus"></span> Add</button>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-12">
								<table id="bsdp_subnets" class="table table-hover <?php echo ($nbengine == "dhcpd" ? "hidden" : ""); ?>">
									<thead>
										<tr>
											<th>Subnet</th>
											<th>Netmask</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
<?php foreach($ifaces as $key => $value) { ?>
										<tr>
											<td><?php echo $value['subnet']; ?></td>
											<td><?php echo $value['netmask']; ?></td>
											<td align="right"><button type="button" class="btn btn-default btn-sm" disabled>Delete</button></td>
										</tr>
<?php }
if (sizeof($ifaces) == 0) { ?>
									<tr>
										<td align="center" valign="top" colspan="3" class="dataTables_empty">No data available in table</td>
									</tr>
<?php } ?>
								<table id="dhcp_subnets" class="table table-hover <?php echo ($nbengine == "pybsdp" ? "hidden" : ""); ?>">
									<thead>
										<tr>
											<th>Subnet</th>
											<th>Netmask</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
<?php foreach($dhcp_subnets as $key => $value) { ?>
										<tr>
											<td><?php echo $value['subnet']; ?></td>
											<td><?php echo $value['netmask']; ?></td>
											<td align="right"><button type="button" name="<?php echo ($value == $ifaces[$nbiface] ? $nbiface : "delete"); ?>" class="btn btn-default btn-sm" data-toggle="modal" data-target="#del-subnet-modal" onClick="$('#del-subnet-name').text('<?php echo $value['subnet']."/".$value['netmask']; ?>'); $('#deleteSubnet').val('<?php echo $value['subnet']; ?>'); $('#deleteNetmask').val('<?php echo $value['netmask']; ?>');" <?php echo ($value == $ifaces[$nbiface] ? "disabled" : ""); ?>>Delete</button></td>
										</tr>
<?php }
if (sizeof($dhcp_subnets) == 0) { ?>
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

				<hr>

				<!-- Add Subnet Modal -->
				<div class="modal fade" id="add-subnet-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title"">Add Subnet</h3>
							</div>
							<div class="modal-body">
								<h5 id="subnet_label"><strong>Subnet</strong> <small>IP or Network address.</small></h5>
								<div class="form-group">
									<input type="text" name="subnet" id="subnet" class="form-control input-sm"/>
								</div>

								<h5 id="netmask_label"><strong>Netmask</strong> <small>Subnet mask for network.</small></h5>
								<div class="form-group">
									<input type="text" name="netmask" id="netmask" class="form-control input-sm"/>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
								<button type="submit" name="addsubnet" id="addsubnet" class="btn btn-primary btn-sm" disabled >Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /#modal -->

				<!-- Delete Subnet Modal -->
				<div class="modal fade" id="del-subnet-modal" tabindex="-1" role="dialog">
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
				</div>
				<!-- /.modal -->

			<!-- </form> --> <!-- end form NetBoot -->
<?php include "inc/footer.php"; ?>