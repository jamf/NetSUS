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

<style> 
	/*#restarting { display: none; }*/
	#restarting li { display: inline; list-style-type: none; font-size: 20px; } 
	#restarting li:last-child { vertical-align: 15px; }
</style>

<div id="restarting" style="display:none;">
	<ul>
		<li><img src="images/progress.gif"></li>
		<li>Restarting...</li>
	</ul>
</div>

<?php if ($accounterror != "") { ?>
	<?php echo "<div class=\"errorMessage\" >ERROR: " . $accounterror . "</div>" ?>
<?php } ?>

<?php if ($accountsuccess != "") { ?>
	<?php echo "<div class=\"successMessage\">" . $accountsuccess . "</div>" ?></span>
<?php } ?>

<h2>SMB</h2>

<div id="form-wrapper">

	<form action="SMB.php" method="post" name="SMB" id="SMB">

		<div id="form-inside">

			<span class="label">SMB Service</span>
			<input type="button" value="Restart" class="insideActionButton" 
							onClick="javascript: return goTo(toggle_visibility('restarting', 'SMB'), 'smbCtl.php?restart=true');"/>
			<br>

			<span class="label">New Password</span>
			<input type="password" name="smbpass1" id="smbpass1" value="" placeholder="[Required]"
							onKeyUp="validatePW();" onChange="validatePW();" />
			<br>

			<span class="label">Confirm New Password</span>
			<input type="password" name="smbpass2" id="smbpass2" value="" placeholder="[Required]"
							onKeyUp="validatePW();" onChange="validatePW();" />
			<br>
			<input type="submit" name="smbpass" id="smbpass" class="insideActionButton" value="Save" />

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='settings.php'">

			</div>

		</div>

	</form> <!-- end SMB form -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
