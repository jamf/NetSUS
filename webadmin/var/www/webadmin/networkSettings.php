<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Network";

include "inc/header.php";

$https_port = trim(suExec("getHttpsPort"));
$ssh_running = (trim(suExec("getSSHstatus")) == "true");
$fw_running = (trim(suExec("getFirewallstatus")) == "true");

$iface_msg = "";
$ssh_error = "";
$fw_error = "";

if (isset($_POST['savehostname'])) {
	suExec("sethostname ".$_POST['hostname']);
}

if (isset($_POST['savecfg'])) {
	$iface = implode($_POST['savecfg']);
	$method = $_POST['method'][$iface];
	if ($method == "static") {
		$ipaddr = $_POST['ipaddr'][$iface];
		$netmask = $_POST['netmask'][$iface];
		$gateway = (empty($_POST['gateway'][$iface]) ? "0.0.0.0" : $_POST['gateway'][$iface]);
		$dns1 = $_POST['dns1'][$iface];
		$dns2 = $_POST['dns2'][$iface];
		suExec("setiface ".$iface." static ".$ipaddr." ".$netmask." ".$gateway." ".$dns1." ".$dns2);
	} else {
		suExec("setiface ".$iface." dhcp");
	}
	$iface_msg = "Configuration saved for ".$iface.". <a data-toggle=\"modal\" data-target=\"#restart-modal\" href=\"\">Restart</a> for changes to take effect.";
}

if (isset($_POST['saveproxy'])) {
	$proxy_auth = "";
	if (isset($_POST['proxyuser']) && !empty($_POST['proxyuser']) && isset($_POST['proxypass']) && !empty($_POST['proxypass'])) {
		$proxy_auth = $_POST['proxyuser'].":".$_POST['proxypass']."@";
	}
	if (isset($_POST['proxyhost']) && !empty($_POST['proxyhost']) && isset($_POST['proxyport']) && !empty($_POST['proxyport'])) {
		suExec("setproxy \"".$proxy_auth.$_POST['proxyhost'].":".$_POST['proxyport']."\"");
	} else {
		suExec("setproxy");
	}
}

if (isset($_POST['SSH'])) {
	if ($ssh_running) {
		suExec("disableSSH");
		$ssh_running = (trim(suExec("getSSHstatus")) == "true");
		if ($ssh_running) {
			$ssh_error = "Failed to disable SSH.";
		}
	} else {
		suExec("enableSSH");
		$ssh_running = (trim(suExec("getSSHstatus")) == "true");
		if (!$ssh_running) {
			$ssh_error = "Failed to enable SSH.";
		}
	}
}

