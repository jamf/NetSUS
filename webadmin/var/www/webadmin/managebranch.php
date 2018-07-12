 <?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$currentBranch = "";
if (isset($_GET['branch']) && $_GET['branch'] != "") {
	$currentBranch = $_GET['branch'];
}

$title = "Manage updates for branch: $currentBranch";

include "inc/header.php";

function susExec($cmd) {
	return exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

if (isset($_POST['applyPackages'])) {
	susExec("deleteBranch ".$currentBranch);
	susExec("createBranch ".$currentBranch);
	susExec("addProducts \"".$_POST['packages']."\" ".$currentBranch);
	if ($conf->getSetting("rootbranch") == $currentBranch) {
		susExec("rootBranch ".$currentBranch);
	}
	$status_msg = "<div class=\"text-success\" style=\"padding-top: 12px;\"><span class=\"glyphicon glyphicon-ok-sign\"></span> Added ".sizeof(explode(' ', $_POST['packages']))." updates to '".$currentBranch."' branch.</div>";
}

$productstr = trim(susExec("productList"));
$products = json_decode($productstr);

$prods_checked = array();
foreach($products as $productobj) {
	if (in_array($currentBranch, $productobj->BranchList)) {
		array_push($prods_checked, $productobj->id);
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
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
		"dom": "<'row'<'col-sm-4'f><'col-sm-4'i><'col-sm-4'<'dataTables_paginate'B>>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4'l><'col-sm-8'p>>",
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

<div class="description"><a href="SUS.php">Software Update Server</a> <span class="glyphicon glyphicon-chevron-right"> </span></div>

<h2 id="heading">Branch: <?php echo $currentBranch; ?></h2>

<div class="row">
	<div class="col-xs-12 col-sm-12">

		<form action="managebranch.php?branch=<?php echo $currentBranch?>" method="post" name="branchPackages" id="branchPackages">

			<hr>
			<div style="padding-top: 12px;" class="description">Select Apple Software Updates to be enabled in this branch. Click the <em>Apply</em> button to save changes.<br><strong>Note:</strong> The <em>Select All</em> and <em>Clear All</em> buttons apply to only updates visible in the table.</div>
			<?php echo (isset($status_msg) ? $status_msg : ""); ?>
			<br>

			<input id="packages" name="packages" type="hidden" value="<?php echo implode(' ', $prods_checked); ?>"/>

			<table id="package_table" class="table table-striped">
				<thead>
					<tr>
						<th>Enable</th>
						<th>Name</th>
						<th>Version</th>
						<th>Date</th>
					</tr>
				</thead>
				<tbody>
				<?php
					$i=0;
					foreach ($products as $productobj) { ?>
					<tr>
						<td>
							<div class="checkbox checkbox-primary" style="margin-top: 0;">
								<input type="checkbox" id="<?php echo $productobj->id; ?>" value="<?php echo $productobj->id; ?>" onChange="checkBox(this.value, this.checked);"<?php echo (in_array($currentBranch, $productobj->BranchList) ? " checked" : ""); ?>/>
								<label/>
							</div>
						</td>
						<td><a data-toggle="modal" href="#Description" onClick="updateModalContent('<?php echo $productobj->title; ?><?php echo ($productobj->Deprecated == "(Deprecated)" ? " <small>(Deprecated)</small>" : "") ?>', '<?php echo $productobj->id; ?>');"><?php echo $productobj->title; ?></a> <?php echo $productobj->Deprecated; ?></td>
						<td nowrap><?php echo $productobj->version; ?></td>
						<td nowrap><?php echo $productobj->PostDate; ?></td>
					</tr>
				<?php $i++;
				} ?>
				</tbody>
			</table>

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

			<nav id="nav-footer" class="navbar navbar-default navbar-fixed-bottom">
				<input type="submit" value="Apply" name="applyPackages" id="applyPackages" class="btn btn-primary btn-sm pull-right" style="margin-top: 10px; margin-bottom: 10px; margin-right: 15px;" disabled/>
			</nav>

		</form>

	</div>
</div>

<?php

include "inc/footer.php";

?>