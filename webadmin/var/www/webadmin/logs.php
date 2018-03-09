<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Logs";

$currenttime = trim(getLocalTime());

if (isset($_POST['display_log']) && $_POST['display_file'] != '')
{
	header('Location: logsCtl.php?log='.$_POST['display_file'].'&lines='.$_POST['display_lines']);
}

if (isset($_POST['flush_log']) && $_POST['flush_file'] != '')
{
	suExec("flushLog ".$_POST['flush_file']);
}

include "inc/header.php";

// ####################################################################
// End of GET/POST parsing
// ####################################################################

$displaylogs_str = trim(suExec("displayLogList"));
$displaylogs = explode(" ", $displaylogs_str);

$flushlogs_str = trim(suExec("flushLogList"));
$flushlogs = explode(" ", $flushlogs_str);

?>

<script type="text/javascript">
function showErr(id, valid)
{
	if (valid || document.getElementById(id).value == "")
	{
		document.getElementById(id).style.borderColor = "";
		document.getElementById(id).style.backgroundColor = "";
	}
	else
	{
		document.getElementById(id).style.borderColor = "#a94442";
		document.getElementById(id).style.backgroundColor = "#f2dede";
	}
}
function enableButton(id, enable)
{
	document.getElementById(id).disabled = !enable;
}

function validateDisplayLog()
{
	var validLog = !(document.getElementById("display_file").value == "");
	var validLines = document.getElementById("display_lines").value == "" || document.getElementById("display_lines").value == parseInt(document.getElementById("display_lines").value);
	showErr("display_lines", validLines);
	enableButton("display_log", validLog && validLines);
}
function validateFlushLog()
{
	var validLog = !(document.getElementById("flush_file").value == "");
	enableButton("flush_log", validLog);
}

</script>

<h2>Logs</h2>

<div class="row">
	<div class="col-xs-12 col-sm-8 col-md-6">

		<form action="logs.php" method="post" name="logs" id="logs">

			<hr>

			<br>

			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>Display Log</strong>
				</div>

				<div class="panel-body">

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Select Log File</div>
						<select id="display_file" name="display_file" class="form-control input-sm" onClick="validateDisplayLog();" onKeyUp="validateDisplayLog();" onChange="validateDisplayLog();">
							<option value="">Select...</option>
							<?php
							foreach($displaylogs as $key => $value)
							{
								echo "<option value=\"".$value."\">".$value."</option>";
							}
							?>
						</select>
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Number of Lines</div>
						<input type="text" name="display_lines" id="display_lines" class="form-control input-sm" onClick="validateDisplayLog();" onKeyUp="validateDisplayLog();" placeholder="[Optional]" />
					</div>

				</div>

				<div class="panel-footer">
					<input type="submit" name="display_log" id="display_log" class="btn btn-primary btn-sm" value="Display" disabled="disabled"/>
				</div>
			</div>

			<!-- To Do: Bug check flush log function -->
			<!-- <div class="panel panel-default">
				<div class="panel-heading">
					<strong>Flush Log</strong>
				</div>

				<div class="panel-body">

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Select Log File</div>
						<select id="flush_file" name="flush_file" class="form-control input-sm" onClick="validateFlushLog();" onKeyUp="validateFlushLog();" onChange="validateFlushLog();">
							<option value="">Select...</option>
							<?php
							/* foreach($flushlogs as $key => $value)
							{
								echo "<option value=\"".$value."\">".$value."</option>";
							} */
							?>
						</select>
					</div>

				</div>

				<div class="panel-footer">
					<input type="submit" name="flush_log" id="flush_log" class="btn btn-primary btn-sm" value="Flush" disabled="disabled" onClick="javascript: return yesnoprompt('Are you sure you want to flush \'' + document.getElementById('flush_file').value + '\'?');"/>
				</div>
			</div> -->

			<hr>
			<br>

			<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

		</form> <!-- end form Logs -->

	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>