if (isset($_POST['Firewall'])) {
	if ($fw_running) {
		suExec("disableFirewall");
		$fw_running = (trim(suExec("getFirewallstatus")) == "true");
		if ($fw_running) {
			$fw_error = "Failed to disable Firewall.";
		}
	} else {
		suExec("enableFirewall");
		$fw_running = (trim(suExec("getFirewallstatus")) == "true");
		if (!$fw_running) {
			$fw_error = "Failed to enable Firewall.";
		}
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

$ifaces = array();
$ifaces_str = trim(suExec("getifaces"));
foreach (explode(" ", $ifaces_str) as $iface) {
	$ifaces[$iface]['state'] = trim(suExec("getstate ".$iface));
	$ifaces[$iface]['hwaddr'] = trim(suExec("gethwaddr ".$iface));
	$method = trim(suExec("getmethod ".$iface));
	$ifaces[$iface]['method'] = ($method == "dhcp" || $method == "bootp" ? "dhcp" : "static");
	$ifaces[$iface]['ipaddr'] = trim(suExec("getipaddr ".$iface));
	$ifaces[$iface]['netmask'] = trim(suExec("getmask ".$iface));
	if (!empty($ifaces[$iface]['ipaddr'])) {
		$ifaces[$iface]['gateway'] = trim(suExec("getgateway ".$iface));
		$nameservers = explode(" ", trim(suExec("getnameservers ".$iface)));
		$ifaces[$iface]['dns1'] = (isset($nameservers[0]) ? $nameservers[0] : "");
		$ifaces[$iface]['dns2'] = (isset($nameservers[1]) ? $nameservers[1] : "");
	}
}

$proxy = array();
$proxy_str = trim(suExec("getproxy"));
if (!empty($proxy_str)) {
	$proxy = (explode(" ", $proxy_str));
}

$reserved = array(22, 80, 111, 139, 389, 445, 548, 636, 892);
$in_use_str = trim(suExec("getPortsInUse"));
$in_use = explode(" ", $in_use_str);
if (($key = array_search($https_port, $in_use)) !== false) {
    unset($in_use[$key]);
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />

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

				function validHostname() {
					var hostname = document.getElementById('hostname');
					if (/^(?=.{1,253}$)(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(hostname.value)) {
						hideError(hostname, 'hostname_label');
						$('#savehostname').prop('disabled', false);
					} else {
						showError(hostname, 'hostname_label');
						$('#savehostname').prop('disabled', true);
					}
				}

				function validIfCfg(iface) {
					method = document.getElementById('method['+iface+']');
					ipaddr = document.getElementById('ipaddr['+iface+']');
					netmask = document.getElementById('netmask['+iface+']');
					gateway = document.getElementById('gateway['+iface+']');
					dns1 = document.getElementById('dns1['+iface+']');
					dns2 = document.getElementById('dns2['+iface+']');
					savecfg = document.getElementById('savecfg['+iface+']');
					if (method.value == 'static') {
						ipaddr.disabled = false;
						netmask.disabled = false;
						gateway.disabled = false;
						dns1.disabled = false;
						dns2.disabled = false;
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(ipaddr.value) && /^(?!127.*$).*/.test(ipaddr.value)) {
							hideError(ipaddr, 'ipaddr_'+iface+'_label');
						} else {
							showError(ipaddr, 'ipaddr_'+iface+'_label');
						}
						if (/^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(netmask.value)) {
							hideError(netmask, 'netmask_'+iface+'_label');
						} else {
							showError(netmask, 'netmask_'+iface+'_label');
						}
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(gateway.value) || gateway.value == '') {
							hideError(gateway, 'gateway_'+iface+'_label');
						} else {
							showError(gateway, 'gateway_'+iface+'_label');
						}
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(dns1.value) || dns1.value == '') {
							if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(dns2.value) || dns2.value == '') {
								hideError(dns1, 'dns_'+iface+'_label');
							} else {
								hideError(dns1);
							}
						} else {
							showError(dns1, 'dns_'+iface+'_label');
						}
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(dns2.value) || dns2.value == '') {
							if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(dns1.value) || dns1.value == '') {
								hideError(dns2, 'dns_'+iface+'_label');
							} else {
								hideError(dns2);
							}
						} else {
							showError(dns2, 'dns_'+iface+'_label');
						}
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(ipaddr.value) && /^(?!127.*$).*/.test(ipaddr.value) && /^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(netmask.value) && (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(gateway.value) || gateway.value == '') && (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(dns1.value) || dns1.value == '') && (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(dns2.value) || dns2.value == '')) {
							savecfg.disabled = false;
						} else {
							savecfg.disabled = true;
						}
					} else {
						hideError(ipaddr, 'ipaddr_'+iface+'_label');
						hideError(netmask, 'netmask_'+iface+'_label');
						hideError(gateway, 'gateway_'+iface+'_label');
						hideError(dns1, 'dns_'+iface+'_label');
						hideError(dns2);
						ipaddr.disabled = true;
						netmask.disabled = true;
						gateway.disabled = true;
						dns1.disabled = true;
						dns2.disabled = true;
						savecfg.disabled = false;
					}
				}

				function validProxy() {
					var proxyhost = document.getElementById('proxyhost');
					var proxyport = document.getElementById('proxyport');
					var proxyuser = document.getElementById('proxyuser');
					var proxypass = document.getElementById('proxypass');
					if (proxyhost.value == "" && proxyport.value == "") {
						hideError(proxyhost, 'proxyhost_label');
						hideError(proxyport, 'proxyhost_label');
						proxyhost.placeholder = "[Optional]";
						proxyport.placeholder = "[Optional]";
					} else {
						proxyhost.placeholder = "[Required]";
						proxyport.placeholder = "[Required]";
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(proxyhost.value)) {
							if (proxyport.value != "" && proxyport.value == parseInt(proxyport.value) && proxyport.value >= 0 && proxyport.value <= 65535) {
								hideError(proxyhost, 'proxyhost_label');
							} else {
								hideError(proxyhost);
							}
						} else {
							showError(proxyhost, 'proxyhost_label');
						}
						if (proxyport.value != "" && proxyport.value == parseInt(proxyport.value) && proxyport.value >= 0 && proxyport.value <= 65535) {
							if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(proxyhost.value)) {
								hideError(proxyport, 'proxyhost_label');
							} else {
								hideError(proxyport);
							}
						} else {
							showError(proxyport, 'proxyhost_label');
						}
					}
					if (proxyhost.value == "" && proxyport.value == "") {
						proxyuser.disabled = true;
						proxypass.disabled = true;
					} else {
						proxyuser.disabled = false;
						proxypass.disabled = false;
					}
					if (proxyuser.value == "" && proxypass.value == "") {
						proxyuser.placeholder = "[Optional]";
						proxypass.placeholder = "[Optional]";
						hideError(proxyuser, 'proxyuser_label');
						hideError(proxypass, 'proxypass_label');
					} else {
						proxyuser.placeholder = "[Required]";
						proxypass.placeholder = "[Required]";
						if (/^.{1,128}$/.test(proxyuser.value)) {
							hideError(proxyuser, 'proxyuser_label');
						} else {
							showError(proxyuser, 'proxyuser_label');
						}
						if (/^.{1,128}$/.test(proxypass.value)) {
							hideError(proxypass, 'proxypass_label');
						} else {
							showError(proxypass, 'proxypass_label');
						}
					}
					if ($('#proxyhost_label').hasClass('text-danger') || $('#proxyuser_label').hasClass('text-danger') || $('#proxypass_label').hasClass('text-danger')) {
						$('#saveproxy').prop('disabled', true);
					} else {
						$('#saveproxy').prop('disabled', false);
					}
				}
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2>Network</h2>
				</div>
			</nav>

			<form action="networkSettings.php" method="post" name="NetworkSettings" id="NetworkSettings">

				<div style="padding: 70px 20px 16px; background-color: #f9f9f9;">
					<h5 id="hostname_label"><strong>Hostname</strong> <small>The NetSUS server's host name.</small></h5>
					<div class="input-group has-feedback">
						<input type="text" name="hostname" id="hostname" class="form-control input-sm" value="<?php echo getCurrentHostname(); ?>" onFocus="validHostname();" onKeyUp="validHostname();" onBlur="validHostname();"/>
						<span class="input-group-btn">
							<button type="submit" name="savehostname" id="savehostname" class="btn btn-primary btn-sm" disabled>Save</button>
						</span>
					</div>
				</div>

				<hr>

				<div style="padding: 6px 20px 1px; overflow-x: auto;">
					<div style="margin-top: 10px; margin-bottom: 6px; border-color: #4cae4c;" class="panel panel-success <?php echo (empty($iface_msg) ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-success glyphicon glyphicon-ok-sign" style="padding-right: 12px;"></span><?php echo $iface_msg; ?></div>
						</div>
					</div>

					<div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
						<div class="row">
							<div class="col-sm-12">
								<table class="table table-hover">
									<thead>
										<tr>
											<th>Interface</th>
											<th>MAC Address</th>
											<th>Address</th>
											<th>Netmask</th>
											<th>Gateway</th>
										</tr>
									</thead>
									<tbody>
