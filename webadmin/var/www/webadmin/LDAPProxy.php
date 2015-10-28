<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "LDAP Proxy";

include "inc/header.php";


if (isset($_POST['disableproxy']))
{
	suExec("disableproxy");
}

if (isset($_POST['enableproxy']))
{
	suExec("enableproxy");
}

if (isset($_POST['addProxy']) && isset($_POST['inLDAP']) && isset($_POST['outLDAP']) && isset($_POST['inURL'])
&& $_POST['outLDAP'] != "" && $_POST['inLDAP'] != "" && $_POST['inURL'] != "")
{
	$conf->addProxy($_POST['outLDAP'], $_POST['inLDAP'], $_POST['inURL']);
	$lpconf = file_get_contents("/var/appliance/conf/slapd.conf");
	$ldapproxies = "";
	foreach($conf->getProxies() as $key => $value)
	{
		$ldapproxies .= "database\tldap\nsuffix\t\"".$value['outLDAP']."\"\noverlay\trwm\nrwm-suffixmassage\t\"".$value['outLDAP']."\" \"".$value['inLDAP']."\"\nuri\t\"".$value['inURL']."\"\nrebind-as-user\nreadonly\tyes\n\n";
	}
	$lpconf = str_replace("##PROXIES##", $ldapproxies, $lpconf);
	suExec("touchconf \"/var/appliance/conf/slapd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/slapd.conf.new", $lpconf) === FALSE)
	{
		echo "<div class=\"errorMessage\">ERROR: Unable to update slapd.conf</div>";
	}
	$wasrunning = getLDAPProxyStatus();
	if ($wasrunning)
	{
		suExec("disableproxy");
	}
	suExec("installslapdconf");
	if ($wasrunning)
	{
		suExec("enableproxy");
	}
}

if (isset($_GET['deleteoutLDAP']) && isset($_GET['deleteinLDAP']) && isset($_GET['deleteinURL'])
&& $_GET['deleteoutLDAP'] != "" && $_GET['deleteinLDAP'] != "" && $_GET['deleteinURL'] != "")
{
	$conf->deleteProxy($_GET['deleteoutLDAP'], $_GET['deleteinLDAP'], $_GET['deleteinURL']);
	$lpconf = file_get_contents("/var/appliance/conf/slapd.conf");
	$ldapproxies = "";
	foreach($conf->getProxies() as $key => $value)
	{
		$ldapproxies .= "database\tldap\nsuffix\t\"".$value['outLDAP']."\"\noverlay\trwm\nrwm-suffixmassage\t\"".$value['outLDAP']."\" \"".$value['inLDAP']."\"\nuri\t\"".$value['inURL']."\"\nrebind-as-user\nreadonly\tyes\n\n";
	}
	$lpconf = str_replace("##PROXIES##", $ldapproxies, $lpconf);
	suExec("touchconf \"/var/appliance/conf/slapd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/slapd.conf.new", $lpconf) === FALSE)
	{
		echo "<div class=\"errorMessage\">ERROR: Unable to update slapd.conf</div>";
	}
	$wasrunning = getLDAPProxyStatus();
	if ($wasrunning)
	{
		suExec("disableproxy");
	}
	suExec("installslapdconf");
	if ($wasrunning)
	{
		suExec("enableproxy");
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>


<style>         
  <!--       
	@media (max-width: 600px) {

		tr:first-child { display: none; }
	
	  td:nth-of-type(1):before { content: "Exposed Distinguished Name";}
   
	  td:nth-of-type(2):before { content: "Real Distinguished Name";}
	  
	  td:nth-of-type(3):before { content: "LDAP URL";}
   
	}
 -->	
</style> 

<h2>LDAP Proxy</h2>

<div id="form-wrapper">

	<form action="LDAPProxy.php" method="post" name="LDAPProxy" id="LDAPProxy">

		<div id="form-inside">

			<div class="labelDescriptionWrapper">
				<span class="label">Proxies</span>
				<span class="description">Proxies that will be available for use.  You can connect to several directories or to several specific OU's in one directory.</span>
			</div>


			<span class="label">Exposed Distinguished Name</span>
			<span class="description">Example: DC=jss,DC=corp</span>
			<input type="text" name="outLDAP" id="outLDAP" value="" />
			<br>

			<span class="label">Real Distinguished Name</span>
			<span class="description">Example: DC=myorg,DC=corp</span>
			<input type="text" name="inLDAP" id="inLDAP" value="" />
			<br>

			<span class="label">LDAP URL</span>
			<span class="description">Example: ldaps://ldap.myorg.com:636/</span>
			<input type="text" name="inURL" id="inURL" value="" />
			<input type="submit" name="addProxy" id="addProxy" class="insideActionButton" value="Add" />
			<br>
			<table class="branchesTable">
				<tr>
					<th>Exposed Distinguished Name</th>
					<th>Real Distinguished Name</th>
					<th>LDAP URL</th>
					<th></th>
				</tr>
				<?php foreach($conf->getProxies() as $key => $value) { ?>
				<tr class="<?php echo ($key % 2 == 0 ? "object0" : "object1")?>">
					<td><?php echo $value['outLDAP']?></td>
					<td><?php echo $value['inLDAP']?></td>
					<td><?php echo $value['inURL']?></td>
					<td><a href="LDAPProxy.php?service=LDAPProxy&deleteoutLDAP=<?php echo urlencode($value['outLDAP'])?>&deleteinLDAP=<?php echo urlencode($value['inLDAP'])?>&deleteinURL=<?php echo urlencode($value['inURL'])?>">Delete</a>
				</tr>
				<?php } ?>
			</table>

			<span>LDAP Proxy Status: </span>
			<?php
			if (getLDAPProxyStatus())
			{
				echo "<img style=\"margin-right:10px;\" src=\"images/active.gif\" alt=\"LDAP Proxy Active\"/>";
			}
			else
			{
				echo "<img style=\"margin-right:10px;\" src=\"images/inactive.gif\" alt=\"LDAP Proxy Inactive\"/>";
			}
			?>

			<?php
			if (getLDAPProxyStatus())
			{
				?>
				<input type="submit" class="insideActionButton" value="Disable LDAP Proxy" name="disableproxy" />
			<?php
			}
			else
			{
				?>
				<input type="submit" class="insideActionButton" value="Enable LDAP Proxy" name="enableproxy" onClick="javascript:return toggle_creating('enabling')" />
				<?php
			}
			?>
		</div> <!-- end #form-inside -->

	</form> <!-- end form NetBoot -->


</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>



