<?
//Read in whether or not the user is an admin - this is populated at the index.php page using the allowedAdminUsers variable
if (isset($_SESSION['isAdmin'])) {
	$isAdmin = $_SESSION['isAdmin'];
}else{
	$isAdmin = false;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= (isset($title) ? $title." - NetBoot/SUS Appliance Management" : "NetBoot/SUS Appliance Management") ?></title>
<link rel="stylesheet" href="theme/Master8.0.css" type="text/css"
	charset="utf-8">
<script type="text/javascript" src="scripts/overlib.js"></script>
<script type="text/javascript" src="scripts/infoPanel.js"></script>
<script type="text/javascript" src="scripts/ajax.js"></script>
<script src="scripts/jquery/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="scripts/jquery.bt.js" type="text/javascript" charset="utf-8"></script>
<?= (isset($jsscriptfiles) ? $jsscriptfiles : "")?>
</head>
<? if (!isset($title)) { $title = "NetBoot/SUS Appliance Management"; } ?>
<body<?= (isset($onloadjs) ? " onload=\"$onloadjs\"" : "")?>>
<!-- Begin creating tabbed navigation system here -->

<div id="container">
<div id="navWrap">
<table id="headWrap" cellspacing="0" cellpadding="0">
	<tr>
		<td>
		<ul id="gHeader">
			<li>
			<div id="globalheader" class="Instance">
			<ul id="globalnav">
				<strong><font size="5">NetBoot/SUS Appliance Management</font></strong>
			</ul>
			</div>
			</div>
			</li>
		</ul>
		<!-- gHeader -->
		<style>
#globalheader {
	width: 746px;
}

#logout {
	width: 746px;
}

ul#logOut {
	width: 746px;
}

ul#gHeader {
	width: 746px;
}
</style>
		<ul id="logOut">
			<li>

			<div id="logout">
			<ul>
				<li><a href="dashboard.php">Dashboard</a>
				|</li>
				<li><a href="admin.php">Admin</a>
				|</li>
   				<li><a href="about.php">About</a>
				|</li>
				<li class="password"><a href="account.php">Change Account</a>
				|</li>
				<li class="logout"><a href="logout.php"
					onClick="javascript:return confirm('Are you sure you want to log out of the NetBoot/SUS Appliance Administration System?')">Logout</a></li>
			</ul>
			<br clear="all" />
			</div>
			<!-- logOut --></li>
		</ul>
		</td>
	</tr>
</table>
</div>
<!--end navWrap--> <!-- Finished creating tabbed navigation system --> <!-- Begin Creating Tab Wrapper -->
<table id="tabWrapper">
	<tr>
		<td>
		<div id="contentWrapper">
		<div id="header">
		<h3><?= $title ?></h3>
		<ul id="dkGradient" class="noShadow">
			<li><br />
			</li>
			<li class="shadow"><br />
			</li>
		</ul>
		</div>
<?
if(isset($headerTabs))
{
	echo $headerTabs;
}
?>
		<div id="contentDisplay">
