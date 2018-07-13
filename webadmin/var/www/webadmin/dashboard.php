<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$currentIP = trim(getCurrentIP());

if ($conf->getSetting("shelluser") != "shelluser") {
	$conf->changedPass("shellaccount");
}

$title = "NetSUS Dashboard";

include "inc/header.php";
?>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>File Sharing</strong>
	</div>
	<?php
	function shareExec($cmd) {
		return shell_exec("sudo /bin/sh scripts/shareHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}
	$smb_conns = trim(suExec("smbconns"));
	$afp_conns = trim(suExec("afpconns"));
	?>

	<div class="panel-body">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-4 col-md-2 dashboard-item">
				<a href="sharingSettings.php">
					<p><img src="images/settings/Category.png" alt="File Sharing"></p>
				</a>
			</div>
			<!-- /Column -->
<?php if ($conf->getSetting("sharing") == "enabled") { ?>
			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Number of Shares</strong></h5>
					<span class="text-muted"><?php echo "2"; ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Disk Usage</strong></h5>
					<span class="text-muted"><?php echo "4.0K"; ?></span>
				</div>
			</div>
			<!-- /Column -->

			<div class="clearfix visible-xs-block visible-sm-block"></div>

			<!-- Column -->
			<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>SMB Status</strong></h5>
					<span class="text-muted"><?php echo (trim(suExec("getsmbstatus")) == "true" ? $smb_conns." Connection".($smb_conns != "1" ? "s" : "") : "Not Running"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>AFP Status</strong></h5>
					<span class="text-muted"><?php echo (trim(suExec("getafpstatus")) == "true" ? $afp_conns." Connection".($afp_conns != "1" ? "s" : "") : "Not Running"); ?></span>
				</div>
			</div>
			<!-- /Column -->
<?php } else { ?>
			<!-- Column -->
			<div class="col-xs-8 col-md-10">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Configure File Sharing</strong> <small>to share files and folders with clients.</small></h5>
					<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='sharingSettings.php'">File Sharing Settings</button>
				</div>
			</div>
			<!-- /Column -->
<?php } ?>
		</div>
		<!-- /Row -->
	</div>
</div>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>Software Update Server</strong>
	</div>
	<?php
	function susExec($cmd) {
		return shell_exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}

	$sync_status = trim(susExec("getSyncStatus")) == "true" ? true : false;

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

	<div class="panel-body">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-4 col-md-2 dashboard-item">
				<a href="susSettings.php">
					<p><img src="images/settings/SoftwareUpdateServer.png" alt="Software Update"></p>
				</a>
			</div>
			<!-- /Column -->
<?php if ($conf->getSetting("sus") == "enabled") { ?>
			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Last Sync</strong></h5>
					<span class="text-muted"><?php echo $last_sync; ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Sync Status</strong></h5>
					<span class="text-muted"><?php echo ($sync_status ? "Running" : "Not Running"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<div class="clearfix visible-xs-block visible-sm-block"></div>

			<!-- Column -->
			<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Disk Usage</strong></h5>
					<span class="text-muted"><?php echo suExec("getsussize"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Number of Branches</strong></h5>
					<span class="text-muted"><?php echo suExec("numofbranches"); ?></span>
				</div>
			</div>
			<!-- /Column -->
<?php } else { ?>
			<!-- Column -->
			<div class="col-xs-8 col-md-10">
				<div class="bs-callout bs-callout-default">
					<h5><strong>Configure the Software Update Server</strong> <small>to manage and provide Apple Software Updates for macOS clients.</small></h5>
					<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='susSettings.php'">Software Update Settings</button>
				</div>
			</div>
			<!-- /Column -->
<?php } ?>
		</div>
		<!-- /Row -->
	</div>
</div>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>NetBoot Server</strong>
	</div>
	<div class="panel-body">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>DHCP Status</h4>
					<span><?php if (getNetBootStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>NetBoot Image Size</h4>
					<span><?php echo suExec("netbootusage"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Active SMB Connections</h4>
					<span><?php echo suExec("smbconns"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<div class="clearfix visible-xs-block visible-sm-block"></div>

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Active AFP Connections</h4>
					<span><?php echo suExec("afpconns"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Shadow File Usage</h4>
					<span><?php echo suExec("shadowusage");?></span>
				</div>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>
</div>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>LDAP Proxy Server</strong>
	</div>
	<div class="panel-body">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>LDAP Proxy Status</h4>
					<span><?php if (getLDAPProxyStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
				</div>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>
</div>

<?php include "inc/footer.php";?>