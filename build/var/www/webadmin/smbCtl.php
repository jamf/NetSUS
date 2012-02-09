<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

if ($_GET['restart']) {
        echo suExec("restartsmb");
}

$sURL="admin.php";
        header('Location: '. $sURL);

include "inc/footer.php"; 

?>

