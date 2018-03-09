<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";


$accounterror = "";
$accountsuccess = "";

//Save the date/time settings if the SaveAccount
if (isset($_POST['SaveWebAccount']) && isset($_POST['username']) && isset($_POST['password']) 
	&& isset($_POST['confirm']) && isset($_POST['confirmold']))
{
	if (hash("sha256",$_POST['confirmold']) == $conf->getSetting("webadminpass"))
	{	
		if ($_POST['password'] == "")
		{
			$accounterror = "Specify new password.";
		}
		else if ($_POST['username'] == "")
		{
			$accounterror = "Specify new username.";
		}
		else if ($_POST['password'] == $_POST['confirm'])
		{
			setWebAdminUser($_POST['username'], $_POST['password']);
			$accountsuccess = "Web application account changed.";
			$conf->changedPass("webaccount");
		}
		else
		{
			$accounterror = "Passwords do not match.";
		}
	}
	else
	{
		$accounterror = "Incorrect current password.";
	}
}

$shelluser = "";
// Change the shell account
if (isset($_POST['saveShellAccount']))
{
	if (isset($_POST['shellUsername']) && isset($_POST['shellPassword']) && isset($_POST['shellConfirm'])
		&& $_POST['shellUsername'] != "" && $_POST['shellPassword'] != "" && $_POST['shellConfirm'] != "")
	{
		if ($_POST['shellPassword'] == $_POST['shellConfirm'])
		{
			$shelluser = $_POST['shellUsername'];
			if ($shelluser != $conf->getSetting("shelluser"))
			{
				print suExec("changeshelluser $shelluser ".$conf->getSetting("shelluser"));
				$conf->setSetting("shelluser", $shelluser);
			}
			$shelluser = $conf->getSetting("shelluser");
			// TODO: Find more secure method
			print suExec("changeshellpass $shelluser ".$_POST['shellPassword']); // Have to pass the password in clear text, unfortunately
			$accountsuccess = "Shell account changed.";
			$conf->changedPass("shellaccount");
		}
		else
		{
			$accounterror = "Passwords do not match.";
		}
	}
	else
	{
		$accounterror = "All fields are required." ;
	}
}
else
{
	// Load current account name
	$shelluser = $conf->getSetting("shelluser");
	if ($shelluser == NULL || $shelluser == "")
	{
		$shelluser = "shelluser";
		$conf->setSetting("shelluser", $shelluser);
	}
}


$ldaperror = "";
$ldapsuccess = "";

if (isset($_POST['saveLDAPConfiguration']) && isset($_POST['server']) && isset($_POST['domain']))
{
	if ($_POST['server'] == "")
	{
		$ldaperror = "Specify LDAP server.";
	}
	else if ($_POST['domain'] == "")
	{
		$ldaperror = "Specify a domain.";
	}
	else {
		$conf->setSetting("ldapserver", $_POST['server']);
		$conf->setSetting("ldapdomain", $_POST['domain']);
		$ldapsuccess = "Saved LDAP configuration.";
	}
}
if (isset($_POST['addadmin']) && isset($_POST['cn']) && $_POST['cn'] != "")
{
	$conf->addAdmin($_POST['cn']);
}
if (isset($_GET['deleteAdmin']) && $_GET['deleteAdmin'] != "")
{
	$conf->deleteAdmin($_GET['deleteAdmin']);
}

$title = "Accounts";

include "inc/header.php";

?>

<?php if ($accounterror != "") { ?>
	<?php echo "<div class=\"alert alert-danger\">ERROR: " . $accounterror . "</div>" ?>
<?php } ?>

<?php if ($accountsuccess != "") { ?>
	<?php echo "<div class=\"alert alert-success\">" . $accountsuccess . "</div>" ?></span>
<?php } ?>

<?php if ($ldaperror != "") { ?>
	<?php echo "<div class=\"alert alert-danger\">ERROR: " . $ldaperror . "</div>" ?>
<?php } ?>

<?php if ($ldapsuccess != "") { ?>
	<?php echo "<div class=\"alert alert-success\">" . $ldapsuccess . "</div>" ?></span>
<?php } ?>

<script>
	function validateLDAPAdmin()
	{
		if (document.getElementById("cn").value != "")
			document.getElementById("addadmin").disabled = false;
		else
			document.getElementById("addadmin").disabled = true;
	}
	//function to save the current tab on refresh
	$(document).ready(function(){
		$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
			localStorage.setItem('activeTab', $(e.target).attr('href'));
		});
		var activeTab = localStorage.getItem('activeTab');
		if(activeTab){
			$('#top-tabs a[href="' + activeTab + '"]').tab('show');
		}
	});

</script>

