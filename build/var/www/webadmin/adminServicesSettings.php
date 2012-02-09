<?php

$netbootimgdir = "/srv/NetBoot/NetBootSP0/";

$currentIP = getCurrentIP();

if ($conf->getSetting("susbaseurl") == NULL || $conf->getSetting("susbaseurl") == "")
{
	if ($_SERVER['HTTP_HOST'] != "")
	{
		$conf->setSetting("susbaseurl", "http://".$_SERVER['HTTP_HOST']."/");
	}
	elseif ($_SERVER['SERVER_NAME'] != "")
	{
		$conf->setSetting("susbaseurl", "http://".$_SERVER['SERVER_NAME']."/");
	}
	else {
		$conf->setSetting("susbaseurl", "http://".getCurrentHostname()."/");
	}
}

if ($conf->getSetting("syncschedule") == NULL || $conf->getSetting("syncschedule") == "")
{
	$conf->setSetting("syncschedule", "Off");
}

$syncschedule = $conf->getSetting("syncschedule");

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
		print suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
		if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
		{
			echo "Unable to update dhcpd.conf";
			 
		}
		print suExec("disablenetboot");
		print suExec("installdhcpdconf");
		
		if ($wasrunning || isset($_POST['enablenetboot']))
		{
			print suExec("setnbimages ".$nbi);
		}
		$conf->setSetting("netbootimage", $nbi);
	}
}

if (isset($_POST['disablenetboot']))
{
	print suExec("disablenetboot");
}

if (isset($_POST['smbpass']))
{
	$smbpw = $_POST['smbpass1'];
	if ($smbpw != "")
	{
		print suExec("resetsmbpw ".$smbpw);
		$conf->changedPass("smbaccount");
	}
}
    
if (isset($_POST['afppass']))
    {
        $smbpw = $_POST['afppass1'];
        if ($smbpw != "")
        {
            print suExec("resetafppw ".$smbpw);
            $conf->changedPass("afpaccount");
        }
    }

if (isset($_POST['addsubnet']) && isset($_POST['subnet']) && isset($_POST['netmask'])
&& $_POST['subnet'] != "" && $_POST['netmask'] != "")
{
	$conf->addSubnet($_POST['subnet'], $_POST['netmask']);
	// 	echo "<script type=\"text/javascript\">\nchangeServiceType('NetBoot');\n</script>\n";
	$nbconf = file_get_contents("/var/appliance/conf/dhcpd.conf");
	$nbsubnets = "";
	foreach($conf->getSubnets() as $key => $value)
	{
		$nbsubnets .= "subnet ".$value['subnet']." netmask ".$value['netmask']." {\n\tallow unknown-clients;\n}\n\n";
	}
	$nbconf = str_replace("##SUBNETS##", $nbsubnets, $nbconf);
	print suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
	{
		echo "Unable to update dhcpd.conf";
	}
	$wasrunning = getNetBootStatus();
	if ($wasrunning)
	{
		suExec("disablenetboot");
	}
	print suExec("installdhcpdconf");
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
	print suExec("touchconf \"/var/appliance/conf/dhcpd.conf.new\"");
	if(file_put_contents("/var/appliance/conf/dhcpd.conf.new", $nbconf) === FALSE)
	{
		echo "Unable to update dhcpd.conf";
	}
	$wasrunning = getNetBootStatus();
	if ($wasrunning)
	{
		suExec("disablenetboot");
	}
	print suExec("installdhcpdconf");
	if ($wasrunning)
	{
		suExec("setnbimages ".$conf->getSetting("netbootimage"));
	}
}

if (isset($_POST['addbranch']))
{
	if(isset($_POST['branchname']) && $_POST['branchname'] != "")
	{
		$branchname = $_POST['branchname'];
		$res = trim(suExec("createBranch $branchname"));
		if ($res != "")
		{
			echo "Unable to create the SUS branch &quot;$branchname&quot; ($res)<br/>\n";
		}
		else
		{
			echo "Created SUS branch &quot;$branchname&quot;<br/>\n";
		}
	}
	else
	{
		echo "Please specify a SUS branch name<br/>\n";
	}
}

