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
			echo "<div class=\"alert alert-warning\">ERROR: Unable to create the SUS branch &quot;$branchname&quot; ($res).</div>\n";
		}
		else
		{
			echo "<div class=\"alert alert-success\">Created SUS branch &quot;$branchname&quot;.</div>\n";
		}
	}
	else
	{
		echo "<div class=\"alert alert-warning\">ERROR: Specify a SUS branch name.\n";
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

if (isset($_POST['apply_proxy']))
{
	if (empty($_POST['proxy_host']) && empty($_POST['proxy_user']))
	{
		suExec("setsusproxy");
	}
	if (!empty($_POST['proxy_host']) && empty($_POST['proxy_user']))
	{
		suExec("setsusproxy ".$_POST['proxy_host']." ".$_POST['proxy_port']);
	}
	if (!empty($_POST['proxy_host']) && !empty($_POST['proxy_user']))
	{
		suExec("setsusproxy ".$_POST['proxy_host']." ".$_POST['proxy_port']." ".$_POST['proxy_user']." ".$_POST['proxy_pass']);
	}
}

$susProxyHost = trim(suExec("getsusproxyhost"));
$susProxyPort = trim(suExec("getsusproxyport"));
$susProxyUser = trim(suExec("getsusproxyuser"));
$susProxyPassword = trim(suExec("getsusproxypass"));

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

function validateBaseURL()
{
	var validBaseURL = /^http:\/\/(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[0-9][\/]|[1-9][0-9]|[1-9][0-9][\/]|1[0-9]{2}|1[0-9]{2}[\/]|2[0-4][0-9]|2[0-4][0-9][\/]|25[0-5]|25[0-5][\/])$|^http:\/\/(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][\/]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9][\/])$/.test(document.getElementById("baseurl").value);
	showErr("baseurl", validBaseURL);
	enableButton("setbaseurl", validBaseURL);
}

function validateBranch()
{
	var validBranch = /^[A-Za-z0-9._+\-]{1,128}$/.test(document.getElementById("branchname").value);
	showErr("branchname", validBranch);
	enableButton("addbranch", validBranch);
}

function toggleHttpProxy()
{
	document.getElementById('proxy_host').disabled = !document.getElementById('http_proxy').checked;
	document.getElementById('proxy_port').disabled = !document.getElementById('http_proxy').checked;
}
function toggleProxyAuth()
{
	document.getElementById('proxy_auth').disabled = !document.getElementById('http_proxy').checked;
	document.getElementById('proxy_user').disabled = !document.getElementById('proxy_auth').checked || document.getElementById('proxy_auth').disabled;
	document.getElementById('proxy_pass').disabled = !document.getElementById('proxy_auth').checked || document.getElementById('proxy_auth').disabled;
}
function validateProxy()
{
	var validHttpProxy = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(document.getElementById("proxy_host").value) || !document.getElementById('http_proxy').checked;
	var validHttpPort = /^\d+$/.test(document.getElementById("proxy_port").value) && document.getElementById("proxy_port").value != "" && !(parseInt(document.getElementById("proxy_port").value) < 0) && !(parseInt(document.getElementById("proxy_port").value) > 65535) || !document.getElementById('http_proxy').checked;
	var validProxyUser = document.getElementById('http_proxy').checked && document.getElementById("proxy_user").value != "" || !document.getElementById('proxy_auth').checked || document.getElementById('proxy_auth').disabled;
	var validProxyPass = document.getElementById("proxy_user").value != "" && document.getElementById("proxy_pass").value != "" || !document.getElementById('proxy_auth').checked || document.getElementById('proxy_auth').disabled;
	showErr("proxy_host", validHttpProxy);
	showErr("proxy_port", validHttpPort);
	enableButton("apply_proxy", validHttpProxy && validHttpPort && validProxyUser && validProxyPass);
}
window.onload = function()
{
	document.getElementById('proxy_host').disabled = !document.getElementById('http_proxy').checked;
	document.getElementById('proxy_port').disabled = !document.getElementById('http_proxy').checked;
	document.getElementById('proxy_auth').disabled = !document.getElementById('http_proxy').checked;
	document.getElementById('proxy_user').disabled = !document.getElementById('proxy_auth').checked;
	document.getElementById('proxy_pass').disabled = !document.getElementById('proxy_auth').checked;
}
</script>

