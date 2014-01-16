<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Backup";
?>

<?php
header('Content-Type: application/x-gzip');
$content_disp = ( ereg('MSIE ([0-9].[0-9]{1,2})', $HTTP_USER_AGENT) == 'IE') ? 'inline' : 'attachment';
header('Content-Disposition: ' . $content_disp . '; filename="backup.tar.gz"');
header('Pragma: no-cache');
header('Expires: 0');

// create the gzipped tarfile.
passthru( "tar cz /srv/SUS/ /srv/NetBoot/NetBootSP0/ /var/appliance/conf/ /var/lib/reposado/preferences.plist");
?>