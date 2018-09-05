
		</div>
		<!-- /#page-content-wrapper -->

	</div>
	<!-- /#wrapper -->

	<!-- Menu Toggle Script -->
	<script>
		$('#menu-toggle').click(function(e) {
			e.preventDefault();
			$('#wrapper').toggleClass('toggled');
		});
	</script>
<?php
// notifications
$notifications = array();
if ($conf->needsToChangeAnyPasses()) {
	array_push($notifications, "accounts");
}
if (trim(suExec("getSSLstatus")) != "true") {
	array_push($notifications, "certificates");
}
$df_result_str = trim(suExec("diskusage"));
$df_result = explode(":", $df_result_str);
$df_free_percent = ceil(100*$df_result[2]/$df_result[0]);
if ($df_free_percent < 20) {
	array_push($notifications, "storage");
}

if (sizeof($notifications) > 0) { ?>

	<script type="text/javascript">
		$(document).ready(function(){
			var count = <?php echo sizeof($notifications); ?>;
			if (count > 0) {
				$('#notify-badge').html(count);
				$('#notify-badge').removeClass('hidden');
				$('#notify-button').prop('disabled', false);
			}
		});
	</script>

	<!-- Notification Modal -->
	<div class="modal fade" id="notify-modal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title">Notifications</h3>
				</div>
				<table class="table table-striped" style="margin-bottom: 0px;">
<?php if (in_array("accounts", $notifications)) { ?>
					<tr>
						<td class="settings-item">
							<a href="accounts.php"><img src="images/settings/Account.png" alt="User Accounts"></a>
						</td>
						<td>
							<p style="padding-top: 12px;">Credentials have not been changed for all the default user accounts.</p>
							<p><a href="accounts.php">Click here to change them.</a></p>
						</td>
					</tr>
<?php }
if (in_array("certificates", $notifications)) { ?>
					<tr>
						<td class="settings-item">
							<a href="certificates.php"><img src="images/settings/PKI.png" alt="Certificates"></a>
						</td>
						<td>
							<p style="padding-top: 12px;">The system is using a self-signed certificate.</p>
							<p><a href="certificates.php">Click here to resolve this.</a></p>
						</td>
					</tr>
<?php }
if (in_array("storage", $notifications)) { ?>
					<tr>
						<td class="settings-item">
							<a href="storage.php"><img src="images/settings/Storage.png" alt="Storage"></a>
						</td>
						<td>
							<p style="padding-top: 12px;">The file system is running low on disk space.</p>
							<p><a href="storage.php">Click here to resolve this.</a></p>
						</td>
					</tr>
<?php } ?>
				</table>
				<div class="modal-footer">
					<button type="button" data-dismiss="modal" class="btn btn-default btn-sm pull-right">Close</button>
				</div>
			</div>
		</div>
	</div>
	<!-- /#modal -->

<?php }

if (isset($_POST['restart-confirm'])) { ?>
	<script>
		$(window).load(function() {
			setTimeout('location.href = "index.php"', 120000);
			$('#restart-title').text('Restarting...');
			$('#restart-message').addClass('hidden');
			$('#restart-progress').removeClass('hidden');
			$('#restart-cancel').prop('disabled', true);
			$('#restart-confirm').prop('disabled', true);
			$('#restart-modal').modal('show');
		});
	</script>
<?php
	shell_exec("sudo /bin/sh scripts/adminHelper.sh restart > /dev/null 2>&1 &");
	// Unset all of the session variables.
	$_SESSION = array();
	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	// Finally, destroy the session.
	session_destroy();
}

if (isset($_POST['shutdown-confirm'])) { ?>
	<script>
		$(window).load(function() {
			setTimeout('location.href = "https://www.jamf.com/jamf-nation/third-party-products/180/"', 10000);
			$('#shutdown-title').text('Shutting Down...');
			$('#shutdown-message').addClass('hidden');
			$('#shutdown-progress').removeClass('hidden');
			$('#shutdown-cancel').prop('disabled', true);
			$('#shutdown-confirm').prop('disabled', true);
			$('#shutdown-modal').modal('show');
		});
	</script>
<?php
	shell_exec("sudo /bin/sh scripts/adminHelper.sh shutdown > /dev/null 2>&1 &");
	// Unset all of the session variables.
	$_SESSION = array();
	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	// Finally, destroy the session.
	session_destroy();
}

if (isset($_POST['disablegui-confirm'])) { ?>
	<script>
		$(window).load(function() {
			setTimeout('location.href = "index.php"', 3000);
			$('#disablegui-title').text('Disabling GUI...');
			$('#disablegui-message').addClass('hidden');
			$('#disablegui-progress').removeClass('hidden');
			$('#disablegui-cancel').prop('disabled', true);
			$('#disablegui-confirm').prop('disabled', true);
			$('#disablegui-modal').modal('show');
		});
	</script>
<?php
	$conf->setSetting("webadmingui", "Disabled");
	// Unset all of the session variables.
	$_SESSION = array();
	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	// Finally, destroy the session.
	session_destroy();
} ?>

</body>

</html>