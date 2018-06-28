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

<style>
/* #page-content-wrapper {
	height: 100vh;
} */
.log-viewer {
    -webkit-box-flex: 1;
    -ms-flex: 1;
    flex: 1;
    border-radius: 3px;
    border: 1px solid #bfbfbf;
    margin-top: 5px;
    padding: 4px;
    pointer-events: auto;
	min-height: 400px;
	/* height: 100%; */
}
</style>

<script type="text/javascript" src="scripts/ace/ace.js"></script>

	<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Information</span> <span class="glyphicon glyphicon-chevron-right"></span> <a href="logs.php">Logs</a> <span class="glyphicon glyphicon-chevron-right"></span></div>
	<h2><?php echo $_GET['log']; ?></h2>
		<div class="row">
			<div class="col-xs-12">

				<hr>

				<br>

				<div id="viewer" class="log-viewer" tabindex="-1"><?php echo htmlentities($logcontent); ?></div>
				<script>
					var viewer = ace.edit("viewer");
					viewer.session.setMode("ace/mode/text");
					viewer.setTheme("ace/theme/clouds");
					viewer.session.setFoldStyle("markbegin");
					viewer.setShowPrintMargin(false);
					viewer.setOption("readOnly", true);
				</script>

			</div>
		</div>
<?php }

if (!isset($_GET['log'])) {
	header('Location: '. $sURL);
}

include "inc/footer.php";

?>