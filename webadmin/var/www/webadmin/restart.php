<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Restart";

include "inc/header.php";

if (isset($_POST['confirm']))
{
	echo '<meta http-equiv="refresh" content="60;url=index.php">';
	echo '<div class="noticeMessage">NOTICE: Restarting the NetBoot/SUS Server.</div>';
}

?>

<h2>Restart</h2>

<div id="form-wrapper">

	<form action="restart.php" method="POST" name="Restart" id="Restart" >

		<div id="form-inside">

			<span class="label">Are you sure you want to restart the NetBoot/SUS Server?</span>
			<?php
			$afpconns = trim(suExec("afpconns"));
			$smbconns = trim(suExec("smbconns"));
			if (($afpconns > 0) || ($smbconns > 0))
			{
				echo '<span class="description">There are '.($afpconns + $smbconns).' users connected to this server.</span>';
				echo '<span class="description">If you restart they will be disconnected.</span>';
			}
			?>
			<br>

			<input type="submit" id="confirm" name="confirm" class="insideActionButton" value="Restart" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='<?php echo $_SERVER['HTTP_REFERER']; ?>'" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

			</div>

		</div>

	</form> <!-- end form Restart -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>

<?php
if (isset($_POST['confirm']))
{
	suExec("restart");
}
?>