if (isset($_GET['deletebranch']) && $_GET['deletebranch'] != "")
{
	suExec("deleteBranch \"".$_GET['deletebranch']."\"");
}

if (isset($_POST['setbaseurl']) && $_POST['baseurl'] != "")
{
	$baseurl = $_POST['baseurl'];
	$conf->setSetting("susbaseurl", $_POST['baseurl']);
	if ($conf->getSetting("mirrorpkgs") == "true")
		{
			suExec("setbaseurl ".$conf->getSetting("susbaseurl"));
		}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################
?>

<script type="text/javascript">
function validateSubnet()
{
	if (document.getElementById("subnet").value != "" && document.getElementById("netmask").value != "")
		document.getElementById("addsubnet").disabled = false;
	else
		document.getElementById("addsubnet").disabled = true;
}
function validatePW()
{
	if (document.getElementById("smbpass1").value != "" && document.getElementById("smbpass2").value != "" && document.getElementById("smbpass1").value == document.getElementById("smbpass2").value)
		document.getElementById("smbpass").disabled = false;
	else
		document.getElementById("smbpass").disabled = true;
}

function validateafpPW()
{
	if (document.getElementById("afppass1").value != "" && document.getElementById("afppass2").value != "" && document.getElementById("afppass1").value == document.getElementById("afppass2").value && document.getElementById("afppass1").value.indexOf("@") == -1)
		document.getElementById("afppass").disabled = false;
	else
		document.getElementById("afppass").disabled = true;
}

function validateField(fieldid, buttonid)
{
	if (document.getElementById(fieldid).value != "")
		document.getElementById(buttonid).disabled = false;
	else
		document.getElementById(buttonid).disabled = true;
}

</script>

<?
if(isset($_GET['service']))
{
	$actionappend = "?service=".$_GET['service'];
}
else
{
	$actionappend = "";
}
?>

<form action="admin.php<?=$actionappend?>" method="post" name="Services" id="Services">
	<input type="hidden" name="userAction" value="Services"> <br>
	<table width="100%" id="payloads" border=1 class="objectList">
		<tr class="object0">
			<td width="140" valign="top">
				<ul class="ipcu">
					<!--  Display the vertical tabs for Settings -->

					<li class="<?=(isset($_GET['service']) && $_GET['service'] == "SUS" ? "current" : "")?>" id="SUS">
						<a href="javascript:changeServiceType('SUS')">
						<span>
							<img class="centerIMG" src="images/services/swu.png" width="28" height="28">
							<strong>SUS</strong>
						</span>
						</a>
					</li>

					<li class="<?=(isset($_GET['service']) && $_GET['service'] == "NetBoot" ? "current" : "")?>" id="NetBoot">
						<a href="javascript:changeServiceType('NetBoot')">
						<span>
							<img class="centerIMG" src="images/services/netboot.png" width="28" height="28">
							<strong>NetBoot</strong>
						</span>
						</a>
					</li>

					<li class="<?=(isset($_GET['service']) && $_GET['service'] == "AFP" ? "current" : "")?>" id="AFP">
						<a href="javascript:changeServiceType('AFP')">
						<span>
							<img class="centerIMG" src="images/services/sharing.png" width="28" height="28">
							<strong>AFP</strong>
						</span>
						</a>
					</li>

					<li class="<?=(isset($_GET['service']) && $_GET['service'] == "SMB" ? "current" : "")?>" id="SMB">
						<a href="javascript:changeServiceType('SMB')">
						<span>
							<img class="centerIMG" src="images/services/sharing.png" width="28" height="28">
							<strong>SMB</strong>
						</span>
						</a>
					</li>

				</ul> <!-- End Services tabs -->
			</td>
			<td valign="top" style="min-width: 450px;">
				<div id="restarting">
					<br>
					<table style="border: 0px;" class="formLabel">
						<tr>
							<td><img src="images/progress.gif" border=0>
							</td>
							<td><b>Restarting...</b>
							</td>
						</tr>
					</table>
					<br>
				</div>
				<table id="SMBContent" class="adminService" <?=(isset($_GET['service']) && $_GET['service'] == "SMB" ? "style=\"display:block;\"" : "")?>>
					<tr>
						<td align="left" nowrap colspan="2"><font class="formLabel">SMB Service Control</font></td>
					</tr>
					<tr>
						<td><input type="button" value="Restart SMB" onClick="javascript: return goTo(toggle_visibility('restarting', 'SMB'), 'smbCtl.php?restart=true');"/></td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td align="left" nowrap colspan="2"><font class="formLabel">Change SMB Password</font></td>
					</tr>
					<tr>
						<td style="text-align: right;"><label for="smbpass1">New Password:</label>
						</td>
						<td><input type="password" name="smbpass1" id="smbpass1" value=""
							onKeyUp="validatePW();" onChange="validatePW();" style="width: 100%;" /></td>
					</tr>
					<tr>
						<td style="text-align: right;"><label for="smbpass2">Confirm New Password:</label></td>
						<td><input type="password" name="smbpass2" id="smbpass2" value=""
							onKeyUp="validatePW();" onChange="validatePW();" style="width: 100%;" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="text-align: right;"><input type="submit" name="smbpass"
							id="smbpass" value="Change SMB Password" disabled="disabled" /></td>
					</tr>
				</table>
				<table id="AFPContent" class="adminService" <?=(isset($_GET['service']) && $_GET['service'] == "AFP" ? "style=\"display:block;\"" : "")?>>
					<tr>
						<td align="left" nowrap colspan="2"><font class="formLabel">AFP Service Control</font></td>
					</tr>
					<tr>
						<td><input type="button" value="Restart AFP" onClick="javascript: return goTo(toggle_visibility('restarting', 'AFP'), 'afpCtl.php?restart=true');"/></td>
					</tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" nowrap colspan="2"><font class="formLabel">Change AFP Password</font></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label for="afppass1">New Password:</label>
                        </td>
                        <td><input type="password" name="afppass1" id="afppass1" value="" onKeyUp="validateafpPW();" onChange="validateafpPW();" style="width: 100%;"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label for="afppass2">Confirm New Password:</label></td>
                        <td><input type="password" name="afppass2" id="afppass2" value="" onKeyUp="validateafpPW();" onChange="validateafpPW();"  style="width: 100%;"/></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: right;"><input type="submit" name="afppass"  id="afppass" value="Change AFP Password" disabled="disabled" /></td>
                    </tr>

				</table>
				<table id="NetBootContent" class="adminService" <?=(isset($_GET['service']) && $_GET['service'] == "NetBoot" ? "style=\"display:block;\"" : "")?>>
					<tr>
						<td align="left" nowrap><font class="formLabel">Upload New NetBoot Image</font></td>
					</tr>
					<tr>
						<td><input type="button" name="uploadnbi" id="uploadnbi" 
								value="Upload NetBoot Image" onClick="javascript: return goTo(true, '<?="smb://".$currentIP."/NetBoot"?>');"/></td>
					</tr>
					<tr>
						<td style="font-size: 8pt;" colspan="3"><span style="font-weight: bold; font-style: italic;">Note:</span><br>
						Refresh this page after uploading a NetBoot image.<br>
						The NetBoot folder name can not contain spaces.</td>
					</tr>						<td><br/></td>
					</tr>
					<tr>
						<td align="left" nowrap><font class="formLabel">Choose NetBoot Image</font></td>
					</tr>
					<tr>
						<td style="text-align: left;" colspan="3">
							<select name="NetBootImage" id="NetBootImage" style="width:100%;" onChange="javascript:ajaxPost('admin.php?service=NetBoot', 'NetBootImage='+this.value);">
								<?
								$nbidircontents = scandir($netbootimgdir);
								$curimg = $conf->getSetting("netbootimage");
								$i = 0;
								foreach($nbidircontents as $item)
								{
									if ($item != "." && $item != ".." && is_dir($netbootimgdir.$item))
									{
									?>
								<option value="<?=$item?>" <?=($curimg == $item ? "selected=\"selected\"" : "")?>><?=$item?></option>
									<?
									}
									$i++;
								}
								
								if ($i == 0)
								{
									echo "<option value=\"\">---</option>\n";
								}

								?>
							</select>
						</td>
					</tr>
					<tr>
						<td><br/></td>
					</tr>

					<tr>
						<td align="left" nowrap><font class="formLabel">Listen on these subnets:</font></td>
					</tr>
					<tr>
						<td><label for="subnet">Subnet</label><input type="text" name="subnet" id="subnet" 
							value="" onKeyUp="validateSubnet();" onChange="validateSubnet();" /></td>
						<td><label for="netmask">Netmask</label><input type="text" name="netmask" id="netmask" 
							value="" onKeyUp="validateSubnet();" onChange="validateSubnet();" /></td>
						<td><input type="submit" name="addsubnet" id="addsubnet" value="Add Subnet" disabled="disabled" /></td>
					</tr>
					<?

					foreach($conf->getSubnets() as $key => $value)
					{
						?>
					<tr>
						<td><?=$value['subnet']?></td>
						<td><?=$value['netmask']?></td>
						<td><a href="admin.php?service=NetBoot&deleteSubnet=<?=urlencode($value['subnet'])?>&deleteNetmask=<?=urlencode($value['netmask'])?>">Delete</a>
						</td>
					</tr>
					<?
					}
					?>
					<tr>
						<td><br/></td>
					</tr>
					<tr>
						<td align="left" nowrap><font class="formLabel">NetBoot Service Control</font></td>
					</tr>
					<tr>
						<td style="text-align: left;">NetBoot Status:
						<?
						if (getNetBootStatus())
						{
							echo "<img src=\"images/active.gif\" alt=\"NetBoot Active\"/>";
						}
						else
						{
							echo "<img src=\"images/inactive.gif\" alt=\"NetBoot Inactive\"/>";
						}
						?>
						</td>
						<td style="text-align: right;">
						<?
						if (getNetBootStatus())
						{
							?>
							<input type="submit" value="Disable NetBoot" name="disablenetboot" />
						<?
						}
						else
						{
							?>
							<input type="submit" value="Enable NetBoot" name="enablenetboot" onClick="javascript:return toggle_creating('enabling')" />
							<?
						}
						?>
						</td>
					</tr>
				</table>
				<table id="SUSContent" class="adminService" <?=(isset($_GET['service']) && $_GET['service'] == "SUS" ? "style=\"display:block;\"" : "")?>>
					<tr>
						<td align="left" nowrap><font class="formLabel">SUS Settings</font></td>
					</tr>
					<tr>
						<td nowrap><label for="baseurl">Base URL:</label>
							<input type="text" name="baseurl" id="baseurl" style="min-width: 50%;"  
							value="<?=$conf->getSetting("susbaseurl")?>" onKeyUp="validateField('baseurl', 'setbaseurl');" onChange="validateField('baseurl', 'setbaseurl');"/>
							<input type="submit" name="setbaseurl" id="setbaseurl" value="Change Base URL" disabled="disabled" /></td>
					</tr>
					<tr>
						<td>
							<input type="checkbox" name="mirrorpkgs" id="mirrorpkgs" value="mirrorpkgs" 
							                	<?if ($conf->getSetting("mirrorpkgs") == "true")
                	{
						echo "checked=\"checked\"";
					}?>
				    onChange="javascript:ajaxPost('ajax.php?service=SUS', 'mirrorpkgs=' + this.checked);"/>
							<label for="mirror">Store software updates on this SUS</label>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
					<tr>
						<td align="left" nowrap><font class="formLabel">SUS Control</font></td>
					</tr>
					<tr>
						<td><input type="button" value="Sync SUS" onClick="javascript: return goTo(true, 'susCtl.php?sync=true');"/></td>
					</tr>
 					<tr>
                        <td width="10%" align="left" nowrap>Sync Schedule: 
                                                <select id="syncsch" onChange="javascript:ajaxPost('ajax.php?service=SUS', 'enablesyncsch=' + this.value);">
  							<option value="Off"<?=($syncschedule == "Off" ? " selected=\"selected\"" : "")?>>Disabled</option>
							<option value="0"<?=($syncschedule == "0" ? " selected=\"selected\"" : "")?>>Midnight</option>
  							<option value="3"<?=($syncschedule == "3" ? " selected=\"selected\"" : "")?>>3 AM</option>
  							<option value="6"<?=($syncschedule == "6" ? " selected=\"selected\"" : "")?>>6 AM</option>
  							<option value="12"<?=($syncschedule == "12" ? " selected=\"selected\"" : "")?>>Noon</option>
							<option value="15"<?=($syncschedule == "15" ? " selected=\"selected\"" : "")?>>3 PM</option>
							<option value="18"<?=($syncschedule == "18" ? " selected=\"selected\"" : "")?>>6 PM</option>
							<option value="21"<?=($syncschedule == "21" ? " selected=\"selected\"" : "")?>>9 PM</option>
						</select>
					</td>
					</tr>
					<tr>
						<td>Last Sync: <?= suExec("lastsussync")?></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
						<td align="left" nowrap><font class="formLabel">Branches</font></td>
					</tr>
					<tr>
						<td style="width:100%;">
						<?
						$branchstr = trim(suExec("getBranchlist"));
						$branches = explode(" ",$branchstr);
							if ($branchstr == "")
									{?><table class="objectList" border="1" style="width: 100%;">
							<tr class="objectHeader">
							<td nowrap class="objectHeader">Root</td>
							<td nowrap class="objectHeader" style="width: 100%;">Branch Name</td>
							<td nowrap class="objectHeader">Branch URL</td><td></td></tr><tr><td></td><td>No Branches</td><td></td><td></td></tr></table><?}
						else
						{
							sort($branches);
							?>
					<table class="objectList" border="1" style="width: 100%;">
						<tr class="objectHeader">
						<td nowrap class="objectHeader">Root</td>
						<td nowrap class="objectHeader" style="width: 100%;">Branch Name</td>
						<td nowrap class="objectHeader">Branch URL</td>
						<td nowrap class="objectHeader">&nbsp;</td>
						<?
						foreach ($branches as $key => $value)
						{
							?>
							<tr class="<?=($key % 2 == 0 ? "object0" : "object1")?>">
								<td><?if ($conf->getSetting("rootbranch") == $value)
                	{
						echo "*";
					}?></td>
								<td style="padding: 2px; width: 100%;"><a href="managebranch.php?branch=<?=$value?>" title="Manage branch: <?=$value?>"><?=$value?></a></td>
								<td nowrap style="padding: 2px; padding-left: 5px; padding-right: 5px;text-align:left;"><?=$conf->getSetting("susbaseurl")."/content/catalogs/index_".$value.".sucatalog"?></a></td>
								<td style="padding: 2px;"><a href="admin.php?service=SUS&deletebranch=<?=$value?>" onClick="javascript: return yesnoprompt('Are you sure you want to delete the branch?');">Delete</a></td>
							</tr>
							<?
						}
						?>
					</table>
						<?
						}
						?>
						</td>
					</tr>
					<tr>
						<td nowrap>
						<label for="branchname">Add Branch:</label>
							<input type="text" name="branchname" id="branchname" style="min-width: 50%;" 
							value="" onKeyUp="validateField('branchname', 'addbranch');" onChange="validateField('branchname', 'addbranch');"/> 
						<input type="submit" name="addbranch" id="addbranch" value="Add SUS Branch" disabled="disabled" /></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
changeServiceType('<?=(isset($_GET['service']) ? $_GET['service'] : "SUS")?>');

</script>