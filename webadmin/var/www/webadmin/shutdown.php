<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Shut Down";

include "inc/header.php";

if (isset($_POST['confirm']))
{
	echo '<meta http-equiv="refresh" content="15;url=https://jamfnation.jamfsoftware.com/viewProduct.html?id=180">';
	echo '<div class="errorMessage">NOTICE: Shutting down the NetBoot/SUS/LDAP Proxy Server.</div>';
}

?>

<h2>Shut Down</h2>

<div id="form-wrapper">

	<form action="shutdown.php" method="POST" name="ShutDown" id="ShutDown" >

		<div id="form-inside">

			<span class="label">Are you sure you want to shut down the NetBoot/SUS/LDAP Proxy Server?</span>
			<?php
			$afpconns = trim(suExec("afpconns"));
			$smbconns = trim(suExec("smbconns"));
			if (($afpconns > 0) || ($smbconns > 0))
			{
				echo '<span class="description">There are '.($afpconns + $smbconns).' users connected to this server.</span>';
				echo '<span class="description">If you shut down they will be disconnected.</span>';
			}
			?>
			<span class="description">The NetBoot/SUS/LDAP Proxy Server will need to be restarted manually.</span>
			<br>

			<input type="submit" id="confirm" name="confirm" class="insideActionButton" value="Shut Down" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='<?php echo $_SERVER['HTTP_REFERER']; ?>'" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

			</div>

		</div>

	</form> <!-- end form ShutDown -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>

<?php

if (isset($_POST['confirm']))
{
	// Unset all of the session variables.
	$_SESSION = array();

	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies"))
	{
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}

	// Finally, destroy the session.
	session_destroy();

	suExec("shutdown");
}

?>