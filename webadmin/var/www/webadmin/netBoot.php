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
$subnetcheck = $conf->getSubnets();

if (isset($_POST['enablenetboot']) && empty($subnetcheck))
{
	echo "<div class=\"alert alert-danger\">ERROR: Ensure you added a proper Subnet and Netmask</div>";
}

if (isset($_POST['enablenetboot']) && (!isset($_POST['NetBootImage']) || $_POST['NetBootImage'] == ""))
{
	echo "<div class=\"alert alert-danger\">ERROR: Ensure you have uploaded and selected a properly configured NetBoot image</div>";
}


if (isset($_POST['NetBootImage']) && $_POST['NetBootImage'] != "")
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
			echo "<div class=\"alert alert-danger\">ERROR: Unable to update dhcpd.conf</div>";

		}
		suExec("disablenetboot");
		suExec("installdhcpdconf");

		if ($wasrunning || isset($_POST['enablenetboot'])) {
			suExec("setnbimages " . $nbi);
		}
		$conf->setSetting("netbootimage", $nbi);

		if (isset($_POST['enablenetboot']) && !getNetBootStatus() && !empty($subnetcheck)) {
			echo "<div class=\"alert alert-danger\">ERROR: Unable to start NetBoot service. Ensure your .nbi directory is properly configured</div>";
		}
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
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update dhcpd.conf</div>";
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
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update dhcpd.conf</div>";
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

