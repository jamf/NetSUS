<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Settings";

include "inc/header.php";

?>


<ul id="settings-box">

	<li class="settings-row">

		<h5 class="green">NetBoot/SUS Server</h5>

		<ul>

			<li class="settings-item">
				<a href="accounts.php">
					<img src="images/settings/accounts.png" alt="SMB">
					<span>Accounts</span>
				</a>
			</li>

			<li class="settings-item">
				<a href="networkSettings.php">
					<img src="images/settings/networkSegments.png" alt="Network Settings">
					<span>Network</span>
				</a>
			</li>

			<li class="settings-item">
				<a href="dateTime.php">
					<img src="images/settings/computerCheckIn.png" alt="Date/Time Settings">
					<span>Date/Time</span>
				</a>
			</li>

                        <li class="settings-item">
                                <a href="ldap.php">
                                        <img src="images/settings/ldap.png" alt="LDAP Configuration">
                                        <span>LDAP</span>
                                </a>
                        </li>

		</ul>

	</li>

	<li class="settings-row">

		<h5 class="green">Shares</h5>

		<ul>

			<li class="settings-item">
				<a href="AFP.php">
					<img src="images/settings/categories.png" alt="AFP">
					<span>AFP</span>
				</a>
			</li>

			<li class="settings-item">
				<a href="SMB.php">
					<img src="images/settings/categories.png" alt="SMB">
					<span>SMB</span>
				</a>
			</li>

		</ul>

	</li>


</ul>


<?php include "inc/footer.php"; ?>



