<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "NetBoot Server";

include "inc/header.php";

$currentIP = trim(getCurrentIP());
$currentNetmask = trim(getCurrentNetmask());
$currentSubnet = trim(getNetAddress($currentIP, $currentNetmask));

$netbootimgdir = "/srv/NetBoot/NetBootSP0/";

if (isset($_POST['NetBootImage']))
{
	$wasrunning = getNetBootStatus();
	$nbi = $_POST['NetBootImage'];
	if ($nbi != "")
	{
		$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
		$nbsubnets = "";
		foreach($conf->getSubnets() as $key => $value)
		{
			$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
		}
		$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
		suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
		if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
		{
			echo "<div class=\"errorMessage\">ERROR: Unable to update dhcpd.conf</div>";
			 
		}
		suExec("disablenetboot");
		suExec("installdhcpdconf");
		
		if ($wasrunning || isset($_POST['enablenetboot']))
		{
			suExec("setnbimages ".$nbi);
		}
		$conf->setSetting("netbootimage", $nbi);
	}
}

if (isset($_POST['disablenetboot']))
{
	suExec("disablenetboot");
}

if (isset($_POST['addsubnet']) && isset($_POST['subnet']) && isset($_POST['netmask'])
&& isValidIPAddress($_POST['subnet']) && isValidNetmask($_POST['netmask']) && !isLoopbackAddress($_POST['subnet']))
{
	$conf->addSubnet(getNetAddress($_POST['subnet'], $_POST['netmask']), $_POST['netmask']);
	// 	echo "<script type=\"text/javascript\">\nchangeServiceType('NetBoot');\n</script>\n";
	$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
	$nbsubnets = "";
	foreach($conf->getSubnets() as $key => $value)
	{
		$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
	}
	$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
	suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
	{
		echo "<div class=\"errorMessage\">ERROR: Unable to update dhcpd.conf</div>";
	}
	$wasrunning = getNetBootStatus();
	if ($wasrunning)
	{
		suExec("disablenetboot");
	}
	suExec("installdhcpdconf");
	if ($wasrunning)
	{
		suExec("setnbimages ".$conf->getSetting("netbootimage"));
	}
}

if (isset($_GET['deleteSubnet']) && isset($_GET['deleteNetmask'])
&& $_GET['deleteSubnet'] != "" && $_GET['deleteNetmask'] != "")
{
	$conf->deleteSubnet($_GET['deleteSubnet'], $_GET['deleteNetmask']);
	$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
	$nbsubnets = "";
	foreach($conf->getSubnets() as $key => $value)
	{
		$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
	}
	$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
	suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
	{
		echo "<div class=\"errorMessage\">ERROR: Unable to update dhcpd.conf</div>";
	}
	$wasrunning = getNetBootStatus();
	if ($wasrunning)
	{
		suExec("disablenetboot");
	}
	suExec("installdhcpdconf");
	if ($wasrunning)
	{
		suExec("setnbimages ".$conf->getSetting("netbootimage"));
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>

<script>
function validateSubnet()
{
	if (document.getElementById("subnet").value != "" && document.getElementById("netmask").value != "")
		document.getElementById("addsubnet").disabled = false;
	else
		document.getElementById("addsubnet").disabled = true;
}
window.onload = validateSubnet;
</script>

<h2>NetBoot Server</h2>

<div id="form-wrapper">

	<form action="netBoot.php" method="post" name="NetBoot" id="NetBoot">

		<?php if ($conf->getSetting("todoenrolled") != "true") { ?>
		<span class="label label-default">New NetBoot Image</span>

		<span class="description">Refresh this page after uploading a NetBoot image. The NetBoot folder name cannot contain spaces</span>
		<input type="button" name="uploadnbi" id="uploadnbi" class="btn btn-sm btn-primary" value="Upload NetBoot Image" onClick="javascript: return goTo(true, 'smbCtl.php?start=true');"/>

		<span class="label label-default">NetBoot Image</span>

		<span class="description">NetBoot image that computers boot to</span>
		<select style="min-width:100px;" name="NetBootImage" id="NetBootImage" onChange="javascript:ajaxPost('ajax.php?service=NetBoot', 'NetBootImage='+this.value);">
			<?php
			$nbidircontents = scandir($netbootimgdir);
			$curimg = $conf->getSetting("netbootimage");
			$i = 0;
			foreach($nbidircontents as $item)
			{
				if ($item != "." && $item != ".." && is_dir($netbootimgdir.$item))
				{
				?>
			<option value="<?php echo $item?>" <?php echo ($curimg == $item ? "selected=\"selected\"" : "")?>><?php echo $item?></option>
				<?php
				}
				$i++;
			}

			if ($i == 0)
			{
				echo "<option value=\"\">---</option>\n";
			}

			?>
		</select>

		<span class="label label-default">Subnets</span>

		<span class="description">Subnets on which to listen for the NetBoot image. One of the subnets must include the IP address of the NetBoot server</span>

		<span>Subnet</span>
		<input type="text" name="subnet" id="subnet" value="<?php if (!array_key_exists($currentSubnet." ".$currentNetmask, $conf->getSubnets())) { echo $currentSubnet; } ?>" onKeyUp="validateSubnet();" onChange="validateSubnet();" />

		<span class="label label-default">Netmask</span>

		<input type="text" name="netmask" id="netmask" value="<?php if (!array_key_exists($currentSubnet." ".$currentNetmask, $conf->getSubnets())) { echo $currentNetmask; } ?>" onKeyUp="validateSubnet();" onChange="validateSubnet();" />
		<input type="submit" name="addsubnet" id="addsubnet" class="btn btn-sm btn-primary" value="Add" disabled="disabled" />

		<br>
		<br>

		<table class="table-striped table-bordered table-condensed">
			<thead>
				<tr>
					<th>Subnet</th>
					<th>Netmask</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($conf->getSubnets() as $key => $value) { ?>
				<tr class="<?php echo ($key % 2 == 0 ? "object0" : "object1")?>">
					<td><?php echo $value['subnet']?></td>
					<td><?php echo $value['netmask']?></td>
					<td><a href="netBoot.php?service=NetBoot&deleteSubnet=<?php echo urlencode($value['subnet'])?>&deleteNetmask=<?php echo urlencode($value['netmask'])?>">Delete</a>
				</tr>
				<?php } ?>
			</tbody>
		</table>

		<br>

		<span>NetBoot Status: </span>
		<?php
		if (getNetBootStatus())
		{
			echo "<img style=\"margin-right:10px;\" src=\"images/active.gif\" alt=\"NetBoot Active\"/>";
		}
		else
		{
			echo "<img style=\"margin-right:10px;\" src=\"images/inactive.gif\" alt=\"NetBoot Inactive\"/>";
		}
		?>

		<?php
		if (getNetBootStatus())
		{
			?>
			<input type="submit" class="insideActionButton" value="Disable NetBoot" name="disablenetboot" />
		<?php
		}
		else
		{
			?>
			<input type="submit" class="btn btn-sm btn-primary" value="Enable NetBoot" name="enablenetboot" onClick="javascript:return toggle_creating('enabling')" />
			<?php
		}
		?>

	</form> <!-- end form NetBoot -->
	<?php 
	}
	else { ?>
	<tr><td><h3>Managed by the JSS</h3></td></tr>
	<?php }?>

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>



