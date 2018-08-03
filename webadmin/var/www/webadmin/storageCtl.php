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
			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span> <a href="storage.php">Storage</a> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2>Expand Logical Volume</h2>
				</div>
			</nav>

			<div style="padding: 80px 20px 16px; overflow-x: auto; background-color: #f9f9f9;">
<?php
$cmd = "sudo /bin/sh scripts/adminHelper.sh resizeDisk";
while (@ ob_end_flush());
$proc = popen($cmd, "r");
?>
				<pre style="background-color: #fff;">
<?php
while (!feof($proc)) {
	echo fread($proc, 128);
	@ flush();
}
?></pre>

				<div class="text-right">
					<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='storage.php'">Done</button>
					<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#restart-modal" onClick="restartModal();">Restart</button>
				</div>
			</div>

			<hr>
<?php }
include "inc/footer.php"; ?>