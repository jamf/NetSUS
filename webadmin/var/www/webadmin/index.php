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
	define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
	if (($username != "") && ($password != "")) {
		if ($username == $admin_username && $password == $admin_password) {
			$isAuth=TRUE;
		}
		$ldapconn = ldap_connect($conf->getSetting("ldapserver"));
		if ($ldapconn) {
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
			if (ldap_bind($ldapconn, $username/*."@".$conf->getSetting("ldapdomain")*/, $_POST['password'])) {
				$basedn = /*"DC=".implode(",DC=", explode(".", */$conf->getSetting("ldapdomain")/*))*/;
				$userdn = getDN($ldapconn, $username, $basedn);
				foreach ($conf->getAdmins() as $key => $value) {
					$groupdn = getDN($ldapconn, $value['cn'], $basedn);
					if (checkLDAPGroupEx($ldapconn, $userdn, $groupdn)) {
						$isAuth=TRUE;
					}
				}
				ldap_unbind($ldapconn);
			}
			else if (ldap_get_option($ldapconn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
				$ldaperror = "Error Binding to LDAP: $extended_error";
			}
			else {
				$ldaperror = "LDAP: Invalid Credentials";
			}
		}
		else {
			$ldaperror = "LDAP: uanble to connect";
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
}
elseif ($conf->getSetting("webadmingui") == "Disabled") {
?>

<!DOCTYPE html>

<html>
	<head> 
	    <title>NetBoot/SUS/LDAP Proxy Server</title>
	    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	    <meta http-equiv="expires" content="0">
	    <meta http-equiv="pragma" content="no-cache">
		<link href="theme/bootstrap.css" rel="stylesheet" media="all">
		<link rel="stylesheet" href="theme/styles.css" type="text/css">
	</head> 

	<body> 

		<div class="alert alert-warning">WebAdmin GUI is disabled</div>

	</body>
</html>
<?php
} else {
?>
<!DOCTYPE html>

<html>
	<head>
		<title>NetBoot/SUS/LDAP Proxy Server Login</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta http-equiv="expires" content="0">
		<meta http-equiv="pragma" content="no-cache">
		<link href="theme/bootstrap.css" rel="stylesheet" media="all">
		<link rel="stylesheet" href="theme/styles.css" type="text/css">
		<script type="text/javascript" src="scripts/jquery/jquery-2.2.0.js"></script>
		<script type="text/javascript" src="scripts/bootstrap.min.js"></script>
	</head> 

	<body>

			<div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
				<div class="panel panel-default panel-login">

					<div class="panel-heading">
						<div class="panel-title text-center"><img src="images/NSUS-logo.png" height="65"></div>
					</div>

					<div class="panel-body">

							<div class="container">
								<?php if(isset($ldaperror)) { ?> <div class="alert alert-danger"> <?php echo $ldaperror; ?> </div> <?php } ?>
								<div class="alert alert-danger">
									<?php
									$ldapconn = ldap_connect("ldap://ad1.ad.jamfsw.corp:389/");
									if (ldap_bind($ldapconn, "Administrator@CN=Administrator,CN=Users,DC=ad,DC=jamfsw,DC=corp", "j@mf1234")) {
										echo "Bind Success";
									} else {
										echo "Bind Fail";
									}
									?>
								</div>

								<form name="loginForm" class="form-horizontal" id="login-form" method="post" action="">
									<div class="username"><input id="username" type="text" class="form-control" name="username" value="" placeholder="Username"></div>
									<div class="password"><input id="password" type="password" class="form-control" name="password" placeholder="Password"></div>
									<div><input type="submit" class="btn btn-primary pull-right" name="submit" value="Log In"></div>
								</form>
							</div>

					</div>

				</div>
			</div>

	</body>
</html>
<?php
}
?>