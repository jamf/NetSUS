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
<div class="alert alert-warning alert-margin-top" role="alert"><strong>WARNING: </strong> Credentials have not been changed for the following accounts:<br>
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
	<h3>Software Update Server</h3>

	<div class="well">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-6 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Last Sync</strong>
					</div>
					<div class="panel-body">
						<span><?php if (trim(suExec("lastsussync")) != "") { print suExec("lastsussync"); } else { echo "Never"; } ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-6 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Sync Status</strong>
					</div>
					<div class="panel-body">
						<span><?php if (getSyncStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<div class="clearfix visible-xs-block visible-sm-block"></div>

			<!-- Column -->
			<div class="col-xs-6 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Disk Usage</strong>
					</div>
					<div class="panel-body">
						<span><?php echo suExec("getsussize"); ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-6 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Number of Branches</strong>
					</div>
					<div class="panel-body">
						<span><?php echo suExec("numofbranches"); ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>

	<h3>NetBoot Server</h3>

	<div class="well">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>DHCP Status</strong>
					</div>
					<div class="panel-body">
						<span><?php if (getNetBootStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>NetBoot Image Size</strong>
					</div>
					<div class="panel-body">
						<span><?php echo suExec("netbootusage"); ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Active SMB Connections</strong>
					</div>
					<div class="panel-body">
						<span><?php echo suExec("smbconns"); ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<div class="clearfix visible-xs-block visible-sm-block"></div>

			<!-- Column -->
			<div class="col-xs-4 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Active AFP Connections</strong>
					</div>
					<div class="panel-body">
						<span><?php echo suExec("afpconns"); ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->

			<!-- Column -->
			<div class="col-xs-4 col-md-2">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Shadow File Usage</strong>
					</div>
					<div class="panel-body">
						<span><?php echo suExec("shadowusage");?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>

	<h3>LDAP Proxy Server</h3>

	<div class="well">
		<div class="row">
			<!-- Column -->
			<div class="col-xs-4 col-md-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>LDAP Proxy Status</strong>
					</div>
					<div class="panel-body">
						<span><?php if (getLDAPProxyStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
					</div>
				</div>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>


<?php include "inc/footer.php";?>