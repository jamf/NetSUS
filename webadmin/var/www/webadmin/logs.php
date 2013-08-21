<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Logs";
include "inc/header.php";

?>
<style>
	.about {
		margin-left: 10px;
	}
	.about p.bold {
		font-weight: bold;
	}
	.about ul {
		list-style-type: disc;
		margin-left: 20px;
	}
</style>

<h2>Logs</h2>
<br>
<?php
$jamflog = file_get_contents('/var/appliance/logs/jamf.log');

print nl2br($jamflog);
?>
<?php include "inc/footer.php"; ?>