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
	echo "<select class=\"form-control input-sm\" id=\"timezone\" name=\"timezone\">\n";
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    foreach($timezone_identifiers as $value){
        if (preg_match('/^(America|Australia|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value))
        {
			if (!isset($continent)) {
				$continent = '';
			}
            $ex=explode("/",$value);//obtain continent,city
            if ($continent!=$ex[0]){
                if ($continent!="") $return = '</optgroup>'."\n";
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
	return shell_exec("sudo /bin/sh scripts/adminHelper.sh ".escapeshellcmd($cmd)." 2>&1");
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
	if (strpos($_SERVER['SERVER_SOFTWARE'], 'Ubuntu') !== FALSE) {
		return trim(shell_exec("cat /etc/timezone"));
	} else {
		return trim(shell_exec("cat /etc/sysconfig/clock | awk -F '\"' '{print $2}'"));
	}
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
	if (isset($_SESSION['username']))
		return $_SESSION['username'];
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

function getDN($ldapconn, $samaccountname, $basedn)
{
    $attributes = array("dn");
    $result = ldap_search($ldapconn, $basedn, "(|(samaccountname=".$samaccountname.")(userprincipalname=".$samaccountname."))", $attributes);
    if ($result === FALSE)
        return '';
    $entries = ldap_get_entries($ldapconn, $result);
    if ($entries['count'] > 0)
        return $entries[0]['dn'];
    else
        return '';
};

function checkLDAPGroupEx($ldapconn, $userdn, $groupdn)
{
    $attributes = array("memberOf");
    $result = ldap_read($ldapconn, $userdn, "(objectclass=*)", $attributes);
    if ($result === FALSE)
        return FALSE;
    $entries = ldap_get_entries($ldapconn, $result);
    if ($entries['count'] <= 0)
        return FALSE;
    if (empty($entries[0]['memberof']))
    {
        return FALSE;
    }
    else
    {
        for ($i = 0; $i < $entries[0]['memberof']['count']; $i++)
        {
            if ($entries[0]['memberof'][$i] == $groupdn)
                return TRUE;
            elseif (checkLDAPGroupEx($ldapconn, $entries[0]['memberof'][$i], $groupdn))
                return TRUE;
        }
    }
    return FALSE;
};


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

function getLDAPProxyStatus()
{
	if (trim(suExec("getldapproxystatus")) == "true")
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
	$package = preg_replace("/'\s*,\s*'/", "','", $package);
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

function checkIn()
{
	return suExecFromAPIBackground("checkin");
}

function isValidIPAddress($string)
{
	return filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
}

function isValidNetmask($string)
{
	if ($string =='')
	{
		return false;
	}
	else
	{
		return in_array(ip2long($string), array(0xffffffff, 0xfffffffe, 0xfffffffc, 0xfffffff8, 0xfffffff0, 0xffffffe0, 0xffffffc0, 0xffffff80, 0xffffff00, 0xfffffe00, 0xfffffc00, 0xfffff800, 0xfffff000, 0xffffe000, 0xffffc000, 0xffff8000, 0xffff0000, 0xfffe0000, 0xfffc0000, 0xfff80000, 0xfff00000, 0xffe00000, 0xffc00000, 0xff800000, 0xff000000, 0xfe000000, 0xfc000000, 0xf8000000, 0xf0000000, 0xe0000000, 0xc0000000, 0x80000000, 0x00000000));
	}
}

function getNetAddress($ip, $mask)
{
	return long2ip(ip2long($ip) & ip2long($mask));
}

function getBcastAddress($ip, $mask)
{
	return long2ip(ip2long($ip) & ip2long($mask) | ~ip2long($mask));
}

function isLoopbackAddress($ip)
{
	return (ip2long($ip) >= 0x7f000000 && ip2long($ip) <= 0x7fffffff);
}

function isValidHostname($string)
{
	return preg_match('/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|-){0,61}[0-9A-Za-z])?)*\.?$/', $string);
}

function getSSHstatus()
{
	if (trim(suExec("getSSHstatus")) == "true")
	{
		return true;
	}
	else
	{
		return false;
	}
}

function getFirewallstatus()
{
	if (trim(suExec("getFirewallstatus")) == "true")
	{
		return true;
	}
	else
	{
		return false;
	}
}

?>