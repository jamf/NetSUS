<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

if ($_GET['restart']) {
        echo suExec("restartafp");
}

if ($_GET['disable']) {
        echo suExec("stopafp");
}

if ($_GET['enable']) {
        echo suExec("startafp");
}

$sURL="AFP.php";
        header('Location: '. $sURL);

include "inc/footer.php"; 

?>

