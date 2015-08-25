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
<link rel="stylesheet" href="theme/reset.css" type="text/css">
<link rel="stylesheet" href="theme/styles.css" type="text/css">

<script type="text/javascript" src="scripts/jquery/jquery.js"></script>
<script type="text/javascript" src="scripts/scripts.js"></script>
<!-- <script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script> -->
<script type="text/javascript" src="scripts/adminNetworkSettings.js"></script>
<script type="text/javascript" src="scripts/adminServicesSettings.js"></script>
<script type="text/javascript" src="scripts/overlib.js"></script>
<script type="text/javascript" src="scripts/infoPanel.js"></script>
<script type="text/javascript" src="scripts/ajax.js"></script>


<!-- For non-Retina iPhone, iPod Touch, and Android 2.1+ devices: -->
<link rel="apple-touch-icon-precomposed" href="images/touchicons/apple-touch-icon-precomposed.png">
<!-- For first- and second-generation iPad: -->
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="images/touchicons/apple-touch-icon-72x72-precomposed.png">
<!-- For iPhone with high-resolution Retina display: -->
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="images/touchicons/apple-touch-icon-114x114-precomposed.png">
<!-- For third-generation iPad with high-resolution Retina display: -->
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="images/touchicons/apple-touch-icon-144x144-precomposed.png">



<?php echo (isset($jsscriptfiles) ? $jsscriptfiles : "")?>
</head>
<?php if (!isset($title)) { $title = "NetBoot/SUS/LDAP Proxy Server Management"; } ?>
<body<?php echo (isset($onloadjs) ? " onload=\"$onloadjs\"" : "")?> id="dual-navigation-page">
<!-- Begin creating tabbed navigation system here -->
<div id="wrapper">
    <!-- open top -->
    <header id="top" class="">
      
      <aside id="logo-dash" class="">

				<a href="dashboard.php" title="Dashboard">
					<img src="images/navigation/NSUS-logo.png" width="230px" height="65px" alt="NetBoot/SUS Server Dashboard" class="hidemobile">
			        <img src="images/navigation/NSUS-logo-plain.png" alt="NetBoot/SUS Server Dashboard" class="showmobile"/>
				</a>
		        
      </aside>

      <div id="navigation" class="">
        
        <div id="right-links" class="hidemobile">

          <div id="user" class="hidemobile">
            <a href="#" id="user-link"><?php echo getCurrentWebUser(); ?> <img src="images/navigation/down-arrow.png" alt="Click" /></a>
              <div id="user-modal">
                <a href="restart.php">Restart</a>
                <a href="shutdown.php">Shut Down</a>
                
                <hr />
                <a href="disablegui.php">Disable GUI</a>
                <hr />
                <a href="logout.php">Log Out</a>
                <hr />
                <img class="handle" src="images/navigation/handle.png" alt="Handle" />
              </div>        
          </div>
          <a href="settings.php" id="settings" class="" title="Settings"></a>

        </div>

      </div>
      <!-- close navigation -->


    </header>
    <!-- close top -->


    <!-- open notifications-mobile -->
    <div id="notifications-mobile" class="showmobile ">
        <a href="#" id="user-link-mobile"><?php echo getCurrentWebUser(); ?> <img src="images/navigation/down-arrow.png" alt="Click" /></a>
        <div id="user-modal-mobile">
          <a href="restart.php">Restart</a>
          <a href="shutdown.php">Shut Down</a>
          <hr />
          <a href="logout.php">Log Out</a>
          <hr />
          <img class="handle" src="images/navigation/handle.png" alt="Handle" />
        </div>
    </div>
    <!-- close notifications-mobile -->



    <div id="right-links-mobile" class="showmobile ">
        <a href="settings.php" class="" id="settings-mobile"></a>
    </div>



    <!-- open content wrapper -->
    <div id="content-wrapper">
      
      
      <!-- open content container -->
      <div id="content-container">

        <?php include "inc/navigation.php"; ?>

          <!-- open content-insdie -->
          <div id="content-inside">