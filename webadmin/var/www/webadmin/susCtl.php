<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

if ($_GET['sync']) {
	echo suExec("reposync");
}

if ($_GET['purge']) {
	echo suExec("repopurge");
}

$sURL="SUS.php";
header('Location: '. $sURL);

include "inc/footer.php";

?>