if (!isset($_POST['disablenetboot']) && getNetBootStatus())
{
	$tftp_running = (trim(suExec("gettftpstatus")) === "true");
	$nfs_running = (trim(suExec("getnfsstatus")) === "true");
	$afp_running = (trim(suExec("getafpstatus")) === "true");
	if (!$tftp_running)
	{
		echo "<div class=\"alert alert-danger\">ERROR: TFTP is not running, restart NetBoot</div>";
	}
	if (!$nfs_running)
	{
		echo "<div class=\"alert alert-danger\">ERROR: NFS is not running, restart NetBoot</div>";
	}
	if (!$afp_running)
	{
		echo "<div class=\"alert alert-warning\">WARNING: AFP is not running, diskless will be unavailable</div>";
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>

<script>
function showErr(id, valid)
{
	if (valid || document.getElementById(id).value == "")
	{
		document.getElementById(id).style.borderColor = "";
		document.getElementById(id).style.backgroundColor = "";
	}
	else
	{
		document.getElementById(id).style.borderColor = "#a94442";
		document.getElementById(id).style.backgroundColor = "#f2dede";
	}
}
function enableButton(id, enable)
{
	document.getElementById(id).disabled = !enable;
}

function validateSubnet()
{
	var validSubnet = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/.test(document.getElementById("subnet").value);
	var validNetmask = /^((255|254|252|248|240|224|192|128|0?)\.){3}(255|254|252|248|240|224|192|128|0)$/.test(document.getElementById("netmask").value);
	showErr("subnet", validSubnet);
	showErr("netmask", validNetmask);
	enableButton("addsubnet", validSubnet && validNetmask);
}
</script>

<h2>NetBoot Server</h2>

<div class="row">
	<div class="col-xs-12 col-sm-10 col-lg-8">

		<form action="netBoot.php" method="post" name="NetBoot" id="NetBoot">

			<hr>

			<br>

			<?php
			if (!isset($_POST['disablenetboot']) && getNetBootStatus())
			{
				echo "<div class=\"alert alert-success alert-with-button\">
						<span>Enabled</span>
						<input type=\"submit\" class=\"btn btn-sm btn-success pull-right\" value=\"Disable NetBoot\" name=\"disablenetboot\" />
					</div>";
			}
			else
			{
				echo "<div class=\"alert alert-danger alert-with-button\">
						<span>Disabled</span>
						<input type=\"submit\" class=\"btn btn-sm btn-danger pull-right\" value=\"Enable NetBoot\" name=\"enablenetboot\" onClick=\"javascript:return toggle_creating('enabling')\" />
					</div>";
			}
			?>

			<?php if ($conf->getSetting("todoenrolled") != "true") { ?>

			<span class="label label-default">Upload NetBoot Image</span>
			<span class="description">Refresh this page after uploading a NetBoot image. The NetBoot folder name cannot contain spaces</span>
			<input type="button" name="uploadnbi" id="uploadnbi" class="btn btn-sm btn-primary" value="Upload NetBoot Image" onClick="javascript: return goTo(true, 'smbCtl.php?start=true');"/>

			<br><br>

			<div class="table-responsive panel panel-default">
				<div class="panel-heading">
					<strong>NetBoot Images</strong>
				</div>
				<table class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
							<th>Enable</th>
							<th>Image</th>
							<th>Name</th>
							<th>Index</th>
							<th>Type</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$nbidircontents = scandir($netbootimgdir);
						$curimg = $conf->getSetting("netbootimage");
						foreach($nbidircontents as $item)
						{
							$i = 0;
							if ($item != "." && $item != ".." && is_dir($netbootimgdir.$item) && file_exists($netbootimgdir.$item."/i386/booter"))
							{
								$Name = trim(suExec("getNBIproperty ".$item." Name"));
								if ($Name == "") { $Name = str_replace(".nbi", "" , $item); }
								$Index = trim(suExec("getNBIproperty ".$item." Index"));
								if ($Index == "") { $Index = "526"; }
								$Type = trim(suExec("getNBIproperty ".$item." Type"));
								if ($Type == "") { $Type = "HTTP"; }
								?>
						<tr>
							<td><input type="radio" name="Enabled[<?php echo $i; ?>]" id="Enabled[<?php echo $i; ?>]" value="<?php echo $item ?>" <?php if ($curimg == $item) { echo "checked=\"checked\""; $NetBootImage = $item; } ?> onChange="document.getElementById('NetBootImage').value = this.value; javascript:ajaxPost('ajax.php?service=NetBoot', 'NetBootImage='+this.value);"/></td>
							<td><a href="managenbi.php?image=<?php echo $item?>"><?php echo $item?></a></td>
							<td><?php echo $Name ?></td>
							<td><?php echo $Index ?></td>
							<td><?php echo $Type ?></td>
						</tr>
								<?php
							}
							$i++;
						}
						?>
					</tbody>
				</table>
			</div>

			<input type="hidden" id="NetBootImage" name="NetBootImage" value="<?php echo $NetBootImage; ?>" />

			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>Netboot Subnet and Netmask</strong>
				</div>

				<div class="panel-body">

					<div class="input-group">
						<div class="input-group-addon no-background">Subnet</div>
						<span class="description">One of the subnets must include the IP address of the NetBoot server</span>
						<input type="text" name="subnet" id="subnet" class="form-control input-sm" value="<?php if (!array_key_exists($currentSubnet." ".$currentNetmask, $conf->getSubnets())) { echo $currentSubnet; } ?>" onClick="validateSubnet();" onKeyUp="validateSubnet();" onChange="validateSubnet();" />
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background">Netmask</div>
						<input type="text" class="form-control input-sm" name="netmask" id="netmask" value="<?php if (!array_key_exists($currentSubnet." ".$currentNetmask, $conf->getSubnets())) { echo $currentNetmask; } ?>" onClick="validateSubnet();" onKeyUp="validateSubnet();" />
					</div>

				</div>

				<div class="panel-footer">
					<input type="submit" name="addsubnet" id="addsubnet" class="btn btn-primary btn-sm" value="Add" disabled="disabled" />
				</div>
			</div>

			<div class="table-responsive panel panel-default">
				<table class="table table-striped table-bordered table-condensed">
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
							<td><a href="netBoot.php?service=NetBoot&deleteSubnet=<?php echo urlencode($value['subnet'])?>&deleteNetmask=<?php echo urlencode($value['netmask'])?>">Delete</a></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

		</form> <!-- end form NetBoot -->
		<?php
		}
		else { ?>
		<tr><td><h3>Managed by Jamf Pro</h3></td></tr>
		<?php }?>

	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>



