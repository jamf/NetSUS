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

<div id="restarting" class="alert alert-warning" style="display:none">
	<span><img src="images/progress.gif" width="25"> Restarting...</span>
</div>

<?php if ($accounterror != "") { ?>
	<?php echo "<div class=\"alert alert-danger\" >ERROR: " . $accounterror . "</div>" ?>
<?php } ?>

<?php if ($accountsuccess != "") { ?>
	<?php echo "<div class=\"alert alert-success\">" . $accountsuccess . "</div>" ?></span>
<?php } ?>

<div class="row">
	<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">

		<h2>SMB</h2>
		<hr>

		<form action="SMB.php" method="post" name="SMB" id="SMB">

			<span class="label label-default">SMB Service</span>
			<input type="button" value="Restart" class="btn btn-sm btn-primary" onClick="javascript: return goTo(toggle_visibility('restarting', 'SMB'), 'smbCtl.php?restart=true');"/>
			<br><br>

			<label class="control-label">New Password</label>
			<input type="password" name="smbpass1" id="smbpass1" class="form-control input-sm" value="" placeholder="Required" onKeyUp="validatePW();" onChange="validatePW();" />

			<label class="control-label">Confirm New Password</label>
			<input type="password" name="smbpass2" id="smbpass2" class="form-control input-sm" value="" placeholder="Required" onKeyUp="validatePW();" onChange="validatePW();" />
			<br>

			<input type="submit" name="smbpass" id="smbpass" class="btn btn-primary" value="Save" />
			<br>
			<br>

		</form> <!-- end SMB form -->

		<hr>
		<br>
		<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

	</div>
</div>

<?php include "inc/footer.php"; ?>
