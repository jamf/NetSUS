<?php

session_start();

if (!($_SESSION['isAuthUser'])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/dbConnect.php";
	include "inc/functions.php";

	// Download Log
	if (isset($_GET['download'])) {
		$logname = basename($_GET['download']);
		$tmp_file = "/tmp/".$logname.".zip";
		$logcontent = suExec("displayLog ".$_GET['download']." ".$_GET['lines']);
		file_put_contents("/tmp/".$logname, $logcontent);
		$zip = new ZipArchive();
		$zip->open($tmp_file, ZipArchive::CREATE);
		$zip->addFile("/tmp/".$logname, $logname);
		$zip->close();
		unlink("/tmp/".$logname);
		if (file_exists($tmp_file)) {
			if (ob_get_level()) ob_end_clean();
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$logname.'.zip"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: '.filesize($tmp_file));
			ob_clean();
			flush();
			readfile($tmp_file);
			unlink($tmp_file);
			exit;
		}
	}
}

?>