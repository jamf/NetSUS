<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Accounts";

include "inc/header.php";

// Active Directory
if (isset($_POST['addadmin']) && isset($_POST['cn']) && $_POST['cn'] != "") {
	$conf->addAdmin($_POST['cn']);
}
if (isset($_POST['deleteAdmin']) && $_POST['deleteAdmin'] != "") {
	$conf->deleteAdmin($_POST['deleteAdmin']);
}

$ldap_scheme = "";
$ldap_host = "";
$ldap_port = "";
$ldap_url = $conf->getSetting("ldapserver");
$ldap_arr = explode(":", $ldap_url);
if (sizeof($ldap_arr) > 1) {
	$ldap_scheme = $ldap_arr[0];
	$ldap_host = str_replace('/', '', $ldap_arr[1]);
	$ldap_port = $ldap_arr[2];
}
$ldap_domain = $conf->getSetting("ldapdomain");
$ldap_base = $conf->getSetting("ldapbase");
$ldap_groups = $conf->getAdmins();

// System Users
$sysPassStatus = "";
$sysPassError = "";
if ($conf->getSetting("shelluser") != "shelluser") {
	$conf->changedPass("shellaccount");
}
if (isset($_POST['saveSysUser'])) {
	// ToDo: Find more secure method
	// Have to pass the passwords in clear text, unfortunately
	if ($_POST['sysUser'] == "afpuser") {
		$result = suExec("resetafppw ".$_POST['sysPass']);
		if (strpos($result,'BAD PASSWORD') !== false) {
			$sysPassError = $result;
		} else {
			$sysPassStatus = "Password changed for ".$_POST['sysUser'].".";
			$conf->changedPass("afpaccount");
		}
	} elseif ($_POST['sysUser'] == "smbuser") {
		suExec("resetsmbpw ".$_POST['sysPass']);
		$sysPassStatus = "Password changed for ".$_POST['sysUser'].".";
		$conf->changedPass("smbaccount");
	} else {
		suExec("changeshellpass ".$_POST['sysUser']." ".$_POST['sysPass']);
		$sysPassStatus = "Password changed for ".$_POST['sysUser'].".";
		if ($_POST['sysUser'] == $conf->getSetting("shelluser")) {
			$conf->changedPass("shellaccount");
		}
	}
}

