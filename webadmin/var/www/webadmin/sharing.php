<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "File Sharing";

include "inc/header.php";

function shareExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/shareHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

// Start SMB
if (!empty($_POST['startsmb'])) {
	shareExec("startsmb");
}

// Start AFP
if (!empty($_POST['startafp'])) {
	shareExec("startafp");
}

// Add Share
if (isset($_POST['addshare']) && $_POST['addsharename'] != "" && $_POST['addsharepath'] != "") {
	shareExec("addSMBshare \"".$_POST['addsharename']."\" \"".$_POST['addsharepath']."\" ".$_POST['addshareowner']);
}

// Delete Share
if (isset($_POST['delshare']) && $_POST['delshare'] != "NetBoot" && $_POST['delshare'] != "") {
	shareExec("delSMBshare ".$_POST['delshare']);
	shareExec("delAFPshare ".$_POST['delshare']);
	shareExec("delHTTPshare ".$_POST['delshare']);
	if (isset($_POST['delsharedata'])) {
		shareExec("delShareData ".$_POST['delsharedata']);
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

// Service Status
$smb_running = (trim(shareExec("getsmbstatus")) === "true");
$afp_running = (trim(shareExec("getafpstatus")) === "true");
$smb_error = "";
$afp_error = "";

// Users & Groups
$uid_min = preg_split("/\s+/", implode(preg_grep("/\bUID_MIN\b/i", file("/etc/login.defs"))))[1];
$uid_max = preg_split("/\s+/", implode(preg_grep("/\bUID_MAX\b/i", file("/etc/login.defs"))))[1];
$users = array();
foreach(file("/etc/passwd") as $entry) {
	$entry_arr = explode(":", $entry);
	$user = array();
	$user['name'] = $entry_arr[0];
	$user['uid'] = $entry_arr[2];
	$user['gecos'] = $entry_arr[4];
	array_push($users, $user);
}
$usernames = array_map(function($el){ return $el['name']; }, $users);

// Shares
$file_shares = array();
$smb_str = trim(shareExec("getSMBshares"));
if ($smb_str != "") {
	foreach(explode("\n", $smb_str) as $value) {
		$share = explode(":", $value);
		$file_shares[$share[1]] = array();
		$file_shares[$share[1]]['smb'] = true;
		$file_shares[$share[1]]['afp'] = false;
		$file_shares[$share[1]]['name'] = $share[0];
		$file_shares[$share[1]]['path'] = $share[1];
		$file_shares[$share[1]]['rwlist'] = array();
		foreach (explode(",", $share[2]) as $user) {
			if (in_array($user, $usernames) && !in_array($user, $file_shares[$share[1]]['rwlist'])) {
				array_push($file_shares[$share[1]]['rwlist'], $user);
			}
		}
		$file_shares[$share[1]]['rolist'] = array();
		foreach (explode(",", $share[3]) as $user) {
			if (in_array($user, $usernames) && !in_array($user, $file_shares[$share[1]]['rolist']) && !in_array($user, $file_shares[$share[1]]['rwlist'])) {
				array_push($file_shares[$share[1]]['rolist'], $user);
			}
		}
	}
}
$afp_str = trim(shareExec("getAFPshares"));
if ($afp_str != "") {
	foreach(explode("\n", $afp_str) as $value) {
		$share = explode(":", $value);
		if (isset($file_shares[$share[1]])) {
			$file_shares[$share[1]]['afp'] = true;
		} else {
			$file_shares[$share[1]] = array();
			$file_shares[$share[1]]['smb'] = false;
			$file_shares[$share[1]]['afp'] = true;
			$file_shares[$share[1]]['name'] = $share[0];
			$file_shares[$share[1]]['path'] = $share[1];
			$file_shares[$share[1]]['rwlist'] = array();
			foreach (explode(",", $share[2]) as $user) {
				if (in_array($user, $usernames) && !in_array($user, $file_shares[$share[1]]['rwlist'])) {
					array_push($file_shares[$share[1]]['rwlist'], $user);
				}
			}
			$file_shares[$share[1]]['rolist'] = array();
			foreach (explode(",", $share[3]) as $user) {
				if (in_array($user, $usernames) && !in_array($user, $file_shares[$share[1]]['rolist']) && !in_array($user, $file_shares[$share[1]]['rwlist'])) {
					array_push($file_shares[$share[1]]['rolist'], $user);
				}
			}
		}
	}
}
$smb_shares = 0;
$afp_shares = 0;
foreach ($file_shares as $key => $value) {
	$file_shares[$key]['http'] = (trim(shareExec("getHTTPshare \"".$key."\"")) === "true");
	if ($file_shares[$key]['smb']) {
		$smb_shares++;
	}
	if ($file_shares[$key]['afp']) {
		$afp_shares++;
	}
}

// Alerts
if ($smb_shares > 1 && !$smb_running) {
	$smb_error = "The SMB service is not running. <a href=\"\" onClick=\"startSMB();\">Click here to start it</a>.";
}
if ($afp_shares > 1 && !$afp_running) {
	$afp_error = "The AFP service is not running. <a href=\"\" onClick=\"startAFP();\">Click here to start it</a>.";
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />

			<script type="text/javascript" src="scripts/dataTables/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="scripts/dataTables/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/buttons.bootstrap.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#share-table').DataTable( {
						buttons: [
							{
								text: '<span class="glyphicon glyphicon-plus"></span> Add',
								className: 'btn-primary btn-sm',
								action: function ( e, dt, node, config ) {
									$("#addshare-modal").modal();
								}
							}
						],
						"dom": "<'row'<'col-sm-4'f><'col-sm-6'i><'col-sm-2'<'dataTables_paginate'B>>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-3'l><'col-sm-9'p>>",
						"order": [ 3, 'asc' ],
						"lengthMenu": [ [5, 10, 25, -1], [5, 10, 25, "All"] ],
						"pageLength": 10,
						"columns": [
							{ "orderable": false },
							{ "orderable": false },
							{ "orderable": false },
							null,
							null,
							{ "orderable": false }
						]
					});
				} );
			</script>

			<script type="text/javascript">
				var smb_running = <?php echo ($smb_running ? "true" : "false"); ?>;
				var afp_running = <?php echo ($afp_running ? "true" : "false"); ?>;
				var shares = <?php print_r(json_encode(array_values($file_shares))); ?>;
				var smb_shares = <?php echo $smb_shares; ?>;
				var afp_shares = <?php echo $afp_shares; ?>;
				var share = {};

				function startSMB() {
					$('#startsmb').val('true');
					$('#Sharing').submit();
				}

				function startAFP() {
					$('#startafp').val('true');
					$('#Sharing').submit();
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

				function validAddShare() {
					var shareNames = [<?php echo "\"".implode('", "', array_map(function($el){ return $el["name"]; }, $file_shares))."\""; ?>];
					var sharePaths = [<?php echo "\"".implode('", "', array_keys($file_shares))."\""; ?>];
					var addsharename = document.getElementById('addsharename');
					var addsharepath = document.getElementById('addsharepath');
					var addshareowner = document.getElementById('addshareowner');
					if (/^([A-Za-z0-9 ._-]){1,32}$/.test(addsharename.value) && shareNames.indexOf(addsharename.value) == -1) {
						hideError(addsharename, 'addsharename_label');
					} else {
						showError(addsharename, 'addsharename_label');
					}
					if (/^(\/)[^\0: ]*$/.test(addsharepath.value) && sharePaths.indexOf(addsharepath.value) == -1) {
						hideError(addsharepath, 'addsharepath_label');
					} else {
						showError(addsharepath, 'addsharepath_label');
					}
					if (addshareowner.value != "") {
						hideError(addshareowner, 'addshareowner_label');
					} else {
						showError(addshareowner, 'addshareowner_label');
					}
					if (/^([A-Za-z0-9 ._-]){1,32}$/.test(addsharename.value) && shareNames.indexOf(addsharename.value) == -1 && /^(\/)[^\0: ]*$/.test(addsharepath.value) && sharePaths.indexOf(addsharepath.value) == -1 && addshareowner.value != "") {
						$('#addshare').prop('disabled', false);
					} else {
						$('#addshare').prop('disabled', true);
					}
				}

				function toggleSMB(i) {
					var smb = document.getElementById('smb-'+i);
					var afp = document.getElementById('afp-'+i);
					if (smb.checked) {
						shares[i]['smb'] = true;
						ajaxPost('sharingCtl.php', 'enablesmb='+shares[i]['name']+':'+shares[i]['path']+':'+shares[i]['rwlist'].toString()+':'+shares[i]['rolist'].toString());
						smb_shares++;
					} else {
						shares[i]['smb'] = false;
						ajaxPost('sharingCtl.php', 'disablesmb='+shares[i]['name']);
						smb_shares--;
					}
					if (smb_shares > 1 && !smb_running) {
						$('#smb_error').removeClass('hidden');
					} else {
						$('#smb_error').addClass('hidden');
					}
					console.log('smb_shares: '+smb_shares);
					if (smb.checked && afp.checked) {
						smb.disabled = false;
						afp.disabled = false;
					} else {
						if (smb.checked) {
							smb.disabled = true;
						}
						if (afp.checked) {
							afp.disabled = true;
						}
					}
				}

				function toggleAFP(i) {
					var afp = document.getElementById('afp-'+i);
					var smb = document.getElementById('smb-'+i);
					if (afp.checked) {
						shares[i]['afp'] = true;
						ajaxPost('sharingCtl.php', 'enableafp='+shares[i]['name']+':'+shares[i]['path']+':'+shares[i]['rwlist'].toString()+':'+shares[i]['rolist'].toString());
						afp_shares++;
					} else {
						shares[i]['afp'] = false;
						ajaxPost('sharingCtl.php', 'disableafp='+shares[i]['name']);
						afp_shares--;
					}
					if (afp_shares > 1 && !afp_running) {
						$('#afp_error').removeClass('hidden');
					} else {
						$('#afp_error').addClass('hidden');
					}
					if (smb.checked && afp.checked) {
						smb.disabled = false;
						afp.disabled = false;
					} else {
						if (smb.checked) {
							smb.disabled = true;
						}
						if (afp.checked) {
							afp.disabled = true;
						}
					}
				}

				function toggleHTTP(i) {
					var http = document.getElementById('http-'+i);
					if (http.checked) {
						shares[i]['http'] = true;
						ajaxPost('sharingCtl.php', 'enablehttp='+shares[i]['name']+':'+shares[i]['path']);
					} else {
						shares[i]['http'] = false;
						ajaxPost('sharingCtl.php', 'disablehttp='+shares[i]['name']);
					}
				}

				function permissionsModal(i) {
					share = shares[i];
					$('#permissionstitle').text(share['name']);
					if (share['name'] == 'NetBoot') {
						$('input[name="readwrite"]').prop('disabled', true);
						$('input[name="readonly"]').prop('disabled', true);
					} else {
						$('input[name="readwrite"]').prop('disabled', false);
						$('input[name="readonly"]').prop('disabled', false);
					}
					$('input[name="readwrite"]').prop('checked', false);
					$('input[name="readonly"]').prop('checked', false);
					for (j = 0; j < share['rwlist'].length; j++) {
						$('input[name="readwrite"][value="' + share['rwlist'][j] + '"]').prop('checked', true);
						if (share['rwlist'].length == 1) {
							$('input[name="readwrite"][value="' + share['rwlist'][j] + '"]').prop('disabled', true);
							$('input[name="readonly"][value="' + share['rwlist'][j] + '"]').prop('disabled', true);
						}
					}
					for (j = 0; j < share['rolist'].length; j++) {
						$('input[name="readonly"][value="' + share['rolist'][j] + '"]').prop('checked', true);
					}
				}

				function toggleRW(element) {
					user = element.value;
					if (element.checked) {
						$('input[name="readonly"][value="' + user + '"]').prop('checked', false);
						if (share['rolist'].indexOf(user) >= 0) {
							share['rolist'].splice(share['rolist'].indexOf(user), 1);
						}
						if (share['rwlist'].indexOf(user) == -1) {
							share['rwlist'].push(user);
						}
					} else {
						if (share['rwlist'].indexOf(user) >= 0) {
							share['rwlist'].splice(share['rwlist'].indexOf(user), 1);
						}
					}
					if (share['rwlist'].length > 1) {
						$('input[name="readwrite"]').prop('disabled', false);
						$('input[name="readonly"]').prop('disabled', false);
					} else {
						$('input[name="readwrite"][value="' + share['rwlist'][0] + '"]').prop('disabled', true);
						$('input[name="readonly"][value="' + share['rwlist'][0] + '"]').prop('disabled', true);
					}
					if (share['smb']) {
						ajaxPost('sharingCtl.php', 'enablesmb='+share['name']+':'+share['path']+':'+share['rwlist'].toString()+':'+share['rolist'].toString());
					}
					if (share['afp']) {
						ajaxPost('sharingCtl.php', 'enableafp='+share['name']+':'+share['path']+':'+share['rwlist'].toString()+':'+share['rolist'].toString());
					}
				}

				function toggleRO(element) {
					user = element.value;
					if (element.checked) {
						$('input[name="readwrite"][value="' + user + '"]').prop('checked', false);
						if (share['rwlist'].indexOf(user) >= 0) {
							share['rwlist'].splice(share['rwlist'].indexOf(user), 1);
						}
						if (share['rolist'].indexOf(user) == -1) {
							share['rolist'].push(user);
						}
					} else {
						if (share['rolist'].indexOf(user) >= 0) {
							share['rolist'].splice(share['rolist'].indexOf(user), 1);
						}
					}
					if (share['rwlist'].length > 1) {
						$('input[name="readwrite"]').prop('disabled', false);
						$('input[name="readonly"]').prop('disabled', false);
					} else {
						$('input[name="readwrite"][value="' + share['rwlist'][0] + '"]').prop('disabled', true);
						$('input[name="readonly"][value="' + share['rwlist'][0] + '"]').prop('disabled', true);
					}
					if (share['smb']) {
						ajaxPost('sharingCtl.php', 'enablesmb='+share['name']+':'+share['path']+':'+share['rwlist'].toString()+':'+share['rolist'].toString());
					}
					if (share['afp']) {
						ajaxPost('sharingCtl.php', 'enableafp='+share['name']+':'+share['path']+':'+share['rwlist'].toString()+':'+share['rolist'].toString());
					}
				}
			</script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#settings').attr('onclick', 'document.location.href="sharingSettings.php"');
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description">&nbsp;</div>
					<div class="row">
						<div class="col-xs-10">
							<h2>File Sharing</h2>
						</div>
						<div class="col-xs-2 text-right">
							<!-- <button type="button" class="btn btn-default btn-sm" >Settings</button> -->
						</div>
					</div>
				</div>
			</nav>

			<form action="sharing.php" method="post" name="Sharing" id="Sharing">

				<div style="padding: 79px 20px 1px; background-color: #f9f9f9; overflow-x: auto;">
					<div id="smb_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($smb_shares > 1 && !$smb_running ? "" : "hidden"); ?>">
						<div class="panel-body">
							<input type="hidden" id="startsmb" name="startsmb" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The SMB service is not running. <a href="" onClick="startSMB();">Click here to start it</a>.</div>
						</div>
					</div>

					<div id="afp_error" style="margin-top: 0px; margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo ($afp_shares > 1 && !$afp_running ? "" : "hidden"); ?>">
						<div class="panel-body">
							<input type="hidden" id="startafp" name="startafp" value="">
							<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>The AFP service is not running. <a href="" onClick="startAFP();">Click here to start it</a>.</div>
						</div>
					</div>

					<table id="share-table" class="table table-hover" style="border-bottom: 1px solid #eee;">
						<thead>
							<tr>
								<th>SMB</th>
								<th>AFP</th>
								<th>HTTP</th>
								<th>Share Name</th>
								<th>Share Path</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php $i = 0;
foreach ($file_shares as $key => $value) {
if ($value["name"] != "NetBoot" && $value["name"] != "NetBootClients") { ?>
							<tr>
								<td>
									<div class="checkbox checkbox-primary checkbox-inline">
										<input type="checkbox" id="smb-<?php echo $i; ?>" value="true" onChange="toggleSMB('<?php echo $i; ?>');" <?php echo ($value["smb"] ? "checked" : ""); ?> <?php echo ($value["name"] == "NetBoot" || $value["afp"] == false ? "disabled" : ""); ?>/>
										<label/>
									</div>
								</td>
								<td>
									<div class="checkbox checkbox-primary checkbox-inline">
										<input type="checkbox" id="afp-<?php echo $i; ?>" value="true" onChange="toggleAFP('<?php echo $i; ?>');" <?php echo ($value["afp"] ? "checked" : ""); ?> <?php echo ($value["name"] == "NetBoot" || $value["smb"] == false ? "disabled" : ""); ?>/>
										<label/>
									</div>
								</td>
								<td>
									<div class="checkbox checkbox-primary checkbox-inline">
										<input type="checkbox" id="http-<?php echo $i; ?>" value="true" onChange="toggleHTTP('<?php echo $i; ?>');" <?php echo ($value["http"] ? "checked" : ""); ?> <?php echo ($value["name"] == "NetBoot" ? "disabled" : ""); ?>/>
										<label/>
									</div>
								</td>
								<td><a data-toggle="modal" href="#permissions-modal" onClick="permissionsModal('<?php echo $i; ?>');"><?php echo $value["name"]; ?></a></td>
								<td><?php echo $key; ?></td>
								<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#delshare-modal" onClick="$('#delsharename').text('<?php echo $value["name"]; ?>'); $('#delsharedata').val('<?php echo $key; ?>'); $('#delshare').val('<?php echo $value["name"]; ?>');" <?php echo ($value["name"] == "NetBoot" ? "disabled" : ""); ?>>Delete</button></td>
							</tr>
<?php }
$i++;
} ?>
						</tbody>
					</table>
				</div>

				<hr>

				<!-- Add Share Modal -->
				<div class="modal fade" id="addshare-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Add Share</h3>
							</div>
							<div class="modal-body">
								<h5 id="addsharename_label"><strong>Share Name</strong> <small>Share display name (e.g. "JamfShare")</small></h5>
								<div class="form-group">
									<input type="text" name="addsharename" id="addsharename" class="form-control input-sm" onFocus="validAddShare();" onKeyUp="validAddShare();" onBlur="validAddShare();" placeholder="[Required]" value=""/>
								</div>
								<h5 id="addsharepath_label"><strong>Share Path</strong> <small>Path to share (e.g. "/srv/JamfShare")</small></h5>
								<div class="form-group">
									<input type="text" name="addsharepath" id="addsharepath" class="form-control input-sm" onFocus="validAddShare();" onKeyUp="validAddShare();" onBlur="validAddShare();" placeholder="[Required]" value=""/>
								</div>
								<h5 id="addshareowner_label"><strong>Share Owner</strong> <small>To add additional users, edit the share after creation</small></h5>
								<div class="form-group has-feedback">
									<select id="addshareowner" name="addshareowner" class="form-control input-sm" onChange="validAddShare();">
										<option value="">Select...</option>
<?php foreach($users as $user) {
if ($user['uid'] >= $uid_min && $user['uid'] <= $uid_max) { ?>
										<option value="<?php echo $user['name']; ?>"><?php echo (empty($user['gecos']) ? $user['name'] : $user['gecos']); ?></option>
<?php }
} ?>
									</select>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="addshare" id="addshare" class="btn btn-primary btn-sm" disabled>Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

				<!-- Permissions Modal -->
				<div class="modal fade" id="permissions-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Permissions for <span id="permissionstitle">Share</span></h3>
							</div>
							<!-- <div class="modal-body"> -->
								<input type="hidden" id="permsname" value=""/>
								<input type="hidden" id="permspath" value=""/>
								<input type="hidden" id="permssmb" value="">
								<input type="hidden" id="permsafp" value="">
								<table id="privilege-table" class="table table-hover" style="margin-bottom: 0px;">
									<thead>
										<tr>
											<th style="padding-left: 16px;">Name</th>
											<th>Read &amp; Write</th>
											<th>Read Only</th>
										</tr>
									</thead>
									<tbody>
<?php foreach($users as $user) {
if ($user['uid'] >= $uid_min && $user['uid'] <= $uid_max) { ?>
										<tr>
											<td style="padding-left: 16px;"><?php echo (empty($user['gecos']) ? $user['name'] : $user['gecos']); ?></td>
											<td>
												<div class="checkbox checkbox-primary checkbox-inline">
													<input type="checkbox" name="readwrite" value="<?php echo $user['name']; ?>" onChange="toggleRW(this);"/>
													<label/>
												</div>
											</td>
											<td>
												<div class="checkbox checkbox-primary checkbox-inline">
													<input type="checkbox" name="readonly" value="<?php echo $user['name']; ?>" onChange="toggleRO(this);"/>
													<label/>
												</div>
											</td>
										</tr>
<?php }
} ?>
									</tbody>
								</table>
							<!-- </div> -->
							<div class="modal-footer">
								<button type="button" class="btn btn-default btn-sm pull-left" onClick="localStorage.setItem('activeAcctsTab', '#system-tab'); document.location.href='accounts.php';">Users</button>
								<button type="button" data-dismiss="modal" class="btn btn-primary btn-sm">Done</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

				<!-- Delete Share Modal -->
				<div class="modal fade" id="delshare-modal" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Delete <span id="delsharename">Share</span></h3>
							</div>
							<div class="modal-body">
								<div class="text-muted">This action is permanent and cannot be undone.</div>
								<div class="checkbox checkbox-primary checkbox-inline" style="padding-top: 12px;">
									<input name="delsharedata" id="delsharedata" class="styled" type="checkbox" value="true">
									<label><strong>Delete Share Directory</strong> <span style="font-size: 75%; color: #777;">DESCRIPTION</span></label>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="delshare" id="delshare" class="btn btn-danger btn-sm" value="">Delete</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->

			</form> <!-- end form Sharing -->
<?php include "inc/footer.php"; ?>