<?php
define("CONF_FILE_PATH", "/var/appliance/conf/appliance.conf.xml");
define("TOP_ELEMENT_NAME", "webadminSettings");


$isAdmin        = false;
$debug 			= false;

/*
 * $admin_username and $admin_password are now defined at the bottom of this script
 */

//Read in whether or not the user is an admin - this is populated at the index.php page using the allowedAdminUsers variable
if (isset($_SESSION['isAdmin'])) {
	$isAdmin = $_SESSION['isAdmin'];
}

class WebadminConfig
{
	private $xmlDoc;
	private $topElement;
	private $settings;
	private $subnets;
	private $admins;
	private $proxies;
	private $autosyncbranches;
	private $defaultpasses;
	private $files;

	function __construct()
	{
		$this->settings = array();
		$this->subnets = array();
		$this->admins = array();
		$this->proxies = array();
		$this->autosyncbranches = array();
		$this->defaultpasses = array();
		$dom = new DOMDocument;
		$dom->load(CONF_FILE_PATH);
		if(!file_exists(CONF_FILE_PATH) || ($this->xmlDoc = $dom) == FALSE)
		{
			shell_exec("sudo /bin/sh scripts/adminHelper.sh touchconf \"".CONF_FILE_PATH."\"");
			// Creating a new settings doc
			$this->xmlDoc = new DOMDocument("1.0", "utf-8");
			$this->topElement = $this->xmlDoc->createElement(TOP_ELEMENT_NAME);
			$this->xmlDoc->appendChild($this->topElement);
			$this->createDefaultPasses();
		}
		else
		{
			// Loading existing settings doc
			$elements = $this->xmlDoc->getElementsByTagName(TOP_ELEMENT_NAME);
			if ($elements->length > 0)
			{
				$this->topElement = $elements->item(0);
				$this->loadSettings();
				$this->loadAutosyncBranches();
				$this->loadDefaultPasses();
			}
			else
			{
				$this->topElement = $this->xmlDoc->createElement(TOP_ELEMENT_NAME);
				$this->xmlDoc->appendChild($this->topElement);
				$this->createDefaultPasses();
			}
		}
	}

	function __destruct()
	{
	}

	public function createElement($name)
	{
		return $this->xmlDoc->createElement($name);
	}

	public function getSetting($name)
	{
		reset($this->settings);
		if (array_key_exists($name, $this->settings))
		{
			return $this->settings[$name];
		}
		else
		{
			return "";
		}
	}

	public function setSetting($name, $setting)
	{
		$this->settings[$name] = $setting;
		$this->saveSettings();
	}

	public function deleteSetting($name)
	{
		reset($this->settings);
		if (array_key_exists($name, $this->settings))
		{
			unset($this->settings[$name]);
			$this->saveSettings();
		}
	}

	public function loadSettings()
	{
		foreach($this->topElement->childNodes as $curNode)
		{
			if ($curNode->nodeName == "ldapproxies" || $curNode->nodeName == "netbootsubnets" || $curNode->nodeName == "autosyncbranches" || $curNode->nodeName == "defaultpasses" || $curNode->nodeName == "files" || $curNode->nodeName == "ldapadmins")
			{
				continue;
			}

			if ($curNode != NULL && $curNode->nodeName != NULL && $curNode->nodeName != "" && $curNode->nodeName != "#comment")
			{
				$this->settings[$curNode->nodeName] = $curNode->nodeValue;
			}
		}

		$this->loadSubnets();
		$this->loadAdmins();
		$this->loadProxies();
	}

