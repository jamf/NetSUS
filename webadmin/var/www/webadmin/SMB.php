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

<h2>SMB</h2>

<div id="form-wrapper">

	<div class="row">
		<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">

			<hr>

			<div id="restarting" class="alert alert-warning alert-margin-top" style="display:none">
				<span><img src="images/progress.gif" width="25"> Restarting...</span>
			</div>

			<?php if ($accounterror != "") { ?>
				<?php echo "<div class=\"alert alert-danger alert-margin-top\" >ERROR: " . $accounterror . "</div>" ?>
			<?php } ?>

			<?php if ($accountsuccess != "") { ?>
				<?php echo "<div class=\"alert alert-success alert-margin-top\">" . $accountsuccess . "</div>" ?></span>
			<?php } ?>

			<form action="SMB.php" method="post" name="SMB" id="SMB">

				<span class="label label-default">SMB Service</span>
				<input type="button" value="Restart" class="btn btn-sm btn-primary" onClick="javascript: return goTo(toggle_visibility('restarting', 'SMB'), 'smbCtl.php?restart=true');"/>
				<br>

				<span class="label label-default">New Password</span>
				<input type="password" name="smbpass1" id="smbpass1" class="form-control" value="" placeholder="[Required]" onKeyUp="validatePW();" onChange="validatePW();" />

				<span class="label label-default">Confirm New Password</span>
				<input type="password" name="smbpass2" id="smbpass2" class="form-control" value="" placeholder="[Required]" onKeyUp="validatePW();" onChange="validatePW();" />
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

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
