<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Software Update Server";

include "inc/header.php";

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

if (isset($_POST['addbranch']))
{
	if(isset($_POST['branchname']) && $_POST['branchname'] != "")
	{
		$branchname = $_POST['branchname'];
		$res = trim(suExec("createBranch $branchname"));
		if ($res != "")
		{
			echo "<div class=\"errorMessage\">ERROR: Unable to create the SUS branch &quot;$branchname&quot; ($res).</div><br/>\n";
		}
		else
		{
			echo "<div class=\"successMessage\">Created SUS branch &quot;$branchname&quot;.</div><br/>\n";
		}
	}
	else
	{
		echo "<div class=\"errorMessage\">ERROR: Specify a SUS branch name.<br/>\n";
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

<script>
function validateField(fieldid, buttonid)
{
	if (document.getElementById(fieldid).value != "")
		document.getElementById(buttonid).disabled = false;
	else
		document.getElementById(buttonid).disabled = true;
}
</script>

<style>         
  <!--       
	@media (max-width: 600px) {

		tr:first-child { display: none; }
	
	  td:nth-of-type(1):before { content: "Root";}
   
	  td:nth-of-type(2):before { content: "Name";}

	  td:nth-of-type(3):before { content: "URL";}
   
	}
 -->
 .tablesorter {
 	margin-bottom: 5px;
 }
</style> 

<h2>Software Update Server</h2>

<div id="form-wrapper">

	<form action="SUS.php" method="post" name="SUS" id="SUS">

		<div id="form-inside">

			<?php if ($conf->getSetting("todoenrolled") != "true") { ?>
			<span class="label">Base URL</span>
			<span class="description">Base URL for the software update server (e.g. "https://sus.mycompany.corp")</span>
			<input type="text" name="baseurl" id="baseurl" 
							value="<?php echo $conf->getSetting("susbaseurl")?>" onKeyUp="validateField('baseurl', 'setbaseurl');" onChange="validateField('baseurl', 'setbaseurl');"/>
			<input type="submit" name="setbaseurl" id="setbaseurl" class="insideActionButton" value="Change URL" disabled="disabled" />
			<br>
			<span class="label">Branches</span>
			<table class="tablesorter">
				<?php	
				$branchstr = trim(suExec("getBranchlist"));
				$branches = explode(" ",$branchstr);
				?>
				<tr>
					<th class="header">Root</th>
					<th class="header">Name</th>
					<th class="header">URL</th>
					<th class="header"></th>
				</tr>
				<?php foreach ($branches as $key => $value) { 
				if ($value != "") {?>
				<tr class="<?php echo ($key % 2 == 0 ? "object0" : "object1")?>">
					<td><?php if ($conf->getSetting("rootbranch") == $value) { echo "*"; }?></td>
					<td><a href="managebranch.php?branch=<?php echo $value?>" title="Manage branch: <?php echo $value?>"><?php echo $value?></a></td>
					<td nowrap><?php echo $conf->getSetting("susbaseurl")."index_".$value.".sucatalog"?></a></td>
					<td><a href="SUS.php?service=SUS&deletebranch=<?php echo $value?>" onClick="javascript: return yesnoprompt('Are you sure you want to delete the branch?');">Delete</a></td>
				</tr>
				<?php } } ?>
			</table>
			<span class="label">New Branch</span>
			<input type="text" name="branchname" id="branchname" value="" 
				onKeyUp="validateField('branchname', 'addbranch');" onChange="validateField('branchname', 'addbranch');"/> 
			<input type="submit" name="addbranch" id="addbranch" class="insideActionButton" value="Add" disabled="disabled"/>
			<?php 
			}
			else { ?>
				<h3>Managed by the JSS</h3>
			<?php }?>
				</table>
				<br>
			<div class="checkboxWrapper">
				<span class="label"><input type="checkbox" name="mirrorpkgs" id="mirrorpkgs" value="mirrorpkgs" 
								<?php if ($conf->getSetting("mirrorpkgs") == "true")
				        {
									echo "checked=\"checked\"";
								}?>
								onChange="javascript:ajaxPost('ajax.php?service=SUS', 'mirrorpkgs=' + this.checked);"/>
								Store software updates on the NetBoot/SUS server</span>
				<span class="description">Ensure that computers install software updates from the NetBoot/SUS server instead of downloading and installing them from Apple's software update server</span>
			</div>

			<span class="label">Manual Sync</span>
			<span class="description">Manual method for syncing the list of available updates with Apple's Software Update server</span>
			<input type="button" value="Sync Manually" class="insideActionButton" onClick="javascript: return goTo(true, 'susCtl.php?sync=true');"/>
			<br>

			<span class="label">Daily Sync Time</span>
			<span class="description">Time at which to sync the list of available updates with Apple's Software Update server each day</span>
      <select id="syncsch" onChange="javascript:ajaxPost('ajax.php?service=SUS', 'enablesyncsch=' + this.value);">
				<option value="Off"<?php echo ($syncschedule == "Off" ? " selected=\"selected\"" : "")?>>None</option>
				<option value="0"<?php echo ($syncschedule == "0" ? " selected=\"selected\"" : "")?>>12 a.m.</option>
				<option value="3"<?php echo ($syncschedule == "3" ? " selected=\"selected\"" : "")?>>3 a.m.</option>
				<option value="6"<?php echo ($syncschedule == "6" ? " selected=\"selected\"" : "")?>>6 a.m.</option>
				<option value="12"<?php echo ($syncschedule == "12" ? " selected=\"selected\"" : "")?>>12 p.m.</option>
				<option value="15"<?php echo ($syncschedule == "15" ? " selected=\"selected\"" : "")?>>3 p.m.</option>
				<option value="18"<?php echo ($syncschedule == "18" ? " selected=\"selected\"" : "")?>>6 p.m.</option>
				<option value="21"<?php echo ($syncschedule == "21" ? " selected=\"selected\"" : "")?>>9 p.m.</option>
			</select>
			<br>
			<div class="labelDescriptionWrapper">
				<span style="font-weight:bold;">Last Sync: </span><span><?php if (trim(suExec("lastsussync")) != "") { print suExec("lastsussync"); } else { echo "Never"; } ?></span>
			</div>

		</div> <!-- end #form-inside -->

	</form> <!-- end form SUS -->

</div> <!-- end #form-wrapper -->

<?php include "inc/footer.php"; ?>




					