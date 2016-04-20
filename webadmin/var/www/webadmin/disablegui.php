<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Disable GUI";

include "inc/header.php";

if (isset($_POST['confirm']))
{
	echo '<meta http-equiv="refresh" content="10;url=index.php">';
	echo '<div class="alert alert-warning">NOTICE: GUI is disabled and you have been logged out.  File system access is required to enable WebAdmin.</div>';
	
		$conf->setSetting("webadmingui", "Disabled");

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

}

?>

<h2>Disable GUI</h2>

<div class="row">
	<div class="col-xs-12 col-sm-10 col-lg-8">

		<hr>

		<form action="disablegui.php" method="POST" name="disablegui" id="disablegui" >

			<br>

			<p>Are you sure you want to disable the web interface for the NetBoot/SUS/LDAP Proxy Server?</p>

			<input type="submit" id="confirm" name="confirm" class="btn btn-sm btn-primary" value="Disable" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>
			<br>
			<br>

		</form> <!-- end form Restart -->

		<hr>
		<br>

		<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='<?php echo $_SERVER['HTTP_REFERER']; ?>'" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

	</div>
</div>

<?php include "inc/footer.php"; ?>