$uid_min = preg_split("/\s+/", implode(preg_grep("/\bUID_MIN\b/i", file("/etc/login.defs"))))[1];
$uid_max = preg_split("/\s+/", implode(preg_grep("/\bUID_MAX\b/i", file("/etc/login.defs"))))[1];
$sys_groups = array();
foreach(file("/etc/group") as $entry) {
	$entry_arr = explode(":", $entry);
	$sys_groups[$entry_arr[2]] = $entry_arr;
}
$sys_users = array();
foreach(file("/etc/passwd") as $entry) {
	$entry_arr = explode(":", $entry);
	$sys_users[$entry_arr[2]] = $entry_arr;
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>

<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>

<script type="text/javascript" src="scripts/acctsValidation.js"></script>

<script type="text/javascript">
// Functions to display warnings / errors
var ldapUrl = "<?php echo $ldap_url; ?>";
var ldapDom = "<?php echo $ldap_domain; ?>";
var ldapBase = "<?php echo $ldap_base; ?>";
var ldapGroups = "<?php echo sizeof($ldap_groups); ?>";

function showLdapError() {
	$('#activedir-tab-link').css('color', '#a94442');
	$('#activedir-tab-icon').removeClass('hidden');
}
function hideLdapError() {
	$('#activedir-tab-link').removeAttr('style');
	$('#activedir-tab-icon').addClass('hidden');
}

function validLdap() {
	var ldapHost = document.getElementById('ldaphost');
	var ldapDomain = document.getElementById('domain');
	var ldapBaseDn = document.getElementById('basedn');
	hideLdapError();
	if (ldapHost.value != "" && ldapHost.parentElement.classList.contains("has-error") == false) {
		if (ldapDomain.parentElement.classList.contains("has-error")) {
			showLdapError();
		}
		if (ldapBaseDn.parentElement.classList.contains("has-error")) {
			showLdapError();
		}
		if (ldapGroups == "0") {
			showLdapError();
		}
	}
}

$(document).ready(function() {
	if (ldapUrl != "") {
		if (ldapDom == "") {
			showLdapError();
			showError(document.getElementById('domain'), 'domain_label');
		}
		if (ldapBase == "") {
			showLdapError();
			showError(document.getElementById('basedn'), 'basedn_label');
		}
		if (ldapGroups == "0") {
			showLdapError();
		}
	}
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
			<li class="active"><a class="tab-font" href="#webadmin-tab" role="tab" data-toggle="tab"><span id="webadmin-tab-icon" class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("webaccount") ? "" : "hidden"); ?>"></span> Built-In Account</a></li>
			<li><a class="tab-font" id="activedir-tab-link" href="#activedir-tab" role="tab" data-toggle="tab"><span id="activedir-tab-icon" class="glyphicon glyphicon-exclamation-sign hidden"></span> Active Directory</a></li>
			<li><a class="tab-font" href="#system-tab" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-exclamation-sign <?php echo ($conf->needsToChangePass("shellaccount") || $conf->needsToChangePass("smbaccount") || $conf->needsToChangePass("afpaccount") ? "" : "hidden"); ?>"></span> System Users</a></li>
		</ul>

		<div class="tab-content">

			<div class="tab-pane active fade in" id="webadmin-tab">
				<form method="POST" name="WebAdmin" id="WebAdmin">

					<div style="padding: 8px 0px;" class="description">WEBADMIN DESCRIPTION</div>
					<div id="webadmin-alert-msg" class="<?php echo ($conf->needsToChangePass("webaccount") ? "" : "hidden"); ?>" style="padding-bottom: 8px;">
						<div class="text-muted"><span class="glyphicon glyphicon-exclamation-sign"></span> Credentials have not been changed for the built-in user account.</div>
					</div>

					<input type="hidden" name="webadminuser" id="webadminuser" value="<?php echo $conf->getSetting("webadminuser");?>"/>

					<h5 id="webadmin_label"><strong>Username</strong> <small>Username for the account.</small></h5>
					<div class="form-group has-feedback">
						<input type="text" name="webadmin" id="webadmin" class="form-control input-sm"  value="<?php echo $conf->getSetting("webadminuser");?>" onFocus="validWebUser(this, 'webadmin_label');" onKeyUp="validWebUser(this, 'webadmin_label');" onChange="updateWebUser(this);"/>
					</div>

					<h5 id="webpass_label"><strong>Current Password</strong> <small>Current password for the account.</small></h5>
					<div class="form-group has-feedback">
						<input type="password" name="webpass" id="webpass" class="form-control input-sm" onChange="updateWebPass('webpass', 'newpassweb', 'verifyweb');" />
					</div>

					<h5 id="newpassweb_label"><strong>New Password</strong> <small>New password for the account.</small></h5>
					<div class="form-group has-feedback">
						<input type="password" name="newpassweb" id="newpassweb" class="form-control input-sm" onFocus="verifyWebPass('newpassweb', 'verifyweb');" onKeyUp="verifyWebPass('newpassweb', 'verifyweb');" onChange="updateWebPass('webpass', 'newpassweb', 'verifyweb');" />
					</div>

					<h5 id="verifyweb_label"><strong>Verify Password</strong></h5>
					<div class="form-group has-feedback">
						<input type="password" name="verifyweb" id="verifyweb" class="form-control input-sm" onFocus="verifyWebPass('newpassweb', 'verifyweb');" onKeyUp="verifyWebPass('newpassweb', 'verifyweb');" onChange="updateWebPass('webpass', 'newpassweb', 'verifyweb');" />
					</div>

				</form>
			</div><!-- /.tab-pane -->

			<div class="tab-pane fade in" id="activedir-tab">
				<form method="POST" name="LDAP" id="LDAP">

					<div style="padding: 8px 0px;" class="description">ACTIVE DIRECTORY DESCRIPTION</div>

					<h5 id="ldaphost_label"><strong>Server and Port</strong> <small>Hostname or IP address, and port number of the LDAP server.</small></h5>
					<div class="row">
						<div class="col-xs-8" style="padding-right: 0px; width: 73%;">
							<div class="has-feedback">
								<input type="text" name="ldaphost" id="ldaphost" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_host; ?>" onFocus="validHost(this, 'ldaphost_label');" onKeyUp="validHost(this, 'ldaphost_label');" onChange="updateHost('ldapscheme', 'ldaphost', 'ldapport'); validLdap();" />
							</div>
						</div>
						<div class="col-xs-1 text-center" style="padding-left: 0px; padding-right: 0px; width: 2%;">:</div>
						<div class="col-xs-3" style="padding-left: 0px;">
							<div class="has-feedback">
								<input type="text" name="ldapport" id="ldapport" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_port; ?>" onFocus="validPort(this, 'ldaphost_label');" onKeyUp="validPort(this, 'ldaphost_label');" onChange="updatePort('ldapscheme', 'ldaphost', 'ldapport');" />
							</div>
						</div>
					</div>

					<br>

					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="ldapscheme" id="ldapscheme" class="styled" type="checkbox" value="ldaps" onChange="updateScheme('ldapscheme', 'ldaphost', 'ldapport');" <?php echo ($ldap_scheme == "ldaps" ? "checked" : ""); ?>>
						<label><strong>Use SSL</strong> <span style="font-size: 75%; color: #777;">Connect to the LDAP server over SSL. SSL must be enabled on the LDAP server for this to work.</span></label>
					</div>

					<br>
					<br>

					<h5 id="domain_label"><strong>Domain</strong> <small>Active Directory fully qualified domain name.</small></h5>
					<div class="form-group has-feedback">
						<input type="text" name="domain" id="domain" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_domain; ?>" onFocus="validDomain('domain', 'ldaphost');" onKeyUp="validDomain('domain', 'ldaphost');" onChange="updateDomain('domain', 'ldaphost'); validLdap();" />
					</div>

					<h5 id="basedn_label"><strong>Search Base</strong> <small>Distinguished name of the search base.</small></h5>
					<div class="form-group has-feedback">
						<input type="text" name="basedn" id="basedn" class="form-control input-sm" placeholder="[Required]" value="<?php echo $ldap_base; ?>" onFocus="validBaseDn('basedn', 'ldaphost');" onKeyUp="validBaseDn('basedn', 'ldaphost');" onChange="updateBaseDn('basedn', 'ldaphost'); validLdap();" />
					</div>

					<br>

					<h5><strong>Administration Groups</strong></a> <small>Active Directory groups allowed to administer this server.</small></h5>
					<?php if (sizeof($ldap_groups) == 0) { ?>
					<div style="padding: 8px 0px;" class="text-danger"><span class="glyphicon glyphicon-exclamation-sign"></span> At least one group is required.</div>
					<?php } ?>

					<table class="table table-striped">
						<tfoot>
							<tr>
								<td colspan="2" align="right"><button id="addgroup" type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addAdGroup" onClick="hideError(document.getElementById('cn'), 'cn_label');"><span class="glyphicon glyphicon-plus"></span> Add</button></td>
							</tr>
						</tfoot>
						<tbody>
							<?php foreach ($ldap_groups as $key => $value) { ?>
							<tr>
								<td><?php echo $value['cn']?></td>
								<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#deleteAdGroup" onClick="document.getElementById('deleteAdmin').value = '<?php echo $value['cn']?>';">Delete</button></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>

					<div class="modal fade" id="addAdGroup" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h3 class="modal-title">Add Group</h3>
								</div>
								<div class="modal-body">

									<h5 id="cn_label"><strong>Group Name</strong> <small>Active Directory group name.</small></h5>
									<div class="form-group">
										<input type="text" name="cn" id="cn" class="form-control input-sm" onFocus="validGroup(this, 'cn_label');" onKeyUp="validGroup(this, 'cn_label');" onBlur="validGroup(this, 'cn_label');" placeholder="[Required]" />
									</div>

								</div>
								<div class="modal-footer">
									<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
									<button type="submit" name="addadmin" id="addadmin" class="btn btn-primary btn-sm" disabled >Save</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="deleteAdGroup" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h3 class="modal-title" id="del_group_title">Delete Group?</h3>
								</div>
								<div class="modal-body">
									<div class="text-muted">This action is permanent and cannot be undone.</div>
								</div>
								<div class="modal-footer">
									<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
									<button type="submit" name="deleteAdmin" id="deleteAdmin" class="btn btn-danger btn-sm" value="">Delete</button>
								</div>
							</div>
						</div>
					</div>

				</form>
			</div><!-- /.tab-pane -->

			<div class="tab-pane fade in" id="system-tab">
				<form method="POST" name="ShellForm" id="ShellForm">

					<div style="padding: 8px 0px;" class="description">SYSTEM DESCRIPTION</div>

					<?php if (!empty($sysPassStatus)) { ?>
					<div style="padding-bottom: 8px;">
						<div class="text-success"><span class="glyphicon glyphicon-ok-sign"></span> <?php echo $sysPassStatus; ?></div>
					</div>
					<?php }
					if (!empty($sysPassError)) { ?>
					<div style="padding-bottom: 8px;">
						<div class="text-danger"><span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo $sysPassError; ?></div>
					</div>
					<?php }
					if ($conf->needsToChangePass("shellaccount") || $conf->needsToChangePass("smbaccount") || $conf->needsToChangePass("afpaccount")) { ?>
					<div style="padding-bottom: 8px;">
						<div class="text-muted"><span class="glyphicon glyphicon-exclamation-sign"></span> Credentials have not been changed for these user accounts.</div>
					</div>
					<?php } ?>

					<table class="table table-striped">
						<thead>
							<tr>
								<th></th>
								<th>User Name</th>
								<th>User ID</th>
								<th>Primary Group</th>
								<th>Full Name</th>
								<th>Login Shell</th>
								<th>Home Directory</th>
								<!-- <th></th> -->
							</tr>
						</thead>
						<tbody>
						<?php foreach($sys_users as $key => $value) {
						if ($key >= $uid_min && $key <= $uid_max) { ?>
							<tr>
								<td><?php echo ($value[0] == "shelluser" && $conf->needsToChangePass("shellaccount") || $value[0] == "smbuser" && $conf->needsToChangePass("smbaccount") || $value[0] == "afpuser" && $conf->needsToChangePass("afpaccount") ? "<span class=\"glyphicon glyphicon-exclamation-sign\"></span>" : ""); ?></td>
								<td><a data-toggle="modal" href="#editSysUser" onClick="sysUserModal('<?php echo $value[0]; ?>', '<?php echo $value[4]; ?>', '<?php echo trim($value[6]); ?>', '<?php echo $value[5]; ?>');"><?php echo $value[0]; ?></a></td>
								<td><?php echo $value[2]; ?></td>
								<td><?php echo $sys_groups[$value[3]][0]; ?></td>
								<td><?php echo $value[4]; ?></td>
								<td><?php echo $value[6]; ?></td>
								<td><?php echo $value[5]; ?></td>
								<!-- <td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#deleteUser" onClick="" <?php echo ($value[0] == "afpuser" || $value[0] == "smbuser" ? "disabled" : ""); ?>>Delete</button></td> -->
							</tr>
						<?php }
						} ?>
						</tbody>
					</table>

					<div class="modal fade" id="editSysUser" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h3 class="modal-title">User Properties</h3>
								</div>
								<div class="modal-body">

									<h5 id="sysUser_label"><strong>User Name</strong> <small>DESCRIPTION</small></h5>
									<div class="form-group">
										<input type="text" name="sysUser" id="sysUser" class="form-control input-sm" onFocus="" onKeyUp="" onBlur="" placeholder="[Required]" readonly />
									</div>

									<h5 id="sysGecos_label"><strong>Full Name</strong> <small>DESCRIPTION</small></h5>
									<div class="form-group">
										<input type="text" name="sysGecos" id="sysGecos" class="form-control input-sm" onFocus="" onKeyUp="" onBlur="" placeholder="[Required]" readonly />
									</div>

									<h5 id="sysPass_label"><strong>New Password</strong> <small>DESCRIPTION</small></h5>
									<div class="form-group">
										<input type="password" name="sysPass" id="sysPass" class="form-control input-sm" onFocus="verifySysPass('sysPass', 'sysVerify');" onKeyUp="verifySysPass('sysPass', 'sysVerify');" onBlur="verifySysPass('sysPass', 'sysVerify');" placeholder="[Required]" />
									</div>

									<h5 id="sysVerify_label"><strong>Verify Password</strong></h5>
									<div class="form-group">
										<input type="password" name="sysVerify" id="sysVerify" class="form-control input-sm" onFocus="verifySysPass('sysPass', 'sysVerify');" onKeyUp="verifySysPass('sysPass', 'sysVerify');" onBlur="verifySysPass('sysPass', 'sysVerify');" placeholder="[Required]" />
									</div>

									<h5 id="sysHome_label"><strong>Home Directory</strong> <small>DESCRIPTION</small></h5>
									<div class="form-group">
										<input type="text" name="sysHome" id="sysHome" class="form-control input-sm" onFocus="" onKeyUp="" onBlur="" placeholder="[Required]" readonly />
									</div>

									<h5 id="sysShell_label"><strong>Login Shell</strong> <small>DESCRIPTION</small></h5>
									<div class="form-group">
										<input type="text" name="sysShell" id="sysShell" class="form-control input-sm" onFocus="" onKeyUp="" onBlur="" placeholder="[Required]" readonly />
									</div>

								</div>
								<div class="modal-footer">
									<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
									<button type="submit" name="saveSysUser" id="saveSysUser" class="btn btn-primary btn-sm" disabled >Save</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div><!-- /.tab-pane -->

		</div> <!-- end .tab-content -->
	</div>
</div>

<?php include "inc/footer.php"; ?>
