<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Certificates";

if (isset($_POST['create_csr']) && isset($_POST['common_name'])
&& $_POST['common_name'] != "")
{
	suExec("createCsr \"".$_POST['common_name']."\" \"".$_POST['organizational_unit']."\" \"".$_POST['organization']."\" \"".$_POST['locality']."\" \"".$_POST['state']."\" \"".$_POST['country']."\"");
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
echo "<div class=\"alert alert-success\">Configuration saved. Restart required.</div>";
}

$ssl_certificate_str = trim(suExec("getSSLCertificate"));
$ssl_certificate = array();
if ($ssl_certificate_str != "")
{
	foreach(explode("\n", $ssl_certificate_str) as $key => $value)
	{
		$tmp = explode(": ", $value);
		$ssl_certificate[$tmp[0]] = $tmp[1];
	}
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
	var validCommonName = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(document.getElementById("common_name").value);
	showErr("common_name", validCommonName);
	enableButton("create_csr", validCommonName);
}
function validateCerts()
{
	var validPrivateKey = !(document.getElementById("privatekey").value == "");
	var validCertificate = !(document.getElementById("certificate").value == "");
	var validChain = !(document.getElementById("chain").value == "");
	enableButton("certs", validPrivateKey && validCertificate && validChain);
}

//function to save the current tab on refresh
$(document).ready(function(){
	$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
		localStorage.setItem('activeTab', $(e.target).attr('href'));
	});
	var activeTab = localStorage.getItem('activeTab');
	if(activeTab){
		$('#top-tabs a[href="' + activeTab + '"]').tab('show');
	}
});
</script>

<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
<h2>Certificates</h2>

	<form action="certificates.php" method="post" name="certificates" id="certificates">

		<div class="row">
			<div class="col-xs-12">

				<!--<hr>-->

				<ul class="nav nav-tabs nav-justified" id="top-tabs">
					<li class="active"><a class="tab-font" href="#cert-tab" role="tab" data-toggle="tab">SSL Certificate</a></li>
					<li><a class="tab-font" href="#csr-tab" role="tab" data-toggle="tab">Certificate Signing Request</a></li>
					<li><a class="tab-font" href="#modify-tab" role="tab" data-toggle="tab">Modify Certificates</a></li>
				</ul>

				<div class="tab-content">

					<div class="tab-pane active fade in" id="cert-tab">

						<div style="padding: 8px 0px;" class="description">CERTIFICATE DESCRIPTION</div>

						<h5><strong>Subject Name</strong></h5>
						<div class="text-muted"><?php echo $ssl_certificate['Owner']; ?></div>

						<h5><strong>Issuer</strong></h5>
						<div class="text-muted"><?php echo $ssl_certificate['Issuer']; ?></div>

						<h5><strong>Expiration Date</strong></h5>
						<div class="text-muted"><?php echo $ssl_certificate['Expires']; ?></div>

					</div><!-- /.tab-pane -->

					<div class="tab-pane fade in" id="csr-tab">

						<div style="padding: 8px 0px;" class="description">CSR DESCRIPTION</div>

						<h5 id="common_name_label"><strong>Common Name</strong> <small>Common Name for the certificate (e.g. "netsus.mycompany.corp").</small></h5>
						<div class="form-group has-feedback">
							<input type="text" name="common_name" id="common_name" class="form-control input-sm" placeholder="[Required]" value="" onClick="validateCSR();" onKeyUp="validateCSR();" />
						</div>

						<h5 id="organizational_unit_label"><strong>Organizational Unit</strong> <small>Name of the organizational unit (e.g. "JAMFSW").</small></h5>
						<div class="form-group has-feedback">
							<input type="text" name="organizational_unit" id="organizational_unit" class="form-control input-sm" placeholder="[Optional]" value="" onClick="validateCSR();" />
						</div>

						<h5 id="organization_label"><strong>Organization</strong> <small>Name of the organization (e.g. "JAMF Software").</small></h5>
						<div class="form-group has-feedback">
							<input type="text" name="organization" id="organization" class="form-control input-sm" placeholder="[Optional]" value="" onClick="validateCSR();" />
						</div>

						<h5 id="locality_label"><strong>City or Locality</strong> <small>Name of the City or Locality (e.g. "Minneapolis").</small></h5>
						<div class="form-group has-feedback">
							<input type="text" name="locality" id="locality" class="form-control input-sm" placeholder="[Optional]" value="" onClick="validateCSR();" />
						</div>

						<h5 id="state_label"><strong>State or Province</strong> <small>Name of the State or Province (e.g. "MN").</small></h5>
						<div class="form-group has-feedback">
							<input type="text" name="state" id="state" class="form-control input-sm" placeholder="[Optional]" value="" onClick="validateCSR();" />
						</div>

						<h5 id="country_label"><strong>Country Code</strong> <small>Two-letter country code for this unit (e.g. "US").</small></h5>
						<div class="form-group has-feedback">
							<input type="text" name="country" id="country" class="form-control input-sm" placeholder="[Optional]" value="" onClick="validateCSR();" />
						</div>

						<div class="text-right">
							<input type="submit" name="create_csr" id="create_csr" class="btn btn-primary btn-sm" value="Create" disabled="disabled" />
						</div>

					</div><!-- /.tab-pane -->

					<div class="tab-pane fade in" id="modify-tab">

						<div style="padding: 8px 0px;" class="description">MODIFY DESCRIPTION</div>

						<h5 id="state_label"><strong>Private Key</strong> <small>Paste the content of RSA private key file, including the BEGIN and END tags.</small></h5>
						<textarea class="form-control input-sm" name="privatekey" id="privatekey" rows="4" onClick="validateCerts();" onKeyUp="validateCerts();"></textarea>

						<h5 id="state_label"><strong>Certificate</strong> <small>Paste the content of the certificate file, including the BEGIN and END tags.</small></h5>
						<textarea class="form-control input-sm" name="certificate" id="certificate" rows="4" onClick="validateCerts();" onKeyUp="validateCerts();"></textarea>

						<h5 id="state_label"><strong>Chain</strong> <small>Paste the content of the CA bundle, including the BEGIN and END tags.</small></h5>
						<textarea class="form-control input-sm" name="chain" id="chain" rows="4" onClick="validateCerts();" onKeyUp="validateCerts();"></textarea>

						<br>

						<div class="text-right">
							<input type="submit" name="certs" id="certs" class="btn btn-primary btn-sm" value="Apply" disabled="disabled" />
						</div>

					</div><!-- /.tab-pane -->

				</div> <!-- end .tab-content -->

			</div>
		</div>
	</form> <!-- end Certificates form -->

<?php include "inc/footer.php"; ?>