<?php foreach ($ifaces as $key => $value) { ?>
										<tr>
											<td><a data-toggle="modal" data-target="#<?php echo $key; ?>-modal" href=""><?php echo $key; ?></a></td>
											<td><?php echo $value['hwaddr']; ?></td>
											<td><?php echo (isset($value['ipaddr']) ? $value['ipaddr'] : ""); ?></td>
											<td><?php echo (isset($value['netmask']) ? $value['netmask'] : ""); ?></td>
											<td><?php echo (isset($value['gateway']) ? $value['gateway'] : ""); ?></td>
										</tr>
<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
<?php foreach ($ifaces as $key => $value) { ?>

					<!-- <?php echo $key; ?> Modal -->
					<div class="modal fade" id="<?php echo $key; ?>-modal" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h3 class="modal-title">Edit Interface <?php echo $key; ?></h3>
								</div>
								<div class="modal-body">
									<h5 id="nettype_label"><strong>Method</strong></h5>
									<div class="form-group has-feedback">
										<select id="method[<?php echo $key; ?>]" name="method[<?php echo $key; ?>]" class="form-control input-sm" onChange="validIfCfg('<?php echo $key; ?>');">
											<option value="dhcp" <?php echo ($value['method'] == "dhcp" ? "selected" : ""); ?>>Automatic (DHCP)</option>
											<option value="static" <?php echo ($value['method'] == "static" ? "selected" : ""); ?>>Manual</option>
										</select>
									</div>

									<h5 id="ipaddr_<?php echo $key; ?>_label"><strong>IP Address</strong></h5>
									<div class="form-group has-feedback">
										<input type="text" name="ipaddr[<?php echo $key; ?>]" id="ipaddr[<?php echo $key; ?>]"  class="form-control input-sm" value="<?php echo (isset($value['ipaddr']) ? $value['ipaddr'] : ""); ?>" onFocus="validIfCfg('<?php echo $key; ?>');" onKeyUp="validIfCfg('<?php echo $key; ?>');" onBlur="validIfCfg('<?php echo $key; ?>');" <?php echo ($value['method'] == "static" ? "" : "disabled"); ?>/>
									</div>

									<h5 id="netmask_<?php echo $key; ?>_label"><strong>Netmask</strong></h5>
									<div class="form-group has-feedback">
										<input type="text" name="netmask[<?php echo $key; ?>]" id="netmask[<?php echo $key; ?>]" class="form-control input-sm" value="<?php echo (isset($value['ipaddr']) ? $value['netmask'] : ""); ?>" onFocus="validIfCfg('<?php echo $key; ?>');" onKeyUp="validIfCfg('<?php echo $key; ?>');" onBlur="validIfCfg('<?php echo $key; ?>');" <?php echo ($value['method'] == "static" ? "" : "disabled"); ?>/>
									</div>

									<h5 id="gateway_<?php echo $key; ?>_label"><strong>Gateway</strong></h5>
									<div class="form-group has-feedback">
										<input type="text" name="gateway[<?php echo $key; ?>]" id="gateway[<?php echo $key; ?>]" class="form-control input-sm" value="<?php echo (isset($value['gateway']) ? $value['gateway'] : ""); ?>" onFocus="validIfCfg('<?php echo $key; ?>');" onKeyUp="validIfCfg('<?php echo $key; ?>');" onBlur="validIfCfg('<?php echo $key; ?>');" <?php echo ($value['method'] == "static" ? "" : "disabled"); ?>/>
									</div>

									<h5 id="dns_<?php echo $key; ?>_label"><strong>DNS Servers</strong></h5>
									<div class="form-group has-feedback">
										<input type="text" name="dns1[<?php echo $key; ?>]" id="dns1[<?php echo $key; ?>]" class="form-control input-sm" style="margin-bottom: 6px;" value="<?php echo (isset($value['dns1']) ? $value['dns1'] : ""); ?>" onFocus="validIfCfg('<?php echo $key; ?>');" onKeyUp="validIfCfg('<?php echo $key; ?>');" onBlur="validIfCfg('<?php echo $key; ?>');" <?php echo ($value['method'] == "static" ? "" : "disabled"); ?>/>
									</div>
									<div class="form-group has-feedback">
										<input type="text" name="dns2[<?php echo $key; ?>]" id="dns2[<?php echo $key; ?>]" class="form-control input-sm" value="<?php echo (isset($value['dns2']) ? $value['dns2'] : ""); ?>" onFocus="validIfCfg('<?php echo $key; ?>');" onKeyUp="validIfCfg('<?php echo $key; ?>');" onBlur="validIfCfg('<?php echo $key; ?>');" <?php echo ($value['method'] == "static" ? "" : "disabled"); ?>/>
									</div>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
									<button type="submit" id="savecfg[<?php echo $key; ?>]" name="savecfg[<?php echo $key; ?>]" class="btn btn-primary btn-sm pull-right" value="<?php echo $key; ?>" <?php echo ($value['method'] == "static" ? "disabled" : ""); ?>>Save</button>
								</div>
							</div>
						</div>
					</div>
					<!-- /.modal -->
<?php } ?>
				</div>

				<hr>

				<div style="padding: 4px 20px; background-color: #f9f9f9;">
					<h5><strong>Proxy</strong> <small>Configure proxy settings to be used for this server.</small></h5>
					<div style="padding-bottom: 12px;">Network Proxy: <a data-toggle="modal" data-target="#proxy-modal" href=""><?php echo (isset($proxy[0]) ? $proxy[0].":".$proxy[1] : "Not Configured"); ?></a></div>
				</div>

				<!-- Proxy Modal -->
				<div class="modal fade" id="proxy-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Network Proxy</h3>
							</div>
							<div class="modal-body">
								<h5 id="proxyhost_label"><strong>Proxy Server</strong> <small>Hostname or IP address, and port number for the proxy server.</small></h5>
								<div class="row">
									<div class="col-xs-8" style="padding-right: 0px; width: 73%;">
										<div class="has-feedback">
											<input type="text" name="proxyhost" id="proxyhost" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[0]) ? $proxy[0] : ""); ?>" onFocus="validProxy();" onKeyUp="validProxy();" onChange="validProxy();" />
										</div>
									</div>
									<div class="col-xs-1 text-center" style="padding-left: 0px; padding-right: 0px; width: 2%;">:</div>
									<div class="col-xs-3" style="padding-left: 0px;">
										<div class="has-feedback">
											<input type="text" name="proxyport" id="proxyport" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[1]) ? $proxy[1] : ""); ?>" onFocus="validProxy();" onKeyUp="validProxy();" onChange="validProxy();" />
										</div>
									</div>
								</div>
								<h5 id="proxyuser_label"><strong>Authentication</strong> <small>Username used to connect to the proxy.</small></h5>
								<div class="form-group has-feedback">
									<input type="text" name="proxyuser" id="proxyuser" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[2]) ? $proxy[2] : ""); ?>" onFocus="validProxy();" onKeyUp="validProxy();" onChange="validProxy();" <?php echo (empty($proxy[0]) ? "disabled" : ""); ?>/>
								</div>
								<h5 id="proxypass_label"><strong>Password</strong> <small>Password used to authenticate with the proxy.</small></h5>
								<div class="form-group has-feedback">
									<input type="password" name="proxypass" id="proxypass" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[3]) ? $proxy[3] : ""); ?>" onFocus="validProxy();" onKeyUp="validProxy();" onChange="validProxy();" <?php echo (empty($proxy[0]) ? "disabled" : ""); ?>/>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
								<button type="submit" id="saveproxy" name="saveproxy" class="btn btn-primary btn-sm pull-right" disabled>Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

				<hr>

				<div style="padding: 4px 20px 16px;">
					<div style="margin-top: 12px; margin-bottom: 10px; border-color: #d43f3a;" class="panel panel-danger <?php echo (empty($ssh_error) ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-ok-sign" style="padding-right: 12px;"></span><?php echo $ssh_error; ?></div>
						</div>
					</div>

					<h5><strong>SSH Server</strong> <small>Allow ssh login to this server.</small></h5>
					<button type="submit" name="SSH" class="btn btn-primary btn-sm" style="width: 65px;"><?php echo ($ssh_running ? 'Disable' : 'Enable'); ?></button>
				</div>

				<hr>

				<div style="padding: 4px 20px 16px; background-color: #f9f9f9;">
					<div style="margin-top: 12px; margin-bottom: 10px; border-color: #d43f3a;" class="panel panel-danger <?php echo (empty($fw_error) ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-ok-sign" style="padding-right: 12px;"></span><?php echo $fw_error; ?></div>
						</div>
					</div>

					<h5><strong>Firewall</strong> <small>Restrict incoming connections to this server.</small></h5>
					<button type="submit" name="Firewall" class="btn btn-primary btn-sm" style="width: 65px;"><?php echo ($fw_running ? 'Disable' : 'Enable'); ?></button>
				</div>

				<hr>

				<!-- <label class="control-label">Default HTTPS Port</label>
				<input type="text" name="https_port" id="https_port" class="form-control input-sm" value="<?php echo $https_port; ?>" onClick="validateNetwork();" onKeyUp="validateNetwork();" /> -->

				<!-- <input type="submit" class="btn btn-primary" value="Save" name="SaveNetwork" id="SaveNetwork" disabled="disabled" /> -->


			</form> <!-- end network settings form -->
<?php include "inc/footer.php"; ?>