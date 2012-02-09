<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$currentIP = trim(getCurrentIP());

$title = "Appliance Dashboard";

include "inc/header.php";

?>
<!-- <h1>Welcome to the NetBoot Appliance</h1> -->
<?
if ($conf->needsToChangeAnyPasses())
{
?>
<p><font color="red">The password has not been changed for the following accounts:</p>
<ul>
<?
if ($conf->needsToChangePass("webaccount"))
{
	echo "<li align=\"left\">WebAdmin</li>\n";
}
if ($conf->needsToChangePass("shellaccount"))
{
	echo "<li align=\"left\">Shell</li>\n";
}
if ($conf->needsToChangePass("afpaccount"))
{
	echo "<li align=\"left\">AFP</li>\n";
}
if ($conf->needsToChangePass("smbaccount"))
{
	echo "<li align=\"left\">SMB</li>\n";
}
?>
</ul>
</font>
<?
}
?>
<table cellpadding=2 width=680>
<tr>
<td width="33%">
<b><u>NetBoot Stats</u></b>
</td>
<td></td>
<td width="33%">
<b><u>SUS Stats</u></b>
</td>
<td></td>
<td width="33%">
<b><u>Appliance Stats</u><b/>
</td>
</tr>

<tr>
<td>

DHCP Status:
<?
if (getNetBootStatus())
{
	echo "Running";
}
else
{
	echo "Not Running";
}
?>

</td>
<td></td>
<td>

Last SUS Sync: <?print suExec("lastsussync")?>

</td>
<td></td>
<td>

Total Disk Usage: <?echo suExec(diskusage);
?>

</td>
</tr>

<tr>
<td>

Total NetBoot Image Size: <?echo suExec(netbootusage);
?>

</td>
<td></td>
<td>
<?
if (getSyncStatus())
{
	echo "Sync State: Running";
}
else
{
	echo "Sync State: Not Running";
}
?>
</td>
<td></td>
<td>

Free Mem: <?echo suExec(freemem);
?>M

</td>
</tr>

<tr>
<td>

Active SMB Connections: <?echo suExec(smbconns);
?>

</td>
<td></td>
<td>
SUS Disk Usage: <?echo suExec(getsussize);
?>
</td>
<td>
<td></td>
</td>
</tr>

<tr>
<td>

Active AFP Connections: <?echo suExec(afpconns);
?>

</td>
<td></td>
<td>
Num of Branches: <?echo suExec(numofbranches);
?>
</td>
<td></td>
<td>
</td>
</tr>

<tr>
<td>

Shadow File Usage: <?echo suExec(shadowusage);
?>

</td>
<td></td>
<td>
</td>
<td></td>
<td>
</td>
</tr>

<tr>
<td>
</td>
<td></td>
<td>
</td>
<td></td>
<td>
</td>
</tr>
</table>


<br/>
<?include "inc/footer.php";?>
</body>
</html>