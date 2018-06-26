<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$sURL="logs.php";
$title = "Logs";

if (isset($_GET['log']) && $_GET['log'] != '')
{
	$logcontent = suExec("displayLog ".$_GET['log']." ".$_GET['lines']);
	include "inc/header.php";
	?>
	<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Information</span> <span class="glyphicon glyphicon-chevron-right"></span> <a href="logs.php">Logs</a> <span class="glyphicon glyphicon-chevron-right"></span></div>
	<h2><?php echo $_GET['log']; ?></h2>
		<div class="row">
			<div class="col-xs-12">

				<hr>

				<br>

				<?php
				print "<pre>".$logcontent."</pre>";
				?>

			</div>
		</div>
<?php }

if (!isset($_GET['log'])) {
	header('Location: '. $sURL);
}

include "inc/footer.php";

?>