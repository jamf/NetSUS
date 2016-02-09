<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "SMB";

include "inc/header.php";

$accounterror = "";
$accountsuccess = "";

if (isset($_POST['smbpass']))
{
	$smbpw1 = $_POST['smbpass1'];
  $smbpw2 = $_POST['smbpass2'];
  if ($smbpw1 != "") 
  {
    if ($smbpw1 == $smbpw2)
    {
        suExec("resetsmbpw ".$smbpw1);
        $accountsuccess = "SMB password changed.";
        $conf->changedPass("smbaccount");
    }
    else 
    {
    	$accounterror = "Passwords do not match.";
    }
  }
  else
  {
  	$accounterror = "All fields required.";
  }
}

?>

<div id="restarting" class="alert alert-warning alert-margin-top">
	<span><img src="images/progress.gif" width="25"> Restarting...</span>
</div>

<?php if ($accounterror != "") { ?>
	<?php echo "<div class=\"alert alert-danger alert-margin-top\" >ERROR: " . $accounterror . "</div>" ?>
<?php } ?>

<?php if ($accountsuccess != "") { ?>
	<?php echo "<div class=\"alert alert-success alert-margin-top\">" . $accountsuccess . "</div>" ?></span>
<?php } ?>

<h2>SMB</h2>

<ul class="nav nav-tabs"></ul>

<div id="form-wrapper">

	<form action="SMB.php" method="post" name="SMB" id="SMB">

		<span class="label label-short">SMB Service</span>
		<input type="button" value="Restart" class="btn btn-sm btn-primary"
						onClick="javascript: return goTo(toggle_visibility('restarting', 'SMB'), 'smbCtl.php?restart=true');"/>
		<br>

		<span class="label label-short">New Password</span>
		<input type="password" name="smbpass1" id="smbpass1" value="" placeholder="[Required]"
						onKeyUp="validatePW();" onChange="validatePW();" />
		<br>

		<span class="label label-short">Confirm New Password</span>
		<input type="password" name="smbpass2" id="smbpass2" value="" placeholder="[Required]"
						onKeyUp="validatePW();" onChange="validatePW();" />
		<br>
		<br>

		<input type="submit" name="smbpass" id="smbpass" class="btn btn-sm btn-primary" value="Save" />
		<br>
		<br>

	</form> <!-- end SMB form -->

	<ul class="nav nav-tabs"></ul>

	<br>

	<input type="button" id="back-button" name="action" class="btn btn-sm btn-primary" value="Back" onclick="document.location.href='settings.php'">

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
