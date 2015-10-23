<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";
$currentIP = trim(getCurrentIP());

if ($_GET['restart']) {
		$sURL="SMB.php";
        echo suExec("restartsmb");
}

if ($_GET['start']) {
        echo suExec("startsmb");
        $sURL="smb://".$currentIP."/NetBoot";
}


        header('Location: '. $sURL);

include "inc/footer.php"; 

?>

