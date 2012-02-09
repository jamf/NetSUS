<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$accounterror = "";

//Change the view depending on what tab the user has clicked on
if (isset($_POST["userAction"]) && $_POST["userAction"] != "") {
	$userAction = $_POST["userAction"];
} else {
	$userAction = "WebAdmin";
}

//Save the date/time settings if the SaveAccount
if (isset($_POST['SaveWebAccount']) && isset($_POST['username']) && isset($_POST['password']) 
	&& isset($_POST['confirm']) && isset($_POST['confirmold']))
{
	if (hash("sha256",$_POST['confirmold']) == $conf->getSetting("webadminpass"))
	{
		if ($_POST['password'] == $_POST['confirm'] && $_POST['password'] != "")
		{
			setWebAdminUser($_POST['username'], $_POST['password']);
			$accounterror = "Web admin account changed";
			$conf->changedPass("webaccount");
		}
		else
		{
			$accounterror = "Passwords do not match";
		}
	}
	else
	{
		$accounterror = "Current password does not match";
	}
}

$shelluser = "";
// Change the shell account
if (isset($_POST['saveShellAccount']))
{
	if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['confirm'])
		&& $_POST['username'] != "" && $_POST['password'] != "" && $_POST['confirm'] != "")
	{
		if ($_POST['password'] == $_POST['confirm'])
		{
			$shelluser = $_POST['username'];
			if ($shelluser != $conf->getSetting("shelluser"))
			{
				print suExec("changeshelluser $shelluser ".$conf->getSetting("shelluser"));
				$conf->setSetting("shelluser", $shelluser);
			}
			$shelluser = $conf->getSetting("shelluser");
			// TODO: Find more secure method
			print suExec("changeshellpass $shelluser ".$_POST['password']); // Have to pass the password in clear text, unfortunately
			$accounterror = "Shell account changed";
			$conf->changedPass("shellaccount");
		}
		else
		{
			$accounterror = "Passwords do not match";
		}
	}
	else
	{
		$accounterror = "All fields are required";
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

// Determine active tab
if ( $userAction == "" | $userAction == "WebAdmin" )
{
	$webadminli = "<li class='selected'>";
	$title = "Change Web Admin Account";
}
else 
	$webadminli = "<li>";
if ( $userAction == "Shell" )
{
	$shellli = "<li class='selected'>";
	$title = "Change Shell Account";
}
else
	$shellli = "<li>";

$headerTabs =<<<ENDOFTABS
<SCRIPT language="JavaScript">
function submitForm(view){
	document.f.userAction.value=view;
	document.f.submit();
}

</SCRIPT>

<form method="post" action="account.php" name="f" id="f">
<input type="hidden" name="userAction" value="$userAction">
<div id="tabNav">
<ul class="tabNav">
$webadminli
<a href="javascript:submitForm('WebAdmin')"><img class="centerIMG" src="images/tabs/Account.png" width="24" height="24">Web Admin</a>
</li>

$shellli
<a href="javascript:submitForm('Shell')"><img class="centerIMG" src="images/tabs/script.png" width="24" height="24">Shell</a>
</li>
</ul>
</div>
</form>
<!--end tabNav-->
ENDOFTABS;

include "inc/header.php";


if ($userAction == "WebAdmin")
{
?>

<script type="text/javascript">
function verifyPasswords()
{
	if (document.getElementById("confirmold").value != "" && document.getElementById("username").value != "" 
		&& document.getElementById("password").value != ""
		&& document.getElementById("password").value == document.getElementById("confirm").value)
		document.getElementById("SaveWebAccount").disabled = false;
	else
		document.getElementById("SaveWebAccount").disabled = true;
}
</script>
<form action="account.php" method="post" name="WebAdmin" id="WebAdmin">
	<input type="hidden" name="userAction" value="WebAdmin">
	<table style="border: 0px;" class="formLabel">
		<?php 
		if ($accounterror != "")
		{
		?>
		<tr>
			<td colspan="2" class="error"><?=$accounterror?></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td style="text-align: right;">Current Username:</td>
			<td><?=getCurrentWebUser();?></td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="confirm">Current Password:</label></td>
			<td><input type="password" name="confirmold" id="confirmold" value="" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="username">New Username: </label></td>
			<td><input type="text" name="username" id="username" value="<?=getCurrentWebUser();?>" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="password">New Password:</label></td>
			<td><input type="password" name="password" id="password" value="" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="confirm">Confirm New Password:</label></td>
			<td><input type="password" name="confirm" id="confirm" value="" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td>
        	<td style="text-align: right;"><input type="submit" value="Save Web Admin Account" name="SaveWebAccount" id="SaveWebAccount" disabled="disabled" /></td>
        </tr>
	</table>
</form>
<?
}
else if ($userAction == "Shell")
{
?>
<script type="text/javascript">
function verifyPasswords()
{
	if (document.getElementById("username").value != "" 
		&& document.getElementById("password").value != ""
		&& document.getElementById("password").value == document.getElementById("confirm").value)
		document.getElementById("saveShellAccount").disabled = false;
	else
		document.getElementById("saveShellAccount").disabled = true;
}
</script>
<form action="account.php" method="post" name="Shell" id="Shell">
	<input type="hidden" name="userAction" value="Shell">
	<table style="border: 0px;" class="formLabel">
		<?php 
		if ($accounterror != "")
		{
		?>
		<tr>
			<td colspan="2" class="error"><?=$accounterror?></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td style="text-align: right;"><label for="username">New Username: </label></td>
			<td><input type="text" name="username" id="username" value="<?=$conf->getSetting("shelluser")?>" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="password">New Password: </label></td>
			<td><input type="password" name="password" id="password" value="" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td style="text-align: right;"><label for="password">Confirm New Password: </label></td>
			<td><input type="password" name="confirm" id="confirm" value="" onKeyUp="verifyPasswords();" onChange="verifyPasswords();" /></td>
		</tr>
		<tr>
			<td></td>
        	<td style="text-align: right;"><input type="submit" value="Save Shell Account" name="saveShellAccount" id="saveShellAccount" disabled="disabled"/></td>
        </tr>
	</table>
</form>

<?php
}

include "inc/footer.php";        

?>
</body>
</html>
