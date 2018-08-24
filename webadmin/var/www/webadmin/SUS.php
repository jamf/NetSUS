<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Software Update Server";

include "inc/header.php";

function susExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
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

// ####################################################################
// End of GET/POST parsing
// ####################################################################

// Branch Catalogs
$branchstr = trim(susExec("getBranchlist"));
if (empty($branchstr)) {
	$branches = array();
} else {
	$branches = explode(" ", $branchstr);
	sort($branches);
}

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
?>

			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />

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

				function validBranch() {
					var existingBranches = [<?php echo (empty($branches) ? "" : "\"".implode('", "', $branches)."\""); ?>];
					var branchname = document.getElementById('branchname');
					if (existingBranches.indexOf(branchname.value) == -1 && /^[A-Za-z0-9._+\-]{1,128}$/.test(branchname.value)) {
						hideError(branchname, 'branchname_label');
						$('#addbranch').prop('disabled', false);
					} else {
						showError(branchname, 'branchname_label');
						$('#addbranch').prop('disabled', true);
					}
				}

				function defaultBranch(element) {
					checked = element.checked;
					elements = document.getElementsByName('rootbranch');
					for (i = 0; i < elements.length; i++) {
						elements[i].checked = false;
					}
					ajaxPost('susCtl.php?branch='+element.value, 'rootbranch='+checked);
					element.checked = checked;
				}

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

			<script type="text/javascript">
				$(document).ready(function() {
					$('#settings').attr('onclick', 'document.location.href="susSettings.php"');
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description">&nbsp;</div>
					<div class="row">
						<div class="col-xs-10"> 
							<h2>Software Update Server</h2>
						</div>
						<div class="col-xs-2 text-right"> 
							<!-- <button type="button" class="btn btn-default btn-sm" >Settings</button> -->
						</div>
					</div>
				</div>
			</nav>

			<div style="padding: 72px 20px 3px; background-color: #f9f9f9;">
				<h5><strong>Last Sync</strong> <small><span id="last_sync"><?php echo $last_sync; ?></span></small></h5>
			</div>

			<hr>

			<div style="padding: 8px 20px 1px; overflow-x: auto;">
				<div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
					<div class="row">
						<div class="col-sm-10">
							<div class="dataTables_filter">
								<h5><strong>Branch Catalogs</strong> <small>Control the availability of updates for clients.</small></h5>
							</div>
						</div>
						<div class="col-sm-2">
							<div class="dataTables_paginate">
								<div class="btn-group">
									<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createBranch" <?php echo ($last_sync == "Never" ? "disabled " : ""); ?>><span class="glyphicon glyphicon-plus"></span> Add</button>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<table id="branchTable" class="table table-hover">
								<thead>
									<tr>
										<th>Default</th>
										<th>Auto Enable</th>
										<th>Name</th>
										<th>URL</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php $i = 0;
foreach ($branches as $branch) {
if ($branch != "") { ?>
									<tr>
										<td>
											<div class="checkbox checkbox-primary checkbox-inline">
												<input type="checkbox" name="rootbranch" id="rootbranch<?php echo $i; ?>" value="<?php echo $branch; ?>" onChange="defaultBranch(this);" <?php echo ($conf->getSetting("rootbranch") == $branch ? "checked" : ""); ?>/>
												<label/>
											</div>
										</td>
										<td>
											<div class="checkbox checkbox-primary checkbox-inline">
												<input type="checkbox" id="autosync[<?php echo $branch; ?>]" value="<?php echo $branch; ?>" onChange="javascript:ajaxPost('susCtl.php?branch='+this.value, 'autosync='+this.checked);" <?php echo ($conf->containsAutosyncBranch($branch) ? "checked" : ""); ?>/>
												<label/>
											</div>
										</td>
										<td><a href="managebranch.php?branch=<?php echo $branch?>" title="Manage branch: <?php echo $branch?>"><?php echo $branch?></a></td>
										<td><?php echo $susbaseurl."content/catalogs/index_".$branch.".sucatalog"?></td>
										<td align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#delete_branch" onClick="$('#delete_title').text('Delete Branch \'<?php echo $branch?>\'?'); $('#deletebranch').val('<?php echo $branch?>');">Delete</button></td>
									</tr>
<?php $i++;
}
}
if (empty($branches)) { ?>
									<tr>
										<td align="center" valign="top" colspan="5" class="dataTables_empty">No data available in table</td>
									</tr>
<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<hr>

			<div style="padding: 4px 20px 16px; background-color: #f9f9f9;">
				<h5><strong>Manual Sync</strong> <small>Manual method for syncing the list of available updates with Apple's Software Update server.</small></h5>
				<button type="button" id="manual_sync" class="btn btn-primary btn-sm" style="width: 53px;" onClick="manSync();">Sync</button>
			</div>

			<hr>

			<div style="padding: 9px 20px 1px;">
				<h5><strong>Purge Deprecated</strong> <small>Removes all deprecated products that are not in any branch catalogs.</small></h5>
				<button type="button" id="purge_dep" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#purge-modal" onClick="purgeModal();"; "<?php echo ($last_sync == "Never" ? "disabled " : ""); ?>>Purge</button>
			</div>

			<!-- Sync Modal -->
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
			<!-- /#modal -->

			<!-- Purge Modal -->
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
			<!-- /#modal -->

			<form action="SUS.php" method="post" name="SUS" id="SUS">

				<!-- Add Branch Modal -->
				<div class="modal fade" id="createBranch" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title" id="new_title">Add Branch Catalog</h3>
							</div>
							<div class="modal-body">

								<h5 id="branchname_label"><strong>Branch Name</strong> <small>This name is appended to the apple catalog names.</small></h5>
								<div class="form-group">
									<input type="text" name="branchname" id="branchname" class="form-control input-sm" onFocus="validBranch();" onKeyUp="validBranch();" onBlur="validBranch();" placeholder="[Required]" />
								</div>

								<h5><strong>Copy Branch</strong> <small>Copies all items from this branch to the new branch.</small></h5>
								<select id="srcbranch" name="srcbranch" class="form-control input-sm">
									<option value="" selected>None</option>
<?php
foreach ($branches as $branch) {
if ($branch != "") { ?>
									<option value="<?php echo $branch; ?>"><?php echo $branch; ?></option>
<?php }
} ?>
								</select>

							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
								<button type="submit" name="addbranch" id="addbranch" class="btn btn-primary btn-sm" disabled >Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /#modal -->

				<!-- Delete Branch Modal -->
				<div class="modal fade" id="delete_branch" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 id="delete_title" class="modal-title">Delete Branch Catalog?</h3>
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
				<!-- /#modal -->

			</form> <!-- end form SUS -->

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
<?php }
include "inc/footer.php"; ?>