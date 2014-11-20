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

	if (($username != "") && ($password != "")) {
		if ($username == $admin_username && $password == $admin_password) {
			$isAuth=TRUE;
		}
			$ldapconn = ldap_connect($conf->getSetting("ldapserver"));
			if ($ldapconn)
			{
				ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

				if (ldap_bind($ldapconn, $username."@".$conf->getSetting("ldapdomain"), $_POST['password']))
				{
					$basedn = "DC=".implode(",DC=", explode(".", $conf->getSetting("ldapdomain")));
					$userdn = getDN($ldapconn, $username, $basedn);
					foreach ($conf->getAdmins() as $key => $value)
					{
						$groupdn = getDN($ldapconn, $value['cn'], $basedn);
						if (checkLDAPGroupEx($ldapconn, $userdn, $groupdn))
						{
							$isAuth=TRUE;
						}
					}
					ldap_unbind($ldapconn);
				}
				else
				{
					echo "LDAP: invalid credentials";
				}
			}
			else
			{
				echo "LDAP: uanble to connect";
			}
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