	public function saveSettings()
	{
		// Create a fresh XML document
		$this->xmlDoc = new DOMDocument("1.0", "utf-8");
		$this->topElement = $this->xmlDoc->createElement(TOP_ELEMENT_NAME);
		$this->xmlDoc->appendChild($this->topElement);
		$this->topElement->appendChild(new DOMComment("Last updated: " . time()));

		// Loop through the settings
		foreach ($this->settings as $key => $value)
		{
			try
			{
				$settingNode = $this->createElement($key);
				$settingNode->nodeValue = $value;
				$this->topElement->appendChild($settingNode);
			}
			catch (DOMException $e)
			{
				echo "Error while creating node for $key [$value]<br/>\n";
			}
		}

		// Create the netbootsubnets node
		$netbootsubnets = $this->createElement("netbootsubnets");
		$this->topElement->appendChild($netbootsubnets);


		// Loop through the Netboot subnets
		foreach($this->subnets as $key => $value)
		{
			$newSubnetNode = $this->createElement("netbootsubnet");
			$netbootsubnets->appendChild($newSubnetNode);
			$newSubnet = $this->createElement("subnet");
			$newSubnet->nodeValue = trim($value['subnet']);
			$newSubnetNode->appendChild($newSubnet);
			$newNetmask = $this->createElement("netmask");
			$newNetmask->nodeValue = trim($value['netmask']);
			$newSubnetNode->appendChild($newNetmask);
		}

		// Create the ldapadmins node
		$ldapadmins = $this->createElement("ldapadmins");
		$this->topElement->appendChild($ldapadmins);

		// Loop through the LDAP admins
		foreach($this->admins as $key => $value)
		{
			$newAdminNode = $this->createElement("ldapadmin");
			$ldapadmins->appendChild($newAdminNode);
			$newAdmin = $this->createElement("cn");
			$newAdmin->nodeValue = trim($value['cn']);
			$newAdminNode->appendChild($newAdmin);
		}

		// Create the ldapproxies node
		$ldapproxies = $this->createElement("ldapproxies");
		$this->topElement->appendChild($ldapproxies);

		// Loop through the LDAP Proxies
		foreach($this->proxies as $key => $value)
		{
			$newProxyNode = $this->createElement("ldapproxy");
			$ldapproxies->appendChild($newProxyNode);
			$newoutLDAP = $this->createElement("outLDAP");
			$newoutLDAP->nodeValue = trim($value['outLDAP']);
			$newProxyNode->appendChild($newoutLDAP);
			$newinLDAP = $this->createElement("inLDAP");
			$newinLDAP->nodeValue = trim($value['inLDAP']);
			$newProxyNode->appendChild($newinLDAP);
			$newinURL = $this->createElement("inURL");
			$newinURL->nodeValue = trim($value['inURL']);
			$newProxyNode->appendChild($newinURL);
		}

		// Create the autosyncbranches node
		$autosyncbranches = $this->createElement("autosyncbranches");
		$this->topElement->appendChild($autosyncbranches);

		// Loop through the autosync branches
		foreach($this->autosyncbranches as $key => $value)
		{
			$newBranchNode = $this->createElement("branch");
			$newBranchNode->nodeValue = $key;
			$autosyncbranches->appendChild($newBranchNode);
		}

		// Create the defaultpasses node
		$defaultpasses = $this->createElement("defaultpasses");
		$this->topElement->appendChild($defaultpasses);

		// Lopo through the default pass list
		foreach($this->defaultpasses as $key => $value)
		{
			$newDefaultPass = $this->createElement("defaultpass");
			$newDefaultPass->nodeValue = $key;
			$defaultpasses->appendChild($newDefaultPass);
		}

		// Write the newly-created XML document to the settings file
		if ($this->xmlDoc->save(CONF_FILE_PATH) === FALSE)
		{
			echo(" ". CONF_FILE_PATH. ": Could not save settings");
		}
	}


	public function loadSubnets()
	{
		$subnetnodes = $this->xmlDoc->getElementsByTagName("netbootsubnet");
		$numsubs = $subnetnodes->length;
		for ($subi = 0; $subi < $numsubs; $subi++)
		{
			$node = $subnetnodes->item($subi)->childNodes;
			if ($node->length != 2)
				continue;
			if ($node->item(0)->nodeName == "subnet")
				$subnet = $node->item(0)->nodeValue;
			else if ($node->item(1)->nodeName == "subnet")
				$subnet = $node->item(1)->nodeValue;
			else
				continue;
			if ($node->item(1)->nodeName == "netmask")
				$netmask = $node->item(1)->nodeValue;
			else if ($node->item(0)->nodeName == "netmask")
				$netmask = $node->item(0)->nodeValue;
			else
				continue;
			$this->subnets["$subnet $netmask"] = array("subnet" => $subnet, "netmask" => $netmask);
		}
	}

