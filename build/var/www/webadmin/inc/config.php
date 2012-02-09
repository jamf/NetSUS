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
	private $autosyncbranches;
	private $defaultpasses;
	
	function __construct()
	{
		$this->settings = array();
		$this->subnets = array();
		$this->autosyncbranches = array();
		$this->defaultpasses = array();
		if(!file_exists(CONF_FILE_PATH) || ($this->xmlDoc = DOMDocument::load(CONF_FILE_PATH)) == FALSE)
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
		$this->saveSettings();
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
		}
	}
	
	public function loadSettings()
	{
		foreach($this->topElement->childNodes as $curNode)
		{
			if ($curNode->nodeName == "netbootsubnets" || $curNode->nodeName == "autosyncbranches" || $curNode->nodeName == "defaultpasses")
			{
				continue;
			}
			
			if ($curNode != NULL && $curNode->nodeName != NULL && $curNode->nodeName != "" && $curNode->nodeName != "#comment")
			{
				$this->settings[$curNode->nodeName] = $curNode->nodeValue;
			}
		}
		
		$this->loadSubnets();
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
			echo("Could not save settings");
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
		}
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
		}
	}
	
	public function containsAutosyncBranch($branch)
	{
		reset($this->autosyncbranches);
		return array_key_exists($branch, $this->autosyncbranches);
	}
	
	public function printDebug()
	{
		echo "Settings: ";
		print_r($this->settings);
		echo "Subnets: ";
		print_r($this->subnets);
		echo "AutosyncBranches: ";
		print_r($this->autosyncbranches);
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

?>
