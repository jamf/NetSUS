<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "LDAP Proxy";

include "inc/header.php";

$proxies = $conf->getProxies();

if (isset($_POST['enableproxy']) && empty($proxies))
{
	echo "<div class=\"alert alert-danger\">ERROR: Ensure you have added a LDAP Proxy specification</div>";
}

if (isset($_POST['disableproxy']))
{
	suExec("disableproxy");
}

if (isset($_POST['enableproxy']) && !empty($proxies))
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
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update slapd.conf</div>";
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
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update slapd.conf</div>";
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

<script>
	//Ensure all inputs have values before enabling the add button

	$(document).ready(function () {
		validate();
		$('#inLDAP, #outLDAP, #inURL').keyup(validate);
		$('#inLDAP, #outLDAP, #inURL').change(validate);
	});

	function validate() {
		if ($('#inLDAP').val().length > 0 &&
			$('#outLDAP').val().length > 0 &&
			$('#inURL').val().length > 0) {
			$("#addProxy").prop("disabled", false);
		} else {
			$("#addProxy").prop("disabled", true);
		}
	}
</script>

<h2>LDAP Proxy</h2>

<div class="row">
	<div class="col-xs-12 col-sm-10 col-lg-8">

		<form action="LDAPProxy.php" method="post" name="LDAPProxy" id="LDAPProxy">

			<hr>

			<br>

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

				<div class="panel-body">

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Exposed Distinguished Name</div>
						<span class="description">Example: DC=jss,DC=corp</span>
						<input type="text" name="outLDAP" id="outLDAP" class="form-control input-sm" value="" />
					</div>

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">Real Distinguished Name</div>
						<span class="description">Example: DC=myorg,DC=corp</span>
						<input type="text" name="inLDAP" id="inLDAP" class="form-control input-sm" value="" />
					</div>

					<div class="input-group">
						<div class="input-group-addon no-background proxy-min-width">LDAP URL</div>
						<span class="description">Example: ldaps://ldap.myorg.com:636/</span>
						<input type="text" name="inURL" id="inURL" class="form-control input-sm" value="" />
					</div>

				</div>

				<div class="panel-footer">
					<input type="submit" name="addProxy" id="addProxy" class="btn btn-primary btn-sm" value="Add" />
				</div>
			</div>

			<div class="table-responsive panel panel-default">
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
			</div>

			<br>

		</form> <!-- end form NetBoot -->

	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>



