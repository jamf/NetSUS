<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Storage";

include "inc/header.php";

function formatSize($size, $precision = 1) {
	$base = log($size, 1024);
	$suffixes = array('B', 'kB', 'MB', 'GB', 'TB');
	if ($size == 0) {
		return "0 B";
	} else {
		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}
}

$df_result_str = trim(suExec("diskusage"));
$df_result = explode(":", $df_result_str);
$df_total = formatSize($df_result[0]*1024);
$df_used = formatSize($df_result[1]*1024);
$df_used_percent = ceil(100*$df_result[1]/$df_result[0]);
$df_free = formatSize($df_result[2]*1024);
$df_reserved = formatSize(($df_result[0]-$df_result[1]-$df_result[2])*1024);
if ($df_result[0]-$df_result[1]-$df_result[2] == 0) {
	$df_reserved_percent = 0;
} else {
	$df_reserved_percent = ceil(100*($df_result[0]-$df_result[1]-$df_result[2])/$df_result[0]);
}

$lv_result = trim(suExec("resizeStatus"));
if (strpos($lv_result, 'ERROR') === false) {
	$lv_layout = explode(":", $lv_result);
	$lv_total = formatSize($lv_layout[0]);
	$lv_allocated = formatSize($lv_layout[1]);
	$lv_available = formatSize($lv_layout[2]);
	$lv_percent = round(100*$lv_layout[1]/$lv_layout[0], 0) or 100;
} else {
	$lv_percent = 100;
}
?>
			<style>
				.progress-bar-light {
					background-color: #558fc0;
				}
				.progress-bar-warning-light {
					background-color: #ebbb77;
				}
				.progress-bar-danger-light {
					background-color: #d2746d;
				}
			</style>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2>Storage</h2>
				</div>
			</nav>

			<div style="padding: 79px 20px 1px; background-color: #f9f9f9;">
				<h5><strong>File System</strong></h5>

				<div class="row">
					<div class="col-xs-4">
						<div><span class="text-muted">Used:</span> <?php echo $df_used; ?></div>
					</div>
					<div class="col-xs-4">
						<div class="text-center"><span class="text-muted">Reserved:</span> <?php echo $df_reserved; ?></div>
					</div>
					<div class="col-xs-4">
						<div class="text-right"><span class="text-muted">Free:</span> <?php echo $df_free; ?></div>
					</div>
				</div>

				<div class="progress" style="margin-bottom: 16px;">
					<div class="progress-bar <?php echo ($df_used_percent >= 80 ? ($df_used_percent >= 90 ? "progress-bar-danger" : "progress-bar-warning") : ""); ?>" role="progressbar" aria-valuenow="<?php echo $df_used_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $df_used_percent; ?>%;"></div>
					<div class="progress-bar <?php echo ($df_used_percent >= 80 ? ($df_used_percent >= 90 ? "progress-bar-danger-light" : "progress-bar-warning-light") : "progress-bar-light"); ?>" role="progressbar" aria-valuenow="<?php echo $df_reserved_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $df_reserved_percent; ?>%;"></div>
				</div>
			</div>

			<hr>

			<div style="padding: 6px 20px 16px;">
				<h5><strong>Logical Volume</strong></h5>
<?php if (strpos($lv_result,'ERROR') !== false) { ?>
				<div style="margin-top: 13px; margin-bottom: 0px;" class="panel panel-primary">
					<div class="panel-body">
						<div class="text-muted"><span class="text-info glyphicon glyphicon-info-sign" style="padding-right: 12px;"></span><?php echo str_replace('ERROR: ', '', $lv_result); ?></div>
					</div>
				</div>
<?php } else { ?>
				<div class="row">
					<div class="col-xs-4">
						<div><span class="text-muted">Total:</span> <?php echo $lv_total; ?></div>
					</div>
					<div class="col-xs-4">
						<div class="text-center"><span class="text-muted">Allocated:</span> <?php echo $lv_allocated; ?></div>
					</div>
					<div class="col-xs-4">
						<div class="text-right"><span class="text-muted">Available:</span> <?php echo $lv_available; ?></div>
					</div>
				</div>

				<div class="progress" style="margin-bottom: 16px;">
					<div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $lv_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $lv_percent; ?>%;"></div>
				</div>

				<div class="text-right">
					<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#confirmExpand" <?php echo ($lv_percent < 100 ? "" : "disabled"); ?>>Expand</button>
				</div>
<?php } ?>
			</div>

			<form action="storageCtl.php" method="POST" name="Storage" id="Storage">
				<!-- Expand Volume Modal -->
				<div class="modal fade" id="confirmExpand" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title">Expand Volume</h3>
							</div>
							<div class="modal-body">
								<div class="text-muted">Are you sure you want to expand the volume? The system will require a restart.</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left">Cancel</button>
								<button type="submit" name="resize-confirm" class="btn btn-primary btn-sm">Continue</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /.modal -->
			</form>

<?php include "inc/footer.php"; ?>