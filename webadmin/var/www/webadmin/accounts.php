<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Accounts";

include "inc/header.php";

// Active Directory Groups
if (isset($_POST['deleteldapgroup']) && $_POST['deleteldapgroup'] != "") {
	$conf->deleteAdmin($_POST['deleteldapgroup']);
}
if (isset($_POST['saveldapgroup'])) {
	$conf->addAdmin($_POST['newldapgroup']);
}

// System Accounts
if (isset($_POST['usermod'])) {
	if ($_POST['usermodlogin'] == "afpuser") {
		suExec("resetafppw ".$_POST['usermodpass']);
		$conf->changedPass("afpaccount");
	} else {
		if ($_POST['usermodlogin'] == "") {
			suExec("addshelluser ".$_POST['usermodnewlogin']." \"".$_POST['usernewmodgecos']."\" ".$_POST['usermodtype']);
			if ($_POST['usermodtype'] == "Administrator") {
				suExec("adminshelluser ".$_POST['usermodnewlogin']);
			}
		} elseif ($_POST['usermodlogin'] == "smbuser") {
			$conf->changedPass("smbaccount");
		} else {
			suExec("changeshelluser ".$_POST['usermodlogin']." \"".$_POST['usernewmodgecos']."\" ".$_POST['usermodnewlogin']);
			if ($_POST['usermodlogin'] == $conf->getSetting("shelluser")) {
				$conf->setSetting("shelluser", $_POST['usermodnewlogin']);
				$conf->changedPass("shellaccount");
			} else {
				if ($_POST['usermodadmin'] == "true") {
					suExec("adminshelluser ".$_POST['usermodnewlogin']);
				} else {
					suExec("stdshelluser ".$_POST['usermodnewlogin']);
				}
			}
		}
		suExec("changeshellpass ".$_POST['usermodnewlogin']." \"".$_POST['usermodpass']."\"");
	}
}
if (isset($_POST['userdel']) && $_POST['userdel'] != "") {
	suExec("delshelluser ".$_POST['userdel']." ".$_POST['userdelhome']);
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

// Built-In Account
$web_user = $conf->getSetting("webadminuser");

// Active Directory
$ldap_scheme = "";
$ldap_host = "";
$ldap_port = "";
$ldap_server = $conf->getSetting("ldapserver");
$ldap_server_arr = explode(":", $ldap_server);
if (sizeof($ldap_server_arr) > 1) {
	$ldap_scheme = $ldap_server_arr[0];
	$ldap_host = str_replace('/', '', $ldap_server_arr[1]);
	$ldap_port = $ldap_server_arr[2];
}
$ldap_domain = $conf->getSetting("ldapdomain");
$ldap_base = $conf->getSetting("ldapbase");
$ldap_admins = $conf->getAdmins();

// System Users
if ($conf->getSetting("shelluser") != "shelluser") {
	$conf->changedPass("shellaccount");
}
$uid_min = preg_split("/\s+/", implode(preg_grep("/\bUID_MIN\b/i", file("/etc/login.defs"))))[1];
$uid_max = preg_split("/\s+/", implode(preg_grep("/\bUID_MAX\b/i", file("/etc/login.defs"))))[1];
$sys_groups = array();
foreach(file("/etc/group") as $entry) {
	$entry_arr = explode(":", $entry);
	$sys_group = array();
	$sys_group['name'] = $entry_arr[0];
	$sys_group['gid'] = $entry_arr[2];
	if (empty(trim($entry_arr[3]))) {
		$sys_group['users'] = array();
	} else {
		$sys_group['users'] = explode(",", trim($entry_arr[3]));
	}
	array_push($sys_groups, $sys_group);
}
$sys_users = array();
foreach(file("/etc/passwd") as $entry) {
	$entry_arr = explode(":", $entry);
	$sys_user = array();
	$sys_user['name'] = $entry_arr[0];
	$sys_user['uid'] = $entry_arr[2];
	$sys_user['gid'] = $entry_arr[3];
	$sys_user['gecos'] = $entry_arr[4];
	$sys_user['home'] = $entry_arr[5];
	$sys_user['shell'] = trim($entry_arr[6]);
	$sys_user['groups'] = array();
	foreach($sys_groups as $sys_group) {
		if (in_array($sys_user['name'], $sys_group['users'])) {
			array_push($sys_user['groups'], $sys_group['name']);
		}
	}
	if ($sys_user['uid'] < $uid_min || $sys_user['uid'] > $uid_max) {
		$sys_user['type'] = "System";
	} elseif (strpos($sys_user['shell'], '/false') || strpos($sys_user['shell'], '/nologin')) {
		$sys_user['type'] = "Sharing";
	} elseif (in_array("adm", $sys_user['groups']) && in_array("sudo", $sys_user['groups']) || in_array("wheel", $sys_user['groups'])) {
		$sys_user['type'] = "Administrator";
	} else {
		$sys_user['type'] = "Standard";
	}
	if ($sys_user['name'] == "shelluser" && $conf->needsToChangePass("shellaccount") || $sys_user['name'] == "smbuser" && $conf->needsToChangePass("smbaccount") || $sys_user['name'] == "afpuser" && $conf->needsToChangePass("afpaccount")) {
		$sys_user['default'] = true;
	} else {
		$sys_user['default'] = false;
	}
	if ($sys_user['name'] == $conf->getSetting("shelluser") || $sys_user['name'] == "afpuser" || $sys_user['name'] == "smbuser") {
		$sys_user['locked'] = true;
	} else {
		$sys_user['locked'] = false;
	}
	array_push($sys_users, $sys_user);
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>

			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />
			<link rel="stylesheet" href="theme/buttons.bootstrap.css" />

			<script type="text/javascript" src="scripts/dataTables/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="scripts/dataTables/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/buttons.bootstrap.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#sysusers-table').DataTable( {
						buttons: [
							{
								text: '<span class="glyphicon glyphicon-plus"></span> New',
								className: 'btn-primary btn-sm',
								action: function ( e, dt, node, config ) {
									$('#usermodlocked').val(false);
									$('#usermodlogin').val('');
									$('#usermodgecos').val('');
									$('#usermodtype').val('Standard');
									$('#usernewmodgecos').val('');
									$('#usermodnewlogin').val('');
									$("#usermod-modal").modal();
								}
							}
						],
						"dom": "<'row'<'col-sm-4'f><'col-sm-4 text-center'i><'col-sm-4 text-right'B>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
						"order": [ 1, 'asc' ],
						"lengthMenu": [ [5, 10, 25, -1], [5, 10, 25, "All"] ],
						"pageLength": 10,
						"columns": [
							{ "orderable": false },
							null,
							null,
							null,
							{ "orderable": false }
						]
					});
				} );
			</script>

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

				function validWebUser() {
					var webuser = document.getElementById('webuser');
					var webpass = document.getElementById('webpass');
					var webnewpass = document.getElementById('webnewpass');
					var webverify = document.getElementById('webverify');
					if (/^([A-Za-z0-9 ._-]){1,64}$/.test(webuser.value)) {
						hideError(webuser, 'webuser_label');
					} else {
						showError(webuser, 'webuser_label');
					}
					if (/^.{1,128}$/.test(webpass.value)) {
						hideError(webpass, 'webpass_label');
					} else {
						showError(webpass, 'webpass_label');
					}
					if (/^.{1,128}$/.test(webnewpass.value)) {
						hideError(webnewpass, 'webnewpass_label');
					} else {
						showError(webnewpass, 'webnewpass_label');
					}
					if (/^.{1,128}$/.test(webverify.value) && webverify.value == webnewpass.value) {
						hideError(webverify, 'webverify_label');
					} else {
						showError(webverify, 'webverify_label');
					}
					if (/^([A-Za-z0-9 ._-]){1,64}$/.test(webuser.value) && /^.{1,128}$/.test(webpass.value) && /^.{1,128}$/.test(webverify.value) && webverify.value == webnewpass.value) {
						$('#savewebuser').prop('disabled', false);
					} else {
						$('#savewebuser').prop('disabled', true);
					}
				}

				function saveWebUser() {
					var webuser = document.getElementById('webuser');
					var webpass = document.getElementById('webpass');
					var webnewpass = document.getElementById('webnewpass');
					if (ajaxPost('ajax.php', 'confirmold='+webpass.value) == 'true') {
						hideError(webpass, 'webpass_label');
						ajaxPost('ajax.php', 'webadminuser='+webuser.value);
						if ($('#logoutuser').text() == $('#webuser_name').text()) {
							$('#logoutuser').text($('#webuser').val());
						}
						$('#webuser_name').text($('#webuser').val());
						ajaxPost('ajax.php', 'webadminpass='+webnewpass.value);
						$('#webuser_warning').addClass('hidden');
						$('#webadmin-tab-icon').addClass('hidden'); // To do: validation for AD to hide warning in tab
						$('#webpass').val('');
						$('#webnewpass').val('');
						$('#webverify').val('');
						$('#savewebuser').prop('disabled', true);
						$('#webuser-modal').modal('hide');
					} else {
						showError(webpass, 'webpass_label');
					}
				}

				function validLdapGroup() {
					var ldapadmins = [<?php echo (sizeof($ldap_admins) > 0 ? "\"".implode('", "', array_map(function($el){ return $el["cn"]; }, $ldap_admins))."\"" : ""); ?>];
					var newldapgroup = document.getElementById('newldapgroup');
					var renameldapgroup = document.getElementById('renameldapgroup');
					if (/^[\w- !@#%&'\$\^\(\)\.\{\}]{1,64}$/.test(newldapgroup.value) && ldapadmins.indexOf(newldapgroup.value) == -1 || newldapgroup.value == renameldapgroup.value && renameldapgroup.value != '') {
						hideError(newldapgroup, 'newldapgroup_label');
					} else {
						showError(newldapgroup, 'newldapgroup_label');
					}
					if (/^[\w- !@#%&'\$\^\(\)\.\{\}]{1,64}$/.test(newldapgroup.value) && newldapgroup.value != renameldapgroup.value) {
						$('#saveldapgroup').prop('disabled', false);
					} else {
						$('#saveldapgroup').prop('disabled', true);
					}
				}

				function validLdap() {
					var ldapdomain = document.getElementById('ldapdomain');
					var ldaphost = document.getElementById('ldaphost');
					var ldapport = document.getElementById('ldapport');
					var ldapbase = document.getElementById('ldapbase');
					if (/^(?=.{2,63}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(ldapdomain.value)) {
						hideError(ldapdomain, 'ldapdomain_label');
					} else {
						showError(ldapdomain, 'ldapdomain_label');
					}
					if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(ldaphost.value)) {
						if (ldapport.value == parseInt(ldapport.value) && ldapport.value >= 0 && ldapport.value <= 65535) {
							hideError(ldaphost, 'ldaphost_label');
						} else {
							hideError(ldaphost);
						}
					} else {
						showError(ldaphost, 'ldaphost_label');
					}
					if (ldapport.value == parseInt(ldapport.value) && ldapport.value >= 0 && ldapport.value <= 65535) {
						if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(ldaphost.value)) {
							hideError(ldapport, 'ldaphost_label');
						} else {
							hideError(ldapport);
						}
					} else {
						showError(ldapport, 'ldaphost_label');
					}
					if (/^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(ldapbase.value)) {
						hideError(ldapbase, 'ldapbase_label');
					} else {
						showError(ldapbase, 'ldapbase_label');
					}
					if (/^(?=.{2,63}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(ldapdomain.value) && /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(ldaphost.value) && ldapport.value == parseInt(ldapport.value) && ldapport.value >= 0 && ldapport.value <= 65535 && /^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(ldapbase.value)) {
						$('#saveldap').prop('disabled', false);
					} else {
						$('#saveldap').prop('disabled', true);
					}
					if (ldapdomain.value == '' && ldaphost.value == '' && ldapbase.value == '') {
						hideError(ldapdomain, 'ldapdomain_label');
						hideError(ldaphost, 'ldaphost_label');
						hideError(ldapport);
						hideError(ldapbase, 'ldapbase_label');
						$('#saveldap').prop('disabled', false);
					}
				}

				function toggleScheme() {
					var ldapscheme = document.getElementById('ldapscheme');
					var ldapport = document.getElementById('ldapport');
					hideWarning(ldapport);
					if (ldapscheme.checked) {
						ldapscheme.value = 'ldaps';
						if (ldapport.value == '' || ldapport.value == '389') {
							ldapport.value = '636';
							showWarning(ldapport);
						}
					} else {
						ldapscheme.value = 'ldap';
						if (ldapport.value == '' || ldapport.value == '636') {
							ldapport.value = '389';
							showWarning(ldapport);
						}
					}
				}

				function saveLdap() {
					var ldapdomain = document.getElementById('ldapdomain');
					var ldapscheme = document.getElementById('ldapscheme');
					var ldaphost = document.getElementById('ldaphost');
					var ldapport = document.getElementById('ldapport');
					var ldapbase = document.getElementById('ldapbase');
					hideWarning(ldapport);
					if (ldapdomain.value == '' && ldaphost.value == '' && ldapbase.value == '') {
						ajaxPost('ajax.php', 'ldapdomain=');
						ajaxPost('ajax.php', 'ldapserver=');
						ajaxPost('ajax.php', 'ldapbase=');
						$('#ldapstatus').text('Not Configured');
					} else {
						ajaxPost('ajax.php', 'ldapdomain='+ldapdomain.value);
						ajaxPost('ajax.php', 'ldapserver='+ldapscheme.value+'://'+ldaphost.value+':'+ldapport.value);
						ajaxPost('ajax.php', 'ldapbase='+ldapbase.value);
						$('#ldapstatus').text($('#ldapdomain').val());
					}
					$('#saveldap').prop('disabled', true);
					$('#ldap-modal').modal('hide');
				}

				function toggleUserAdmin() {
					var usermodadmin = document.getElementById('usermodadmin');
					var usermodtype = document.getElementById('usermodtype');
					if (usermodadmin.checked) {
						usermodtype.value = "Administrator";
					} else {
						usermodtype.value = "Standard";
					}
				}

				function validUserMod() {
					var sysusers = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['name']; }, $sys_users))."\""; ?>];
					var sysnames = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['gecos']; }, $sys_users))."\""; ?>];
					var usernewmodgecos = document.getElementById('usernewmodgecos');
					var usermodlogin = document.getElementById('usermodlogin');
					var usermodnewlogin = document.getElementById('usermodnewlogin');
					var usermodpass = document.getElementById('usermodpass');
					var usermodverify = document.getElementById('usermodverify');
					if (/^[a-z_][a-z0-9_-]{1,31}$/.test(usermodnewlogin.value) && (sysusers.indexOf(usermodnewlogin.value) == -1 || usermodlogin.value == usermodnewlogin.value)) {
						hideError(usermodnewlogin, 'usermodnewlogin_label');
					} else {
						showError(usermodnewlogin, 'usermodnewlogin_label');
					}
					if (/^[^:]{1,128}$/.test(usernewmodgecos.value) && (sysnames.indexOf(usernewmodgecos.value) == -1 || usermodgecos.value == usernewmodgecos.value)) {
						hideError(usernewmodgecos, 'usernewmodgecos_label');
					} else {
						showError(usernewmodgecos, 'usernewmodgecos_label');
					}
					if (/^.{1,128}$/.test(usermodpass.value)) {
						hideError(usermodpass, 'usermodpass_label');
					} else {
						showError(usermodpass, 'usermodpass_label');
					}
					if (/^.{1,128}$/.test(usermodverify.value) && usermodverify.value == usermodpass.value) {
						hideError(usermodverify, 'usermodverify_label');
					} else {
						showError(usermodverify, 'usermodverify_label');
					}
					if (/^[a-z_][a-z0-9_-]{1,31}$/.test(usermodnewlogin.value) && (sysusers.indexOf(usermodnewlogin.value) == -1 || usermodlogin.value == usermodnewlogin.value) && /^[^:]{1,128}$/.test(usernewmodgecos.value) && (sysnames.indexOf(usernewmodgecos.value) == -1 || usermodgecos.value == usernewmodgecos.value) && /^.{1,128}$/.test(usermodverify.value) && usermodverify.value == usermodpass.value) {
						$('#usermod').prop('disabled', false);
					} else {
						$('#usermod').prop('disabled', true);
					}
				}
			</script>

			<script type="text/javascript">
				$(document).ready(function(){
					$('#usermod-modal').on('show.bs.modal', function(e) {
						if ($('#usermodtype').val() == 'Administrator') {
							$('#usermodadmin').prop('checked', true);
						} else {
							$('#usermodadmin').prop('checked', false);
						}
						if ($('#usermodtype').val() == 'Sharing' || $('#usermodlocked').val() == 'true' || $('#usermodlogin').val() == '') {
							$('#usermodadmin').prop('disabled', true);
						} else {
							$('#usermodadmin').prop('disabled', false);
						}
						if ($('#usermodlogin').val() == 'afpuser' || $('#usermodlogin').val() == 'smbuser') {
							$('#usernewmodgecos').prop('readonly', true);
							$('#usermodnewlogin').prop('readonly', true);
						} else {
							$('#usernewmodgecos').prop('readonly', false);
							$('#usermodnewlogin').prop('readonly', false);
						}
						if ($('#usermodlogin').val() == '') {
							$('#usermod_title').text('Add User');
							$('#usermodtype_wrapper').removeClass('hidden');
							$('#usermodadmin_wrapper').addClass('hidden');
						} else {
							$('#usermod_title').text('Modify User');
							$('#usermodtype_wrapper').addClass('hidden');
							$('#usermodadmin_wrapper').removeClass('hidden');
						}
						$('#usermodpass').val('');
						$('#usermodverify').val('');
					});
				});
			</script>

			<script type="text/javascript">
				//function to save the current tab on refresh
				$(document).ready(function(){
					$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
						localStorage.setItem('activeAcctsTab', $(e.target).attr('href'));
					});
					var activeAcctsTab = localStorage.getItem('activeAcctsTab');
					if(activeAcctsTab){
						$('#top-tabs a[href="' + activeAcctsTab + '"]').tab('show');
					}
				});
			</script>

			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
			<h2>Accounts</h2>

			<div class="row">
				<div class="col-xs-12">

					<ul class="nav nav-tabs nav-justified" id="top-tabs">
						<li class="active"><a class="tab-font" href="#webadmin-tab" role="tab" data-toggle="tab"><span id="webadmin-tab-icon" class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("webaccount") ? "" : "hidden"); ?>"></span> Web Interface</a></li>
						<li><a class="tab-font" href="#system-tab" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("shellaccount") || $conf->needsToChangePass("smbaccount") || $conf->needsToChangePass("afpaccount") ? "" : "hidden"); ?>"></span> System Users</a></li>
					</ul>

					<div class="tab-content">

						<div class="tab-pane active fade in" id="webadmin-tab">

							<form method="POST" name="webadmin-form" id="webadmin-form">

								<div style="padding: 8px 0px;" class="description">WEB INTERFACE DESCRIPTION</div>

								<h5><strong>Users &amp; Groups</strong> <small>Users &amp; groups for administering the server.</small></h5>

								<table class="table table-striped">
									<thead>
										<tr>
											<th></th>
											<th>Name</th>
											<th>Type</th>
											<th></th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<td colspan="4" align="right"><button id="addldapgroup" type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#ldapgroup-modal" onClick="$('#renameldapgroup').val(''); $('#newldapgroup').val('');" <?php echo ($ldap_server == "" ? "disabled": ""); ?>><span class="glyphicon glyphicon-plus"></span> Add</button></td>
										</tr>
									</tfoot>
									<tbody>
										<tr>
											<td><span id="webuser_warning" class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("webaccount") ? "" : "hidden"); ?>"></span></td>
											<td><a data-toggle="modal" href="#webuser-modal"><span id="webuser_name"><?php echo $web_user; ?></span></a></td>
											<td>Built-in account.</td>
											<td align="right"><button type="button" class="btn btn-default btn-sm" disabled>Delete</button></td>
										</tr>
