<?php

session_start();

if (!($_SESSION["isAuthUser"])) {

	echo "Not authorized - please log in";

} else {

	include "inc/config.php";
	include "inc/functions.php";

	function susExec($cmd) {
		return shell_exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
	}

	if (isset($_POST['sync'])) {
		susExec("repoSync");
	}

	if (isset($_POST['purge'])) {
		susExec("repoPurge");
	}

	if (isset($_POST['service'])) {
		if ($_POST['service'] == "enable") {
			$conf->setSetting("sus", "enabled");
		} else {
			$conf->setSetting("sus", "disabled");
		}
	}

	if (isset($_POST['dashboard'])) {
		if ($_POST['dashboard'] == "true") {
			$conf->setSetting("showsus", "true");
		} else {
			$conf->setSetting("showsus", "false");
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
			echo "<td align=\"right\"><strong>Mac OSx:</strong></td>";
			echo "<td>&nbsp;</td>";
			echo "<td align=\"left\">";
			foreach($details->oscatalogs as $oscatalog){
				echo '<span class="badge badge-info" style="background-color:#337ab7 !important;">'.$oscatalog.'</span> ';
			}
			if($details->Deprecated == "(Deprecated)"){
				echo '<span class="badge badge-info" style="background-color:#f0ad4e !important;">Deprecated</span> ';
			}
			echo "</td>";
			echo "</tr>";
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
			echo "<br/>";
			foreach($details->packages as $package){
				$pack_name = substr($package->URL, strrpos($package->URL, '/') + 1);
				//$pack_name .= " (".$package->Size.")";

				$size = intval($package->Size);
				if($size < 1000)
					$pack_name .= " (".$size." octet)";
				else if($size < 1000000)
					$pack_name .= " (".round(($size/1024),2)." Ko)";
				else if($size < 1000000000)
					$pack_name .= " (".round(($size/(1024*1024)),2)." Mo)";
				else
					$pack_name .= " (".round(($size/(1024*1024*1024)),2)." Go)";

				echo '<span class="badge badge-info" style="background-color:#5cb85c !important;">';
				echo '<a target="_blank" href="'.$package->URL.'" style="color:white;">'.$pack_name.'</a>';
				echo '</span>';
			}
			echo "<br/>";

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

	if(isset($_POST['susfilters'])){
		// POST
		$exp = explode(';', $_POST["susfilters"]);
		if(count($exp) != 2)
			die("false");

		// Current Setting
		$filterset = $conf->getSetting("susfilters");
		$exp_fs = explode(';', $filterset);
		
		$to_set = '';
		foreach($exp_fs as $filter){
			$exp_f = explode('=', $filter);
			if(count($exp_f) != 2)
				continue;

			if($exp_f[0] != $exp[0])
				$to_set .= $exp_f[0]."=".$exp_f[1].";";
		}

		$to_set .= $exp[0]."=".$exp[1];

		$conf->setSetting("susfilters", $to_set);
		die("true");
	}

	if(isset($_GET['susfilters'])){
		echo $conf->getSetting("susfilters");
	}

	if(isset($_GET['criticalupdates'])){
		$criticalProductstr = trim(susExec("criticalProductList"));

		$update_ids = array();
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $criticalProductstr) as $line){
			$end = strpos($line, ' ');
			if($end !== false){
				$upd_id = substr($line, 0, $end);
				$update_ids[$upd_id] = $line;
			}
		} 
		
		echo json_encode($update_ids);
	}

	if(isset($_POST['susEnableFilters'])){
		$conf->setSetting("filterEnable", $_POST['susEnableFilters']);
		echo $conf->getSetting("filterEnable");
	}

	if(isset($_GET['susEnableFilters'])){
		echo $conf->getSetting("filterEnable");
	}
}

?>