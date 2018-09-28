<?php

session_start();

if (!($_SESSION['isAuthUser'])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/auth.php";
	include "inc/functions.php";

	suExec("backupConf");
	if (file_exists('/tmp/backup.tar.gz')) {
		if (ob_get_level()) ob_end_clean();
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="backup.tar.gz"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize('/tmp/backup.tar.gz'));
		ob_clean();
		flush();
		readfile('/tmp/backup.tar.gz');
		exit;
	}

}
?>