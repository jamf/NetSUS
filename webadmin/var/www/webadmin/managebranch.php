<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$sURL="SUS.php";

function susExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$branchstr = trim(susExec("getBranchlist"));
if (empty($branchstr)) {
	$branches = array();
} else {
	$branches = explode(" ", $branchstr);
}

if (isset($_GET['branch']) && in_array($_GET['branch'], $branches)) {
	$currentBranch = $_GET['branch'];
} else {
	header("Location: ". $sURL);
}

$title = "Manage Branch: ".$currentBranch;

include "inc/header.php";

if (isset($_POST['applyPackages'])) {
	susExec("deleteBranch ".$currentBranch);
	susExec("createBranch ".$currentBranch);
	susExec("addProducts \"".$_POST['packages']."\" ".$currentBranch);
	if ($conf->getSetting("rootbranch") == $currentBranch) {
		susExec("rootBranch ".$currentBranch);
	}
	$status_msg = sizeof(explode(' ', $_POST['packages']))." updates enabled in '".$currentBranch."' branch.";
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

$productstr = trim(susExec("productList"));
$products = json_decode($productstr);

$prods_checked = array();
foreach($products as $productobj) {
	if (in_array($currentBranch, $productobj->BranchList)) {
		array_push($prods_checked, $productobj->id);
	}
}
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />

			<script type="text/javascript" src="scripts/dataTables/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="scripts/dataTables/dataTables.bootstrap.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="scripts/Buttons/buttons.bootstrap.min.js"></script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#package_table').DataTable( {
						buttons: [
							{
								text: 'Select All',
								className: 'btn-sm',
								action: function ( e, dt, node, config ) {
									selectAllVisible();
								}
							},
							{
								text: 'Clear All',
								className: 'btn-sm',
								action: function ( e, dt, node, config ) {
									clearAllVisible();
								}
							}
						],
						"dom": "<'row'<'col-sm-4'f><'col-sm-4'i><'col-sm-4'<'dataTables_paginate'B>>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-3'l><'col-sm-9'p>>",
						"order": [ 3, 'desc' ],
						"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
						"columns": [
							{ "orderable": false },
							null,
							null,
							null
						]
					});
				} );
			</script>

			<script type="text/javascript">
				var pkgEnabledList = ["<?php echo implode('", "', $prods_checked); ?>"];
				pkgEnabledList.sort();
				var pkgCheckedList = ["<?php echo implode('", "', $prods_checked); ?>"];
				pkgCheckedList.sort();

				function selectAllVisible() {
					var boxes = document.getElementsByTagName('input');
					for (i = 0; i < boxes.length; i++) {
						if ( boxes[i].type === 'checkbox' ) {
							boxes[i].checked = true;
							checkBox(boxes[i].value, true);
						}
					}
				}

				function clearAllVisible() {
					var boxes = document.getElementsByTagName('input');
					for (i = 0; i < boxes.length; i++) {
						if ( boxes[i].type === 'checkbox' ) {
							boxes[i].checked = false;
							checkBox(boxes[i].value, false);
						}
					}
				}

				function checkBox(id, checked) {
					index = pkgCheckedList.indexOf(id);
					if (checked) {
						if (index == -1) {
							pkgCheckedList.push(id)
						}
					} else {
						if (index > -1) {
							pkgCheckedList.splice(index, 1);
						}
					}
					pkgCheckedList.sort();
					document.getElementById('packages').value = pkgCheckedList.join(' ');
					document.getElementById('applyPackages').disabled = pkgEnabledList.join(' ') == pkgCheckedList.join(' ');
				}

				function getProductInfo(id) {
					http = getHTTPObj();
					http.open("GET", "susCtl.php?prodinfo="+id, false);
					http.send();
					return http.responseText;
				}

				function updateModalContent(title, id) {
					document.getElementById('modalTitle').innerHTML = title;
					document.getElementById('modalBody').innerHTML = getProductInfo(id);
				}
			</script>

			<script type="text/javascript">
				$(document).ready(function() {
					$('#settings').attr('onclick', 'document.location.href="susSettings.php"');
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="SUS.php">Software Update Server</a> <span class="glyphicon glyphicon-chevron-right"></span> Manage Branch <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2 id="heading"><?php echo $currentBranch; ?></h2>
				</div>
			</nav>

			<form action="managebranch.php?branch=<?php echo $currentBranch?>" method="post" name="branchPackages" id="branchPackages">

				<input id="packages" name="packages" type="hidden" value="<?php echo implode(' ', $prods_checked); ?>"/>

				<div style="padding: 63px 20px 1px; background-color: #f9f9f9;">
					<div style="margin-top: 16px; margin-bottom: 0px; border-color: #4cae4c;" class="panel panel-success <?php echo (isset($status_msg) ? "" : "hidden"); ?>">
						<div class="panel-body">
							<div class="text-muted"><span class="text-success glyphicon glyphicon-ok-sign" style="padding-right: 12px;"></span><?php echo $status_msg; ?></div>
						</div>
					</div>

					<div class="text-muted" style="font-size: 12px; padding: 16px 0px;">Select Apple Software Updates to be enabled in this branch. Click the <em>Apply</em> button to save changes.<br><strong>Note:</strong> The <em>Select All</em> and <em>Clear All</em> buttons apply to only updates visible in the table.</div>
				</div>

				<hr>

				<div style="padding: 15px 20px 1px; overflow-x: auto;">
					<table id="package_table" class="table table-hover" style="border-bottom: 1px solid #eee;">
						<thead>
							<tr>
								<th>Enable</th>
								<th>Name</th>
								<th>Version</th>
								<th>Date</th>
							</tr>
						</thead>
						<tbody>
<?php $i=0;
foreach ($products as $productobj) { ?>
							<tr>
								<td>
									<div class="checkbox checkbox-primary checkbox-inline">
										<input type="checkbox" id="<?php echo $productobj->id; ?>" value="<?php echo $productobj->id; ?>" onChange="checkBox(this.value, this.checked);"<?php echo (in_array($currentBranch, $productobj->BranchList) ? " checked" : ""); ?>/>
										<label/>
									</div>
								</td>
								<td><a data-toggle="modal" href="#Description" onClick="updateModalContent('<?php echo $productobj->title; ?><?php echo ($productobj->Deprecated == "(Deprecated)" ? " <small>(Deprecated)</small>" : "") ?>', '<?php echo $productobj->id; ?>');"><?php echo $productobj->title; ?></a> <?php echo $productobj->Deprecated; ?></td>
								<td><?php echo $productobj->version; ?></td>
								<td><?php echo $productobj->PostDate; ?></td>
							</tr>
<?php $i++;
} ?>
						</tbody>
					</table>
				</div>

				<!-- Description Modal -->
				<div class="modal fade" id="Description" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title" id="modalTitle"></h3>
							</div>
							<div class="modal-body" id="modalBody">

							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm">Close</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /#modal -->

				<nav id="nav-footer" class="navbar navbar-default navbar-fixed-bottom">
					<button type="submit" id="applyPackages" name="applyPackages" class="btn btn-primary btn-sm btn-footer pull-right" disabled>Apply</button>
					<button type="button" class="btn btn-default btn-sm btn-footer pull-right" onClick="document.location.href='SUS.php'">Done</button>
				</nav>

			</form> <!-- end form branchPackages -->
<?php include "inc/footer.php"; ?>