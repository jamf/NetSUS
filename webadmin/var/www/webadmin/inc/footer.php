
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
<?php } ?>

</body>

</html>