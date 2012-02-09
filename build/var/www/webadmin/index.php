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
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
	<head> 
	    <title>NetBoot/SUS Appliance Admin Login</title>
	    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	    <meta http-equiv="expires" content="0">
	    <meta http-equiv="pragma" content="no-cache"> 
		<link rel="stylesheet" href="theme/Master8.0.css" type="text/css" charset="utf-8">
	</head> 
	
	<body> 
	<center> 
	
	<table id="loginTable" cellspacing=0 cellpadding=0 border=0 background="images/loginBackground.png" width="590" height="325"> 
		<tr> 
			<td align="center">
				<h3 style="color:#fff;">NetBoot/SUS Appliance Admin</h3><br/>
				<form method="post" name="loginForm" action=""> 
				<table> 
					 <tr> 
	      				<td align="right"> 
							<font class="login">Username:</font> 
						</td> 
	      				<td> 
							<input type="text" name="username" value=""> 
						</td> 
	    			</tr> 
	   				<tr> 
	      					<td align="right"> 
							<font class="login">Password:</font> 
						</td> 
	      				
	      					<td> 
							<input type="password" name="password"> 
						</td> 
	    				</tr> 
					<tr> 
	    					<td colspan="2" align="center"> 
							<input type="submit" name="submit" value="Login"> 
						</td> 
	   				 </tr> 
				</table> 
				</form> 
			</td> 
		</tr> 
	 
	</table> 
	</center>
	
	<script type="text/javascript"> 
	<!--
	document.loginForm.username.focus();
	// -->
	</script>
	
	</body>
</html>
<?php
}
?>
