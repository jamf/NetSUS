<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$errorMessage = "";
$statusMessage = "";

$image = "";
if (isset($_GET['image']) && $_GET['image'] != "")
{
	$image = $_GET['image'];
}

$title = "Manage properties for image: $image";

include "inc/header.php";

$netbootimgdir = "/srv/NetBoot/NetBootSP0/";

if($image != "" && isset($_POST['changeimage']))
{
	suExec("setNBIproperties ".$image." \"".$_POST['Name']."\" \"".$_POST['Description']."\" ".$_POST['Type']." ".$_POST['Index']." ".$_POST['SupportsDiskless']);
	$curimg = $conf->getSetting("netbootimage");
	$wasrunning = getNetBootStatus();
	if ($image == $curimg && $wasrunning)
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
		suExec("setnbimages ".$image);
	}
}

if ($image != "") {
	$Name = trim(suExec("getNBIproperty ".$image." Name"));
	$Description = trim(suExec("getNBIproperty ".$image." Description"));
	$Type = trim(suExec("getNBIproperty ".$image." Type"));
	$Index = trim(suExec("getNBIproperty ".$image." Index"));
	$SupportsDiskless = trim(suExec("getNBIproperty ".$image." SupportsDiskless"));
	$imageType = trim(suExec("getNBIproperty ".$image." imageType"));

	if ($Name == "") {
		$Name = str_replace(".nbi", "" , $image);
		$errorMessage = "WARNING: Unable to read NBImageInfo.plist default values are being used";
	}
	if ($Type == "") { $Type = "HTTP"; }
	if ($Index == "") { $Index = rand(1, 4095); }
	if ($SupportsDiskless == "") { $SupportsDiskless = "False"; }
	if ($imageType == "") { $imageType = "netboot"; }
}

?>

<script>
function showErr(id, valid)
{
	if (valid)
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

function validateProperties()
{
	var validName = /^[A-Za-z0-9._ +\-]{1,256}$/.test(document.getElementById("Name").value);
	var validIndex = /^\d+$/.test(document.getElementById("Index").value) && !(parseInt(document.getElementById("Index").value) < 1) && !(parseInt(document.getElementById("Index").value) > 4095);
	showErr("Name", validName);
	showErr("Index", validIndex);
	enableButton("changeimage", validName && validIndex);
}
</script>

<?php
if ($errorMessage != "")
{
	echo "<div class=\"alert alert-warning\">$errorMessage</div>";
}
else if ($statusMessage != "")
{
	echo "<div class=\"alert alert-success\">$statusMessage</div>";
}
?>

<div class="row">
	<div class="col-xs-12 col-sm-10 col-lg-8">

		<h2><?php echo $image; ?></h2>

		<hr>

		<form action="managenbi.php?image=<?php echo $image?>" method="post" name="imageProperties" id="imageProperties">

			<span class="label label-default">Choose Image</span>

			<select name="NetBootImage" id="NetBootImage" class="form-control input-sm" onChange="javascript:location.href='managenbi.php?image='+this.value">
				<?php
				$nbidircontents = scandir($netbootimgdir);
				$i = 0;
				foreach($nbidircontents as $item)
				{
					if ($item != "." && $item != ".." && is_dir($netbootimgdir.$item) && file_exists($netbootimgdir.$item."/i386/booter"))
					{
						?>
						<option value="<?php echo $item?>" <?php echo ($image == $item ? "selected=\"selected\"" : "")?>><?php echo $item?></option>
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

			<br>

			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>Image Properties</strong>
				</div>

				<div class="panel-body">
					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Network Disk</div>
						<span class="description">This name identifies the image in the Startup Disk preferences pane on client computers</span>
						<input type="text" name="Name" id="Name" class="form-control input-sm" value="<?php echo $Name; ?>" onClick="validateProperties();" onKeyUp="validateProperties();" onChange="validateProperties();" <?php if ($image == "") { echo "disabled=\"disabled\""; } ?> />
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Description</div>
						<span class="description">(Optional) Notes or other information to help you characterize the image</span>
						<textarea name="Description" id="Description" class="form-control input-sm" rows="2" onKeyUp="validateProperties();" onClick="validateProperties();" onKeyUp="validateProperties();" onChange="validateProperties();" <?php if ($image == "") { echo "disabled=\"disabled\""; } ?> /><?php echo $Description; ?></textarea>
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Make available over</div>
						<span class="description">By default, images are available over HTTP</span>
						<select name="Type" id="Type" class="form-control input-sm" onClick="validateProperties();" onChange="validateProperties();" <?php if ($image == "") { echo "disabled=\"disabled\""; } ?> />
							<option value="HTTP" <?php if ($Type == "HTTP") { echo 'selected="selected"'; } ?>>HTTP</option>
							<option value="NFS" <?php if ($Type == "NFS") { echo 'selected="selected"'; } ?>>NFS</option>
						</select>
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Image Index</div>
						<span class="description">1-4095 indicates a local image unique to this server</span>
						<input type="text" name="Index" id="Index" class="form-control input-sm" value="<?php echo $Index; ?>" onClick="validateProperties();" onKeyUp="validateProperties();" onChange="validateProperties();" <?php if ($image == "") { echo "disabled=\"disabled\""; } ?> />
					</div>

					<br>

					<div class="checkbox">
						<label>
							<input class="checkbox" type="checkbox" name="SupportsDiskless" id="SupportsDiskless" value="True" onClick="validateProperties();" onChange="validateProperties();" <?php if ($SupportsDiskless == "True") { echo "checked=\"checked\""; } ?> <?php if ($imageType != "netboot" || $image == "") { echo "disabled=\"disabled\""; } ?> />
							Make this image available for diskless booting
						</label>
					</div>
				</div>

				<div class="panel-footer">
					<input type="submit" name="changeimage" id="changeimage" class="btn btn-primary btn-sm" value=" Apply " disabled="disabled" />
				</div>

			</div>

		</form>

		<hr>
		<br>
		<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='netBoot.php'">

	</div>
</div>

<?php

include "inc/footer.php";

?>
