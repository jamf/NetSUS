<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Logs";

include "inc/header.php";

function formatSize($size, $precision = 1) {
    $base = log($size, 1024);
    $suffixes = array('B', 'kB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

$log_content = "";
if (isset($_POST['display_log'])) {
	$log_content = suExec("displayLog ".$_POST['display_file']." ".$_POST['display_lines']);
	$log_size = formatSize(strlen($log_content));
}
/*
if (isset($_POST['flush_log']) && $_POST['flush_file'] != '') {
	suExec("flushLog ".$_POST['flush_file']);
}
*/
// ####################################################################
// End of GET/POST parsing
// ####################################################################

if (empty($log_content)) {
	$display_log_str = trim(suExec("displayLogList"));
	$display_log_list = explode(" ", $display_log_str);
/*
	$flush_log_str = trim(suExec("flushLogList"));
	$flush_log_list = explode(" ", $flush_log_str);
*/
?>
			<script type="text/javascript">
				function showError(element, labelId = false) {
					element.parentElement.classList.add("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.add("text-danger");
					}
				}

				function hideError(element, labelId = false) {
					element.parentElement.classList.remove("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.remove("text-danger");
					}
				}

				function validDisplay() {
					var display_file = document.getElementById('display_file');
					var display_lines = document.getElementById('display_lines');
					if (display_file.value == "") {
						showError(display_file, 'display_file_label');
					} else {
						hideError(display_file, 'display_file_label');
					}
					if (parseInt(display_lines.value) > 0 && display_lines.value == parseInt(display_lines.value) || display_lines.value == "") {
						hideError(display_lines, 'display_lines_label');
					} else {
						showError(display_lines, 'display_lines_label');
					}
					if (display_file.value != "" && (parseInt(display_lines.value) > 0 && display_lines.value == parseInt(display_lines.value) || display_lines.value == "")) {
						$('#display_log').prop('disabled', false);
					} else {
						$('#display_log').prop('disabled', true);
					}
				}

				/*
				function validFlush() {
					var flush_file = document.getElementById('flush_file');
					if (flush_file.value == "") {
						showError(flush_file, 'flush_file_label');
						$('#flush_log').prop('disabled', true);
					} else {
						hideError(flush_file, 'flush_file_label');
						$('#flush_log').prop('disabled', false);
					}
				}
				*/
			</script>
<?php } else { ?>
			<style>
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
				}
			</style>

			<script type="text/javascript" src="scripts/ace/ace.js"></script>
<?php } ?>

			<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Information</span> <span class="glyphicon glyphicon-chevron-right"></span><?php echo (isset($_POST['display_file']) ? " <a href=\"logs.php\">Logs</a> <span class=\"glyphicon glyphicon-chevron-right\"></span>" : ""); ?></div>
			<h2><?php echo (isset($_POST['display_file']) ? $_POST['display_file'] : "Logs"); ?></h2>

			<div class="row">
				<div class="col-xs-12">

					<hr>
<?php if (empty($log_content)) { ?>
					<form action="logs.php" method="post" name="logs" id="logs">

						<h5 id="display_file_label"><strong>Display Log</strong> <small>Select log file to view.<br><strong>Note:</strong> Only text-based logs are visible from within this interface.</small></h5>
						<div class="form-group has-feedback" style="max-width: 464px;">
							<select id="display_file" name="display_file" class="form-control input-sm" onChange="validDisplay();">
								<option value="">Select...</option>
<?php foreach($display_log_list as $display_file) { ?>
								<option value="<?php echo $display_file; ?>"><?php echo $display_file; ?></option>
<?php } ?>
							</select>
						</div>

						<h5 id="display_lines_label"><strong>Number of Lines</strong> <small>The number of lines from the end of the log file to display.</small></h5>
						<div class="form-group has-feedback" style="max-width: 464px;">
							<input type="text" name="display_lines" id="display_lines" class="form-control input-sm" onFocus="validDisplay();" onKeyUp="validDisplay();" onBlur="validDisplay();" placeholder="[Optional]" />
						</div>

						<button type="submit" name="display_log" id="display_log" class="btn btn-primary btn-sm" disabled>Display</button>

						<!-- To Do: Bug check flush log function -->
<!--
						<br>
						<br>
						<hr>

						<h5 id="flush_file_label"><strong>Flush Log</strong> <small>Select log file to flush.</small></h5>
						<div class="form-group has-feedback" style="max-width: 464px;">
							<select id="flush_file" name="flush_file" class="form-control input-sm" onChange="validFlush();">
								<option value="">Select...</option>
<?php // foreach($flush_log_list as $flush_file) { ?>
								<option value="<?php // echo $flush_file; ?>"><?php //echo $flush_file; ?></option>
<?php // } ?>
							</select>
						</div>

						<button type="submit" name="flush_log" id="flush_log" class="btn btn-primary btn-sm" onClick="javascript: return yesnoprompt('Are you sure you want to flush \'' + document.getElementById('flush_file').value + '\'?');" disabled>Flush</button>
-->

					</form> <!-- end form Logs -->
<?php } else { ?>
					<div class="text-muted" style="padding: 12px; 0px;"><?php echo basename($_POST['display_file'])." (".$log_size.")"; ?></div>

<div class="form-group has-feedback">
					<div id="viewer" class="log-viewer" tabindex="-1"><?php echo htmlentities($log_content); ?></div>
					<script>
						var viewer = ace.edit("viewer");
						viewer.session.setMode("ace/mode/text");
						viewer.setTheme("ace/theme/clouds");
						viewer.session.setFoldStyle("markbegin");
						viewer.setShowPrintMargin(false);
						viewer.setOption("readOnly", true);
					</script>
</div>
					<button type="button" class="btn btn-primary btn-sm pull-right" onClick="document.location.href='logsCtl.php?download=<?php echo $_POST['display_file']; ?>&lines=<?php echo $_POST['display_lines']; ?>'">Download</button>
					<button type="button" class="btn btn-default btn-sm pull-right" style="margin-right: 20px;" onClick="document.location.href='logs.php'">Done</button>
<?php } ?>
				</div> <!-- /.col -->
			</div> <!-- /.row -->
<?php include "inc/footer.php"; ?>