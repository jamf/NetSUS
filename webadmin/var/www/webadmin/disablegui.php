<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Disable GUI";

include "inc/header.php";

if (isset($_POST['confirm']))
{
	echo '<meta http-equiv="refresh" content="10;url=index.php">';
	echo '<div class="noticeMessage">NOTICE: GUI is disabled and you have been logged out.  File system access is required to enable WebAdmin.</div>';
	
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

<div id="form-wrapper">

	<form action="disablegui.php" method="POST" name="disablegui" id="disablegui" >

		<div id="form-inside">

			<span class="label">Are you sure you want to disable the web interface for the NetBoot/SUS/LDAP Proxy Server?</span>
			<br>

			<input type="submit" id="confirm" name="confirm" class="insideActionButton" value="Disable" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

		</div> <!-- end #form-inside -->

		<div id="form-buttons">

			<div id="read-buttons">

				<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='<?php echo $_SERVER['HTTP_REFERER']; ?>'" <?php if (isset($_POST['confirm'])) { echo "disabled"; } ?>>

			</div>

		</div>

	</form> <!-- end form Restart -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>