<?php foreach ($ldap_admins as $key => $value) { ?>
										<tr>
											<td></td>
											<td><a data-toggle="modal" href="#ldapgroup-modal" onClick="$('#renameldapgroup').val('<?php echo $value["cn"]; ?>'); $('#newldapgroup').val('<?php echo $value["cn"]; ?>');"><?php echo $value["cn"]; ?></a></td>
											<td>Active Directory group.</td>
											<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#deleteldap-modal" onClick="$('#deleteldap-title').text('Delete \'<?php echo $value["cn"]; ?>\'?'); $('#deleteldapgroup').val('<?php echo $value["cn"]; ?>');">Delete</button></td>
										</tr>
<?php } ?>
									</tbody>
								</table>

								<h5><strong>Active Directory</strong> <small>Allow login to the web interface using Active Directory.</small></h5>

								<div style="padding-bottom: 12px;">Domain: <span id="ldapstatus" class="text-muted"><?php echo (empty($ldap_server) || empty($ldap_domain) || empty($ldap_base) ? "Not Configured" : $ldap_domain); ?></span></div>
								<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#ldap-modal">Configure</button>

								<!-- Webuser Modal -->
								<div class="modal fade" id="webuser-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 class="modal-title">Built-In Account</h3>
											</div>
											<div class="modal-body">
												<h5 id="webuser_label"><strong>Username</strong> <small>Username for the account.</small></h5>
												<div class="form-group has-feedback">
													<input type="text" name="webuser" id="webuser" class="form-control input-sm" placeholder="[Required]" value="<?php echo $web_user; ?>" onFocus="validWebUser();" onKeyUp="validWebUser();" onBlur="validWebUser();"/>
												</div>

												<h5 id="webpass_label"><strong>Current Password</strong> <small>Current password for the account.</small></h5>
												<div class="form-group has-feedback">
													<input type="password" name="webpass" id="webpass" class="form-control input-sm" placeholder="[Required]" onFocus="validWebUser();" onKeyUp="validWebUser();" onBlur="validWebUser();"/>
												</div>

												<h5 id="webnewpass_label"><strong>New Password</strong> <small>New password for the account.</small></h5>
												<div class="form-group has-feedback">
													<input type="password" name="webnewpass" id="webnewpass" class="form-control input-sm" placeholder="[Required]" onFocus="validWebUser();" onKeyUp="validWebUser();" onBlur="validWebUser();"/>
												</div>

												<h5 id="webverify_label"><strong>Verify Password</strong></h5>
												<div class="form-group has-feedback">
													<input type="password" name="webverify" id="webverify" class="form-control input-sm" placeholder="[Required]" onFocus="validWebUser();" onKeyUp="validWebUser();" onBlur="validWebUser();"/>
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
												<button type="button" id="savewebuser" class="btn btn-primary btn-sm pull-right" onClick="saveWebUser();" disabled>Save</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

								<!-- Add/Edit AD Group Modal -->
								<div class="modal fade" id="ldapgroup-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 class="modal-title">Active Directory Group</h3>
											</div>
											<div class="modal-body">
												<h5 id="newldapgroup_label"><strong>Group Name</strong> <small>Active Directory group name.</small></h5>
												<div class="form-group has-feedback">
													<input type="text" id="newldapgroup" name="newldapgroup" class="form-control input-sm" placeholder="[Required]" value="" onFocus="validLdapGroup();" onKeyUp="validLdapGroup();" onBlur="validLdapGroup();"/>
												</div>
												<input type="hidden" id="renameldapgroup" name="deleteldapgroup" value=""/>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
												<button type="submit" id="saveldapgroup" name="saveldapgroup" class="btn btn-primary btn-sm pull-right" disabled>Save</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

								<!-- Delete AD Group Modal -->
								<div class="modal fade" id="deleteldap-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 id="deleteldap-title" class="modal-title">Delete Group?</h3>
											</div>
											<div class="modal-body">
												<div class="text-muted">Members of this group will no longer be able to log in to the web interface.</div>
											</div>
											<div class="modal-footer">
												<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
												<button type="submit" id="deleteldapgroup" name="deleteldapgroup" class="btn btn-danger btn-sm" value="">Delete</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

								<!-- AD Configuration Modal -->
								<div class="modal fade" id="ldap-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 class="modal-title">Active Directory</h3>
											</div>
											<div class="modal-body">
												<h5 id="ldapdomain_label"><strong>Domain</strong> <small>Active Directory fully qualified domain name.</small></h5>
												<div class="form-group has-feedback">
													<input type="text" name="ldapdomain" id="ldapdomain" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_domain; ?>" onFocus="validLdap();" onKeyUp="validLdap();" onBlur="validLdap();"/>
												</div>
												<h5 id="ldaphost_label"><strong>Server and Port</strong> <small>Hostname or IP address, and port number of the LDAP server.</small></h5>
												<div class="row">
													<div class="col-xs-8" style="padding-right: 0px; width: 73%;">
														<div class="has-feedback">
															<input type="text" name="ldaphost" id="ldaphost" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_host; ?>" onFocus="validLdap();" onKeyUp="validLdap();" onBlur="validLdap();"/>
														</div>
													</div>
													<div class="col-xs-1 text-center" style="padding-left: 0px; padding-right: 0px; width: 2%;">:</div>
													<div class="col-xs-3" style="padding-left: 0px;">
														<div class="has-feedback">
															<input type="text" name="ldapport" id="ldapport" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_port; ?>" onFocus="hideWarning(this); validLdap();" onKeyUp="validLdap();" onBlur="validLdap();"/>
														</div>
													</div>
												</div>
												<div class="checkbox checkbox-primary checkbox-inline" style="padding-top: 12px;">
													<input name="ldapscheme" id="ldapscheme" class="styled" type="checkbox" value="ldaps" onChange="toggleScheme(); validLdap();" <?php echo ($ldap_scheme == "ldaps" ? "checked" : ""); ?>>
													<label><strong>Use SSL</strong> <span style="font-size: 75%; color: #777;">Connect to the LDAP server over SSL. SSL must be enabled on the LDAP server for this to work.</span></label>
												</div>
												<h5 id="ldapbase_label"><strong>Search Base</strong> <small>Distinguished name of the LDAP search base.</small></h5>
												<div class="form-group has-feedback">
													<input type="text" name="ldapbase" id="ldapbase" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_base; ?>" onFocus="validLdap();" onKeyUp="validLdap();" onBlur="validLdap();"/>
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
												<button type="button" id="saveldap" class="btn btn-primary btn-sm pull-right" onClick="saveLdap();" disabled>Save</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

							</form>

						</div> <!-- /.tab-pane -->

						<div class="tab-pane fade in" id="system-tab">

							<form method="POST" name="system-form" id="system-form">

								<div style="padding: 8px 0px;" class="description">SYSTEM USERS DESCRIPTION</div>

