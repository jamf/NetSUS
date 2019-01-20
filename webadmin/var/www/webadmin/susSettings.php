<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Software Update Server";

include "inc/header.php";

// Helper Function
function susExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

// Catalog URLs
$default_catalog_map = array(
	array("default" => true, "name" => "10.4", "url" => "http://swscan.apple.com/content/catalogs/index.sucatalog"),
	array("default" => false, "name" => "10.4", "url" => "http://swscan.apple.com/content/catalogs/index-1.sucatalog"),
	array("default" => true, "name" => "10.5", "url" => "http://swscan.apple.com/content/catalogs/others/index-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.6", "url" => "http://swscan.apple.com/content/catalogs/others/index-leopard-snowleopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.7", "url" => "http://swscan.apple.com/content/catalogs/others/index-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.8", "url" => "http://swscan.apple.com/content/catalogs/others/index-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.8seed", "url" => "http://swscan.apple.com/content/catalogs/others/index-mountainlionseed-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.9", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.9seed", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.9seed-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.10", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.10beta", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.10beta-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.10seed", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.10seed-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.11", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.11beta", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.11beta-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.11seed", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.11seed-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.12", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.12beta", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.12beta-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.12seed", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.12seed-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.13", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.13beta", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.13beta-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.13seed", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.13seed-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => true, "name" => "10.14", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.14-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.14beta", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.14beta-10.14-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
	array("default" => false, "name" => "10.14seed", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.14seed-10.14-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
);
$default_catalog_urls = array();
foreach ($default_catalog_map as $array) {
	if ($array["default"]) {
		array_push($default_catalog_urls, $array["url"]);
	}
}
$apple_catalog_urls_str = trim(susExec("getCatalogURLs"));
if (empty($apple_catalog_urls_str)) {
	$apple_catalog_urls = $default_catalog_urls;
} else {
	$apple_catalog_urls = explode(" ", $apple_catalog_urls_str);
}
if (isset($_POST["addcatalogurl"])) {
	array_push($apple_catalog_urls, $_POST["newcatalogurl"]);
	$apple_catalog_urls_str = implode(" ", $apple_catalog_urls);
	susExec("setCatalogURLs \"".$apple_catalog_urls_str."\"");
}
if (isset($_POST["deletecatalogurl"])) {
	if (($key = array_search($_POST["deletecatalogurl"], $apple_catalog_urls)) !== false) {
		unset($apple_catalog_urls[$key]);
	}
	$apple_catalog_urls_str = implode(" ", $apple_catalog_urls);
	susExec("setCatalogURLs \"".$apple_catalog_urls_str."\"");
}
$other_catalog_urls = array();
foreach ($apple_catalog_urls as $url) {
	if (!in_array($url, $default_catalog_urls)) {
		array_push($other_catalog_urls, $url);
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

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

// Preferences
$root_dir = trim(susExec("getPref UpdatesRootDir"));
$meta_dir = trim(susExec("getPref UpdatesMetadataDir"));

// Daily Sync Time
if ($conf->getSetting("syncschedule") == NULL || $conf->getSetting("syncschedule") == "") {
	$conf->setSetting("syncschedule", "Off");
}
$syncschedule = $conf->getSetting("syncschedule");

// SUS Status
$sync_status = trim(susExec("getSyncStatus")) == "true" ? true : false;
$util_status = trim(susExec("getUtilStatus")) == "true" ? true : false;
?>
			<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>
			<link rel="stylesheet" href="theme/dataTables.bootstrap.css" />
			<link rel="stylesheet" href="theme/bootstrap-toggle.css">

			<script type="text/javascript" src="scripts/toggle/bootstrap-toggle.min.js"></script>

			<script type="text/javascript">
				var appleCatalogURLs = [<?php echo (empty($apple_catalog_urls) ? "" : "\"".implode('", "', $apple_catalog_urls)."\""); ?>];
				var otherCatalogURLs = [<?php echo (empty($other_catalog_urls) ? "" : "\"".implode('", "', $other_catalog_urls)."\""); ?>];
				var validCatalogURLs = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['url']; }, $default_catalog_map))."\""; ?>];

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

				function showSuccess(element, offset = false) {
					var span = document.createElement("span");
					span.className = "glyphicon glyphicon-ok form-control-feedback text-success";
					if (offset) {
						span.style.right = offset + "px";
					}
					element.parentElement.appendChild(span);
				}

				function hideSuccess(element) {
					var span = element.parentElement.getElementsByTagName("span");
					for (var i = 0; i < span.length; i++) {
						if (span[i].classList.contains("form-control-feedback")) {
							element.parentElement.removeChild(span[i]);
						}
					}
				}

				function validBaseUrl(element, labelId = false) {
					hideSuccess(element);
					if (/^http(s)?:\/\/(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[0-9][\/]|[1-9][0-9]|[1-9][0-9][\/]|1[0-9]{2}|1[0-9]{2}[\/]|2[0-4][0-9]|2[0-4][0-9][\/]|25[0-5]|25[0-5][\/])$|^http(s)?:\/\/(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][\/]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9][\/])$/.test(element.value)) {
						hideError(element, labelId);
					} else {
						showError(element, labelId);
					}
				}

				function updateBaseUrl(element, offset = false) {
					if (/^http(s)?:\/\/(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[0-9][\/]|[1-9][0-9]|[1-9][0-9][\/]|1[0-9]{2}|1[0-9]{2}[\/]|2[0-4][0-9]|2[0-4][0-9][\/]|25[0-5]|25[0-5][\/])$|^http(s)?:\/\/(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][\/]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9][\/])$/.test(element.value)) {
						ajaxPost('susCtl.php', 'baseurl='+element.value);
						showSuccess(element);
					}
				}

				function setSyncSchedule(element) {
					var syncSch = "Off";
					var checked = element.checked;
					if (checked) {
						syncSch = element.value;
					}
					elements = document.getElementsByName('syncsch');
					for (i = 0; i < elements.length; i++) {
						elements[i].checked = false;
					}
					ajaxPost('susCtl.php', 'syncschedule='+syncSch);
					element.checked = checked;
				}

				function validCatalogURL(element, labelId = false) {
					if (validCatalogURLs.indexOf(element.value) >= 0 && appleCatalogURLs.indexOf(element.value) == -1) {
						hideError(element, labelId);
						$('#addcatalogurl').prop('disabled', false);
					} else {
						showError(element, labelId);
						$('#addcatalogurl').prop('disabled', true);
					}
				}

				function setCatalogURLs(element) {
					var checkedCatalogURLs = [];
					elements = document.getElementsByName('catalogurl');
					for (i = 0; i < elements.length; i++) {
						if (elements[i].checked) {
							checkedCatalogURLs.push(elements[i].value);
						}
					}
					if (checkedCatalogURLs.length == 1 && otherCatalogURLs.length == 0) {
						for (i = 0; i < elements.length; i++) {
							if (elements[i].checked) {
								elements[i].disabled = true;
							}
						}
					} else {
						for (i = 0; i < elements.length; i++) {
							elements[i].disabled = false;
						}
					}
					if (document.getElementById("delete_other")) {
						document.getElementById("delete_other").disabled = checkedCatalogURLs.length == 0 && otherCatalogURLs.length == 1;
					}
					appleCatalogURLs = checkedCatalogURLs.concat(otherCatalogURLs);
					ajaxPost("susCtl.php", "catalogurls="+appleCatalogURLs);
				}

				function toggleService() {
					if ($('#susenabled').prop('checked')) {
						$('#sus').removeClass('hidden');
						$('#baseurl').prop('disabled', false);
						$('#mirrorpkgs').prop('disabled', false);
						$('[name="catalogurl"]').prop('disabled', false);
						$('#add_other').prop('disabled', false);
						$('#delete_other').prop('disabled', false);
						$('[name="syncsch"]').prop('disabled', false);
						ajaxPost('susCtl.php', 'service=enable');
					} else {
						$('#sus').addClass('hidden');
						$('#baseurl').prop('disabled', true);
						$('#mirrorpkgs').prop('disabled', true);
						$('[name="catalogurl"]').prop('disabled', true);
						$('#add_other').prop('disabled', true);
						$('#delete_other').prop('disabled', true);
						$('[name="syncsch"]').prop('disabled', true);
						$('[name="syncsch"]').prop('checked', false);
						ajaxPost('susCtl.php', 'syncschedule=Off');
						ajaxPost('susCtl.php', 'service=disable');
					}
				}

				function toggleDashboard() {
					if ($('#dashboard').prop('checked')) {
						ajaxPost('susCtl.php', 'dashboard=true');
					} else {
						ajaxPost('susCtl.php', 'dashboard=false');
					}
				}
			</script>

			<script type="text/javascript">
				$(document).ready(function(){
					toggleService();
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<div class="row">
						<div class="col-xs-10">
							<h2>Software Update Server</h2>
						</div>
						<div class="col-xs-2 text-right">
							<input type="checkbox" id="susenabled" data-toggle="toggle" data-size="small" onChange="toggleService();" <?php echo ($conf->getSetting("sus") == "enabled" ? "checked" : ""); ?>>
						</div>
					</div>
				</div>
			</nav>

			<div style="padding: 70px 20px 1px; background-color: #f9f9f9;">
				<div class="checkbox checkbox-primary">
					<input name="dashboard" id="dashboard" class="styled" type="checkbox" value="true" onChange="toggleDashboard();" <?php echo ($conf->getSetting("showsus") == "false" ? "" : "checked"); ?>>
					<label><strong>Show in Dashboard</strong><br><span style="font-size: 75%; color: #777;">Display service status in the NetSUS dashboard.</span></label>
				</div>
			</div>

			<hr>

			<div style="padding: 9px 20px 1px;">
				<h5 id="baseurl_label"><strong>Base URL</strong> <small>Base URL for the software update server (e.g. "http://sus.mycompany.corp").</small></h5>
				<div class="form-group has-feedback">
					<input type="text" name="baseurl" id="baseurl" class="form-control input-sm long-text-input" placeholder="[Required]" value="<?php echo $susbaseurl; ?>" onFocus="validBaseUrl(this, 'baseurl_label');" onKeyUp="validBaseUrl(this, 'baseurl_label');" onChange="updateBaseUrl(this);"/>
				</div>

				<div class="checkbox checkbox-primary">
					<input name="mirrorpkgs" id="mirrorpkgs" class="styled" type="checkbox" value="mirrorpkgs" onChange="javascript: ajaxPost('susCtl.php', 'mirrorpkgs=' + this.checked);" <?php echo ($conf->getSetting("mirrorpkgs") == "true" ? "checked" : ""); ?>>
					<label><strong>Store Updates on this Server</strong> <span style="font-size: 75%; color: #777;">Ensure that computers install software updates from this software update server instead of downloading and installing them from Apple's software update server.</span></label>
				</div>
			</div>

			<hr>

			<div style="padding: 9px 20px 16px; background-color: #f9f9f9;">
				<h5><strong>Schedule</strong> <small>Time at which to sync the list of available updates with Apple's Software Update server each day.</small></h5>
				<div class="row">
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[0]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="0" <?php echo ($syncschedule == "0" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 12 am </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[1]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="3" <?php echo ($syncschedule == "3" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 3 am </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[2]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="6" <?php echo ($syncschedule == "6" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 6 am </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[3]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="9" <?php echo ($syncschedule == "9" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 9 am </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[4]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="12" <?php echo ($syncschedule == "12" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 12 pm </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[5]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="15" <?php echo ($syncschedule == "15" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 3 pm </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[6]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="18" <?php echo ($syncschedule == "18" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 6 pm </label>
						</div>
					</div>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="syncsch" id="syncsch[7]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="21" <?php echo ($syncschedule == "21" ? "checked" : ""); ?>>
							<label class="text-nowrap"> 9 pm </label>
						</div>
					</div>
				</div>
			</div>

			<hr>

			<div style="padding: 9px 20px 16px;">
				<h5><strong>Apple Catalog URLs</strong> <small>Select the Apple SUS catalog URLs to replicate.</small></h5>
				<div class="row">
<?php foreach ($default_catalog_map as $array) {
if ($array["default"]) { ?>
					<div class="col-xs-2 col-md-1">
						<div class="checkbox checkbox-primary checkbox-inline">
							<input name="catalogurl" class="styled" type="checkbox" onChange="setCatalogURLs(this);" value="<?php echo $array["url"]; ?>" <?php echo (in_array($array["url"], $apple_catalog_urls) ? (sizeof($apple_catalog_urls) == 1 ? "checked disabled" : "checked") : ""); ?> />
							<label class="text-nowrap"> <?php echo $array["name"]; ?> </label>
						</div>
					</div>
<?php }
} ?>
				</div>
			</div>

			<hr>

			<div style="padding: 8px 20px 1px; background-color: #f9f9f9; overflow-x: auto;">
				<div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
					<div class="row">
						<div class="col-sm-10">
							<div class="dataTables_filter">
								<h5><strong>Additional Catalog URLs</strong> <small>Specify additional SUS catalog URLs to replicate.</small></h5>
							</div>
						</div>
						<div class="col-sm-2">
							<div class="dataTables_paginate">
								<div class="btn-group">
									<button type="button" id="add_other" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createCatalog"><span class="glyphicon glyphicon-plus"></span> Add</button>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<table id="catalogTable" class="table table-hover">
								<thead>
									<tr>
										<th>URL</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php foreach ($other_catalog_urls as $catalog_url) { ?>
									<tr>
										<td><?php echo $catalog_url; ?></td>
										<td align="right"><button id="delete_other" type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#delete_catalog" onClick="document.getElementById('deletecatalogurl').value = '<?php echo $catalog_url?>';" <?php echo (sizeof($apple_catalog_urls) == 1 ? "disabled" : ""); ?>>Delete</button></td>
									</tr>
<?php }
if (sizeof($other_catalog_urls) == 0) { ?>
									<tr>
										<td align="center" valign="top" colspan="2" class="dataTables_empty">No data available in table</td>
									</tr>
<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<hr>

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

			<form action="susSettings.php" method="post" name="SUS" id="SUS">

				<!-- Add Catalog Modal -->
				<div class="modal fade" id="createCatalog" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title" id="new_title">Add Catalog URL</h3>
							</div>
							<div class="modal-body">

								<h5 id="addcatalogurl_label"><strong>URL</strong> <small>Additional SUS catalog URL to replicate.</small></h5>
								<div class="form-group">
									<input type="text" name="newcatalogurl" id="newcatalogurl" class="form-control input-sm" onKeyUp="validCatalogURL(this, 'addcatalogurl_label');" onBlur="validCatalogURL(this, 'addcatalogurl_label');" placeholder="[Required]" />
								</div>

							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
								<button type="submit" name="addcatalogurl" id="addcatalogurl" class="btn btn-primary btn-sm" disabled >Save</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /#modal -->

				<!-- Delete Catalog Modal -->
				<div class="modal fade" id="delete_catalog" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h3 class="modal-title" id="delete_title">Delete Catalog URL?</h3>
							</div>
							<div class="modal-body">
								<div class="text-muted">This action is permanent and cannot be undone.</div>
							</div>
							<div class="modal-footer">
								<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
								<button type="submit" name="deletecatalogurl" id="deletecatalogurl" class="btn btn-danger btn-sm" value="">Delete</button>
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
