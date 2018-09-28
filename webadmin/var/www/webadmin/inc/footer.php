
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
// connected sharing users
$connections = trim(suExec("getconns"));
if ($connections > 0) {
	if ($connections == 1) {
		$conns_msg = "is 1 user";
	} else {
		$conns_msg = "are ".$connections." users";
	}
}

// ssh status
$gui_ssh_msg = "";
$ssh_running = (trim(suExec("getSSHstatus")) == "true");
if (!$ssh_running) {
	$gui_ssh_msg = "SSH is disabled. Console access is required to re-enable the web interface.";
}
?>
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST" name="Server" id="Server">
		<!-- Restart Modal -->
		<div class="modal fade" id="restart-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h3 id="restart-title" class="modal-title">Restart</h3>
					</div>
					<div class="modal-body" id="restart-message">
<?php if (isset($conns_msg)) { ?>
						<div style="margin-top: 10px; margin-bottom: 6px; border-color: #eea236;" class="panel panel-warning">
							<div class="panel-body">
								<div class="text-muted"><span class="text-warning glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>There <?php echo $conns_msg; ?> connected to this server. If you restart they will be disconnected.</div>
							</div>
						</div>
<?php } ?>
						<div style="padding: 8px 0px;">Are you sure you want to restart the Server?</div>
					</div>
					<div class="modal-body hidden" id="restart-progress">
						<div class="text-center" style="padding: 8px 0px;"><img src="images/progress.gif"></div>
					</div>
					<div class="modal-footer">
						<button type="button" id="restart-cancel" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
						<button type="submit" name="restart-confirm" id="restart-confirm" class="btn btn-primary btn-sm pull-right" value="restart">Restart</button>
					</div>
				</div>
			</div>
		</div>
		<!-- /#modal -->

		<!-- Shut Down Modal -->
		<div class="modal fade" id="shutdown-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h3 id="shutdown-title" class="modal-title">Shut Down</h3>
					</div>
					<div class="modal-body" id="shutdown-message">
<?php if (isset($conns_msg)) { ?>
						<div style="margin-top: 10px; margin-bottom: 6px; border-color: #eea236;" class="panel panel-warning">
							<div class="panel-body">
								<div class="text-muted"><span class="text-warning glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span>There <?php echo $conns_msg; ?> connected to this server. If you restart they will be disconnected.</div>
							</div>
						</div>
<?php } ?>
						<div style="padding: 8px 0px;">Are you sure you want to shut down the Server?<br>The Server will need to be restarted manually.</div>
					</div>
					<div class="modal-body hidden" id="shutdown-progress">
						<div class="text-center" style="padding: 8px 0px;"><img src="images/progress.gif"></div>
					</div>
					<div class="modal-footer">
						<button type="button" id="shutdown-cancel" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
						<button type="submit" name="shutdown-confirm" id="shutdown-confirm" class="btn btn-primary btn-sm pull-right" value="shutdown">Shut Down</button>
					</div>
				</div>
			</div>
		</div>
		<!-- /#modal -->

		<!-- Disable GUI Modal -->
		<div class="modal fade" id="disablegui-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h3 id="disablegui-title" class="modal-title">Disable GUI</h3>
					</div>
					<div class="modal-body" id="disablegui-message">
<?php if (!empty($gui_ssh_msg)) { ?>
						<div style="margin-top: 10px; margin-bottom: 6px; border-color: #eea236;" class="panel panel-warning">
							<div class="panel-body">
								<div class="text-muted"><span class="text-warning glyphicon glyphicon-exclamation-sign" style="padding-right: 12px;"></span><?php echo $gui_ssh_msg; ?></div>
							</div>
						</div>
<?php } ?>
						<div style="padding: 8px 0px;">Are you sure you want to disable the web interface for the Server?<br>Command line access is required to re-enable the web interface.</div>
					</div>
					<div class="modal-body hidden" id="disablegui-progress">
						<div class="text-center" style="padding: 8px 0px;"><img src="images/progress.gif"></div>
					</div>
					<div class="modal-footer">
						<button type="button" id="disablegui-cancel" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
						<button type="submit" name="disablegui-confirm" id="disablegui-confirm" class="btn btn-primary btn-sm pull-right" value="disablegui">Disable</button>
					</div>
				</div>
			</div>
		</div>
		<!-- /#modal -->
	</form>

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

if (isset($_POST['restart-confirm'])) {
	shell_exec("sudo /bin/sh scripts/adminHelper.sh restart > /dev/null 2>&1 &");
}
if (isset($_POST['shutdown-confirm'])) {
	shell_exec("sudo /bin/sh scripts/adminHelper.sh shutdown > /dev/null 2>&1 &");
}
if (isset($_POST['disablegui-confirm'])) {
	$conf->setSetting("webadmingui", "Disabled");
}
if (isset($_POST['restart-confirm']) || isset($_POST['shutdown-confirm']) || isset($_POST['disablegui-confirm'])) {
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
?>
	<script>
		$(window).load(function() {
			$(location).attr('href', 'index.php');
		});
	</script>
<?php } ?>

</body>

</html>