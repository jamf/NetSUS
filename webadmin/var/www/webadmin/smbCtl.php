<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

if ($_GET['restart']) {
        echo suExec("restartsmb");
}

$sURL="SMB.php";
        header('Location: '. $sURL);

include "inc/footer.php"; 

?>

