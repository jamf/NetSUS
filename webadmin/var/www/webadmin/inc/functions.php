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
        if (preg_match('/^(America|Australia|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value))
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

// ldap administrators lookup (contributor: Tyler Winfield)
function getLDAPAdmins()
{
        global $conf;
        $results = array();

        $conn = ldap_connect($conf->getSetting("ldapserver"));
        if ($conn) {
                ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);  //Set the LDAP Protocol used by your AD service
                ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);         //This was necessary for my AD to do anything

                if (ldap_bind($conn, $conf->getSetting("ldapusername"), base64_decode($conf->getSetting("ldappassword")))) {
                        $adminlist = array();
                        foreach ($conf->getAdmins() as $key => $value)
                                $results = array_merge($results, getLDAPAdmin($conn, $value['cn']));
                        ldap_unbind($conn);
                }
                ldap_close($conn);
        }
        return $results;
}

// recursive ldap lookup (contributor: Tyler Winfield)
function getLDAPAdmin($conn, $cn) {
        global $conf;
        $domain = "DC=".implode(",DC=", explode(".", $conf->getSetting("ldapdomain")));

        // CHECK FOR BASE CASE: User account
        $user_search = ldap_search($conn, $domain, "(&(objectcategory=user)(|(samaccountname=".$cn.")(userprincipalname=".$cn.")))", array("dn", "cn", "samaccountname", "userprincipalname"));
        $user_records = ldap_get_entries($conn, $user_search);
        if ($user_records['count'] > 0) {
                $user_account = $user_records[0];
                if ($user_account['samaccountname']['count'] > 0)
                        return array($user_account['samaccountname'][0] => $user_account['dn']);
                if ($user_accoutn['userprincipalname']['count'] > 0)
                        return array($user_account['userprincipalname'][0] => $user_account['dn']);
        }

        $userlist = array();
        // RECURSIVE CASE: Group account
        $group_search = ldap_search($conn, $domain, "(&(objectcategory=group)(|(samaccountname=".$cn.")(userprincipalname=".$cn.")))", array("dn", "cn", "samaccountname", "userprincipalname"));
        $group_records = ldap_get_entries($conn, $group_search);
        if ($group_records['count'] > 0) {
                $group_account = $group_records[0];
                $members_search = ldap_search($conn, $domain, "(&(memberOf=".$group_account['dn']."))", array("dn", "cn", "samaccountname", "userprincipalname"));
                $members_records = ldap_get_entries($conn, $members_search);
                for ($i = 0; $i < $members_records['count']; $i++) {
                        if (isset($members_records[$i]['samaccountname'][0]))
                                $userlist = array_merge($userlist, getLDAPAdmin($conn, $members_records[$i]['samaccountname'][0]));
                        else if (isset($members_records[$i]['userprincipalname'][0]))
                                $userlist = array_merge($userlist, getLDAPAdmin($conn, $members_records[$i]['userprincipalname'][0]));
                }
        }
        return $userlist;
}

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

?>
