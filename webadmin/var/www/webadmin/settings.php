<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Settings";

include "inc/header.php";

?>

<h3>NetBoot/SUS/LDAP Proxy Server</h3>

<div class="row">
	<!-- Column -->
	<div class="col-xs-3 col-sm-2 settings-item">
		<a href="accounts.php">
			<p><img src="images/settings/accounts.png" alt="SMB"></p>
			<p>Accounts</p>
		</a>
	</div>
	<!-- /Column -->
	<!-- Column -->
	<div class="col-xs-3 col-sm-2 settings-item">
		<a href="networkSettings.php">
			<p><img src="images/settings/networkSegments.png" alt="Network Settings"></p>
			<p>Network</p>
		</a>
	</div>
	<!-- /Column -->
	<!-- Column -->
	<div class="col-xs-3 col-sm-2 settings-item">
		<a href="dateTime.php">
			<p><img src="images/settings/computerCheckIn.png" alt="Date/Time Settings"></p>
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
</div>
<!-- /Row -->

<h3>Shares</h3>

<div class="row">
	<!-- Column -->
	<div class="col-xs-3 col-sm-2 settings-item">
		<a href="AFP.php">
			<p><img src="images/settings/categories.png" alt="AFP"></p>
			<p>AFP</p>
		</a>
	</div>
	<!-- /Column -->
	<!-- Column -->
	<div class="col-xs-3 col-sm-2 settings-item">
		<a href="SMB.php">
			<p><img src="images/settings/categories.png" alt="SMB"></p>
			<p>SMB</p>
		</a>
	</div>
	<!-- /Column -->
</div>
<!-- /Row -->


<?php include "inc/footer.php"; ?>



