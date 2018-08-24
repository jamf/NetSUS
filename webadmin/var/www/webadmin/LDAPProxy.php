<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "LDAP Proxy";

include "inc/header.php";

$slapd_error = "";

// Helper Function
function ldapExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/ldapHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

if (!empty($_POST['enableproxy'])) {
	ldapExec("enableproxy");
}

if (isset($_POST['addProxy']) && isset($_POST['inLDAP']) && isset($_POST['outLDAP']) && isset($_POST['inURL'])
&& $_POST['outLDAP'] != "" && $_POST['inLDAP'] != "" && $_POST['inURL'] != "") {
	$conf->addProxy($_POST['outLDAP'], $_POST['inLDAP'], $_POST['inURL']);
	$lpconf = file_get_contents("/var/appliance/conf/slapd.conf");
	$ldapproxies = "";
	foreach($conf->getProxies() as $key => $value) {
		$ldapproxies .= "database\tldap\nsuffix\t\"".$value['outLDAP']."\"\noverlay\trwm\nrwm-suffixmassage\t\"".$value['outLDAP']."\" \"".$value['inLDAP']."\"\nuri\t\"".$value['inURL']."\"\nrebind-as-user\nreadonly\tyes\n\n";
	}
	$lpconf = str_replace("##PROXIES##", $ldapproxies, $lpconf);
	ldapExec("touchconf \"/var/appliance/conf/slapd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/slapd.conf.new", $lpconf) === FALSE) {
		$slapd_error = "Unable to update slapd.conf";
	}
	if (trim(ldapExec("getldapproxystatus")) === "true") {
		ldapExec("disableproxy");
	}
	ldapExec("installslapdconf");
	ldapExec("enableproxy");
}

if (isset($_POST['delProxy'])) {
	$conf->deleteProxy($_POST['deleteoutLDAP'], $_POST['deleteinLDAP'], $_POST['deleteinURL']);
	$lpconf = file_get_contents("/var/appliance/conf/slapd.conf");
	$ldapproxies = "";
	foreach($conf->getProxies() as $key => $value) {
		$ldapproxies .= "database\tldap\nsuffix\t\"".$value['outLDAP']."\"\noverlay\trwm\nrwm-suffixmassage\t\"".$value['outLDAP']."\" \"".$value['inLDAP']."\"\nuri\t\"".$value['inURL']."\"\nrebind-as-user\nreadonly\tyes\n\n";
	}
	$lpconf = str_replace("##PROXIES##", $ldapproxies, $lpconf);
	ldapExec("touchconf \"/var/appliance/conf/slapd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/slapd.conf.new", $lpconf) === FALSE) {
		$slapd_error = "Unable to update slapd.conf";
	}
	ldapExec("disableproxy");
	ldapExec("installslapdconf");
	if (sizeof($conf->getProxies()) > 0) {
		ldapExec("enableproxy");
	}
}

