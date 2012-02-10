<?php

if( isset( $FUNCTIONS ) ) {
    return;
}

$FUNCTIONS=1;

function getSystemTimeZones($path="/usr/share/zoneinfo/right")
{
	$tzlist = array();
	$i=0;
	if ($handle = opendir($path)) {
	    /* This is the correct way to loop over the directory. */
	    while (false !== ($file = readdir($handle))) {
	        if (is_dir($path."/".$file) && $file != "posix" && $file != "right")
	        {
	        	$zones = getSystemTimeZones($path."/".$file);
	        	foreach ($zones as $zone)
	        	{
	        		$tzlist[$i++] = $file."/".$zone;
	        	}
	        }
	        else
	        {
	        	$tzlist[$i++] = $file;
	        }
	    }
	    
	    closedir($handle);
	}
	sort($tzlist);
	return $tzlist;
}

function getSystemTimeZoneMenu()
{
	$currentTZ = getCurrentTimeZone();
	echo "<select id=\"timezone\" name=\"timezone\">\n";
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    foreach($timezone_identifiers as $value){
        if (preg_match('/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value))
        {
            $ex=explode("/",$value);//obtain continent,city    
            if ($continent!=$ex[0]){
                if ($continent!="") $return .= '</optgroup>'."\n";
                echo '<optgroup label="'.$ex[0].'">'."\n";
            }
    
            $city=$ex[1];
            $continent=$ex[0];
            echo '<option value="'.$value.'"'.($value==$currentTZ?" selected=\"selected\"":"").'>'.$city.(isset($ex[2])?"/".$ex[2]:"").'</option>'."\n";
        }
    }
    echo "</optgroup>\n";
    echo "</select>\n";
}

function suExec($cmd)
{
	return shell_exec("sudo /bin/sh scripts/adminHelper.sh ".escapeshellcmd($cmd));
}

function getLocalTime()
{
	return suExec("getlocaltime");
}

function getCurrentTimeServer()
{
	return suExec("gettimeserver");
}

function setTimeServer($ts)
{
	suExec("settimeserver ".$ts);
}

function getCurrentTimeZone()
{
	return trim(shell_exec("cat /etc/timezone"));
}

function setTimeZone($tz)
{
	suExec("settz ".$tz);
}

function getCurrentIP()
{
	return trim(suExec("getip"));
}

function getCurrentNetmask()
{
	return suExec("getnetmask");
}

function getCurrentGateway()
{
	return suExec("getgateway");
}

function getCurrentNameServers()
{
	return explode(" ", suExec("getdns"));
}

function getCurrentHostname()
{
	return shell_exec("/bin/hostname");
}

function setHostname($hostname)
{
	return suExec("sethostname ".escapeshellcmd($hostname));
}

function getNetType()
{
	return trim(suExec("getnettype"));
}

function getCurrentWebUser()
{
	global $admin_username;
	return $admin_username;
}

function setWebAdminUser($username, $password)
{
	global $conf;
	global $admin_username;
	$admin_username = $username;
	$conf->setSetting("webadminuser", $username);
	$conf->setSetting("webadminpass", hash("sha256",$password));
}

// function setShellAccount($oldUser, $newUser, $newPass)
// {
// 	// TODO
// }

function getNetBootStatus()
{
	if (trim(suExec("getnetbootstatus")) == "true")
	{
		return true;
	}
	else
	{
		return false;
	}
}

function getSyncStatus()
{
	if (trim(suExec("getsyncstatus")) == "true")
	{
		return true;
	}
	else
	{
		return false;
	}
}

function formatPackage($package)
{
	$package = preg_replace("/\s\s+/", " ", $package);
	$package = preg_replace("/\[([^\s]+)\s([^\]]+)\]/", "[$1$2]", $package);
	$parts = explode(" ", $package);
	$len = count($parts);
	$id = $parts[0];
	$deprecated = trim($parts[$len-1]);
	$branches = $parts[$len-2];
	if (substr($branches, 0, 1) == "[")
	{
		$date = $parts[$len-3];
		$version = $parts[$len-4];
		unset($parts[$len-1]);
		unset($parts[$len-2]);
		unset($parts[$len-3]);
		unset($parts[$len-4]);
	}
	else if (substr(($branches = $parts[$len-1]), 0, 1) == "[")
	{
		$deprecated = "";
		$date = $parts[$len-2];
		$version = $parts[$len-3];
		unset($parts[$len-1]);
		unset($parts[$len-2]);
		unset($parts[$len-3]);
	}
	else
	{
		$branches = "";
		$date = $parts[$len-2];
		$version = $parts[$len-3];
		unset($parts[$len-1]);
		unset($parts[$len-2]);
		unset($parts[$len-3]);
	}
	if ($deprecated != "")
	{
		$version .= " ".$deprecated;
	}
	unset($parts[0]);
	$name = implode(" ", $parts);
	return array($id, $name, $version, $date, $branches);
}

?>