<?php
session_start();

$noAuthURL="index.php";
if ( !isset($_SESSION['isAuthUser']) || !($_SESSION['isAuthUser']) ) {
	header('Location: '. $noAuthURL);
}

?>