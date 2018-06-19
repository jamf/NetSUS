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
$currentUser = getCurrentWebUser();

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
	<script type="text/javascript" src="scripts/adminNetworkSettings.js"></script>
	<script type="text/javascript" src="scripts/adminServicesSettings.js"></script>
	<script type="text/javascript" src="scripts/ajax.js"></script>
	<?php echo (isset($jsscriptfiles) ? $jsscriptfiles : "")?>
</head>

<?php if (!isset($title)) { $title = "NetBoot/SUS/LDAP Proxy Server Management"; } ?>
<body <?php echo (isset($onloadjs) ? " onload=\"$onloadjs\"" : "")?>>
    <!-- Restart Modal -->
    <div class="modal fade" id="restart-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="restart-title" class="modal-title">Restart</h3>
                </div>
                <div class="modal-body" id="restart-body">

                </div>
                <div class="modal-footer">
                    <button id="restart-cancel" type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
                    <button id="restart-confirm" type="button" class="btn btn-primary btn-sm pull-right" onClick="restartServer();">Restart</button>
                </div>
            </div>
        </div>
    </div>
	<!-- /#modal -->

    <!-- Shut Down Modal -->
    <div class="modal fade" id="shutdown-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="shutdown-title" class="modal-title">Shut Down</h3>
                </div>
                <div class="modal-body" id="shutdown-body">

                </div>
                <div class="modal-footer">
                    <button id="shutdown-cancel" type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
                    <button id="shutdown-confirm" type="button" class="btn btn-primary btn-sm pull-right" onClick="shutdownServer();">Shut Down</button>
                </div>
            </div>
        </div>
    </div>
	<!-- /#modal -->

    <!-- Disable GUI Modal -->
    <div class="modal fade" id="disablegui-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="disablegui-title" class="modal-title">Disable GUI</h3>
                </div>
                <div class="modal-body" id="disablegui-body">

                </div>
                <div class="modal-footer">
                    <button id="disablegui-cancel" type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-left" >Cancel</button>
                    <button id="disablegui-confirm" type="button" class="btn btn-primary btn-sm pull-right" onClick="disableGUI();">Disable</button>
                </div>
            </div>
        </div>
    </div>
	<!-- /#modal -->

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
                            <?php if ($currentUser == $conf->getSetting("webadminuser")) { ?>
                            <li><a href="accounts.php" onClick="localStorage.setItem('activeTab', 'webadmin-tab');">Change Password</a></li>
                            <li role="separator" class="divider"></li>
                            <?php } ?>
                            <li><a href="logout.php">Logout <?php echo $currentUser ?></a></li>
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
                            <li><a data-toggle="modal" href="#restart-modal" data-backdrop="static" onClick="restartModal();">Restart</a></li>
                            <li><a data-toggle="modal" href="#shutdown-modal" data-backdrop="static" onClick="shutdownModal();">Shut Down</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a data-toggle="modal" href="#disablegui-modal" data-backdrop="static" onClick="disableGUIModal();">Disable GUI</a></li>
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





