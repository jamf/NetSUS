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
	<h2>Expanding Volume</h2>
		<div class="row">
			<div class="col-xs-12 col-sm-10 col-lg-8">
				<hr>
				<br>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-10 col-lg-8">
	<?php
	$cmd = "sudo /bin/sh scripts/adminHelper.sh resizeDisk";
	while (@ ob_end_flush());
	$proc = popen($cmd, "r");
	echo "<pre>";
	while (!feof($proc))
	{
		echo fread($proc, 128);
		@ flush();
	}
	echo "</pre>";
	?>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-10 col-lg-8">
				<br>
				<hr>
				<br>
				<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Restart" onclick="document.location.href='restart.php'">
			</div>
		</div>
<?php
}

if (!isset($_GET['resize']))
{
	header('Location: '. $sURL);
}

include "inc/footer.php";

?>