<h2>Software Update Server</h2>


<div class="row">
	<div class="col-xs-12 col-sm-10 col-lg-8">

		<form action="SUS.php" method="post" name="SUS" id="SUS">

			<hr>

			<?php if ($conf->getSetting("todoenrolled") != "true") { ?>

			<span class="label label-default">Base URL</span>

			<span class ="description">Base URL for the software update server (e.g. "http://sus.mycompany.corp")</span>

			<div class="input-group">
				<input type="text" name="baseurl" id="baseurl" class="form-control input-sm long-text-input" value="<?php echo $conf->getSetting("susbaseurl")?>" onClick="validateBaseURL();" onKeyUp="validateBaseURL();" onChange="validateBaseURL();"/>
				<span class="input-group-btn">
					<input type="submit" name="setbaseurl" id="setbaseurl" class="btn btn-primary btn-sm" value="Change URL" disabled="disabled" />
				</span>
			</div>

			<br>

			<div class="table-responsive panel panel-default">
				<div class="panel-heading">
					<strong>Branches</strong>
				</div>
				<table class="table table-striped table-bordered table-condensed">
					<?php
					$branchstr = trim(suExec("getBranchlist"));
					$branches = explode(" ",$branchstr);
					?>
					<thead>
					<tr>
						<th>Root</th>
						<th>Name</th>
						<th>URL</th>
						<th></th>
					</tr>
					</thead>
					<tobdy>
						<?php foreach ($branches as $key => $value) {
							if ($value != "") {?>
								<tr>
									<td class="table-center"><?php if ($conf->getSetting("rootbranch") == $value) { echo "<span class=\"glyphicon glyphicon-ok\"></span>"; }?></td>
									<td><a href="managebranch.php?branch=<?php echo $value?>" title="Manage branch: <?php echo $value?>"><?php echo $value?></a></td>
									<td nowrap><?php echo $conf->getSetting("susbaseurl")."content/catalogs/index_".$value.".sucatalog"?></a></td>
									<td><a href="SUS.php?service=SUS&deletebranch=<?php echo $value?>" onClick="javascript: return yesnoprompt('Are you sure you want to delete the branch?');">Delete</a></td>
								</tr>
							<?php } } ?>
					</tobdy>
				</table>
			</div>

			<span class="label label-default">New Branch</span>

			<div class="input-group">
				<input type="text" name="branchname" id="branchname" class="form-control input-sm" value="" onClick="validateBranch();" onKeyUp="validateBranch();" onChange="validateBranch();"/>
				<span class="input-group-btn">
					<input type="submit" name="addbranch" id="addbranch" class="btn btn-primary btn-sm" value="Add Branch" disabled="disabled"/>
				</span>
			</div>

			<?php
			}
			else { ?>
				<h3>Managed by Jamf Pro</h3>
			<?php }?>

			<span class="label label-default">Store Updates on the NetBoot/SUS/LDAP Proxy Server</span>
			<div class="checkbox">
				<label>
					<input class="checkbox" type="checkbox" name="mirrorpkgs" id="mirrorpkgs" value="mirrorpkgs"
						<?php if ($conf->getSetting("mirrorpkgs") == "true")
						{
							echo "checked=\"checked\"";
						}?>
						   onChange="javascript:ajaxPost('ajax.php?service=SUS', 'mirrorpkgs=' + this.checked);"/>
					Ensure that computers install software updates from the NetBoot/SUS/LDAP Proxy server instead of downloading and installing them from Apple's software update server
				</label>
			</div>

			<span class="label label-default">Purge Deprecated Updates</span>
			<span class="description">Removes all deprecated products that are not in any branch catalogs</span>
			<input type="button" value="Purge Deprecated" class="btn btn-primary btn-sm" onClick="javascript: return goTo(true, 'susCtl.php?purge=true');"/>

			<span class="label label-default">Manual Sync</span>

			<span class="description">Manual method for syncing the list of available updates with Apple's Software Update server</span>
			<input type="button" value="Sync Manually" class="btn btn-sm btn-primary" onClick="javascript: return goTo(true, 'susCtl.php?sync=true');"/>

			<span class="label label-default">Daily Sync Time</span>

			<span class="description">Time at which to sync the list of available updates with Apple's Software Update server each day</span>
			<select id="syncsch" class="form-control input-sm" onChange="javascript:ajaxPost('ajax.php?service=SUS', 'enablesyncsch=' + this.value);">
				<option value="Off"<?php echo ($syncschedule == "Off" ? " selected=\"selected\"" : "")?>>None</option>
				<option value="0"<?php echo ($syncschedule == "0" ? " selected=\"selected\"" : "")?>>12 a.m.</option>
				<option value="3"<?php echo ($syncschedule == "3" ? " selected=\"selected\"" : "")?>>3 a.m.</option>
				<option value="6"<?php echo ($syncschedule == "6" ? " selected=\"selected\"" : "")?>>6 a.m.</option>
				<!-- 2017-04-27: NetSUS Update added (missing) 9am option -->
				<option value="9"<?php echo ($syncschedule == "9" ? " selected=\"selected\"" : "")?>>9 a.m.</option>
				<option value="12"<?php echo ($syncschedule == "12" ? " selected=\"selected\"" : "")?>>12 p.m.</option>
				<option value="15"<?php echo ($syncschedule == "15" ? " selected=\"selected\"" : "")?>>3 p.m.</option>
				<option value="18"<?php echo ($syncschedule == "18" ? " selected=\"selected\"" : "")?>>6 p.m.</option>
				<option value="21"<?php echo ($syncschedule == "21" ? " selected=\"selected\"" : "")?>>9 p.m.</option>
			</select>

			<br>

			<span style="font-weight:bold;">Last Sync: </span><span><?php if (trim(suExec("lastsussync")) != "") { print suExec("lastsussync"); } else { echo "Never"; } ?></span>

			<br>
			<br>

			<div class="panel panel-default">
				<div class="panel-heading">
					<strong>Proxy Configuration</strong>
				</div>

				<div class="panel-body">

					<div class="checkbox">
						<label><input class="checkbox" type="checkbox" name="http_proxy" id="http_proxy" value="http_proxy" <?php if ($susProxyHost != "") { echo "checked=\"checked\""; } ?> onChange="toggleHttpProxy(); toggleProxyAuth(); validateProxy();" />Proxy Server</label>
					</div>

					<div class="input-group">
						<div class="input-group-addon no-background">Host</div>
						<input type="text" name="proxy_host" id="proxy_host" class="form-control input-sm" value="<?php echo $susProxyHost; ?>" onClick="validateProxy();" onKeyUp="validateProxy();" onChange="validateProxy();" />
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background">Port</div>
						<input type="text" name="proxy_port" id="proxy_port" class="form-control input-sm" value="<?php echo $susProxyPort; ?>" onClick="validateProxy();" onKeyUp="validateProxy();" onChange="validateProxy();" />
					</div>

					<br>

					<div class="checkbox">
						<label><input class="checkbox" type="checkbox" name="proxy_auth" id="proxy_auth" value="proxy_auth" <?php if ($susProxyUser != "") { echo "checked=\"checked\""; } ?> onChange="toggleProxyAuth(); validateProxy();" />Proxy Requires Authentication</label>
					</div>

					<div class="input-group">
						<div class="input-group-addon no-background">Username</div>
						<input type="text" name="proxy_user" id="proxy_user" class="form-control input-sm" value="<?php echo $susProxyUser; ?>" onClick="validateProxy();" onKeyUp="validateProxy();" onChange="validateProxy();" />
					</div>

					<br>

					<div class="input-group">
						<div class="input-group-addon no-background">Password</div>
						<input type="password" name="proxy_pass" id="proxy_pass" class="form-control input-sm" value="<?php echo $susProxyPassword; ?>" onClick="validateProxy();" onKeyUp="validateProxy();" onChange="validateProxy();" />
					</div>

				</div>

				<div class="panel-footer">
					<input type="submit" name="apply_proxy" id="apply_proxy" class="btn btn-primary btn-sm" value="Apply" disabled="disabled" />
				</div>
			</div>


		</form> <!-- end form SUS -->
	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>