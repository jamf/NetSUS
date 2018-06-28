<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Software Update Server";

include "inc/header.php";

function susExec($cmd) {
	return exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

// Base URL
if ($conf->getSetting("susbaseurl") == NULL || $conf->getSetting("susbaseurl") == "") {
	if ($_SERVER['HTTP_HOST'] != "") {
		$conf->setSetting("susbaseurl", "http://".$_SERVER['HTTP_HOST']."/");
	} elseif ($_SERVER['SERVER_NAME'] != "") {
		$conf->setSetting("susbaseurl", "http://".$_SERVER['SERVER_NAME']."/");
	} else {
		$conf->setSetting("susbaseurl", "http://".getCurrentHostname()."/");
	}
}
$susbaseurl = $conf->getSetting("susbaseurl");

// Reposado Log
if (trim(susExec("getPref RepoSyncLogFile")) == "") {
	susExec("setLogFile /var/log/reposado_sync.log");
}

// Add Branch Catalog
if (isset($_POST['addbranch'])) {
	if ($_POST["srcbranch"] != "" && $_POST["branchname"] != "") {
		susExec("copyBranch ".$_POST["srcbranch"]." ".$_POST["branchname"]);
	} elseif ($_POST["branchname"] != "") {
		susExec("createBranch ".$_POST["branchname"]);
	}
}

// Delete Branch Catalog
if (isset($_POST['deletebranch']) && $_POST['deletebranch'] != "") {
	if ($conf->getSetting("rootbranch") == $_POST['deletebranch']) {
		$conf->deleteSetting("rootbranch");
		susExec("rootBranch");
	}
	$conf->deleteAutosyncBranch($_POST['deletebranch']);
	susExec("deleteBranch ".$_POST['deletebranch']);
}

// Branch Catalogs
$branchstr = trim(susExec("getBranchlist"));
$branches = explode(" ", $branchstr);
sort($branches);

// SUS Status
$sync_status = trim(susExec("getSyncStatus")) == "true" ? true : false;
$util_status = trim(susExec("getUtilStatus")) == "true" ? true : false;

// Last Sync
$last_sync = $conf->getSetting("lastsussync");
if (empty($last_sync)) {
	$last_sync = trim(susExec("getLastSync"));
}
if (empty($last_sync)) {
	$last_sync = "Never";
} else {
	$last_sync = date("Y-m-d H:i:s", $last_sync);
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>

<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>

<script type="text/javascript">
	var existingBranches = [<?php echo (empty($branches) ? "" : "\"".implode('", "', $branches)."\""); ?>];
</script>

<script type="text/javascript" src="scripts/susValidation.js"></script>

<script type="text/javascript">
function manSync() {
	$("#sync-modal").addClass('fade');
	$('#sync-modal').modal('show');
	setTimeout('window.location.reload()', 5000);
	ajaxPost('susCtl.php', 'sync=true');
}
function purgeModal() {
	$("#purge-modal").addClass('fade');
	$("#purge-progress").addClass('hidden');
	$("#purge-refresh").addClass('hidden');
	$("#purge-warning").removeClass('hidden');
	$("#purge-confirm").removeClass('hidden');
}
function purgeDep() {
	$("#purge-warning").addClass('hidden');
	$("#purge-confirm").addClass('hidden');
	$("#purge-progress").removeClass('hidden');
	$("#purge-refresh").removeClass('hidden');
	setTimeout('window.location.reload()', 5000);
	ajaxPost('susCtl.php', 'purge=true');
}
</script>

<div class="description">&nbsp;</div>

<h2>Software Update Server</h2>

<div class="row">
	<div class="col-xs-12">

		<form action="SUS.php" method="post" name="SUS" id="SUS">

			<hr>

			<div style="padding-top: 12px;" class="description">SUS DESCRIPTION</div>

			<h5><strong>Manual Sync</strong> <small>Manual method for syncing the list of available updates with Apple's Software Update server.</small></h5>
			<button type="button" id="manual_sync" class="btn btn-primary btn-sm" onClick="manSync();">Sync</button>
			<div style="padding: 12px 0px;"><strong>Last Sync:</strong> <span id="last_sync" class="text-muted"><?php echo $last_sync; ?></span></div>

			<hr>
			<br>

			<h5><strong>Branch Catalogs</strong> <small>BRANCHES DESCRIPTION.</small></h5>
			<div class="form-inline">
				<table id="branchTable" class="table table-striped">
					<thead>
						<tr>
							<th>Default</th>
							<th><nobr>Auto Enable</nobr></th>
							<th>Name</th>
							<th>URL</th>
							<th></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td align="right" colspan="5"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#createBranch" <?php echo ($last_sync == "Never" ? "disabled " : ""); ?>><span class="glyphicon glyphicon-plus"></span> Add</button></td>
						<tr>
					</tfoot>
					<tbody>
						<?php
						$i = 0;
						foreach ($branches as $branch) {
							if ($branch != "") {
						?>
						<tr>
							<td>
								<div class="checkbox checkbox-primary" style="margin-top: 0;">
									<input type="checkbox" name="rootbranch" id="rootbranch<?php echo $i; ?>" value="<?php echo $branch; ?>" onChange="defaultBranch(this);" <?php echo ($conf->getSetting("rootbranch") == $branch ? "checked" : ""); ?>/>
									<label/>
								</div>
							</td>
							<td>
								<div class="checkbox checkbox-primary" style="margin-top: 0;">
									<input type="checkbox" id="autosync[<?php echo $branch; ?>]" value="<?php echo $branch; ?>" onChange="javascript:ajaxPost('susCtl.php?branch='+this.value, 'autosync='+this.checked);" <?php echo ($conf->containsAutosyncBranch($branch) ? "checked" : ""); ?>/>
									<label/>
								</div>
							</td>
							<td><a href="managebranch.php?branch=<?php echo $branch?>" title="Manage branch: <?php echo $branch?>"><?php echo $branch?></a></td>
							<td><?php echo $susbaseurl."content/catalogs/index_".$branch.".sucatalog"?></td>
							<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#delete_branch" onClick="document.getElementById('deletebranch').value = '<?php echo $branch?>'; document.getElementById('delete_title').innerHTML = 'Delete Branch \'<?php echo $branch?>\'?';">Delete</button></td>
						</tr>
						<?php
								$i++;
							}
						}
						?>
					</tbody>
				</table>
			</div>

			<hr>
			<br>

			<h5><strong>Purge Deprecated</strong> <small>Removes all deprecated products that are not in any branch catalogs.</small></h5>
			<button type="button" id="purge_dep" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#purge-modal" onClick="purgeModal();"; "<?php echo ($last_sync == "Never" ? "disabled " : ""); ?>>Purge</button>

			<div class="modal" id="sync-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h3 class="modal-title">Sync Running</h3>
						</div>
						<div class="modal-body">
							<div class="text-center" style="padding: 8px 0px;"><img src="images/progress.gif"></div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default btn-sm pull-right" onClick="document.location.href='dashboard.php';">Home</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade" id="createBranch" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h3 class="modal-title" id="new_title">New Branch Catalog</h3>
						</div>
						<div class="modal-body">

							<h5 id="branchname_label"><strong>Branch Name</strong> <small>This name is appended to the apple catalog names.</small></h5>
							<div class="form-group">
								<input type="text" name="branchname" id="branchname" class="form-control input-sm" onKeyUp="validBranch(this, 'branchname_label');" onBlur="validBranch(this, 'branchname_label');" placeholder="[Required]" />
							</div>

							<h5><strong>Copy Branch</strong> <small>Copies all items from this branch to the new branch.</small></h5>
							<select id="srcbranch" name="srcbranch" class="form-control input-sm">
								<option value="" selected>None</option>
								<?php
								foreach ($branches as $branch) {
									if ($branch != "") {
								?>
								<option value="<?php echo $branch; ?>"><?php echo $branch; ?></option>
								<?php
									}
								}
								?>
							</select>

						</div>
						<div class="modal-footer">
							<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
							<button type="submit" name="addbranch" id="addbranch" class="btn btn-primary btn-sm" disabled >Save</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade" id="delete_branch" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h3 class="modal-title" id="delete_title">Delete Branch Catalog?</h3>
						</div>
						<div class="modal-body">
							<div class="text-muted">This action is permanent and cannot be undone.</div>
						</div>
						<div class="modal-footer">
							<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
							<button type="submit" name="deletebranch" id="deletebranch" class="btn btn-danger btn-sm" value="">Delete</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal" id="purge-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header" id="purge-title">
							<h3 class="modal-title">Purge Deprecated</h3>
						</div>
						<div class="modal-body hidden" id="purge-warning">
							<div class="text-muted">This action is permanent and cannot be undone.</div>
						</div>
						<div class="modal-body" id="purge-progress">
							<div class="text-center" style="padding: 8px 0px;"><img src="images/progress.gif"></div>
						</div>
						<div class="modal-footer hidden" id="purge-confirm">
							<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
							<button type="button" class="btn btn-danger btn-sm" onClick="purgeDep();">Purge</button>
						</div>
						<div class="modal-footer" id="purge-refresh">
							<button type="button" class="btn btn-default btn-sm pull-right" onClick="document.location.href='dashboard.php';">Home</button>
						</div>
					</div>
				</div>
			</div>

		</form> <!-- end form SUS -->
	</div><!-- /.col -->
</div><!-- /.row -->

<?php if ($sync_status) { ?>
<script>
	$(window).load(function() {
		setTimeout('window.location.reload()', 5000);
		$('#sync-modal').modal('show');
	}); 
</script>
<?php }
if ($util_status) { ?>
<script>
	$(window).load(function() {        
		setTimeout('window.location.reload()', 5000);
		$('#purge-modal').modal('show');
	}); 
</script>
<?php } ?>
<?php include "inc/footer.php"; ?>