	public function getSubnets()
	{
		return $this->subnets;
	}

	public function addSubnet($subnet, $netmask)
	{
		if (isset($this->subnets["$subnet $netmask"]))
		{
			return false; // False means duplicate
		}
		else
		{
			$this->subnets["$subnet $netmask"] = array("subnet" => $subnet, "netmask" => $netmask);
			$this->saveSettings();
			return true; // True means added
		}
	}

	public function deleteSubnet($subnet, $netmask)
	{
		reset($this->subnets);
		if (array_key_exists("$subnet $netmask", $this->subnets))
		{
			unset($this->subnets["$subnet $netmask"]);
			$this->saveSettings();
		}
	}

	public function loadAdmins()
	{
		$adminnodes = $this->xmlDoc->getElementsByTagName("ldapadmin");
		$numadmins = $adminnodes->length;
		for ($admini = 0; $admini < $numadmins; $admini++)
		{
			$node = $adminnodes->item($admini)->childNodes;
			if ($node->length != 1)
				continue;
			if ($node->item(0)->nodeName == "cn")
				$cn = $node->item(0)->nodeValue;
			else
				continue;

			$this->admins["$cn"] = array("cn" => $cn);
		}
	}

	public function printAdmins()
	{
		print_r($this->admins);
	}

	public function getAdmins()
	{
		return $this->admins;
	}

	public function addAdmin($cn)
	{
		if (isset($this->admins["$cn"]))
		{
			return false; // False means duplicate
		}
		else
		{
			$this->admins["$cn"] = array("cn" => $cn);
			$this->saveSettings();
			return true; // True means added
		}
	}

	public function deleteAdmin($cn)
	{
		reset($this->admins);
		if (array_key_exists("$cn", $this->admins))
		{
			unset($this->admins["$cn"]);
			$this->saveSettings();
		}
	}

	public function loadProxies()
	{
		$proxynodes = $this->xmlDoc->getElementsByTagName("ldapproxy");
		$numproxies = $proxynodes->length;
		for ($proxyi = 0; $proxyi < $numproxies; $proxyi++)
		{
			$node = $proxynodes->item($proxyi)->childNodes;
			if ($node->length != 3)
				continue;
			if ($node->item(0)->nodeName == "outLDAP")
				$outLDAP = $node->item(0)->nodeValue;
			else if ($node->item(1)->nodeName == "outLDAP")
				$outLDAP = $node->item(1)->nodeValue;
			else if ($node->item(2)->nodeName == "outLDAP")
				$outLDAP = $node->item(2)->nodeValue;
			else
				continue;
			if ($node->item(1)->nodeName == "inLDAP")
				$inLDAP = $node->item(1)->nodeValue;
			else if ($node->item(0)->nodeName == "inLDAP")
				$inLDAP = $node->item(0)->nodeValue;
			else if ($node->item(2)->nodeName == "inLDAP")
				$inLDAP = $node->item(2)->nodeValue;
			else
				continue;
			if ($node->item(1)->nodeName == "inURL")
				$inURL = $node->item(1)->nodeValue;
			else if ($node->item(0)->nodeName == "inURL")
				$inURL = $node->item(0)->nodeValue;
			else if ($node->item(2)->nodeName == "inURL")
				$inURL = $node->item(2)->nodeValue;
			else
				continue;
			$this->proxies["$outLDAP $inLDAP $inURL"] = array("outLDAP" => $outLDAP, "inLDAP" => $inLDAP, "inURL" => $inURL);
		}
	}

	public function getProxies()
	{
		return $this->proxies;
	}

