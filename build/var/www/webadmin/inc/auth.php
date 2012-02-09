<?php
session_start();

$noAuthURL="index.php";
if (!($_SESSION['isAuthUser'])) {
	header('Location: '. $noAuthURL);
}

?>