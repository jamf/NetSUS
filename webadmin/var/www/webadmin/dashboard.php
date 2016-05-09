<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$currentIP = trim(getCurrentIP());

$title = "Dashboard";

include "inc/header.php";
?>
<?php

if ($conf->needsToChangeAnyPasses())
{
?>
<div class="alert alert-warning" role="alert"><strong>WARNING: </strong> Credentials have not been changed for the following accounts:<br>
	<ul>
		<?php
		if ($conf->needsToChangePass("webaccount"))
		{
			echo "<li>Web Application</li>\n";
		}
		if ($conf->needsToChangePass("shellaccount"))
		{
			echo "<li>Shell</li>\n";
		}
		if ($conf->needsToChangePass("afpaccount"))
		{
			echo "<li>AFP</li>\n";
		}
		if ($conf->needsToChangePass("smbaccount"))
		{
			echo "<li>SMB</li>\n";
		}
		?>
	</ul>
</div>
<?php
}
?>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>Software Update Server</strong>
	</div>

	<div class="panel-body">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-6 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Last Sync</h4>
					<span><?php if (trim(suExec("lastsussync")) != "") { print suExec("lastsussync"); } else { echo "Never"; } ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-6 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Sync Status</h4>
					<span><?php if (getSyncStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
				</div>
			</div>
			<!-- /Column -->

			<div class="clearfix visible-xs-block visible-sm-block"></div>

			<!-- Column -->
			<div class="col-xs-6 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Disk Usage</h4>
					<span><?php echo suExec("getsussize"); ?></span>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-6 col-md-2">
				<div class="bs-callout bs-callout-default">
					<h4>Number of Branches</h4>
					<span><?php echo suExec("numofbranches"); ?></span>
				</div>
			</div>
			<!-- /Column -->
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