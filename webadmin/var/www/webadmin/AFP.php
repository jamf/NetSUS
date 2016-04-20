<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "AFP";

include "inc/header.php";

$accounterror = "";
$accountsuccess = "";

if (isset($_POST['afppass']))
{
	$afppw1 = $_POST['afppass1'];
  $afppw2 = $_POST['afppass2'];
  if ($afppw1 != "") 
  {
    if ($afppw1 == $afppw2)
    {
        $result = suExec("resetafppw ".$afppw1);
        if (strpos($result,'BAD PASSWORD') !== false) {
                $accounterror = $result;
        }
        else {
        $accountsuccess = "AFP password changed.";
        $conf->changedPass("afpaccount");
        }
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

		<h2>AFP</h2>

		<hr>

		<form action="AFP.php" method="post" name="AFP" id="AFP">

			<span class="label label-default">AFP Service</span>
			<input type="button" value="Restart" class="btn btn-sm btn-primary" onClick="javascript: return goTo(toggle_visibility('restarting', 'AFP'), 'afpCtl.php?restart=true');"/>
			<br><br>

			<label class="control-label">New Password</label>
			<input type="password" placeholder="Required" name="afppass1" id="afppass1" class="form-control input-sm" value="" onKeyUp="validateafpPW();" onChange="validateafpPW();" />

			<label class="control-label">Confirm New Password</label>
			<input type="password" placeholder="Required" name="afppass2" id="afppass2" class="form-control input-sm" value="" onKeyUp="validateafpPW();" onChange="validateafpPW();" />
			<br>

			<input type="submit" name="afppass" id="afppass" value="Save" class="btn btn-primary" />
			<br>
			<br>

		</form> <!-- end AFP form -->

		<hr>
		<br>
		<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

	</div>
</div>

<?php include "inc/footer.php"; ?>
