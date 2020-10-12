<?php
// Re-direct to HTTPS if connecting via HTTP
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") {
	header("Location: https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
}

//Read in whether or not the user is an admin - this is populated at the index.php page using the allowedAdminUsers variable
if (isset($_SESSION['isAdmin'])) {
	$isAdmin = $_SESSION['isAdmin'];
} else {
	$isAdmin = false;
}

// to find current page
$currentFile = $_SERVER['PHP_SELF'];
$parts = explode("/", $currentFile);
$pageURI = $parts[count($parts) - 1];

// to find current user
$currentUser = getCurrentWebUser();
?>
<!DOCTYPE html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta http-equiv="refresh" content="<?php print ini_get("session.gc_maxlifetime"); ?>; url=/webadmin/logout.php">
	<title><?php echo (isset($title) ? $title : "NetSUS"); ?></title>
	<!-- Roboto Font CSS -->
	<link href="theme/roboto.font.css" rel="stylesheet" type="text/css">
	<!-- Bootstrap CSS -->
	<link href="theme/bootstrap.css" rel="stylesheet" media="all">
	<!-- Project CSS -->
	<link href="theme/styles.css" rel="stylesheet" type="text/css">
	<!-- JQuery -->
	<script type="text/javascript" src="scripts/jquery/jquery-2.2.4.js"></script>
	<!-- Bootstrap JavaScript -->
	<script type="text/javascript" src="scripts/bootstrap.min.js"></script>
	<!-- Project JavaScript -->
	<script type="text/javascript" src="scripts/ajax.js"></script>
</head>

<?php if (!isset($title)) { $title = "NetSUS Management"; } ?>
<body>

	<!-- Fixed Top Navbar -->
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="container-fluid">
			<div class="navbar-header">
				<button class="navbar-toggle navbar-left" data-target="#menu-toggle" id="menu-toggle">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" style="padding-top: 8px;" href="dashboard.php"><img src="images/NSUS-logo.svg" height="34"></a>
				<div id="version-number-text" class="navbar-text">v5.0.2</div>
				<div class="navbar-user">
					<div class="btn-group">
						<button type="button" class="navbar-btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-user"></span></button>
						<ul class="dropdown-menu dropdown-menu-right dropdown-menu-navbar">
							<li><a data-toggle="modal" href="#disablegui-modal">Disable GUI</a></li>
							<li role="separator" class="divider"></li>
<?php if ($currentUser == $conf->getSetting("webadminuser")) { ?>
							<li><a href="accounts.php" onClick="localStorage.setItem('activeAcctsTab', '#webadmin-tab');">Change Password</a></li>
							<li role="separator" class="divider"></li>
<?php } ?>
							<li><a href="logout.php">Logout <span id="logoutuser"><?php echo $currentUser ?></span></a></li>
						</ul>
					</div>
				</div>
				<div class="navbar-flash">
					<button type="button" id="notify-button" class="navbar-btn-icon" data-toggle="modal" data-target="#notify-modal" disabled><span class="glyphicon glyphicon-flash"></span><span id="notify-badge" class="badge hidden"></span></button>
				</div>
				<div class="navbar-gear">
					<button type="button" id="settings" class="navbar-btn-icon" onClick="document.location.href='settings.php'"><span class="glyphicon glyphicon-cog"></span></button>
				</div>
				<div class="navbar-off">
					<div class="btn-group">
						<button type="button" class="navbar-btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-off"></span></button>
						<ul class="dropdown-menu dropdown-menu-right dropdown-menu-navbar">
							<li><a data-toggle="modal" href="#restart-modal">Restart</a></li>
							<li><a data-toggle="modal" href="#shutdown-modal">Shut Down</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</nav>

	<div id="wrapper">

		<!-- Sidebar -->
		<div id="sidebar-wrapper" class="nav navbar-default">
			<ul class="nav sidebar-nav">
				<li class=""><a href="#"><span class="glyphicon"></span></a></li>
				<!-- begin Sidebar Menu Items -->
				<li id="sharing" class="<?php echo ($conf->getSetting("sharing") == "enabled" ? ($pageURI == "sharing.php" ? "active" : "") : "hidden"); ?>"><a href="sharing.php"><span class="glyphicon glyphicon-folder-open marg-right"></span>File Sharing</a></li>
				<li id="sus" class="<?php echo ($conf->getSetting("sus") == "enabled" ? ($pageURI == "SUS.php" ? "active" : "") : "hidden"); ?>"><a href="SUS.php"><span class="netsus-icon icon-sus marg-right"></span>Software Update Server</a></li>
				<li id="netboot" class="<?php echo ($conf->getSetting("netboot") == "enabled" ? ($pageURI == "netBoot.php" ? "active" : "") : "hidden"); ?>"><a href="netBoot.php"><span class="netsus-icon icon-netboot marg-right"></span>NetBoot Server</a></li>
				<li id="ldapproxy" class="<?php echo ($conf->getSetting("ldapproxy") == "enabled" ? ($pageURI == "LDAPProxy.php" ? "active" : "") : "hidden"); ?>"><a href="LDAPProxy.php"><span class="glyphicon glyphicon-book marg-right"></span>LDAP Proxy</a></li>
				<!-- end Sidebar Menu Items -->
			</ul>
		</div>
		<!-- /#sidebar-wrapper -->

		<!-- Page Content -->
		<div class="container-fluid" id="page-content-wrapper">
