<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$sURL="storage.php";
$title = "Storage";

if (isset($_GET['resize']))
{
	include "inc/header.php";
	?>

	<style>
	@media(min-width:768px) {
	  #nav-footer {
		left: 220px;
	  }
	}
	</style>

	<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span> <a href="storage.php">Storage</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Logical Volume</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
	<h2>Expand</h2>
		<div class="row">
			<div class="col-xs-12">

				<hr>
				<br>

	<?php
	$cmd = "sudo /bin/sh scripts/adminHelper.sh resizeDisk";
	while (@ ob_end_flush());
	$proc = popen($cmd, "r");
	echo "<pre>";
	while (!feof($proc)) {
		echo fread($proc, 128);
		@ flush();
	}
	echo "</pre>";
	?>

				<nav id="nav-footer" class="navbar navbar-default navbar-fixed-bottom">
					<button type="button" class="btn btn-primary btn-sm pull-right" style="margin-top: 10px; margin-bottom: 10px; margin-right: 15px;" data-toggle="modal" data-target="#restart-modal" onClick="restartModal();">Restart</button>
				</nav>

			</div>
		</div>
<?php }

if (!isset($_GET['resize'])) {
	header('Location: '. $sURL);
}

include "inc/footer.php";

?>