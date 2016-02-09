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

$title = "Accounts";

include "inc/header.php";

?>

<?php if ($accounterror != "") { ?>
	<?php echo "<div class=\"alert alert-danger alert-margin-top\">ERROR: " . $accounterror . "</div>" ?>
<?php } ?>

<?php if ($accountsuccess != "") { ?>
	<?php echo "<div class=\"alert alert-success alert-margin-top\">" . $accountsuccess . "</div>" ?></span>
<?php } ?>

<h2>Accounts</h2>

<div id="form-wrapper">

	<ul class="nav nav-tabs" id="top-tabs">
		<li class="active"><a href="#webadmin-tab" role="tab" data-toggle="tab">Web Application</a></li>
		<li><a href="#shell-tab" role="tab" data-toggle="tab">Shell</a></li>
	</ul>

	<div class="tab-content">

		<div class="tab-pane active" id="webadmin-tab">

			<form method="POST" name="WebAdmin" id="WebAdmin">
					<input type="hidden" name="userAction" value="WebAdmin">

					<span class="label label-short">Current Username</span>
					<input type="text" value="<?php echo getCurrentWebUser();?>" readonly class="disabled"/>
					<br>

					<span class="label label-short">Current Password</span>
					<input type="password" name="confirmold" id="confirmold" value="" />
					<br>

					<span class="label label-short">New Username</span>
					<input type="text" name="username" id="username" value="<?php echo getCurrentWebUser();?>" />
					<br>

					<span class="label label-short">New Password</span>
					<input type="password" name="password" id="password" value="" />
					<br>

					<span class="label label-short">Verify New Password</span>
					<input type="password" name="confirm" id="confirm" value="" />

					<br>
					<br>

					<input type="submit" value="Save" name="SaveWebAccount" id="SaveWebAccount" class="btn btn-sm btn-primary" />
			</form>
		</div>

		<div class="tab-pane" id="shell-tab">

			<form method="POST" name="ShellForm" id="ShellForm">
					<input type="hidden" name="userAction" value="Shell">

					<span class="label label-short">New Username</span>
					<input type="text" name="shellUsername" id="shellUsername" value="<?php echo $conf->getSetting("shelluser")?>" />
					<br>

					<span class="label label-short">New Password</span>
					<input type="password" name="shellPassword" id="shellPassword" value="" />
					<br>

					<span class="label label-short">Verify New Password</span>
					<input type="password" name="shellConfirm" id="shellConfirm" value="" />

					<br>
					<br>

					<input type="submit" value="Save" name="saveShellAccount" id="saveShellAccount" class="btn btn-sm btn-primary" />
			</form>
		</div>

	</div> <!-- end .tab-content -->

	<br>

	<ul class="nav nav-tabs"></ul>

	<br>

	<input type="button" id="back-button" name="action" class="btn btn-sm btn-primary" value="Back" onclick="document.location.href='settings.php'">


</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