	public function addProxy($outLDAP, $inLDAP, $inURL)
	{
		if (isset($this->proxies["$outLDAP $inLDAP $inURL"]))
		{
			return false; // False means duplicate
		}
		else
		{
			$this->proxies["$outLDAP $inLDAP $inURL"] = array("outLDAP" => $outLDAP, "inLDAP" => $inLDAP, "inURL" => $inURL);
			$this->saveSettings();
			return true; // True means added
		}
	}

	public function deleteProxy($outLDAP, $inLDAP, $inURL)
	{
		reset($this->proxies);
		if (array_key_exists("$outLDAP $inLDAP $inURL", $this->proxies))
		{
			unset($this->proxies["$outLDAP $inLDAP $inURL"]);
			$this->saveSettings();
		}
	}

	public function loadAutosyncBranches()
	{
		$branchnodes = $this->xmlDoc->getElementsByTagName("branch");
		$numbranches = $branchnodes->length;
		for ($i = 0; $i < $numbranches; $i++)
		{
			$node = $branchnodes->item($i);
			$this->autosyncbranches[$node->nodeValue] = "on";
		}
	}

	public function getAutosyncBranches()
	{
		return $this->autosyncbranches;
	}

	public function addAutosyncBranch($branch)
	{
		if (isset($this->autosyncbranches[$branch]))
		{
			return false; // False means duplicate
		}
		else
		{
			$this->autosyncbranches[$branch] = "on";
			$this->saveSettings();
			return true; // True means added
		}
	}

	public function deleteAutosyncBranch($branch)
	{
		reset($this->autosyncbranches);
		if (array_key_exists($branch, $this->autosyncbranches))
		{
			unset($this->autosyncbranches[$branch]);
			$this->saveSettings();
		}
	}


	public function containsAutosyncBranch($branch)

	{
		reset($this->autosyncbranches);
		return array_key_exists($branch, $this->autosyncbranches);
	}


	public function loadDefaultPasses()
	{
		$defaultpassnodes = $this->xmlDoc->getElementsByTagName("defaultpass");
		$numpasses = $defaultpassnodes->length;
		// Check if we need to start this list from scratch
		if ($numpasses == 0 && $this->xmlDoc->getElementsByTagName("defaultpasses")->length == 0)
		{
			$this->createDefaultPasses();
		}
		else
		{
			for ($i = 0; $i < $numpasses; $i++)
			{
				$node = $defaultpassnodes->item($i);
				$this->defaultpasses[$node->nodeValue] = $node->nodeValue;
			}
		}
	}

	public function createDefaultPasses()
	{
		$this->defaultpasses["webaccount"] = "webaccount";
		$this->defaultpasses["shellaccount"] = "shellaccount";
		$this->defaultpasses["afpaccount"] = "afpaccount";
		$this->defaultpasses["smbaccount"] = "smbaccount";
		$this->saveSettings();
	}

	public function needsToChangeAnyPasses()
	{
		if (count($this->defaultpasses) > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function needsToChangePass($name)
	{
		reset($this->defaultpasses);
		if (array_key_exists($name, $this->defaultpasses))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function changedPass($name)
	{
		reset($this->defaultpasses);
		if (array_key_exists($name, $this->defaultpasses))
		{
			unset($this->defaultpasses[$name]);
			$this->saveSettings();
		}
	}

	public function printDebug()
	{
		echo "Settings: ";
		print_r($this->settings);
		echo "Subnets: ";
		print_r($this->subnets);
		echo "Proxies: ";
		print_r($this->proxies);
		echo "AutosyncBranches: ";
		print_r($this->autosyncbranches);
		echo "Files: ";
		print_r($this->files);
	}
}

$conf = new WebadminConfig();

$admin_username = $conf->getSetting("webadminuser");
$admin_password = $conf->getSetting("webadminpass");


if ($admin_username == NULL || $admin_username == "")
{
	$admin_username = "webadmin";
	$admin_password = hash("sha256","webadmin");
	$conf->setSetting("webadminuser", $admin_username);
	$conf->setSetting("webadminpass", $admin_password);
}

if ($debug)
{
	$conf->printDebug();
}
?>
