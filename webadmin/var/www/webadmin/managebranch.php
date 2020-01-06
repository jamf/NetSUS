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

// Filters
$filtersEnabled = $conf->getSetting("filterEnable");
if($filtersEnabled != "true"){
	$filtersEnabled = false;
}else{
	$filtersEnabled = true;
}

// Products
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
				var table = null;

				$(document).ready(function() {
					// Init DataTable
					table = $('#package_table').DataTable( {
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
							"dom": "<'row'<'#table-left-component.col-sm-4'f <'#filter-category'>><'col-sm-4'i><'col-sm-4'<'dataTables_paginate'B>>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-3'l><'col-sm-9'p>>",
							"order": [ 5, 'desc' ],
							"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
							"columns": [
								{ "orderable": false },
								null,
								{ "visible" : false },
								null,
								null,
								null
							]
						});

					http = getHTTPObj();
					http.open("GET", "susCtl.php?susEnableFilters", false);
					http.send();

					let filtersEnable = http.responseText;
					if(filtersEnable == "true"){
						table.column(2).visible(true);
						
						initFilters();
						criticalUpdates();
					}
					
				});	

				function criticalUpdates(){
					$('#loading-info-banner').removeClass('hidden');
					$('#loading-info-text').html("Config-Data");

					$.get("susCtl.php?criticalupdates")
						.done(function(data){
							let json = JSON.parse(data);

							if(json != null && json != undefined){
								table.rows().every(function(idx, tableLoop, rowLoop){
									let node = $(this.node());
									let elem = $(node).find('.update-category');
									let updateCode = $(elem).data('update-code');

									let row = table.row(idx).data();
									
									if(json[updateCode] !== undefined){
										row[2] += ' <span data-filter="configdata" class="badge badge-info">Config-Data</span>';
										table.row(idx).data(row).invalidate();
									}
								});

								$('#loading-info-banner').addClass('hidden');
							}else{
								$('#loading-info-text').html("Failed to load : Config-Data");
								
								setTimeout(() => {
									$('#loading-info-banner').removeClass('hidden');	
								}, 3000);
							}
						});
				}

				function initFilters(){
					http = getHTTPObj();
					http.open("GET", "susCtl.php?susfilters", false);
					http.send();

					let filters = http.responseText.split(';');
					let susfilters = {};
					$.each(filters, function(i, filter){
						let arf = filter.split('=');
						if(arf.length == 2){
							susfilters[arf[0]] = arf[1];
						}
					});

					// Filter buttons
					let htmlFilter = '';
					htmlFilter += '<div class="dropdown" style="margin-left: 10px;">';
					htmlFilter += '<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
					htmlFilter += 'Filter';
					htmlFilter += '<span class="caret" style="margin-left: 8px;"></span>';
					htmlFilter += '</button>';
					htmlFilter += '<ul class="dropdown-menu" id="drop-menu-filter" aria-labelledby="dropdownMenu1">';
					htmlFilter += '<li><a><input data-filter="others" class="checkbox-filter" type="checkbox" checked>Others</a></li>';
					htmlFilter += '<li><a><input data-filter="configdata" class="checkbox-filter" type="checkbox"' + ((susfilters["critical"] != undefined && susfilters["critical"] == "true") ? ' checked'  : '') + '>Critical</a></li>';
					htmlFilter += '<li><a><input data-filter="deprecated" class="checkbox-filter" type="checkbox"' + ((susfilters["deprecated"] != undefined && susfilters["deprecated"] == "true") ? ' checked'  : '') + '>Deprecated</a></li>';
					htmlFilter += '<li><a><input data-filter="printer" class="checkbox-filter" type="checkbox"' + ((susfilters["printer"] != undefined && susfilters["printer"] == "true") ? ' checked'  : '') + '>Printer</a></li>';
					htmlFilter += '<li><a><input data-filter="voice" class="checkbox-filter" type="checkbox"' + ((susfilters["voice"] != undefined && susfilters["voice"] == "true") ? ' checked'  : '') + '>Voice</a></li>';
					htmlFilter += '<li><a><input data-filter="word" class="checkbox-filter" type="checkbox"' + ((susfilters["word"] != undefined && susfilters["word"] == "true") ? ' checked'  : '') + '>Word</a></li>';
					htmlFilter += '</ul>';
					htmlFilter += '</div>';

					// Add component
					$('#filter-category').html(htmlFilter);
					$('#table-left-component').css('display', 'flex');

					// Extend search function with filter
					var matchToFilter = [];
					$.fn.dataTable.ext.search.push(
						function( settings, data, dataIndex, row ){
							let ret = true;
							if(row[2] != null && row[2] != ""){
								//console.log(row[2]);
								$(row[2] + " span").each(function(i, elem){
									let filter = $(elem).data('filter');

									//console.log(filter);

									if(matchToFilter[filter] == false){
										ret = false;
									}
								});
							}else{
								if(matchToFilter["others"] == false)
									ret = false;
							}

							return ret;
						}
					);

					// Filter event
					$('.checkbox-filter').on('change', function(){
						match = $(this).data('filter');

						// Vérifie si contenu dans le tableau
						if(matchToFilter[match] === undefined){
							matchToFilter[match] = false;
						}
						// Set la valeurs si coché
						if($(this).is(':checked')){
							matchToFilter[match] = true;
						}else{
							matchToFilter[match] = false;
						}

						// Redraw table
						table.draw();
					});

					// Init filter event
					$('.checkbox-filter').change();
				}

			</script>

			<script type="text/javascript">
				var pkgEnabledList = ["<?php echo implode('", "', $prods_checked); ?>"];
				pkgEnabledList.sort();
				var pkgCheckedList = ["<?php echo implode('", "', $prods_checked); ?>"];
				pkgCheckedList.sort();

				function selectAllVisible() {
					var boxes = document.getElementsByTagName('input');
					for (i = 0; i < boxes.length; i++) {
						if($(boxes[i]).hasClass("product-checked")){
							boxes[i].checked = true;
							checkBox(boxes[i].value, true);
						}
					}
				}

				function clearAllVisible() {
					var boxes = document.getElementsByTagName('input');
					for (i = 0; i < boxes.length; i++) {
						if($(boxes[i]).hasClass("product-checked")){
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
							<div class="text-muted"><span class="text-success glyphicon glyphicon-ok-sign" style="padding-right: 12px;"></span><?php echo (isset($status_msg) ? $status_msg : ""); ?></div>
						</div>
					</div>

					<div class="text-muted" style="font-size: 12px; padding: 16px 0px;">
						Select Apple Software Updates to be enabled in this branch. Click the <em>Apply</em> button to save changes.<br>
						<strong>Note:</strong> The <em>Select All</em> and <em>Clear All</em> buttons apply to only updates visible in the table.

						<div id="loading-info-banner" class="pull-right hidden" style="position: relative; bottom: 12px; right: 10px;">
							<em>Loading </em><em id="loading-info-text"></em> <img style="height: 30px; width: 30px;" src="images/progress.gif">
						</div>
					</div>
				</div> 

				<hr>

				<div style="padding: 15px 20px 1px; overflow-x: auto;">
					<table id="package_table" class="table table-hover" style="border-bottom: 1px solid #eee;">
						<thead>
							<tr>
								<th>Enable</th>
								<th>Mac OSx</th>
								<?php echo ($filtersEnabled == true) ? "<th>Category Update</th>" : "<th></th>"; ?>
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
										<input type="checkbox" class="product-checked" id="<?php echo $productobj->id; ?>" value="<?php echo $productobj->id; ?>" onChange="checkBox(this.value, this.checked);"<?php echo (in_array($currentBranch, $productobj->BranchList) ? " checked" : ""); ?>/>
										<label/>
									</div>
								</td>
								<td>
									<?php
										if(count($productobj->oscatalogs) <= 2){
											foreach($productobj->oscatalogs as $oscatalog){
												echo '<span class="badge badge-info" style="background-color:#337ab7 !important;">'.$oscatalog.'</span> ';
											}
										}else{
											echo '<span class="badge badge-info" style="background-color:#337ab7 !important;">'.$productobj->oscatalogs[0].' / '.$productobj->oscatalogs[count($productobj->oscatalogs) - 1].'</span>';
										}
									?>
								</td>
								<td class="update-category" data-update-code="<?php echo $productobj->id; ?>">
									<?php

										if($filtersEnabled == true){
											if((strlen($productobj->title) > 12 && substr(trim($productobj->title), 0, 12) === "Voice Update") ||
												(strlen($productobj->title) > 6 && substr($productobj->title, (strlen($productobj->title) - 6), 6) == "Voices"))
											{
												echo '<span data-filter="voice" class="badge badge-info">Voices</span>';
											}
											else if(strlen($productobj->title) > 13 && strpos($productobj->title, "Printer") !== false && strpos($productobj->title, "Update") !== false)
											{
												echo '<span data-filter="printer" class="badge badge-info">Printer</span>';
											}
											else if(strlen($productobj->title) > 10 && strpos($productobj->title, "Word") !== false && strpos($productobj->title, "Update") !== false)
											{
												echo '<span data-filter="word" class="badge badge-info">Word</span>';
											}
											else if($productobj->Deprecated == "(Deprecated)")
											{
												echo '<span data-filter="deprecated" class="badge badge-info" style="background-color:#f0ad4e !important;">Deprecated</span>';
											}	
										}
										
									?>
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