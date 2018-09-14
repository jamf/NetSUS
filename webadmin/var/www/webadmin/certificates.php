<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Certificates";

$cert_error = "";
$cert_success = "";

if (isset($_POST['create_csr'])) {
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

if (isset($_POST['apply-certs'])) {
	// Certificate Verification Checks
	$tmpkey = tempnam("/tmp", "key");
	$tmpcert = tempnam("/tmp", "cert");
	$tmpbundle = tempnam("/tmp", "bundle");
	if (openssl_pkey_get_private($_POST['privatekey']) === FALSE) {
		$cert_error = "Invalid private key.";
	} else {
		if (file_put_contents($tmpkey, $_POST['privatekey']) === FALSE) {
			$cert_error = "Unable to create ".$tmpkey.".";
		}
	}
	if (empty($cert_error)) {
		if (openssl_x509_read($_POST['certificate']) === FALSE) {
			$cert_error = "Invalid certificate.";
		} else {
			if (file_put_contents($tmpcert, $_POST['certificate']) === FALSE) {
				$cert_error = "Unable to create ".$tmpcert.".";
			}
		}
	}
	if (empty($cert_error)) {
		if (openssl_x509_read($_POST['cabundle']) === FALSE) {
			$cert_error = "Invalid CA bundle.";
		} else {
			if (file_put_contents($tmpbundle, $_POST['cabundle']) === FALSE) {
				$cert_error = "Unable to create ".$tmpbundle.".";
			}
		}
	}
	if (empty($cert_error)) {
		$result = trim(suExec("validCertKey ".$tmpkey." ".$tmpcert));
		if ($result != "true") {
			$cert_error = "Certificate and private key do not match.";
		}
	}
	if (empty($cert_error)) {
		$result = trim(suExec("validCertChain ".$tmpbundle." ".$tmpcert));
		if (!empty($result)) {
			$cert_error = "Chain verify ".$result.".";
		}
	}
	unlink($tmpkey);
	unlink($tmpcert);
	unlink($tmpbundle);

	// Apply Certificates
	if (empty($cert_error)) {
		suExec("touchconf \"/var/appliance/conf/appliance.private.key\"");
		if (file_put_contents("/var/appliance/conf/appliance.private.key", $_POST['privatekey']) === FALSE) {
			$cert_error = "Unable to update appliance.private.key.";
		}
	}
	if (empty($cert_error)) {
		suExec("touchconf \"/var/appliance/conf/appliance.certificate.pem\"");
		if (file_put_contents("/var/appliance/conf/appliance.certificate.pem", $_POST['certificate']) === FALSE) {
			$cert_error = "Unable to update appliance.certificate.pem.";
		}
	}
	if (empty($cert_error)) {
		suExec("touchconf \"/var/appliance/conf/appliance.chain.pem\"");
		if (file_put_contents("/var/appliance/conf/appliance.chain.pem", $_POST['cabundle']) === FALSE) {
			$cert_error = "Unable to update appliance.chain.pem.";
		}
	}
	if (empty($cert_error)) {
		suExec("updateCert");
		$cert_success = "Certificates updated. Restart required.";
	}
}

// ####################################################################
// End of GET/POST parsing
// ####################################################################

$ssl_certificate_str = trim(suExec("getSSLCertificate"));
$ssl_certificate = array();
if ($ssl_certificate_str != "") {
	foreach(explode("\n", $ssl_certificate_str) as $key => $value) {
		$tmp = explode(": ", $value);
		$ssl_certificate[$tmp[0]] = $tmp[1];
	}
}
?>
			<style>
				#tab-content {
					margin-top: 209px;
				}
				@media(min-width:768px) {
					#tab-content {
						margin-top: 119px;
					}
				}
			</style>

			<script type="text/javascript">
				function showError(element, labelId = false) {
					element.parentElement.classList.add("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.add("text-danger");
					}
				}

				function hideError(element, labelId = false) {
					element.parentElement.classList.remove("has-error");
					if (labelId) {
						document.getElementById(labelId).classList.remove("text-danger");
					}
				}

				function validCerts() {
					var privatekey = document.getElementById("privatekey");
					var certificate = document.getElementById("certificate");
					var cabundle = document.getElementById("cabundle");
					if (privatekey.value.trim().match('^-----BEGIN RSA PRIVATE KEY-----') && privatekey.value.trim().match('-----END RSA PRIVATE KEY-----$')) {
						hideError(privatekey, 'privatekey_label');
					} else {
						showError(privatekey, 'privatekey_label');
					}
					if (certificate.value.trim().match('^-----BEGIN CERTIFICATE-----') && certificate.value.trim().match('-----END CERTIFICATE-----$')) {
						hideError(certificate, 'certificate_label');
					} else {
						showError(certificate, 'certificate_label');
					}
					if (cabundle.value.trim().match('^-----BEGIN CERTIFICATE-----') && cabundle.value.trim().match('-----END CERTIFICATE-----$')) {
						hideError(cabundle, 'cabundle_label');
					} else {
						showError(cabundle, 'cabundle_label');
					}
					if (privatekey.value.trim().match('^-----BEGIN RSA PRIVATE KEY-----') && privatekey.value.trim().match('-----END RSA PRIVATE KEY-----$') && certificate.value.trim().match('^-----BEGIN CERTIFICATE-----') && certificate.value.trim().match('-----END CERTIFICATE-----$') && cabundle.value.trim().match('^-----BEGIN CERTIFICATE-----') && cabundle.value.trim().match('-----END CERTIFICATE-----$')) {
						$('#apply-certs').prop('disabled', false);
					} else {
						$('#apply-certs').prop('disabled', true);
					}
				}

				function validCSR() {
					var common_name = document.getElementById("common_name");
					if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(common_name.value)) {
						hideError(common_name, 'common_name_label');
						$('#create_csr').prop('disabled', false);
					} else {
						showError(common_name, 'common_name_label');
						$('#create_csr').prop('disabled', true);
					}
				}
			</script>

			<script type="text/javascript">
				//function to save the current tab on refresh
				$(document).ready(function(){
					$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
						localStorage.setItem('activeCertTab', $(e.target).attr('href'));
					});
					var activeCertTab = localStorage.getItem('activeCertTab');
					if(activeCertTab){
						$('#top-tabs a[href="' + activeCertTab + '"]').tab('show');
					}
				});
			</script>

			<nav id="nav-title" class="navbar navbar-default navbar-fixed-top">
				<div style="padding: 19px 20px 1px;">
					<div class="description"><a href="settings.php">Settings</a> <span class="glyphicon glyphicon-chevron-right"></span> <span class="text-muted">System</span> <span class="glyphicon glyphicon-chevron-right"></span></div>
					<h2>Certificates</h2>
				</div>
				<div style="padding: 16px 20px 0px; background-color: #f9f9f9; border-bottom: 1px solid #ddd;">
					<ul class="nav nav-tabs nav-justified" id="top-tabs" style="margin-bottom: -1px;">
						<li class="active"><a class="tab-font" href="#cert-tab" role="tab" data-toggle="tab">SSL Certificate</a></li>
						<li><a class="tab-font" href="#csr-tab" role="tab" data-toggle="tab">Certificate Signing Request</a></li>
						<li><a class="tab-font" href="#modify-tab" role="tab" data-toggle="tab">Modify Certificates</a></li>
					</ul>
				</div>
			</nav>

			<form action="certificates.php" method="post" name="Certificates" id="Certificates">

				<div id="tab-content" class="tab-content">

					<div class="tab-pane active fade in" id="cert-tab">

						<div style="padding: 16px 20px 1px;">
							<h5><strong>Subject Name</strong></h5>
							<div class="text-muted"><?php echo $ssl_certificate['Owner']; ?></div>

							<h5><strong>Issuer</strong></h5>
							<div class="text-muted"><?php echo $ssl_certificate['Issuer']; ?></div>

							<h5><strong>Expiration Date</strong></h5>
							<div class="text-muted"><?php echo $ssl_certificate['Expires']; ?></div>
						</div>

					</div><!-- /.tab-pane -->

					<div class="tab-pane fade in" id="csr-tab">

						<div style="padding: 16px 20px 1px;">
							<h5 id="common_name_label"><strong>Common Name</strong> <small>Common Name for the certificate (e.g. "netsus.mycompany.corp").</small></h5>
							<div class="form-group has-feedback">
								<input type="text" name="common_name" id="common_name" class="form-control input-sm" placeholder="[Required]" value="" onFocus="validCSR();" onKeyUp="validCSR();" onBlur="validCSR();"/>
							</div>

							<h5 id="organizational_unit_label"><strong>Organizational Unit</strong> <small>Name of the organizational unit (e.g. "JAMFSW").</small></h5>
							<div class="form-group has-feedback">
								<input type="text" name="organizational_unit" id="organizational_unit" class="form-control input-sm" placeholder="[Optional]" value="" onFocus="validCSR();" onKeyUp="validCSR();" onBlur="validCSR();"/>
							</div>

							<h5 id="organization_label"><strong>Organization</strong> <small>Name of the organization (e.g. "Jamf").</small></h5>
							<div class="form-group has-feedback">
								<input type="text" name="organization" id="organization" class="form-control input-sm" placeholder="[Optional]" value="" onFocus="validCSR();" onKeyUp="validCSR();" onBlur="validCSR();"/>
							</div>

							<h5 id="locality_label"><strong>City or Locality</strong> <small>Name of the City or Locality (e.g. "Minneapolis").</small></h5>
							<div class="form-group has-feedback">
								<input type="text" name="locality" id="locality" class="form-control input-sm" placeholder="[Optional]" value="" onFocus="validCSR();" onKeyUp="validCSR();" onBlur="validCSR();"/>
							</div>

							<h5 id="state_label"><strong>State or Province</strong> <small>Name of the State or Province (e.g. "MN").</small></h5>
							<div class="form-group has-feedback">
								<input type="text" name="state" id="state" class="form-control input-sm" placeholder="[Optional]" value="" onFocus="validCSR();" onKeyUp="validCSR();" onBlur="validCSR();"/>
							</div>

							<h5 id="country_label"><strong>Country Code</strong> <small>Two-letter country code for this unit (e.g. "US").</small></h5>
							<div class="form-group has-feedback">
								<input type="text" name="country" id="country" class="form-control input-sm" placeholder="[Optional]" value="" onFocus="validCSR();" onKeyUp="validCSR();" onBlur="validCSR();"/>
							</div>

							<div class="text-right">
								<button type="submit" name="create_csr" id="create_csr" class="btn btn-primary btn-sm" disabled>Create</button>
							</div>
						</div>

					</div><!-- /.tab-pane -->

					<div class="tab-pane fade in" id="modify-tab">

						<div style="padding: 16px 20px 1px;">
							<div style="margin-bottom: 16px; border-color: #d43f3a;" class="panel panel-danger <?php echo (empty($cert_error) ? "hidden" : ""); ?>">
								<div class="panel-body">
									<div class="text-muted"><span class="text-danger glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span><?php echo $cert_error; ?></div>
								</div>
							</div>

							<div style="margin-bottom: 16px; border-color: #4cae4c;" class="panel panel-success <?php echo (empty($cert_success) ? "hidden" : ""); ?>">
								<div class="panel-body">
									<div class="text-muted"><span class="text-success glyphicon glyphicon-ok-sign" style="padding-right: 12px;"></span><?php echo $cert_success; ?></div>
								</div>
							</div>

							<h5 id="privatekey_label"><strong>Private Key</strong> <small>Paste the content of RSA private key file, including the BEGIN and END tags.</small></h5>
							<div class="form-group has-feedback">
								<textarea class="form-control input-sm" name="privatekey" id="privatekey" rows="4" onFocus="validCerts();" onKeyUp="validCerts();" onBlur="validCerts();"><?php echo (isset($_POST['privatekey']) ? $_POST['privatekey'] : ""); ?></textarea>
							</div>

							<h5 id="certificate_label"><strong>Certificate</strong> <small>Paste the content of the certificate file, including the BEGIN and END tags.</small></h5>
							<div class="form-group has-feedback">
								<textarea class="form-control input-sm" name="certificate" id="certificate" rows="4" onFocus="validCerts();" onKeyUp="validCerts();" onBlur="validCerts();"><?php echo (isset($_POST['certificate']) ? $_POST['certificate'] : ""); ?></textarea>
							</div>

							<h5 id="cabundle_label"><strong>CA Bundle</strong> <small>Paste the content of the CA bundle, including the BEGIN and END tags.</small></h5>
							<div class="form-group has-feedback">
								<textarea class="form-control input-sm" name="cabundle" id="cabundle" rows="4" onFocus="validCerts();" onKeyUp="validCerts();" onBlur="validCerts();"><?php echo (isset($_POST['cabundle']) ? $_POST['cabundle'] : ""); ?></textarea>
							</div>

							<div class="text-right">
								<button type="button" class="btn btn-primary btn-sm <?php echo (empty($cert_success) ? "hidden" : ""); ?>" data-toggle="modal" data-target="#restart-modal">Restart</button>
								<button type="submit" name="apply-certs" id="apply-certs" class="btn btn-primary btn-sm <?php echo (empty($cert_success) ? "" : "hidden"); ?>" disabled>Apply</button>
							</div>
						</div>

					</div><!-- /.tab-pane -->

				</div> <!-- end .tab-content -->

			</form> <!-- end Certificates form -->
<?php include "inc/footer.php"; ?>