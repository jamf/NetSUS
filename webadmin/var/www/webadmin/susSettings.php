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

// Preferences
$root_dir = trim(susExec("getPref UpdatesRootDir"));
$meta_dir = trim(susExec("getPref UpdatesMetadataDir"));

// Daily Sync Time
if ($conf->getSetting("syncschedule") == NULL || $conf->getSetting("syncschedule") == "") {
	$conf->setSetting("syncschedule", "Off");
}
$syncschedule = $conf->getSetting("syncschedule");

// Proxy Details
$proxy_str = trim(susExec("getProxy"));
$proxy = explode(":", $proxy_str);

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
	array("default" => false, "name" => "10.14", "url" => "https://swscan.apple.com/content/catalogs/others/index-10.14-10.13-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"),
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

// SUS Status
$sync_status = trim(susExec("getSyncStatus")) == "true" ? true : false;
$util_status = trim(susExec("getUtilStatus")) == "true" ? true : false;

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>

<link rel="stylesheet" href="theme/awesome-bootstrap-checkbox.css"/>

<script type="text/javascript">
	var appleCatalogURLs = [<?php echo (empty($apple_catalog_urls) ? "" : "\"".implode('", "', $apple_catalog_urls)."\""); ?>];
	var otherCatalogURLs = [<?php echo (empty($other_catalog_urls) ? "" : "\"".implode('", "', $other_catalog_urls)."\""); ?>];
	var validCatalogURLs = [<?php echo "\"".implode('", "', array_map(function($el){ return $el['url']; }, $default_catalog_map))."\""; ?>];
</script>

<script type="text/javascript" src="scripts/susValidation.js"></script>

<script type="text/javascript">
$(document).ready(function(){
	$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
		localStorage.setItem('activeSusTab', $(e.target).attr('href'));
	});
	var activeSusTab = localStorage.getItem('activeSusTab');
	if(activeSusTab){
		$('#top-tabs a[href="' + activeSusTab + '"]').tab('show');
	}
});
</script>

<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">Services</span> <span class="glyphicon glyphicon-chevron-right"></span></div>

<h2>Software Update Server</h2>