$ldap_running = (trim(ldapExec("getldapproxystatus")) === "true");
if ($conf->getSetting("ldapproxy") == "enabled" && sizeof($conf->getProxies()) > 0 && !$ldap_running) {
	$slapd_error = "The LDAP service is not running. <a href=\"\" onClick=\"enableProxy();\">Click here to start it</a>.";
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />

			<script type="text/javascript" src="scripts/dataTables/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="scripts/dataTables/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/buttons.bootstrap.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#proxy-table').DataTable( {
						buttons: [
							{
								text: '<span class="glyphicon glyphicon-plus"></span> Add',
								className: 'btn-primary btn-sm',
								action: function ( e, dt, node, config ) {
									$("#addproxy-modal").modal();
								}
							}
						],
						"dom": "<'row'<'col-sm-4'f><'col-sm-4'i><'col-sm-4'<'dataTables_paginate'B>>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
						"order": [ 0, 'asc' ],
						"lengthMenu": [ [5, 10, 25, -1], [5, 10, 25, "All"] ],
						"pageLength": 10,
						"columns": [
							null,
							null,
							null,
							{ "orderable": false }
						]
					});
				} );
			</script>

			<script>
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

				function showWarning(element, offset = false) {
					var span = document.createElement("span");
					span.className = "glyphicon glyphicon-exclamation-sign form-control-feedback text-muted";
					if (offset) {
						span.style.right = offset + "px";
					}
					element.parentElement.appendChild(span);
				}

				function hideWarning(element) {
					var span = element.parentElement.getElementsByTagName("span");
					for (var i = 0; i < span.length; i++) {
						if (span[i].classList.contains("form-control-feedback")) {
							element.parentElement.removeChild(span[i]);
						}
					}
				}

				function validLdap() {
					var outLDAP = document.getElementById('outLDAP');
					var inLDAP = document.getElementById('inLDAP');
					var inScheme = document.getElementById('inScheme');
					var inHost = document.getElementById('inHost');
					var inPort = document.getElementById('inPort');
					if (/^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(outLDAP.value)) {
						hideError(outLDAP, 'outLDAP_label');
					} else {
						showError(outLDAP, 'outLDAP_label');
					}
					if (/^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(inLDAP.value)) {
						hideError(inLDAP, 'inLDAP_label');
					} else {
						showError(inLDAP, 'inLDAP_label');
					}
					if (inScheme.checked) {
						inScheme.value = 'ldaps';
					} else {
						inScheme.value = 'ldap';
					}
					if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(inHost.value)) {
						if (inPort.value == parseInt(inPort.value) && inPort.value >= 0 && inPort.value <= 65535) {
							hideError(inHost, 'inHost_label');
						} else {
							hideError(inHost);
						}
					} else {
						showError(inHost, 'inHost_label');
					}
					if (inPort.value == parseInt(inPort.value) && inPort.value >= 0 && inPort.value <= 65535) {
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(inHost.value)) {
							hideError(inPort, 'inHost_label');
						} else {
							hideError(inPort);
						}
					} else {
						showError(inPort, 'inHost_label');
					}
					$('#inURL').val(inScheme.value + '://' + inHost.value + ':' + inPort.value + '/');
					if (/^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(outLDAP.value) && /^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(inLDAP.value)  && /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(inHost.value)  && inPort.value == parseInt(inPort.value) && inPort.value >= 0 && inPort.value <= 65535) {
						$('#addProxy').prop('disabled', false);
					} else {
						$('#addProxy').prop('disabled', true);
					}
				}

				function toggleScheme() {
					var inScheme = document.getElementById('inScheme');
					var inPort = document.getElementById('inPort');
					hideWarning(inPort);
					if (inScheme.checked) {
						inScheme.value = 'ldaps';
						if (inPort.value == '' || inPort.value == '389') {
							inPort.value = '636';
							showWarning(inPort);
						}
					} else {
						inScheme.value = 'ldap';
						if (inPort.value == '' || inPort.value == '636') {
							inPort.value = '389';
							showWarning(inPort);
						}
					}
				}

				function enableProxy() {
					$('#enableproxy').val('true');
					$('#LDAPProxy').submit();
				}

				//Ensure all inputs have values before enabling the add button
				$(document).ready(function () {
					$('#inLDAP, #outLDAP, #inHost, #inPort').focus(validLdap);
					$('#inLDAP, #outLDAP, #inHost, #inPort').keyup(validLdap);
					$('#inLDAP, #outLDAP, #inHost, #inPort').blur(validLdap);
				});
			</script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#settings').attr('onclick', 'document.location.href="proxySettings.php"');
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description">&nbsp;</div>
					<div class="row">
						<div class="col-xs-10"> 
							<h2>LDAP Proxy</h2>
						</div>
						<div class="col-xs-2 text-right"> 
							<!-- <button type="button" class="btn btn-default btn-sm" >Settings</button> -->
						</div>
					</div>
				</div>
			</nav>

			<form action="LDAPProxy.php" method="post" name="LDAPProxy" id="LDAPProxy">

				<div style="padding: 79px 20px 1px; background-color: #f9f9f9; overflow-x: auto;">
					<div id="slapd_info" style="margin-top: 0px; margin-bottom: 16px;" class="panel panel-primary <?php echo ($conf->getSetting("ldapproxy") != "enabled" || sizeof($conf->getProxies()) > 0 ? "hidden" : ""); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-info glyphicon glyphicon-info-sign" style="padding-right: 12px;"></span>The LDAP service will start when a proxy configuration is added.</div>
						</div>
					</div>

					<div id="slapd_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo (empty($slapd_error) ? "hidden" : ""); ?>">
						<div class="panel-body">
							<input type="hidden" id="enableproxy" name="enableproxy" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span><?php echo $slapd_error; ?></div>
						</div>
					</div>

					<table id="proxy-table" class="table table-hover" style="border-bottom: 1px solid #eee;">
						<thead>
							<tr>
								<th>Exposed Distinguished Name</th>
								<th>Real Distinguished Name</th>
								<th>LDAP URL</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php foreach($conf->getProxies() as $key => $value) { ?>
							<tr>
								<td><?php echo $value['outLDAP']?></td>
								<td><?php echo $value['inLDAP']?></td>
								<td><?php echo $value['inURL']?></td>
								<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#delproxy-modal" onClick="$('#deleteoutLDAP').val('<?php echo $value['outLDAP']; ?>'); $('#deleteinLDAP').val('<?php echo $value['inLDAP']; ?>'); $('#deleteinURL').val('<?php echo $value['inURL']; ?>');">Delete</button></td>
							</tr>
<?php } ?>
						</tbody>
					</table>
				</div>

				<hr>

				<!-- Add Proxy Modal -->
				<div class="modal fade" id="addproxy-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Add LDAP Proxy</h3>
							</div>
							<div class="modal-body">
								<h5 id="outLDAP_label"><strong>Exposed Distinguished Name</strong> <small>Example: DC=jss,DC=corp</small></h5>
								<div class="form-group">
									<input type="text" name="outLDAP" id="outLDAP" class="form-control input-sm" placeholder="[Required]" value=""/>
								</div>
								<h5 id="inLDAP_label"><strong>Real Distinguished Name</strong> <small>Example: DC=myorg,DC=corp</small></h5>
								<div class="form-group">
									<input type="text" name="inLDAP" id="inLDAP" class="form-control input-sm" placeholder="[Required]" value=""/>
								</div>
								<h5 id="inHost_label"><strong>Server and Port</strong> <small>Example: ldap.myorg.corp:636</small></h5>
								<div class="row">
									<input type="hidden" name="inURL" id="inURL" value=""/>
									<div class="col-xs-8" style="padding-right: 0px; width: 73%;">
										<div class="has-feedback">
											<input type="text" name="inHost" id="inHost" class="form-control input-sm" placeholder="[Required]" value=""/>
										</div>
									</div>
									<div class="col-xs-1 text-center" style="padding-left: 0px; padding-right: 0px; width: 2%;">:</div>
									<div class="col-xs-3" style="padding-left: 0px;">
										<div class="has-feedback">
											<input type="text" name="inPort" id="inPort" class="form-control input-sm" placeholder="[Required]" value="" onFocus="hideWarning(this);"/>
										</div>
									</div>
								</div>
								<div class="checkbox checkbox-primary checkbox-inline" style="padding-top: 12px;">
									<input name="inScheme" id="inScheme" class="styled" type="checkbox" value="ldaps" onChange="toggleScheme(); validLdap();">
									<label><strong>Use SSL</strong> <span style="font-size: 75%; color: #777;">Connect to the LDAP server over SSL. SSL must be enabled on the LDAP server for this to work.</span></label>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="addProxy" id="addProxy" class="btn btn-primary btn-sm" disabled>Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

				<!-- Delete Proxy Modal -->
				<div class="modal fade" id="delproxy-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Delete Proxy</h3>
							</div>
							<div class="modal-body">
								<input type="hidden" id="deleteoutLDAP" name="deleteoutLDAP" value=""/>
								<input type="hidden" id="deleteinLDAP" name="deleteinLDAP" value=""/>
								<input type="hidden" id="deleteinURL" name="deleteinURL" value=""/>
								<div class="text-muted">This action is permanent and cannot be undone.</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="delProxy" id="delProxy" class="btn btn-danger btn-sm" value="delProxy">Delete</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

			</form> <!-- end form LDAPProxy -->
<?php include "inc/footer.php"; ?>