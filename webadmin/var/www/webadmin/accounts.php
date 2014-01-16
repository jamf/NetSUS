<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

//Change the view depending on what tab the user has clicked on
if (isset($_POST["userAction"]) && $_POST["userAction"] != "") {
	$userAction = $_POST["userAction"];
} else {
	$userAction = "WebAdmin";
}

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
	<?php echo "<div class=\"errorMessage\">ERROR: " . $accounterror . "</div>" ?>
<?php } ?>

<?php if ($accountsuccess != "") { ?>
	<?php echo "<div class=\"successMessage\">" . $accountsuccess . "</div>" ?></span>
<?php } ?>

<h2>Accounts</h2>

<div id="form-wrapper">

		<ul class="tabs" id="top-tabs">

			<?php
			// Determine active tab
			if ( $userAction == "" | $userAction == "WebAdmin" )
			{
				$webadminActive = "class=\"active\"";
				$hideShell = "style=\"display:none;\"";
			} 
			else
			{
				$shellActive = "class=\"active\"";
				$hideWebAdmin = "style=\"display:none;\"";
			}
			?>

			<li id="WebAdmin" <?php echo $webadminActive ?>>
				<a href="javascript:changeTab('WebAdmin')">Web Application</a>
			</li>

			<li id="Shell" <?php echo $shellActive ?>>
				<a href="javascript:changeTab('Shell')">Shell</a>
			</li>

		</ul>

		<div id="form-inside">

			<form method="POST" name="WebAdmin" id="WebAdmin" >

				<div id="WebAdmin_Pane" class="pane" <?php echo $hideWebAdmin; ?>>

					<input type="hidden" name="userAction" value="WebAdmin">
 
					<span class="label">Current Username</span>
					<input type="text" value="<?php echo getCurrentWebUser();?>" readonly class="disabled"/>
					<br>
					
					<span class="label">Current Password</span>
					<input type="password" name="confirmold" id="confirmold" value="" />
					<br>

					<span class="label">New Username</span>
					<input type="text" name="username" id="username" value="<?php echo getCurrentWebUser();?>" />
					<br>

					<span class="label">New Password</span>
					<input type="password" name="password" id="password" value="" />
					<br>

					<span class="label">Verify New Password</span>
					<input type="password" name="confirm" id="confirm" value="" />
					<br>

					<input type="submit" value="Save" name="SaveWebAccount" id="SaveWebAccount" class="insideActionButton" />

				</div> <!-- end #WebAdmin_Pane -->

			</form>

			<form method="POST" name="ShellForm" id="ShellForm">

				<div id="Shell_Pane" class="pane" <?php echo $hideShell; ?>>

					<input type="hidden" name="userAction" value="Shell">

					<span class="label">New Username</span>
					<input type="text" name="shellUsername" id="shellUsername" value="<?php echo $conf->getSetting("shelluser")?>" />
					<br>

					<span class="label">New Password</span>
					<input type="password" name="shellPassword" id="shellPassword" value="" />
					<br>

					<span class="label">Verify New Password</span>
					<input type="password" name="shellConfirm" id="shellConfirm" value="" />
					<br>

					<input type="submit" value="Save" name="saveShellAccount" id="saveShellAccount" class="insideActionButton" />

				</div> <!-- end #Shell_Pane -->

			</form>

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='settings.php'">

			</div>

		</div>

	</form> <!-- end form f -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
