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

if (isset($_POST['netbootName']))
{
	$conf->setSetting("netbootname", $_POST['netbootName']);
}

if ((isset($_POST['enablenetboot']) || isset($_POST['changenetboot'])) && empty($subnetcheck))
{
	echo "<div class=\"alert alert-danger\">ERROR: Ensure you added a proper Subnet and Netmask</div>";
}

if ((isset($_POST['enablenetboot']) || isset($_POST['changenetboot'])) && !isset($_POST['NetBootImage']))
{
	echo "<div class=\"alert alert-danger\">ERROR: Ensure you have uploaded a properly configured NetBoot image</div>";
}


if (isset($_POST['NetBootImage']))
{
	$wasrunning = getNetBootStatus();
	$netbootname = $conf->getSetting("netbootname");
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

		if ($wasrunning || isset($_POST['enablenetboot']) || isset($_POST['changenetboot'])) {
			suExec("setnbimages " . $nbi . " " . $netbootname);
		}
		$conf->setSetting("netbootimage", $nbi);

		if ((isset($_POST['enablenetboot']) || isset($_POST['changenetboot'])) && !getNetBootStatus() && !empty($subnetcheck)) {
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

<div class="row">
	<div class="col-xs-12 col-sm-10 col-lg-8">

		<form action="netBoot.php" method="post" name="NetBoot" id="NetBoot">

			<hr>

			<br>

			<?php
			if (getNetBootStatus())
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

			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>NetBoot Image and Name</strong>
				</div>
				<div class="panel-body">
					<div class="input-group">
						<div class="input-group-addon no-background">Image</div>
						<span class="description">NetBoot image that computers boot to</span>
						<select name="NetBootImage" id="NetBootImage" class="form-control input-sm" onChange="javascript:ajaxPost('ajax.php?service=NetBoot', 'NetBootImage='+this.value);">
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
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background">Name</div>
						<span class="description">(Optional) NetBoot name to appear on receiving boot devices. Defaults to the .nbi folder name. Cannot contain spaces</span>
						<input type="text" name="netbootName" id="netbootName" class="form-control input-sm" value="<?php echo $conf->getSetting("netbootname")?>" />
					</div>
				</div>

				<div class="panel-footer">
					<input type="submit" name="changenetboot" id="changenetboot" class="btn btn-primary btn-sm" value="Change"/>
				</div>

			</div>

			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>Netboot Subnet and Netmask</strong>
				</div>

				<div class="panel-body">

					<div class="input-group">
						<div class="input-group-addon no-background">Subnet</div>
						<span class="description">One of the subnets must include the IP address of the NetBoot server</span>
						<input type="text" name="subnet" id="subnet" class="form-control input-sm" value="<?php if (!array_key_exists($currentSubnet." ".$currentNetmask, $conf->getSubnets())) { echo $currentSubnet; } ?>" onKeyUp="validateSubnet();" onChange="validateSubnet();" />
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background">Netmask</div>
						<input class="form-control round-corners input-sm" type="text" name="netmask" id="netmask" value="<?php if (!array_key_exists($currentSubnet." ".$currentNetmask, $conf->getSubnets())) { echo $currentNetmask; } ?>" onKeyUp="validateSubnet();" onChange="validateSubnet();" />
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
							<td><a href="netBoot.php?service=NetBoot&deleteSubnet=<?php echo urlencode($value['subnet'])?>&deleteNetmask=<?php echo urlencode($value['netmask'])?>">Delete</a>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

		</form> <!-- end form NetBoot -->
		<?php
		}
		else { ?>
		<tr><td><h3>Managed by the JSS</h3></td></tr>
		<?php }?>

	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>



