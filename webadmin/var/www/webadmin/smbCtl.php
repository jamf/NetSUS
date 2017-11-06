<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";
$currentIP = trim(getCurrentIP());
$sURL="SMB.php";

#if ($_GET['restart']) {
#        echo suExec("restartsmb");
#}

if ($_GET['start']) {
        echo suExec("startsmb");
        $sURL="smb://".$currentIP."/NetBoot";
}

if ($_GET['disable']) {
        echo suExec("stopsmb");
}

if ($_GET['enable']) {
        echo suExec("startsmb");
}

        header('Location: '. $sURL);

include "inc/footer.php"; 

?>

