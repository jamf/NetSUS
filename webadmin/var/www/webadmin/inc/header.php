<?php
//Read in whether or not the user is an admin - this is populated at the index.php page using the allowedAdminUsers variable
if (isset($_SESSION['isAdmin'])) {
	$isAdmin = $_SESSION['isAdmin'];
}else{
	$isAdmin = false;
}

// to find current page
$currentFile = $_SERVER["PHP_SELF"];
$parts = Explode('/',$currentFile);
$pageURI = $parts[count($parts) -1];

?>
<!DOCTYPE html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title><?php echo (isset($title) ? $title : "NetBoot/SUS/LDAP Proxy Server") ?></title>
	<!-- Roboto Font CSS -->
	<link href="theme/roboto.font.css" rel='stylesheet' type='text/css'>
	<!-- Bootstrap CSS -->
	<link href="theme/bootstrap.css" rel="stylesheet" media="all">
	<!-- Project CSS -->
	<link rel="stylesheet" href="theme/styles.css" type="text/css">
	<!-- JQuery -->
	<script type="text/javascript" src="scripts/jquery/jquery-2.2.0.js"></script>
	<!-- Bootstrap JavaScript -->
	<script type="text/javascript" src="scripts/bootstrap.min.js"></script>
	<!-- <script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script> -->
	<script type="text/javascript" src="scripts/adminNetworkSettings.js"></script>
	<script type="text/javascript" src="scripts/adminServicesSettings.js"></script>
	<script type="text/javascript" src="scripts/overlibmws.js"></script>
	<script type="text/javascript" src="scripts/infoPanel.js"></script>
	<script type="text/javascript" src="scripts/ajax.js"></script>
	<?php echo (isset($jsscriptfiles) ? $jsscriptfiles : "")?>
</head>

<?php if (!isset($title)) { $title = "NetBoot/SUS/LDAP Proxy Server Management"; } ?>
<body <?php echo (isset($onloadjs) ? " onload=\"$onloadjs\"" : "")?>>
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
                <div id="version-number-text" class="navbar-text">v4.5</div>
                <div class="navbar-user">
                    <div class="btn-group">
                        <button type="button" class="navbar-btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-user"></span></button>
                        <ul class="dropdown-menu dropdown-menu-right dropdown-menu-user">
                            <li><a href="logout.php">Log Out <?php echo getCurrentWebUser(); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="navbar-info">
                    <button type="button" class="navbar-btn-icon" onClick="document.location.href='about.php'"><span class="glyphicon glyphicon-info-sign"></span></button>
                </div>
                <div class="navbar-gear">
                    <div class="btn-group">
                        <button type="button" class="navbar-btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-cog"></span></button>
                        <ul class="dropdown-menu dropdown-menu-right dropdown-menu-user">
                            <li><a href="restart.php">Restart</a></li>
                            <li><a href="shutdown.php">Shut Down</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="disablegui.php">Disable GUI</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="settings.php">Settings</a></li>
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
                <li class="<?php if ($pageURI == "SUS.php") { echo "active"; } ?>"><a href="SUS.php"><span class="glyphicon glyphicon-hdd marg-right"></span>Software Update Server</a></li>
                <li class="<?php if ($pageURI == "netBoot.php") { echo "active"; } ?>"><a href="netBoot.php"><span class="glyphicon glyphicon-import marg-right"></span>NetBoot Server</a></li>
                <li class="<?php if ($pageURI == "LDAPProxy.php") { echo "active"; } ?>"><a href="LDAPProxy.php"><span class="glyphicon glyphicon-transfer marg-right"></span>LDAP Proxy</a></li>
                <!-- end Sidebar Menu Items -->
            </ul>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div class="container-fluid" id="page-content-wrapper">





