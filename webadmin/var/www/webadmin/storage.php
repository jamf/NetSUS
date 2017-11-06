<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "Storage";

if (isset($_POST['resize']))
{
	header('Location: storageCtl.php?resize=true');
}

include "inc/header.php";

// ####################################################################
// End of GET/POST parsing
// ####################################################################

$result = trim(suExec("allowResize"));
if (strpos($result, 'ERROR') === false) {
	$resize = preg_replace('/[^\d]+/', '', $result);
} else {
	$resize = 0;
}

?>

<h2>Storage</h2>

<div class="row">
	<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">

		<form action="storage.php" method="post" name="storage" id="system">

			<hr>

			<span class="label label-default">Expand Volume</span>
			<span class="description">
			<?php
				if (strpos($result,'ERROR') !== false) {
					echo str_replace('ERROR: ', '', $result);
				}
				else {
					echo $resize." MB available";
				}
			?>
			</span>
			<input type="submit" name="resize" id="resize" class="btn btn-primary btn-sm" value="Resize" onClick="javascript: return yesnoprompt('Are you sure you want to expand the volume?\nThe system will require a restart.');" <?php if ($resize < 4) { echo "disabled=\"disabled\""; } ?>/>

			<br>
			<br>
			<hr>
			<br>

			<input type="button" id="back-button" name="action" class="btn btn-sm btn-default" value="Back" onclick="document.location.href='settings.php'">

		</form> <!-- end form Storage -->

	</div><!-- /.col -->
</div><!-- /.row -->

<?php include "inc/footer.php"; ?>