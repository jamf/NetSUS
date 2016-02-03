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

		<div class="alert alert-info">
			<div class="row vertical-divider">
				<div class="col-xs-3">
					<strong>Last Sync:</strong>
				</div>
				<div class="col-xs-3">
					<strong>Sync Status:</strong>
				</div>
				<div class="col-xs-3">
					<strong>Disk Usage:</strong>
				</div>
				<div class="col-xs-3">
					<strong>Number of Branches:</strong>
				</div>
			</div>
			<br>
			<div class="row vertical-divider">

				<div class="col-xs-3 ">
					<span><?php if (trim(suExec("lastsussync")) != "") { print suExec("lastsussync"); } else { echo "Never"; } ?></span>
				</div>
				<div class="col-xs-3">
					<span><?php if (getSyncStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
				</div>
				<div class="col-xs-3">
					<span><?php echo suExec("getsussize"); ?></span>
				</div>
				<div class="col-xs-3">
					<span><?php echo suExec("numofbranches"); ?></span>
				</div>


			</div>

		</div>

	<div id="netboot-server">

		<h3>NetBoot Server</h3>

		<div class="container">

			<ul>

				<li>
					<span>DHCP Status:</span>
					<br>
					<br>
					<br>
					<span><?php if (getNetBootStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
				</li>

				<li>
					<span>Total NetBoot Image Size:</span>
					<br>
					<br>
					<span><?php echo suExec("netbootusage"); ?></span>
				</li>

				<li>
					<span>Number of Active SMB Connections:</span>
					<br>
					<br>
					<span><?php echo suExec("smbconns"); ?></span>
				</li>

				<li>
					<span>Number of Active AFP Connections:</span>
					<br>
					<br>
					<span><?php echo suExec("afpconns"); ?></span>
				</li>

				<li>
					<span>Shadow File Usage:</span>
					<br>
					<br>
					<span><?php echo suExec("shadowusage");?></span>
				</li>

			</ul>

		</div>
		</div>
		
		<div id="netboot-server">

		<h3>LDAP Proxy Server</h3>

		<div class="container">

			<ul>

				<li>
					<span>LDAP Proxy Status:</span>
					<br>
					<br>
					<br>
					<span><?php if (getLDAPProxyStatus()) { echo "Running"; } else { echo "Not Running"; } ?></span>
				</li>

			</ul>

		</div>
		
	</div>

<?php include "inc/footer.php";?>