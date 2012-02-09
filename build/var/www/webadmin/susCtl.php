<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

if ($_GET['sync']) {
	echo suExec("reposync");
}

$sURL="admin.php";
header('Location: '. $sURL);

include "inc/footer.php";

?>

