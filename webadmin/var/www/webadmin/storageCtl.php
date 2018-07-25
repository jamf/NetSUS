<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$sURL="storage.php";
$title = "Expand Logical Volume";

if (!isset($_GET['resize'])) {
	header('Location: '. $sURL);
} else {
	include "inc/header.php";
?>
			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span> <a href="storage.php">Storage</a> <span class="glyphicon glyphicon-chevron-right"></span></div>
			<h2>Expand Logical Volume</h2>

			<div class="row">
				<div class="col-xs-12">

					<hr>
					<br>

<?php
$cmd = "sudo /bin/sh scripts/adminHelper.sh resizeDisk";
while (@ ob_end_flush());
$proc = popen($cmd, "r");
?>
					<pre>
<?php
while (!feof($proc)) {
	echo fread($proc, 128);
	@ flush();
}
?></pre>

					<button type="button" class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#restart-modal" onClick="restartModal();">Restart</button>
					<button type="button" class="btn btn-default btn-sm pull-right" style="margin-right: 10px;" onClick="document.location.href='storage.php'">Done</button>

				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php }
include "inc/footer.php"; ?>