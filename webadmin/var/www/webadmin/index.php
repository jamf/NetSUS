<?php
session_start();
include "inc/config.php";
include "inc/functions.php";
$amAuthURL="dashboard.php";
if (isset($_SESSION['isAuthUser'])) {
	header('Location: '. $amAuthURL);
}

$isAuth=FALSE;

$type="suslogin";

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
	$username = $_POST['username'];
	$password = hash("sha256", $_POST['password']);
	$_SESSION['username'] = $username;

	if ($_POST['loginwith'] == 'suslogin') {
		if (($username != "") && ($password != "")) {
			if ($username == $admin_username && $password == $admin_password) {
				$isAuth = TRUE;
			} else {
				$loginerror = "Invalid Credentials";
			}
		}
	}

	if ($_POST['loginwith'] == 'adlogin') {

		define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
		$type="adlogin";

		$ldapconn = ldap_connect($conf->getSetting("ldapserver"));
		if ($ldapconn) {
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
			if (ldap_bind($ldapconn, $username . "@" . $conf->getSetting("ldapdomain"), $_POST['password'])) {
				$basedn = "DC=" . implode(",DC=", explode(".", $conf->getSetting("ldapdomain")));
				$userdn = getDN($ldapconn, $username, $basedn);
				foreach ($conf->getAdmins() as $key => $value) {
					$groupdn = getDN($ldapconn, $value['cn'], $basedn);
					if (checkLDAPGroupEx($ldapconn, $userdn, $groupdn)) {
						$isAuth = TRUE;
					}
				}
				ldap_unbind($ldapconn);
			} else if (ldap_get_option($ldapconn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
				$loginerror = "Error on LDAP Bind - ".$extended_error;
			} else {
				$loginerror = "Invalid Credentials";
			}
		} else {
			$loginerror = "Unable to Connect to LDAP Server";
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
		<style>
			body {
				background-color: #292929;
			}
			.login-wrapper {
				float: right;
				position: relative;
				left: -50%;
				text-align: left;
			}
			.login-wrapper > .login-panel {
				position: relative;
				left: 50%;
				width: 300px;
				margin-top: 70px;
				-webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
				-moz-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
				box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
			}
		</style>
	</head>

	<body>
		<div class="login-wrapper">
			<div class="login-panel panel panel-default">
				<div class="panel-heading" style="background: #ffffff;">
					<div class="panel-title text-center"><img src="images/NSUS-color.svg" height="42"></div>
				</div>
				<div class="panel-body">
					<div class="text-center text-muted" style="padding: 4px 0px;">WebAdmin GUI is disabled.</div>
				</div>
			</div>
		</div>
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
		<style>
			body {
				background-color: #292929;
			}
			.login-wrapper {
				float: right;
				position: relative;
				left: -50%;
				text-align: left;
			}
			.login-wrapper > .login-panel {
				position: relative;
				left: 50%;
				width: 300px;
				margin-top: 70px;
				-webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
				-moz-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
				box-shadow: 0 3px 9px rgba(0, 0, 0, .5);
			}
		</style>
		<script type="text/javascript" src="scripts/jquery/jquery-2.2.0.js"></script>
		<script type="text/javascript" src="scripts/bootstrap.min.js"></script>
	</head> 

	<body>

		<div class="login-wrapper">
			<div class="login-panel panel panel-default">

				<div class="panel-heading" style="background: #ffffff;">
					<div class="panel-title text-center"><img src="images/NSUS-color.svg" height="42"></div>
				</div>

				<div class="panel-body">
					<?php if(isset($loginerror)) {
						echo "<div class=\"text-danger text-center\" style=\"padding-bottom: 8px\"><span class=\"glyphicon glyphicon-exclamation-sign\"></span> ".$loginerror.".</div>";
					} ?>
					<form name="loginForm" class="form-horizontal" id="login-form" method="post">
						<div class="text-center">
							<div class="radio radio-inline radio-primary">
								<input type="radio" id="suslogin" name="loginwith" value="suslogin" <?php echo ($type=="suslogin"?" checked=\"checked\"":"") ?>>
								<label for="suslogin">Local Account</label>
							</div>
							<div class="radio radio-inline radio-primary">
								<input type="radio" id="adlogin" name="loginwith" value="adlogin" <?php echo ($type=="adlogin"?" checked=\"checked\"":"") ?>>
								<label for="adlogin">LDAP Account</label>
							</div>
						</div>
						<div class="username">
							<input type="text" name="username" id="username" class="form-control input-sm" placeholder="[Username]" />
						</div>
						<div class="password">
							<input type="password" name="password" id="password" class="form-control input-sm" placeholder="[Password]" />
						</div>
						<div>
							<button type="submit" name="submit" id="submit" class="btn btn-primary btn-sm pull-right">Log In</button>
						</div>
					</form>
				</div>

			</div>
		</div>

	</body>
</html>
<?php
}
?>