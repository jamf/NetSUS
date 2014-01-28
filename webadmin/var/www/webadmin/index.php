<?php
session_start();

include "inc/config.php";
include "inc/functions.php";


$amAuthURL="dashboard.php";
if (isset($_SESSION['isAuthUser'])) {
	header('Location: '. $amAuthURL);
}

$isAuth=FALSE;

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
	$username=$_POST['username'];
	$password=hash("sha256",$_POST['password']);

	$_SESSION['username'] = $username;

	if (($username != "") && ($password != "")) {
		if ($username == $admin_username && $password == $admin_password) {
			$isAuth=TRUE;
		}

		// LDAP login		
                $authorizedUsers = getLDAPAdmins();

                $ldapconn = ldap_connect($conf->getSetting("ldapserver")) or die("Could not connect to LDAP server.");

                ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);  //Set the LDAP Protocol used by your AD service
                ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);         //This was necessary for my AD to do anything

                if ($ldapconn) {
                        if (isset($authorizedUsers[$username])) {
                                if (ldap_bind($ldapconn, $authorizedUsers[$username], $_POST['password']))
                                        $isAuth=TRUE;
                        }
                }
                ldap_close($ldapconn);
	}
}

if ($isAuth) {
	$_SESSION['isAuthUser'] = 1;
	
	$sURL = "dashboard.php";

	if ($debug) {
		print $sURL . "<br>";
		print $_SESSION['isAuth'];
	}

	if (!($debug)) {
		header('Location: '. $sURL);
	}
} else {

?>
<!DOCTYPE html>

<html>
	<head> 
	    <title>NetBoot/SUS Server Login</title>
	    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	    <meta http-equiv="expires" content="0">
	    <meta http-equiv="pragma" content="no-cache"> 
		<link rel="stylesheet" href="theme/reset.css" type="text/css">
		<link rel="stylesheet" href="theme/styles.css" type="text/css">
	</head> 

	<body> 

		<div id="login-wrapper">

			<form method="post" name="loginForm" action="">

				<div id="login-panel">

					<span class="label">Username</span>
					<input type="text" name="username" id="username" class="input" value="">

					<span class="label">Password</span>
					<input type="password" name="password" id="password" class="input">

					<input type="submit" class="button" name="submit" value="Log In"> 

				</div>

			</form>

		</div>
		<script> 
		<!--
		document.loginForm.username.focus();
		// -->
		</script>

	
	</body>
</html>
<?php
}
?>
