<?php

session_start();

if (!($_SESSION["isAuthUser"])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";

	function susExec($cmd) {
		return exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}

	if (isset($_POST['sync'])) {
		susExec("repoSync");
	}

	if (isset($_POST['purge'])) {
		susExec("repoPurge");
	}

	if (isset($_POST['service'])) {
		if ($_POST['service'] == "enable") {
			$conf->setSetting("sus", "true");
		} else {
			$conf->deleteSetting("sus");
		}
	}

	if (isset($_POST['baseurl'])) {
		$conf->setSetting("susbaseurl", $_POST['baseurl']);
		if ($conf->getSetting("mirrorpkgs") == "true") {
			susExec("setBaseUrl ".$_POST['baseurl']);
		}
	}

	if (isset($_POST['mirrorpkgs'])) {
		$conf->setSetting("mirrorpkgs", $_POST['mirrorpkgs']);
		if ($_POST['mirrorpkgs'] == "true") {
			susExec("setBaseUrl ".$conf->getSetting("susbaseurl"));
		} else {
			susExec("setBaseUrl");
		}
	}

	if (isset($_POST['syncschedule'])) {
		$conf->setSetting("syncschedule", $_POST['syncschedule']);
		if ($_POST['syncschedule'] != "Off") {
			susExec("setSchedule \"".$_POST['syncschedule']."\"");
		} else {
			susExec("delSchedule");
		}
	}

	if (isset($_POST['proxy'])) {
		susExec("setProxy ".$_POST['proxy']);
	}

	if (isset($_POST['catalogurls'])) {
		$apple_catalog_urls_str = str_replace(",", " ", $_POST['catalogurls']);
		susExec("setCatalogURLs \"".$apple_catalog_urls_str."\"");
	}

	if (isset($_GET['branch']) && isset($_POST['autosync'])) {
		if ($_POST['autosync'] == "true") {
			$conf->addAutosyncBranch($_GET['branch']);
		} else {
			$conf->deleteAutosyncBranch($_GET['branch']);
		}
	}

	if (isset($_GET['branch']) && isset($_POST['rootbranch'])) {
		if ($_POST['rootbranch'] == "true") {
			$conf->setSetting("rootbranch", $_GET['branch']);
			susExec("rootBranch ".$_GET['branch']);
		} else {
			$conf->deleteSetting("rootbranch");
			susExec("rootBranch");
		}
	}

	if (isset($_GET['prodinfo'])) {

		$detailstr = trim(susExec("productInfo ".$_GET['prodinfo']));
		$details = json_decode($detailstr);

		if ($detailstr == "") {
			echo "No product id";
		} else {
			echo "<table>";
			echo "<tr>";
			echo "<td align=\"right\"><strong>Product ID:</strong></td>";
			echo "<td>&nbsp;</td>";
			echo "<td align=\"left\">".$details->id."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align=\"right\"><strong>Title:</strong></td>";
			echo "<td>&nbsp;</td>";
			echo "<td align=\"left\">".$details->title."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align=\"right\"><strong>Version:</strong></td>";
			echo "<td>&nbsp;</td>";
			echo "<td align=\"left\">".$details->version."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align=\"right\"><strong>Size:</strong></td>";
			echo "<td>&nbsp;</td>";
			echo "<td align=\"left\">".$details->size."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align=\"right\"><strong>Post Date:</strong></td>";
			echo "<td>&nbsp;</td>";
			echo "<td align=\"left\">".$details->PostDate."</td>";
			echo "</tr>";
			echo "</table>";
			$lines = explode("\n", $details->description);
			$description = "";
			$capture = false;
			foreach ($lines as $line) {
				if (strpos($line, "<body>") !== FALSE) {
					$description = $line."\n";
					$capture = true;;
				}
				else if (strpos($line, "</body>") !== FALSE) {
					$description .= $line."\n";
					$description = str_replace("<body>", "", str_replace("</body>", "", $description));
					echo "<br/><hr/><br/>".$description."<br/>\n";
				}
				else if ($capture) {
					$description .= $line."\n";
				}
			}
		}
	}

}

?>