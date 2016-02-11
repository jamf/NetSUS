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
		echo "<div class=\"alert alert-danger alert-margin-top\">ERROR: Unable to update slapd.conf</div>";
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
		echo "<div class=\"alert alert-danger alert-margin-top\">ERROR: Unable to update slapd.conf</div>";
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


<h2>LDAP Proxy</h2>

<div id="form-wrapper">

	<div class="row">
		<div class="col-xs-12 col-sm-10 col-lg-8">

			<form action="LDAPProxy.php" method="post" name="LDAPProxy" id="LDAPProxy">

				<hr>

				<span class="description">Proxies that will be available for use.  You can connect to several directories or to several specific OU's in one directory.</span>

				<span class="label label-default">LDAP Proxy Status</span>

				<?php
				if (getLDAPProxyStatus())
				{
					echo "<div class=\"alert alert-success alert-with-button\">
							<span>Enabled</span>
							<input type=\"submit\" class=\"btn btn-sm btn-success pull-right\" value=\"Disable LDAP Proxy\" name=\"disableproxy\" />
						</div>";
				}
				else
				{
					echo "<div class=\"alert alert-danger alert-with-button\">
							<span>Disabled</span>
							<input type=\"submit\" class=\"btn btn-sm btn-danger pull-right\" value=\"Enable LDAP Proxy\" name=\"enableproxy\" onClick=\"javascript:return toggle_creating('enabling')\" />
						</div>";
				}
				?>

				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>Add LDAP Proxy</strong>
					</div>

					<div class="panel-body no-pad-top">

						<span class="description">Example: DC=jss,DC=corp</span>
						<div class="input-group">
							<div class="input-group-addon">Exposed Distinguished Name</div>
							<input type="text" name="outLDAP" id="outLDAP" class="form-control" value="" />
						</div>

						<span class="description">Example: DC=myorg,DC=corp</span>
						<div class="input-group">
							<div class="input-group-addon">Real Distinguished Name</div>
							<input type="text" name="inLDAP" id="inLDAP" class="form-control" value="" />
						</div>

						<span class="description">Example: ldaps://ldap.myorg.com:636/</span>
						<div class="input-group">
							<div class="input-group-addon">LDAP URL</div>
							<input type="text" name="inURL" id="inURL" class="form-control" value="" />
						</div>

					</div>

					<div class="panel-footer">
						<input type="submit" name="addProxy" id="addProxy" class="btn btn-primary" value="Add" />
					</div>
				</div>

				<table class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
							<th>Exposed Distinguished Name</th>
							<th>Real Distinguished Name</th>
							<th>LDAP URL</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($conf->getProxies() as $key => $value) { ?>
						<tr class="<?php echo ($key % 2 == 0 ? "object0" : "object1")?>">
							<td><?php echo $value['outLDAP']?></td>
							<td><?php echo $value['inLDAP']?></td>
							<td><?php echo $value['inURL']?></td>
							<td><a href="LDAPProxy.php?service=LDAPProxy&deleteoutLDAP=<?php echo urlencode($value['outLDAP'])?>&deleteinLDAP=<?php echo urlencode($value['inLDAP'])?>&deleteinURL=<?php echo urlencode($value['inURL'])?>">Delete</a>
						</tr>
						<?php } ?>
					</tbody>
				</table>

				<br>

			</form> <!-- end form NetBoot -->

		</div><!-- /.col -->
	</div><!-- /.row -->

</div><!--  end #form-wrapper -->

<?php include "inc/footer.php"; ?>



