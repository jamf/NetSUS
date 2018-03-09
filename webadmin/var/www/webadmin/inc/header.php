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
    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a id="version-number-text" class="navbar-brand text-muted">NetSUS 4.2.3</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo getCurrentWebUser(); ?> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="restart.php">Restart</a></li>
                            <li><a href="shutdown.php">Shut Down</a></li>
                            <li class="divider"></li>
                            <li><a href="disablegui.php">Disable GUI</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php">Log Out</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="hidden-lg hidden-md hidden-sm navbar-inverse">
                    <ul class="nav navbar-nav navbar-inverse">
                        <li class="<?php if ($pageURI == "dashboard.php") { echo "active"; } ?>"><a href="dashboard.php"><span class="glyphicon glyphicon-dashboard marg-right"></span>Dashboard</a></li>
                        <li class="<?php if ($pageURI == "SUS.php") { echo "active"; } ?>"><a href="SUS.php"><span class="glyphicon glyphicon-hdd marg-right"></span>Software Update Server</a></li>
                        <li class="<?php if ($pageURI == "netBoot.php") { echo "active"; } ?>"><a href="netBoot.php"><span class="glyphicon glyphicon-import marg-right"></span>NetBoot Server</a></li>
                        <li class="<?php if ($pageURI == "LDAPProxy.php") { echo "active"; } ?>"><a href="LDAPProxy.php"><span class="glyphicon glyphicon-transfer marg-right"></span>LDAP Proxy</a></li>
                        <li class="<?php if ($pageURI == "settings.php") { echo "active"; } ?>"><a href="settings.php"><span class="glyphicon glyphicon-cog marg-right"></span>Settings</a></li>
                        <li class="<?php if ($pageURI == "about.php") { echo "active"; } ?>"><a href="about.php"><span class="glyphicon glyphicon-info-sign marg-right"></span>About</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebar-wrapper" class="nav navbar-default">
        <div id="logo-wrapper">
            <a href="dashboard.php"><img src="images/NSUS-logo.png"></a>
        </div>
        <div id="navbar">
            <ul class="nav sidebar-nav">
                <li class="<?php if ($pageURI == "dashboard.php") { echo "active"; } ?>"><a href="dashboard.php"><span class="glyphicon glyphicon-dashboard marg-right"></span>Dashboard</a></li>
                <li class="<?php if ($pageURI == "SUS.php") { echo "active"; } ?>"><a href="SUS.php"><span class="glyphicon glyphicon-hdd marg-right"></span>Software Update Server</a></li>
                <li class="<?php if ($pageURI == "netBoot.php") { echo "active"; } ?>"><a href="netBoot.php"><span class="glyphicon glyphicon-import marg-right"></span>NetBoot Server</a></li>
                <li class="<?php if ($pageURI == "LDAPProxy.php") { echo "active"; } ?>"><a href="LDAPProxy.php"><span class="glyphicon glyphicon-transfer marg-right"></span>LDAP Proxy</a></li>
                <li class="<?php if ($pageURI == "settings.php") { echo "active"; } ?>"><a href="settings.php"><span class="glyphicon glyphicon-cog marg-right"></span>Settings</a></li>
                <li class="<?php if ($pageURI == "about.php") { echo "active"; } ?>"><a href="about.php"><span class="glyphicon glyphicon-info-sign marg-right"></span>About</a></li>
            </ul>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div class="container-fluid" id="page-content-wrapper">





