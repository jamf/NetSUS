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
		<link href="theme/bootstrap.min.css" rel="stylesheet" media="all">
		<link rel="stylesheet" href="theme/styles-fresh.css" type="text/css">
		<script type="text/javascript" src="scripts/bootstrap.min.js"></script>
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
            <link href="theme/bootstrap.min.css" rel="stylesheet" media="all">
            <link rel="stylesheet" href="theme/login.css" type="text/css">
            <script type="text/javascript" src="scripts/jquery/jquery-2.2.0.js"></script>
            <script type="text/javascript" src="scripts/bootstrap.min.js"></script>

        </head>

        <body>

			<div class="container">

				<div id="loginbox" class="mainbox col-md-6 col-md-offset-3 col-sm-6 col-sm-offset-3">

					<div class="row">
						<div class="iconmelon">
						  <svg viewBox="0 0 32 32">
							<g filter="">
							  <use xlink:href="#git"></use>
							</g>
						  </svg>
						</div>
					</div>

					<div class="panel panel-default" >
						<div class="panel-heading">
							<div class="panel-title text-center">Bootsnipp.com</div>
						</div>

						<div class="panel-body" >

							<form name="form" id="form" class="form-horizontal" enctype="multipart/form-data" method="POST">

								<div class="input-group">
									<span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
									<input id="user" type="text" class="form-control" name="user" value="" placeholder="User">
								</div>

								<div class="input-group">
									<span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
									<input id="password" type="password" class="form-control" name="password" placeholder="Password">
								</div>

								<div class="form-group">
									<!-- Button -->
									<div class="col-sm-12 controls">
										<button type="submit" href="#" class="btn btn-primary pull-right"><i class="glyphicon glyphicon-log-in"></i> Log in</button>
									</div>
								</div>

							</form>

						</div>
					</div>
				</div>
			</div>
<!--
            <div class="login-wrapper">

                <div id="logo-wrapper">
                    <img src="images/navigation/NSUS-logo-plain.png" width="75">
                </div>
                <div class="login-form-wrapper">
                    <form method="post" name="loginForm" action="">

                        <span class="label">Username</span>
                        <input type="text" name="username" id="username" class="input" value="">

                        <br>

                        <span class="label">Password</span>
                        <input type="password" name="password" id="password" class="input">

                        <br>

                        <input type="submit" class="button" name="submit" value="Log In">

                    </form>
                </div>
            </div>


			<div class="login-container">

				<div class="login-img">
					<img src="images/navigation/NSUS-logo-plain.png" width="75"/>
				</div>

				<div class="login-form-container">
					<form method="post" name="loginForm" class="login-form" action="">

						<input type="text" name="username" id="username" class="form-control" placeholder="User Name" required autofocus>
						<input type="password" name="passoword" id="password" class="form-control" placeholder="Password" required>

						<button class="btn btn-lg btn-primary btn-block btn-signin" name ="submit" type="submit">Log In</button>

					</form>
				</div>
			</div>
-->
	</body>
</html>
<?php
}
?>
