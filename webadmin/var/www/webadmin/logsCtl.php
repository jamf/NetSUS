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
	<h2>Display Log</h2>
		<div class="row">
			<div class="col-xs-12 col-sm-10 col-lg-8">
				<hr>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-10 col-lg-8">
	<?php
	echo "<span class=\"label label-default\">".$_GET['log']."</span>";
	print "<pre>".$logcontent."</pre>";
	?>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-10 col-lg-8">
				<br>
				<hr>
				<br>
				<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='logs.php'">
			</div>
		</div>
<?php
}

if (!isset($_GET['log']))
{
	header('Location: '. $sURL);
}

include "inc/footer.php";

?>