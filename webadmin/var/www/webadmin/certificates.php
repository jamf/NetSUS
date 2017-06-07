<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Certificates";

if (isset($_POST['create_csr']) && isset($_POST['common_name'])
&& $_POST['common_name'] != "")
{
	suExec("createCsr '".$_POST['common_name']."'");
	$tmp_file = "/tmp/certreq.zip";
	$zip = new ZipArchive();
	$zip->open($tmp_file, ZipArchive::CREATE);
	$zip->addFile('/tmp/private.key', 'private.key');
	$zip->addFile('/tmp/certreq.csr', 'certreq.csr');
	$zip->close();
	if (file_exists('/tmp/certreq.zip')) {
		if (ob_get_level()) ob_end_clean();
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename='.basename($tmp_file));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize($tmp_file));
		ob_clean();
		flush();
		readfile($tmp_file);
		unlink($tmp_file);
		unlink('/tmp/private.key');
		unlink('/tmp/certreq.csr');
		exit;
	}
}

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

<script type="text/javascript">
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

function validateCSR()
{
	var validCommonName = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(document.getElementById("common_name").value);
	showErr("common_name", validCommonName);
	enableButton("create_csr", validCommonName);
}
</script>

<h2>Certificates</h2>

	<form action="certificates.php" method="post" name="certificates" id="certificates">

		<div class="row">
			<div class="col-xs-12 col-sm-8 col-md-6">

				<hr>

				<span class="label label-default">Certificate Signing Request</span>
				<span class="description">Common Name for the certificate (e.g. "netsus.mycompany.corp")</span>

				<div class="input-group">
					<input type="text" name="common_name" id="common_name" class="form-control input-sm" value="<?php echo getCurrentHostname(); ?>" onClick="validateCSR();" onKeyUp="validateCSR();" onChange="validateCSR();" />
					<span class="input-group-btn">
						<input type="submit" name="create_csr" id="create_csr" class="btn btn-primary btn-sm" value="Create" disabled="disabled" />
					</span>
				</div>
				<br>

				<span class="label label-default">Modify Certificates</span>

				<label class="control-label">Private Key</label>
				<span class="description">Paste the content of RSA private key file, including the BEGIN and END tags</span>
				<textarea class="form-control input-sm" name="privatekey" rows="3"></textarea>

				<label class="control-label">Certificate</label>
				<span class="description">Paste the content of the certificate file, including the BEGIN and END tags</span>
				<textarea class="form-control input-sm" name="certificate" rows="3"></textarea>

				<label class="control-label">Chain</label>
				<span class="description">Paste the content of the CA bundle, including the BEGIN and END tags</span>
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
