<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Certificates";

include "inc/header.php";

if (isset($_POST['privatekey']) && isset($_POST['certificate']) && isset($_POST['chain']) && isset($_POST['certs'])
&& $_POST['privatekey'] != "" && $_POST['certificate'] != "" && $_POST['chain'] != "")
{
	if(openssl_pkey_get_private($_POST['privatekey']) === FALSE)
	{
	echo "<div class=\"alert alert-danger\">ERROR: Unable to read the private key, aborting</div>";
	return;
	}	
	if(openssl_x509_read($_POST['certificate']) === FALSE)
	{
	echo "<div class=\"alert alert-danger\">ERROR: Unable to read the certificate, aborting</div>";
	return;
	}
	
	if(openssl_x509_read($_POST['chain']) === FALSE)
	{
	echo "<div class=\"alert alert-danger\">ERROR: Unable to read the chain, aborting</div>";
	return;
	}
	
suExec("touchconf \"/var/appliance/conf/appliance.private.key\"");	
	if(file_put_contents("/var/appliance/conf/appliance.private.key", $_POST['privatekey']) === FALSE)
	{
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update appliance.private.key</div>";
		return;
	}
suExec("touchconf \"/var/appliance/conf/appliance.certificate.pem\"");
	if(file_put_contents("/var/appliance/conf/appliance.certificate.pem", $_POST['certificate']) === FALSE)
	{
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update appliance.certificate.pem</div>";
		return;
	}
suExec("touchconf \"/var/appliance/conf/appliance.chain.pem\"");
	if(file_put_contents("/var/appliance/conf/appliance.chain.pem", $_POST['chain']) === FALSE)
	{
		echo "<div class=\"alert alert-danger\">ERROR: Unable to update appliance.chain.pem</div>";
		return;
	}
suExec("updateCert");
echo "<div class=\"alert alert-success\">Configuration saved.  Restart required.</div>";
}


?>

<h2>Certificates</h2>

	<form action="certificates.php" method="post" name="certificates" id="certificates">

		<div class="row">
			<div class="col-xs-12 col-sm-8 col-md-6">

				<hr>

				<label class="control-label">Private Key</label>
				<textarea class="form-control input-sm" name="privatekey" rows="3"></textarea>

				<label class="control-label">Certificate</label>
				<textarea class="form-control input-sm" name="certificate" rows="3"></textarea>

				<label class="control-label">Chain</label>
				<textarea class="form-control input-sm" name="chain" rows="3"></textarea>
				<br>

				<input type="submit" name="certs" id="certs" class="btn btn-primary" value="Save" />
				<br>
				<br>
				<hr>
				<br>

				<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

			</div>
		</div>
	</form> <!-- end SMB form -->

<?php include "inc/footer.php"; ?>
