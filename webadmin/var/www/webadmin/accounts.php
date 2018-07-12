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
if (isset($_POST['addsysuser'])) {
	suExec("addshelluser ".$_POST['addsysuserlogin']." \"".$_POST['addsysusergecos']."\" ".$_POST['addsysusertype']);
	if ($_POST['addsysusertype'] == "Administrator") {
		suExec("adminshelluser ".$_POST['addsysuserlogin']);
	}
	suExec("changeshellpass ".$_POST['addsysuserlogin']." \"".$_POST['addsysuserpass']."\"");
}
if (isset($_POST['savesysuser'])) {
	suExec("changeshelluser ".$_POST['sysuserlogin']." \"".$_POST['sysusergecos']."\" ".$_POST['sysuserhome']." ".$_POST['sysusernewlogin']." ".$_POST['sysusershell']." ".$_POST['sysusernewuid']);
	if ($_POST['sysuserlogin'] == $conf->getSetting("shelluser")) {
		$conf->setSetting("shelluser", $_POST['sysusernewlogin']);
		$conf->changedPass("shellaccount");
	} else {
		if ($_POST['sysuseradmin'] == "true") {
			suExec("addshelladmin ".$_POST['sysusernewlogin']);
		} else {
			suExec("remshelladmin ".$_POST['sysusernewlogin']);
		}
	}
}
if (isset($_POST['savesyspass'])) {
	if ($_POST['syspasslogin'] == "afpuser") {
		suExec("resetafppw ".$_POST['sysnewpass']);
		$conf->changedPass("afpaccount");
	} else {
		suExec("changeshellpass ".$_POST['syspasslogin']." \"".$_POST['sysnewpass']."\"");
		if ($_POST['syspasslogin'] == "smbuser") {
			$conf->changedPass("smbaccount");
		}
		if ($_POST['syspasslogin'] == $conf->getSetting("shelluser")) {
			$conf->changedPass("shellaccount");
		}
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
$user_shells_str = trim(suExec("getShellList"));
$user_shells = explode(" ", $user_shells_str);
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
	$sys_user['shell'] = basename(trim($entry_arr[6]));
	$sys_user['groups'] = array();
	foreach($sys_groups as $sys_group) {
		if (in_array($sys_user['name'], $sys_group['users'])) {
			array_push($sys_user['groups'], $sys_group['name']);
		}
	}
	if ($sys_user['uid'] < $uid_min || $sys_user['uid'] > $uid_max) {
		$sys_user['type'] = "System";
	} elseif ($sys_user['shell'] == "false" || $sys_user['shell'] == "nologin") {
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

			<script type="text/javascript" src="scripts/dataTables/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="scripts/dataTables/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/buttons.bootstrap.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#sysusers-table').DataTable( {
						buttons: [
							{
								text: '<span class="glyphicon glyphicon-plus"></span> Add',
								className: 'btn-primary btn-sm',
								action: function ( e, dt, node, config ) {
									$("#addsysuser-modal").modal();
								}
							}
						],
						"dom": "<'row'<'col-sm-4'f><'col-sm-4'i><'col-sm-4'<'dataTables_paginate'B>>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
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
				var web_default = <?php echo ($conf->needsToChangePass("webaccount") ? "true" : "false"); ?>;
				var ldap_groups = <?php echo sizeof($ldap_admins); ?>;
				var ldap_server = "<?php echo $ldap_server; ?>";

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
						$('#webadmin_warning').addClass('hidden');
						$('#webuser_warning').addClass('hidden');
						if (ldap_server == "" && ldap_groups == 0 || ldap_server != "" && ldap_groups > 0) {
							$('#webadmin-tab-icon').addClass('hidden');
						}
						$('#webpass').val('');
						$('#webnewpass').val('');
						$('#webverify').val('');
						$('#savewebuser').prop('disabled', true);
						$('#webuser-modal').modal('hide');
						web_default = false;
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
						$('#addldapgroup').prop('disabled', true);
						ldap_server = "";
					} else {
						ajaxPost('ajax.php', 'ldapdomain='+ldapdomain.value);
						ajaxPost('ajax.php', 'ldapserver='+ldapscheme.value+'://'+ldaphost.value+':'+ldapport.value);
						ajaxPost('ajax.php', 'ldapbase='+ldapbase.value);
						$('#ldapstatus').text($('#ldapdomain').val());
						$('#addldapgroup').prop('disabled', false);
						ldap_server = ldapscheme.value+'://'+ldaphost.value+':'+ldapport.value;
					}
					if (ldap_server != "" && ldap_groups == 0) {
						$('#group_error').removeClass('hidden');
					} else {
						$('#group_error').addClass('hidden');
					}
					if (ldap_server == "" && ldap_groups > 0) {
						$('#ldap_error').removeClass('hidden');
					} else {
						$('#ldap_error').addClass('hidden');
					}
					if (web_default == false) {
						if (ldap_server == "" && ldap_groups == 0 || ldap_server != "" && ldap_groups > 0) {
							$('#webadmin-tab-icon').addClass('hidden');
						} else {
							$('#webadmin-tab-icon').removeClass('hidden');
						}
					}
					$('#saveldap').prop('disabled', true);
					$('#ldap-modal').modal('hide');
				}

				function validAddSysUser() {
					var syslogins = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['name']; }, $sys_users))."\""; ?>];
					var addsysusergecos = document.getElementById('addsysusergecos');
					var addsysuserlogin = document.getElementById('addsysuserlogin');
					var addsysuserpass = document.getElementById('addsysuserpass');
					var addsysuserverify = document.getElementById('addsysuserverify');
					if (/^[^:]{0,128}$/.test(addsysusergecos.value)) {
						hideError(addsysusergecos, 'addsysusergecos_label');
					} else {
						showError(addsysusergecos, 'addsysusergecos_label');
					}
					if (/^[a-z_][a-z0-9_-]{1,31}$/.test(addsysuserlogin.value) && syslogins.indexOf(addsysuserlogin.value) == -1) {
						hideError(addsysuserlogin, 'addsysuserlogin_label');
					} else {
						showError(addsysuserlogin, 'addsysuserlogin_label');
					}
					if (/^.{1,128}$/.test(addsysuserpass.value)) {
						hideError(addsysuserpass, 'addsysuserpass_label');
					} else {
						showError(addsysuserpass, 'addsysuserpass_label');
					}
					if (/^.{1,128}$/.test(addsysuserverify.value) && addsysuserverify.value == addsysuserpass.value) {
						hideError(addsysuserverify, 'addsysuserverify_label');
					} else {
						showError(addsysuserverify, 'addsysuserverify_label');
					}
					if (/^[^:]{0,128}$/.test(addsysusergecos.value) && /^[a-z_][a-z0-9_-]{1,31}$/.test(addsysuserlogin.value) && syslogins.indexOf(addsysuserlogin.value) && /^.{1,128}$/.test(addsysuserpass.value) && addsysuserpass.value == addsysuserverify.value) {
						$('#addsysuser').prop('disabled', false);
					} else {
						$('#addsysuser').prop('disabled', true);
					}
				}

				function validSysPass() {
					var sysnewpass = document.getElementById('sysnewpass');
					var syspassverify = document.getElementById('syspassverify');
					if (/^.{1,128}$/.test(sysnewpass.value)) {
						hideError(sysnewpass, 'sysnewpass_label');
					} else {
						showError(sysnewpass, 'sysnewpass_label');
					}
					if (/^.{1,128}$/.test(syspassverify.value) && syspassverify.value == sysnewpass.value) {
						hideError(syspassverify, 'syspassverify_label');
					} else {
						showError(syspassverify, 'syspassverify_label');
					}
					if (/^.{1,128}$/.test(sysnewpass.value) && syspassverify.value == sysnewpass.value) {
						$('#savesyspass').prop('disabled', false);
					} else {
						$('#savesyspass').prop('disabled', true);
					}
				}

				function validSysUser() {
					var sysuidmin = <?php echo $uid_min; ?>;
					var sysuidmax = <?php echo $uid_max; ?>;
					var sysuids = [<?php echo implode(', ', array_map(function($el){ return $el['uid']; }, $sys_users)); ?>];
					var syslogins = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['name']; }, $sys_users))."\""; ?>];
					var sysgecos = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['gecos']; }, $sys_users))."\""; ?>];
					var sysuserlocked = document.getElementById('sysuserlocked');
					var sysuseruid = document.getElementById('sysuseruid');
					var sysuserlogin = document.getElementById('sysuserlogin');
					var sysusernewuid = document.getElementById('sysusernewuid');
					var sysusernewlogin = document.getElementById('sysusernewlogin');
					var sysusergecos = document.getElementById('sysusergecos');
					var sysusershell = document.getElementById('sysusershell');
					var sysuserhome = document.getElementById('sysuserhome');
					var sysusertype = document.getElementById('sysusertype');
					var sysuseradmin = document.getElementById('sysuseradmin');
					if (sysusernewuid.value == parseInt(sysusernewuid.value) && (sysuids.indexOf(parseInt(sysusernewuid.value)) == -1 || sysusernewuid.value == sysuseruid.value) && sysusernewuid.value >= sysuidmin && sysusernewuid.value <= sysuidmax) {
						hideError(sysusernewuid, 'sysusernewuid_label');
					} else {
						showError(sysusernewuid, 'sysusernewuid_label');
					}
					if (/^[a-z_][a-z0-9_-]{1,31}$/.test(sysusernewlogin.value) && (syslogins.indexOf(sysusernewlogin.value) == -1 || sysusernewlogin.value == sysuserlogin.value)) {
						hideError(sysusernewlogin, 'sysusernewlogin_label');
					} else {
						showError(sysusernewlogin, 'sysusernewlogin_label');
					}
					if (/^[^:]{0,128}$/.test(sysusergecos.value)) {
						hideError(sysusergecos, 'sysusergecos_label');
					} else {
						showError(sysusergecos, 'sysusergecos_label');
					}
					if (sysusershell.value == "nologin" || sysusershell.value == "false") {
						sysuserhome.readOnly = true;
						sysuserhome.value = "/dev/null";
						sysuseradmin.checked = false;
						sysusertype.value = "Sharing";
					} else {
						sysuserhome.readOnly = false;
						if (/^(\/)[^\0:]*$/.test(sysuserhome.value) && sysuserhome.value != "/dev/null") {
							hideError(sysuserhome, 'sysuserhome_label');
						} else {
							showError(sysuserhome, 'sysuserhome_label');
						}
						if (sysuseradmin.checked) {
							sysusertype.value = "Administrator";
						} else {
							sysusertype.value = "Standard";
						}
					}
					if (sysuserlocked.value == "true" || sysusershell.value == "false" || sysusershell.value == "nologin") {
						sysuseradmin.disabled = true;
					} else {
						sysuseradmin.disabled = false;
					}
					if (sysusernewuid.value == parseInt(sysusernewuid.value) && (sysuids.indexOf(parseInt(sysusernewuid.value)) == -1 || sysusernewuid.value == sysuseruid.value) && sysusernewuid.value >= sysuidmin && sysusernewuid.value <= sysuidmax && /^[a-z_][a-z0-9_-]{1,31}$/.test(sysusernewlogin.value) && (syslogins.indexOf(sysusernewlogin.value) == -1 || sysusernewlogin.value == sysuserlogin.value) && /^[^:]{0,128}$/.test(sysusergecos.value) && (sysusertype.value == "Sharing" || sysusertype.value != "Sharing" && /^(\/)[^\0:]*$/.test(sysuserhome.value) && sysuserhome.value != "/dev/null")) {
						$('#savesysuser').prop('disabled', false);
					} else {
						$('#savesysuser').prop('disabled', true);
					}
				}
			</script>

			<script type="text/javascript">
				$(document).ready(function(){
					$('#sysuser-modal').on('show.bs.modal', function(e) {
						$('#sysusernewuid').val($('#sysuseruid').val());
						$('#sysusernewlogin').val($('#sysuserlogin').val());
						if ($('#sysusertype').val() == 'Administrator') {
							$('#sysuseradmin').prop('checked', true);
						} else {
							$('#sysuseradmin').prop('checked', false);
						}
						if ($('#sysuserlocked').val() == 'true') {
							$('#sysusershell option[value="false"]').prop('disabled', true);
							$('#sysusershell option[value="nologin"]').prop('disabled', true);
						} else {
							$('#sysusershell option[value="false"]').prop('disabled', false);
							$('#sysusershell option[value="nologin"]').prop('disabled', false);
						}
						validSysUser();
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
						<li class="active"><a class="tab-font" href="#webadmin-tab" role="tab" data-toggle="tab"><span id="webadmin-tab-icon" class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("webaccount") || $ldap_server != "" && sizeof($ldap_admins) == 0 || $ldap_server == "" && sizeof($ldap_admins) > 0 ? "" : "hidden"); ?>"></span> Web Interface</a></li>
						<li><a class="tab-font" href="#system-tab" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("shellaccount") || $conf->needsToChangePass("smbaccount") || $conf->needsToChangePass("afpaccount") ? "" : "hidden"); ?>"></span> System Users</a></li>
					</ul>

					<div class="tab-content">

						<div class="tab-pane active fade in" id="webadmin-tab">

							<form method="POST" name="webadmin-form" id="webadmin-form">

								<div style="padding: 12px 0px;" class="description">WEB INTERFACE DESCRIPTION</div>

								<div id="webadmin_warning" class="<?php echo ($conf->needsToChangePass("webaccount") ? "" : "hidden"); ?>" style="padding-bottom: 12px;">
									<div class="text-muted"><span class="glyphicon glyphicon-exclamation-sign"></span> Credentials have not been changed for built-in account.</div>
								</div>

								<div id="group_error" class="<?php echo ($ldap_server != "" && sizeof($ldap_admins) == 0 ? "" : "hidden"); ?>" style="padding-bottom: 12px;">
									<div class="text-danger"><span class="glyphicon glyphicon-exclamation-sign"></span> At least one group is required for Active Directory login.</div>
								</div>

								<div id="ldap_error" class="<?php echo ($ldap_server == "" && sizeof($ldap_admins) > 0 ? "" : "hidden"); ?>" style="padding-bottom: 12px;">
									<div class="text-danger"><span class="glyphicon glyphicon-exclamation-sign"></span> Active Directory must be configured for group members to login.</div>
								</div>

								<div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
									<div class="row">
										<div class="col-sm-10">
											<div class="dataTables_filter">
												<h5><strong>Users &amp; Groups</strong> <small>Users &amp; groups for administering the server.</small></h5>
											</div>
										</div>
										<div class="col-sm-2">
											<div class="dataTables_paginate">
												<div class="btn-group">
													<button id="addldapgroup" type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#ldapgroup-modal" onClick="$('#renameldapgroup').val(''); $('#newldapgroup').val('');" <?php echo ($ldap_server == "" ? "disabled": ""); ?>><span class="glyphicon glyphicon-plus"></span> Add</button>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-sm-12">
											<table class="table table-striped">
												<thead>
													<tr>
														<th></th>
														<th>Name</th>
														<th>Type</th>
														<th></th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td><span id="webuser_warning" class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("webaccount") ? "" : "hidden"); ?>"></span></td>
														<td><a data-toggle="modal" href="#webuser-modal"><span id="webuser_name"><?php echo $web_user; ?></span></a></td>
														<td>Built-in account.</td>
														<td align="right"><button type="button" class="btn btn-default btn-sm" disabled>Delete</button></td>
													</tr>
<?php foreach ($ldap_admins as $key => $value) { ?>
													<tr>
														<td><span id="webuser_warning" class="glyphicon glyphicon-exclamation-sign <?php echo ($ldap_server == "" && sizeof($ldap_admins) > 0 ? "" : "hidden"); ?>"></span></td>
														<td><a data-toggle="modal" href="#ldapgroup-modal" onClick="$('#renameldapgroup').val('<?php echo $value["cn"]; ?>'); $('#newldapgroup').val('<?php echo $value["cn"]; ?>');"><?php echo $value["cn"]; ?></a></td>
														<td>Active Directory group.</td>
														<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#deleteldap-modal" onClick="$('#deleteldap-title').text('Delete \'<?php echo $value["cn"]; ?>\'?'); $('#deleteldapgroup').val('<?php echo $value["cn"]; ?>');">Delete</button></td>
													</tr>
<?php } ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>

								<br>

								<h5><strong>Active Directory</strong> <small>Allow login to the web interface using Active Directory.</small></h5>

								<div style="padding-bottom: 12px;">Domain: <a data-toggle="modal" data-target="#ldap-modal"><span id="ldapstatus"><?php echo (empty($ldap_server) || empty($ldap_domain) || empty($ldap_base) ? "Not Configured" : $ldap_domain); ?></span></a></div>
								<!-- <button type="button" id="configure_ldap" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#ldap-modal"><?php echo (empty($ldap_server) || empty($ldap_domain) || empty($ldap_base) ? "Configure" : "Modify"); ?></button> -->

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

								<div style="padding: 12px 0px;" class="description">SYSTEM USERS DESCRIPTION</div>

<?php if ($conf->needsToChangePass("shellaccount") || $conf->needsToChangePass("smbaccount") || $conf->needsToChangePass("afpaccount")) { ?>
								<div style="padding-bottom: 12px;">
									<div class="text-muted"><span class="glyphicon glyphicon-exclamation-sign"></span> Credentials have not been changed for these user accounts.</div>
								</div>
<?php } ?>

								<table id="sysusers-table" class="table table-striped">
									<thead>
										<tr>
											<th></th>
											<th>User Name</th>
											<th>Full Name</th>
											<th>Type</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
<?php foreach($sys_users as $sys_user) {
if ($sys_user['type'] != "System") { ?>
										<tr>
											<td><?php echo ($sys_user['default'] ? "<span class=\"glyphicon glyphicon-exclamation-sign\"></span>" : "&nbsp;"); ?></td>
											<td>
												<div class="dropdown">
													<a href="#" id="sysuser<?php echo $sys_user['uid']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><?php echo $sys_user['name']; ?></a>
													<ul class="dropdown-menu" aria-labelledby="sysuser<?php echo $sys_user['uid']; ?>">
														<li class="<?php echo ($sys_user['name'] == "afpuser" || $sys_user['name'] == "smbuser" ? "disabled" : ""); ?>"><a data-toggle="modal" href="<?php echo ($sys_user['name'] == "afpuser" || $sys_user['name'] == "smbuser" ? "" : "#sysuser-modal"); ?>" onClick="$('#sysuserlocked').val(<?php echo ($sys_user['locked'] ? "true" : "false"); ?>); $('#sysuseruid').val('<?php echo $sys_user['uid']; ?>'); $('#sysuserlogin').val('<?php echo $sys_user['name']; ?>'); $('#sysusergecos').val('<?php echo $sys_user['gecos']; ?>'); $('#sysusershell').val('<?php echo $sys_user['shell']; ?>'); $('#sysuserhome').val('<?php echo $sys_user['home']; ?>'); $('#sysusertype').val('<?php echo $sys_user['type']; ?>');">Modify User</a></li>
														<li><a data-toggle="modal" href="#syspass-modal" onClick="$('#syspass_title').text('<?php echo $sys_user['gecos']; ?>'); $('#syspasslogin').val('<?php echo $sys_user['name']; ?>'); $('#sysnewpass').val(''); $('#syspassverify').val('');">Reset Password</a></li>
													</ul>
												</div>
												
											</td>
											<td><?php echo $sys_user['gecos']; ?></td>
											<td><?php echo $sys_user['type']; ?></td>
											<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#userdel-modal" onClick="$('#userdelgecos').text('<?php echo $sys_user['gecos']; ?>'); $('#userdelhome').prop('checked', false); $('#userdelhome').prop('disabled', <?php echo ($sys_user['type'] == "Sharing" ? "true" : "false"); ?>); $('#userdel').val('<?php echo $sys_user['name']; ?>');" <?php echo ($sys_user['locked'] ? "disabled" : ""); ?>>Delete</button></td>
										</tr>
<?php }
} ?>
									</tbody>
								</table>

								<!-- Add System User Modal -->
								<div class="modal fade" id="addsysuser-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 class="modal-title">Add User</h3>
											</div>
											<div class="modal-body">
												<h5><strong>Account Type</strong></h5>
												<select id="addsysusertype" name="addsysusertype" class="form-control input-sm" onFocus="validAddSysUser();">
													<option value="Administrator">Administrator</option>
													<option value="Standard" selected>Standard</option>
													<option value="Sharing">Sharing</option>
												</select>
												<h5 id="addsysuserlogin_label"><strong>User Name</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="text" name="addsysuserlogin" id="addsysuserlogin" class="form-control input-sm" onFocus="validAddSysUser();" onKeyUp="validAddSysUser();" onBlur="validAddSysUser();" placeholder="[Required]" value=""/>
												</div>
												<h5 id="addsysusergecos_label"><strong>Full Name</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="text" name="addsysusergecos" id="addsysusergecos" class="form-control input-sm" onFocus="validAddSysUser();" onKeyUp="validAddSysUser();" onBlur="validAddSysUser();" placeholder="[Optional]" value=""/>
												</div>
												<h5 id="addsysuserpass_label"><strong>New Password</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="password" name="addsysuserpass" id="addsysuserpass" class="form-control input-sm" onFocus="validAddSysUser();" onKeyUp="validAddSysUser();" onBlur="validAddSysUser();" placeholder="[Required]" value=""/>
												</div>
												<h5 id="addsysuserverify_label"><strong>Verify Password</strong></h5>
												<div class="form-group">
													<input type="password" name="addsysuserverify" id="addsysuserverify" class="form-control input-sm" onFocus="validAddSysUser();" onKeyUp="validAddSysUser();" onBlur="validAddSysUser();" placeholder="[Required]" value=""/>
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
												<button type="submit" name="addsysuser" id="addsysuser" class="btn btn-primary btn-sm" disabled>Save</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

								<!-- System User Modal -->
								<div class="modal fade" id="sysuser-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 id="sysuser_title" class="modal-title">Modify User</h3>
											</div>
											<div class="modal-body">
												<input type="hidden" name="sysuserlocked" id="sysuserlocked" value=""/>
												<h5 id="sysusernewuid_label"><strong>User ID</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="hidden" name="sysuseruid" id="sysuseruid" value=""/>
													<input type="text" name="sysusernewuid" id="sysusernewuid" class="form-control input-sm" onFocus="validSysUser();" onKeyUp="validSysUser();" onBlur="validSysUser();" placeholder="[Required]" value=""/>
												</div>
												<h5 id="sysusernewlogin_label"><strong>User Name</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="hidden" name="sysuserlogin" id="sysuserlogin" value=""/>
													<input type="text" name="sysusernewlogin" id="sysusernewlogin" class="form-control input-sm" onFocus="validSysUser();" onKeyUp="validSysUser();" onBlur="validSysUser();" placeholder="[Required]" value=""/>
												</div>
												<h5 id="sysusergecos_label"><strong>Full Name</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="text" name="sysusergecos" id="sysusergecos" class="form-control input-sm" onFocus="validSysUser();" onKeyUp="validSysUser();" onBlur="validSysUser();" placeholder="[Optional]" value=""/>
												</div>
												<h5><strong>Login Shell</strong></h5>
												<select id="sysusershell" name="sysusershell" class="form-control input-sm" onFocus="validSysUser();" onChange="validSysUser();" onBlur="validSysUser();">
<?php foreach ($user_shells as $user_shell) { ?>
													<option value="<?php echo basename($user_shell); ?>"><?php echo $user_shell; ?></option>
<?php } ?>
												</select>
												<h5 id="sysuserhome_label"><strong>Home Directory</strong> <small>DESCRIPTION</small></h5>
												<div class="form-group">
													<input type="text" name="sysuserhome" id="sysuserhome" class="form-control input-sm" onFocus="validSysUser();" onKeyUp="validSysUser();" onBlur="validSysUser();" placeholder="[Required]" value=""/>
												</div>
												<input type="hidden" name="sysusertype" id="sysusertype" value=""/>
												<div id="sysuseradmin_wrapper" class="checkbox checkbox-primary checkbox-inline" style="padding-top: 12px;">
													<input name="sysuseradmin" id="sysuseradmin" class="styled" type="checkbox" value="true" onChange="validSysUser();">
													<label><strong>Allow User to Administer this Server</strong> <span style="font-size: 75%; color: #777;">DESCRIPTION</span></label>
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
												<button type="submit" name="savesysuser" id="savesysuser" class="btn btn-primary btn-sm" disabled>Save</button>
											</div>
										</div>
									</div>
								</div>
								<!-- /.modal -->

								<!-- System Password Modal -->
								<div class="modal fade" id="syspass-modal" tabindex="-1" role="dialog">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<h3 class="modal-title">Reset Password</h3>
											</div>
											<div class="modal-body">
												<input type="hidden" name="syspasslogin" id="syspasslogin" value=""/>
												<h5 id="sysnewpass_label"><strong>New Password</strong> <small>New password for <strong><span id="syspass_title">Username</span></strong>.</small></h5>
												<div class="form-group">
													<input type="password" name="sysnewpass" id="sysnewpass" class="form-control input-sm" onFocus="validSysPass();" onKeyUp="validSysPass();" onBlur="validSysPass();" placeholder="[Required]" />
												</div>
												<h5 id="syspassverify_label"><strong>Verify Password</strong></h5>
												<div class="form-group">
													<input type="password" name="syspassverify" id="syspassverify" class="form-control input-sm" onFocus="validSysPass();" onKeyUp="validSysPass();" onBlur="validSysPass();" placeholder="[Required]" />
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
												<button type="submit" name="savesyspass" id="savesyspass" class="btn btn-primary btn-sm" disabled>Save</button>
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