<div class="row">
	<div class="col-xs-12">

		<form action="susSettings.php" method="post" name="SUS" id="SUS">

			<ul class="nav nav-tabs nav-justified" id="top-tabs">
				<li class="active"><a class="tab-font" href="#preferences-tab" role="tab" data-toggle="tab">Preferences</a></li>
				<li><a class="tab-font" href="#schedule-tab" role="tab" data-toggle="tab">Schedule</a></li>
				<li><a class="tab-font" href="#proxy-tab" role="tab" data-toggle="tab">Proxy</a></li>
			</ul>

			<div class="tab-content">

				<div class="tab-pane active fade in" id="preferences-tab">

					<div style="padding: 8px 0px;" class="description">PREFERENCES DESCRIPTION</div>

					<h5 id="baseurl_label"><strong>Base URL</strong> <small>Base URL for the software update server (e.g. "http://sus.mycompany.corp").</small></h5>
					<div class="form-group has-feedback">
						<input type="text" name="baseurl" id="baseurl" class="form-control input-sm long-text-input" placeholder="[Required]" value="<?php echo $susbaseurl; ?>" onFocus="validBaseUrl(this, 'baseurl_label');" onKeyUp="validBaseUrl(this, 'baseurl_label');" onChange="updateBaseUrl(this);"/>
					</div>

					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="mirrorpkgs" id="mirrorpkgs" class="styled" type="checkbox" value="mirrorpkgs" onChange="javascript: ajaxPost('susCtl.php', 'mirrorpkgs=' + this.checked);" <?php echo ($conf->getSetting("mirrorpkgs") == "true" ? "checked" : ""); ?>>
						<label><strong>Store Updates on this Server</strong> <span style="font-size: 75%; color: #777;">Ensure that computers install software updates from this software update server instead of downloading and installing them from Apple's software update server.</span></label>
					</div>

					<br>
					<br>
					<hr>
					<br>

					<h5><strong>Apple Catalog URLs</strong> <small>Specify the Apple SUS catalog URLs to replicate.</small></h5>
					<?php foreach ($default_catalog_map as $array) {
						if ($array["default"]) { ?>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="catalogurl" class="styled" type="checkbox" onChange="setCatalogURLs(this);" value="<?php echo $array["url"]; ?>" <?php echo (in_array($array["url"], $apple_catalog_urls) ? (sizeof($apple_catalog_urls) == 1 ? "checked disabled" : "checked") : ""); ?> />
						<label> <?php echo $array["name"]; ?> </label>
					</div>
					<?php }
					} ?>

					<br>
					<br>

					<h5><strong>Additional Catalog URLs</strong></a> <small>Additional SUS catalog URLs to replicate.</small></h5>
					<table class="table table-striped">
						<tfoot>
							<tr>
								<td colspan="2" align="right"><button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#createCatalog"><span class="glyphicon glyphicon-plus"></span> Add</button></td>
							</tr>
						</tfoot>
						<tbody>
							<?php foreach ($other_catalog_urls as $catalog_url) { ?>
							<tr>
								<td><?php echo $catalog_url; ?></td>
								<td align="right"><button id="delete_other" type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#delete_catalog" onClick="document.getElementById('deletecatalogurl').value = '<?php echo $catalog_url?>';" <?php echo (sizeof($apple_catalog_urls) == 1 ? "disabled" : ""); ?>>Delete</button></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>

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

				</div><!-- /.tab-pane -->

				<div class="tab-pane fade in" id="schedule-tab">

					<div style="padding: 8px 0px;" class="description">SCHEDULE DESCRIPTION</div>

					<h5><strong>Schedule</strong> <small>Time at which to sync the list of available updates with Apple's Software Update server each day.</small></h5>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[0]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="0" <?php echo ($syncschedule == "0" ? "checked" : ""); ?>>
						<label> 12 am </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[1]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="3" <?php echo ($syncschedule == "3" ? "checked" : ""); ?>>
						<label> 3 am </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[2]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="6" <?php echo ($syncschedule == "6" ? "checked" : ""); ?>>
						<label> 6 am </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[3]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="9" <?php echo ($syncschedule == "9" ? "checked" : ""); ?>>
						<label> 9 am </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[4]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="12" <?php echo ($syncschedule == "12" ? "checked" : ""); ?>>
						<label> 12 pm </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[5]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="15" <?php echo ($syncschedule == "15" ? "checked" : ""); ?>>
						<label> 3 pm </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[6]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="18" <?php echo ($syncschedule == "18" ? "checked" : ""); ?>>
						<label> 6 pm </label>
					</div>
					<div class="checkbox checkbox-primary checkbox-inline">
						<input name="syncsch" id="syncsch[7]" class="styled" type="checkbox" onChange="setSyncSchedule(this);" value="21" <?php echo ($syncschedule == "21" ? "checked" : ""); ?>>
						<label> 9 pm </label>
					</div>

				</div><!-- /.tab-pane -->

				<div class="tab-pane fade in" id="proxy-tab">

					<div style="padding: 8px 0px;" class="description">PROXY DESCRIPTION</div>

					<h5 id="proxyhost_label"><strong>Proxy Server</strong> <small>Hostname or IP address, and port number for the proxy server.</small></h5>
					<div class="row">
						<div class="col-xs-8" style="padding-right: 0px; width: 73%;">
							<div class="has-feedback">
								<input type="text" name="proxyhost" id="proxyhost" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[0]) ? $proxy[0] : ""); ?>" onFocus="validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onKeyUp="hideSuccess(this); validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onChange="updateProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" />
							</div>
						</div>
						<div class="col-xs-1 text-center" style="padding-left: 0px; padding-right: 0px; width: 2%;">:</div>
						<div class="col-xs-3" style="padding-left: 0px;">
							<div class="has-feedback">
								<input type="text" name="proxyport" id="proxyport" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[1]) ? $proxy[1] : ""); ?>" onFocus="validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onKeyUp="hideSuccess(this); validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onChange="updateProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" />
							</div>
						</div>
					</div>
					<h5 id="proxyuser_label"><strong>Authentication</strong> <small>Username used to connect to the proxy.</small></h5>
					<div class="form-group has-feedback">
						<input type="text" name="proxyuser" id="proxyuser" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[2]) ? $proxy[2] : ""); ?>" onFocus="validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onKeyUp="hideSuccess(this); validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onChange="updateProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" <?php echo (empty($proxy[0]) ? "disabled" : ""); ?>/>
					</div>
					<h5 id="proxypass_label"><strong>Password</strong> <small>Password used to authenticate with the proxy.</small></h5>
					<div class="form-group has-feedback">
						<input type="password" name="proxypass" id="proxypass" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[3]) ? $proxy[3] : ""); ?>" onFocus="validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onKeyUp="hideSuccess(this); hideSuccess(document.getElementById('proxyverify')); validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onChange="updateProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" <?php echo (empty($proxy[0]) ? "disabled" : ""); ?>/>
					</div>
					<h5 id="proxyverify_label"><strong>Verify Password</strong></h5>
					<div class="form-group has-feedback">
						<input type="password" name="proxyverify" id="proxyverify" class="form-control input-sm" placeholder="[Optional]" value="<?php echo (isset($proxy[3]) ? $proxy[3] : ""); ?>" onFocus="validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onKeyUp="hideSuccess(this); hideSuccess(document.getElementById('proxypass')); validProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" onChange="updateProxy('proxyhost', 'proxyport', 'proxyuser', 'proxypass', 'proxyverify');" <?php echo (empty($proxy[0]) ? "disabled" : ""); ?>/>
					</div>

				</div><!-- /.tab-pane -->

			</div> <!-- end .tab-content -->

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