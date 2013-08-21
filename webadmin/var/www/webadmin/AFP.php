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
<script>
// function validateafpPW()
// {
// 	if (document.getElementById("afppass1").value != "" && document.getElementById("afppass2").value != "" && document.getElementById("afppass1").value == document.getElementById("afppass2").value && document.getElementById("afppass1").value.indexOf("@") == -1)
// 		document.getElementById("afppass").disabled = false;
// 	else
// 		document.getElementById("afppass").disabled = true;
// }
</script>

<style>
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

<h2>AFP</h2>

<div id="form-wrapper">

	<form action="AFP.php" method="post" name="AFP" id="AFP">

		<div id="form-inside">

			<span class="label">AFP Service</span>
			<input type="button" value="Restart" class="insideActionButton" onClick="javascript: return goTo(toggle_visibility('restarting', 'AFP'), 'afpCtl.php?restart=true');"/>
			<br>

			<span class="label">New Password</span>
			<input type="password" placeholder="[Required]" name="afppass1" id="afppass1" value="" onKeyUp="validateafpPW();" onChange="validateafpPW();" />
			<br>

			<span class="label">Verify New Password</span>
			<input type="password" placeholder="[Required]" name="afppass2" id="afppass2" value="" onKeyUp="validateafpPW();" onChange="validateafpPW();" />
			<br>
			<input type="submit" name="afppass" id="afppass" value="Save" class="insideActionButton" />

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='settings.php'">

			</div>

		</div>

	</form> <!-- end AFP form -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>
