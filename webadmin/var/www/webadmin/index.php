<?php
// Re-direct to HTTPS if connecting via HTTP
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") {
	header("Location: https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
}

session_start();
include "inc/config.php";
include "inc/functions.php";
$amAuthURL = "dashboard.php";
if (isset($_SESSION['isAuthUser'])) {
	header('Location: '. $amAuthURL);
}

$isAuth = FALSE;

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
	$username = $_POST['username'];
	$_SESSION['username'] = $username;

	if ($_POST['loginwith'] == 'suslogin') {

		$type = "suslogin";

		// encrypted password
		$password = hash("sha256", $_POST['password']);

		if (($username != "") && ($password != "")) {
			if ($username == $admin_username && $password == $admin_password) {
				$isAuth = TRUE;
			} else {
				$loginerror = "Invalid Username or Password.";
			}
		}
	}

	if ($_POST['loginwith'] == 'adlogin') {

		define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
		$type="adlogin";

		// password
		$password = $_POST['password'];

		// ldap server url
		$ldap_url = $conf->getSetting("ldapserver");

		// active directory DN (base location of ldap search)
		$ldap_dn = $conf->getSetting("ldapbase");

		// active directory admin group names
		$admin_grps = $conf->getAdmins();

		// domain, for purposes of constructing $username
		$domain = '@'.$conf->getSetting("ldapdomain");

		// connect to active directory
		$ldap = ldap_connect($ldap_url);

		// configure ldap params
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

		// verify user and password
		if($bind = @ldap_bind($ldap, $username.$domain, $password)) {
			// valid
			// check presence in groups
			$filter = "(sAMAccountName=".$username.")";
			$attr = array("memberof");
			$result = ldap_search($ldap, $ldap_dn, $filter, $attr);

			$entries = ldap_get_entries($ldap, $result);
			ldap_unbind($ldap);

			// check groups
			$access = 0;
			foreach ($entries[0]['memberof'] as $grps) {
				// check group membership
				foreach ($admin_grps as $key => $value) {
					// is admin, break loop
					if(strpos($grps, $value['cn'])) { $isAuth = TRUE; break 2; }
				}
			}
			if (!$isAuth) {
				$loginerror = "Access Denied for ".$username;
			}
		} else {
			if (ldap_get_option($handle, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
				// get error msg
				$loginerror = $extended_error;
			} else {
				// invalid user or password
				$loginerror = "Invalid Username or Password";
			}
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
} elseif ($conf->getSetting("webadmingui") == "Disabled") {
?>
<!DOCTYPE html>

<html>
	<head>
		<title>NetSUS Server</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta http-equiv="expires" content="0">
		<meta http-equiv="pragma" content="no-cache">
		<!-- Roboto Font CSS -->
		<link href="theme/roboto.font.css" rel='stylesheet' type='text/css'>
		<!-- Bootstrap CSS -->
		<link href="theme/bootstrap.css" rel="stylesheet" media="all">
		<!-- Project CSS -->
		<link rel="stylesheet" href="theme/styles.css" type="text/css">
		<style>
			body {
				background-color: #292929;
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
} elseif (trim(suExec("getshutdownstaus")) == "true") {
	$shutdowntype = trim(file_get_contents("/var/appliance/.shutdownMessage"));
	if (empty($shutdowntype)) {
		$shutdowntype = "Shutting Down";
	}
?>
<!DOCTYPE html>

<html>
	<head>
		<title>NetSUS Server</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php if ($shutdowntype == "Restarting") { ?>
		<meta http-equiv="refresh" content="120; url=/webadmin/index.php">
<?php } else { ?>
		<meta http-equiv="refresh" content="60; url=https://www.jamf.com/jamf-nation/third-party-products/180/">
<?php } ?>
		<meta http-equiv="expires" content="0">
		<meta http-equiv="pragma" content="no-cache">
		<!-- Roboto Font CSS -->
		<link href="theme/roboto.font.css" rel='stylesheet' type='text/css'>
		<!-- Bootstrap CSS -->
		<link href="theme/bootstrap.css" rel="stylesheet" media="all">
		<!-- Project CSS -->
		<link rel="stylesheet" href="theme/styles.css" type="text/css">
		<style>
			body {
				background-color: #292929;
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
					<div class="text-center text-muted" style="padding: 4px 0px;">The Server is <?php echo $shutdowntype; ?>...</div>
				</div>
			</div>
		</div>
	</body>
</html>
<?php
} else {

$ldap_url = $conf->getSetting("ldapserver");
$ldap_domain = $conf->getSetting("ldapdomain");
$ldap_base = $conf->getSetting("ldapbase");
$ldap_groups = $conf->getAdmins();
$ldap_enabled = $ldap_url != "" && $ldap_domain != "" && $ldap_base != "" && sizeof($ldap_groups) > 0;
if (!isset($type)) {
	$type = ($ldap_enabled ? "adlogin" : "suslogin");
}
?>
<!DOCTYPE html>

<html>
	<head>
		<title>NetSUS Server Login</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta http-equiv="expires" content="0">
		<meta http-equiv="pragma" content="no-cache">
		<!-- Roboto Font CSS -->
		<link href="theme/roboto.font.css" rel='stylesheet' type='text/css'>
		<!-- Bootstrap CSS -->
		<link href="theme/bootstrap.css" rel="stylesheet" media="all">
		<!-- Project CSS -->
		<link rel="stylesheet" href="theme/styles.css" type="text/css">
		<style>
			body {
				background-color: #292929;
			}
		</style>
		<script type="text/javascript" src="scripts/jquery/jquery-2.2.4.js"></script>
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
								<input type="radio" id="suslogin" name="loginwith" value="suslogin" <?php echo ($type == "suslogin" ? "checked" : ""); ?>>
								<label for="suslogin">Built-In Account</label>
							</div>
							<div class="radio radio-inline radio-primary">
								<input type="radio" id="adlogin" name="loginwith" value="adlogin" <?php echo ($type == "adlogin" ? "checked" : ""); ?> <?php echo ($ldap_enabled ? "" : "disabled"); ?>>
								<label for="adlogin">Active Directory</label>
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