<div class="row">
	<div class="col-xs-12 col-sm-8 col-md-5 col-lg-4">

		<h2>Accounts</h2>

		<ul class="nav nav-tabs nav-justified" id="top-tabs">
			<li class="active"><a class="tab-font" href="#webadmin-tab" role="tab" data-toggle="tab">Local Account</a></li>
			<li><a class="tab-font" href="#shell-tab" role="tab" data-toggle="tab">Shell Account</a></li>
			<li><a class="tab-font" href="#activedir-tab" role="tab" data-toggle="tab">Active Directory</a></li>
		</ul>

		<div class="tab-content">

			<div class="tab-pane active fade in" id="webadmin-tab">
				<form method="POST" name="WebAdmin" id="WebAdmin">
					<input type="hidden" name="userAction" value="WebAdmin">

					<label class="control-label">Current Username</label>
					<input type="text" class="form-control input-sm" value="<?php echo getCurrentWebUser();?>" readonly class="disabled"/>

					<label class="control-label">Current Password</label>
					<input type="password" name="confirmold" id="confirmold" class="form-control input-sm"  value="" />

					<label class="control-label">New Username</label>
					<input type="text" name="username" id="username" class="form-control input-sm"  value="<?php echo getCurrentWebUser();?>" />

					<label class="control-label">New Password</label>
					<input type="password" name="password" id="password" class="form-control input-sm"  value="" />

					<label class="control-label">Verify New Password</label>
					<input type="password" name="confirm" id="confirm" class="form-control input-sm"  value="" />

					<br>

					<input type="submit" value="Save" name="SaveWebAccount" id="SaveWebAccount" class="btn btn-primary" />
				</form>
			</div><!-- /.tab-pane -->

			<div class="tab-pane fade in" id="shell-tab">
				<form method="POST" name="ShellForm" id="ShellForm">
					<input type="hidden" name="userAction" value="Shell">

					<label class="control-label">New Username</label>
					<input type="text" name="shellUsername" id="shellUsername" class="form-control input-sm" value="<?php echo $conf->getSetting("shelluser")?>" />

					<label class="control-label">New Password</label>
					<input type="password" name="shellPassword" id="shellPassword" class="form-control input-sm"  value="" />

					<label class="control-label">Verify New Password</label>
					<input type="password" name="shellConfirm" id="shellConfirm" class="form-control input-sm"  value="" />

					<br>

					<input type="submit" value="Save" name="saveShellAccount" id="saveShellAccount" class="btn btn-primary" />
				</form>
			</div><!-- /.tab-pane -->

			<div class="tab-pane fade in" id="activedir-tab">
				<form method="POST" name="LDAP" id="LDAP">
					<input type="hidden" name="userAction" value="ADForm">

					<label class="control-label">LDAP URL</label>
					<span class="description">Example: ldaps://ldap.myorg.com:636/</span>
					<input type="text" name="server" id="server" class="form-control input-sm" value="<?php echo $conf->getSetting('ldapserver'); ?>" />

					<label class="control-label">LDAP Domain</label>
					<span class="description">Example: ad.myorg.corp</span>
					<input type="text" name="domain" id="domain" class="form-control input-sm" value="<?php echo $conf->getSetting('ldapdomain'); ?>" />

					<label class="control-label">Administration Groups</label>
					<span class="description">Example: Domain Admins</span>
					<div class="input-group">
						<input type="text" name="cn" id="cn" value="" class="form-control input-sm" onClick="validateLDAPAdmin();" onKeyUp="validateLDAPAdmin();" />
						<span class="input-group-btn">
							<input type="submit" name="addadmin" id="addadmin" class="btn btn-primary btn-sm" value="Add" disabled="disabled" />
						</span>
					</div>

					<br>

					<div class="table-responsive panel panel-default">
						<table class="table table-striped table-bordered table-condensed">
							<tr>
								<th>Group Name</th>
								<th></th>
							</tr>
							<?php foreach($conf->getAdmins() as $key => $value) { ?>
								<tr class="<?php ($key % 2 == 0 ? "object0" : "object1"); ?>">
									<td><?php echo $value['cn']?></td>
									<td><a href="accounts.php?service=LDAP&deleteAdmin=<?php echo urlencode($value['cn'])?>">Delete</a>
								</tr>
							<?php } ?>
						</table>
					</div>

					<input type="submit" value="Save" name="saveLDAPConfiguration" id="saveLDAPConfiguration" class="btn btn-primary" />
				</form>
			</div><!-- /.tab-pane -->

		</div> <!-- end .tab-content -->

		<br>
		<hr>
		<br>
		<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

	</div>
</div>

<?php include "inc/footer.php"; ?>
