<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Settings";

include "inc/header.php";

?>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>NetBoot/SUS/LDAP Proxy Server</strong>
	</div>
	<div class="panel-body">

		<div class="row">
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="accounts.php">
					<p><img src="images/settings/Account.png" alt="User Accounts"></p>
					<p>Accounts</p>
				</a>
			</div>
			<!-- /Column -->
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="networkSettings.php">
					<p><img src="images/settings/NetworkSegment.png" alt="Network Settings"></p>
					<p>Network</p>
				</a>
			</div>
			<!-- /Column -->
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="dateTime.php">
					<p><img src="images/settings/ClientCheckIn.png" alt="Date/Time Settings"></p>
					<p>Date/Time</p>
				</a>
			</div>
			<!-- /Column -->
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="certificates.php">
					<p><img src="images/settings/PKI.png" alt="Certificates"></p>
					<p>Certificates</p>
				</a>
			</div>
			<!-- /Column -->
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="logs.php">
					<p><img src="images/settings/ChangeManagement.png" alt="Logs"></p>
					<p>Logs</p>
				</a>
			</div>
			<!-- /Column -->
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="storage.php">
					<p><img src="images/settings/Storage.png" alt="Storage"></p>
					<p>Storage</p>
				</a>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>
</div>

<div class="panel panel-default panel-main">
	<div class="panel-heading">
		<strong>Shares</strong>
	</div>
	<div class="panel-body">

		<div class="row">
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="AFP.php">
					<p><img src="images/settings/Category.png" alt="AFP"></p>
					<p>AFP</p>
				</a>
			</div>
			<!-- /Column -->
			<!-- Column -->
			<div class="col-xs-3 col-sm-2 settings-item">
				<a href="SMB.php">
					<p><img src="images/settings/Category.png" alt="SMB"></p>
					<p>SMB</p>
				</a>
			</div>
			<!-- /Column -->
		</div>
		<!-- /Row -->
	</div>
</div>

<?php include "inc/footer.php"; ?>