<?php if ($conf->needsToChangePass("shellaccount") || $conf->needsToChangePass("smbaccount") || $conf->needsToChangePass("afpaccount")) { ?>
								<div style="padding-bottom: 8px;">
									<div class="text-muted"><span class="glyphicon glyphicon-exclamation-sign"></span> Credentials have not been changed for these user accounts.</div>
								</div>
<?php } ?>
								<br>

								<table id="sysusers-table" class="table table-striped">
									<thead>
										<tr>
											<th></th>
											<th>Full Name</th>
											<th>User Name</th>
											<th>Type</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
<?php foreach($sys_users as $sys_user) {
if ($sys_user['type'] != "System") { ?>
										<tr>
											<td><?php echo ($sys_user['default'] ? "<span class=\"glyphicon glyphicon-exclamation-sign\"></span>" : "&nbsp;"); ?></td>
											<td><a data-toggle="modal" href="#usermod-modal" onClick="$('#usermodlocked').val(<?php echo ($sys_user['locked'] ? "true" : "false"); ?>); $('#usermodlogin').val('<?php echo $sys_user['name']; ?>'); $('#usermodgecos').val('<?php echo $sys_user['gecos']; ?>'); $('#usermodtype').val('<?php echo $sys_user['type']; ?>'); $('#usernewmodgecos').val('<?php echo $sys_user['gecos']; ?>'); $('#usermodnewlogin').val('<?php echo $sys_user['name']; ?>');"><?php echo $sys_user['gecos']; ?></a></td>
											<td><?php echo $sys_user['name']; ?></a></td>
											<td><?php echo $sys_user['type']; ?></td>
											<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#userdel-modal" onClick="$('#userdelgecos').text('<?php echo $sys_user['gecos']; ?>'); $('#userdelhome').prop('checked', false); $('#userdelhome').prop('disabled', <?php echo ($sys_user['type'] == "Sharing" ? "true" : "false"); ?>); $('#userdel').val('<?php echo $sys_user['name']; ?>');" <?php echo ($sys_user['locked'] ? "disabled" : ""); ?>>Delete</button></td>
										</tr>
<?php }
} ?>
									</tbody>
								</table>

								<!-- System User Modal -->
								<div class="modal fade" id="usermod-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 id="usermod_title" class="modal-title">Modify User</h3>
											</div>
											<div class="modal-body">
												<input type="hidden" name="usermodlocked" id="usermodlocked" value=""/>
												<input type="hidden" name="usermodlogin" id="usermodlogin" value=""/>
												<input type="hidden" name="usermodgecos" id="usermodgecos" value=""/>
												<div id="usermodtype_wrapper">
													<h5><strong>Account Type</strong></h5>
													<select id="usermodtype" name="usermodtype" class="form-control input-sm" onFocus="validUserMod();">
														<option value="Administrator">Administrator</option>
														<option value="Standard" selected>Standard</option>
														<option value="Sharing">Sharing</option>
													</select>
												</div>
												<h5 id="usernewmodgecos_label"><strong>Full Name</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="text" name="usernewmodgecos" id="usernewmodgecos" class="form-control input-sm" onFocus="validUserMod();" onKeyUp="validUserMod();" onBlur="validUserMod();" placeholder="[Required]" value=""/>
												</div>
												<h5 id="usermodnewlogin_label"><strong>User Name</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="text" name="usermodnewlogin" id="usermodnewlogin" class="form-control input-sm" onFocus="validUserMod();" onKeyUp="validUserMod();" onBlur="validUserMod();" placeholder="[Required]" value=""/>
												</div>
												<h5 id="usermodpass_label"><strong>New Password</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="password" name="usermodpass" id="usermodpass" class="form-control input-sm" onFocus="validUserMod();" onKeyUp="validUserMod();" onBlur="validUserMod();" placeholder="[Required]" />
												</div>
												<h5 id="usermodverify_label"><strong>Verify Password</strong></h5>
												<div class="form-group">
													<input type="password" name="usermodverify" id="usermodverify" class="form-control input-sm" onFocus="validUserMod();" onKeyUp="validUserMod();" onBlur="validUserMod();" placeholder="[Required]" />
												</div>
												<div id="usermodadmin_wrapper" class="checkbox checkbox-primary checkbox-inline" style="padding-top: 12px;">
													<input name="usermodadmin" id="usermodadmin" class="styled" type="checkbox" value="true" onFocus="validUserMod();" onChange="toggleUserAdmin();">
													<label><strong>Allow User to Administer this Server</strong> <span style="font-size: 75%; color: #777;">DESCRIPTION</span></label>
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
												<button type="submit" name="usermod" id="usermod" class="btn btn-primary btn-sm" disabled>Save</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

								<!-- Delete User Modal -->
								<div class="modal fade" id="userdel-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 class="modal-title">Delete '<span id="userdelgecos">User</span>'</h3>
											</div>
											<div class="modal-body">
												<div class="text-muted">This action is permanent and cannot be undone.</div>
												<div class="checkbox checkbox-primary checkbox-inline" style="padding-top: 12px;">
													<input name="userdelhome" id="userdelhome" class="styled" type="checkbox" value="true">
													<label><strong>Delete Home Directory</strong> <span style="font-size: 75%; color: #777;">DESCRIPTION</span></label>
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
												<button type="submit" name="userdel" id="userdel" class="btn btn-danger btn-sm" value="">Delete</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

							</form>

						</div> <!-- /.tab-pane -->

					</div> <!-- /.tab-content -->

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>