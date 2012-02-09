<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

//Change the view depending on what tab the user has clicked on
if (isset($_POST["userAction"]) && $_POST["userAction"] != "") {
	$userAction = $_POST["userAction"];
} else {
	$userAction = "Services";
}

if ( $userAction == "Network" )
{
	$type = getNetType();
	$jsscriptfiles = "<script type=\"text/javascript\" src=\"scripts/adminNetworkSettings.js\"></script>";
	$onloadjs = "onLoadStaticOptionsToggle('$type');";
}

if ( $userAction == "Services" )
{
	$jsscriptfiles = "<script type=\"text/javascript\" src=\"scripts/adminServicesSettings.js\"></script>";
	$onloadjs = "resetProgressIndicator();";
}

$currentIP = trim(getCurrentIP());

$title = "Appliance Administration";

if ( $userAction == "" | $userAction == "Services" )
{
	$servicesli = "<li class='selected'>";
}
else 
	$servicesli = "<li>";
if ( $userAction == "Network" )
{
	$networkli = "<li class='selected'>";
}
else
	$networkli = "<li>";
if ( $userAction == "DateTime" )
{
	$datetimeli = "<li class='selected'>";
}
else
	$datetimeli = "<li>";

$headerTabs =<<<ENDOFTABS
<script type="text/javascript">
function submitForm(view){
	document.f.userAction.value=view;
	document.f.submit();
}

</script>

<form method="post" action="admin.php" name="f" id="f">
<input type="hidden" name="userAction" value="$userAction">
<div id="tabNav">
<ul class="tabNav">
$servicesli
<a href="javascript:submitForm('Services')"><img class="centerIMG" src="images/tabs/Advanced.png" width="24" height="24">Services</a>
</li>

$networkli
<a href="javascript:submitForm('Network')"><img class="centerIMG" src="images/tabs/Network.png" width="24" height="24">Network</a>
</li>

$datetimeli
<a href="javascript:submitForm('DateTime')"><img class="centerIMG" src="images/tabs/DateAndTime.png" width="24" height="24">Date/Time</a>
</li>
</ul>
</div>
</form>

<!--end tabNav-->
ENDOFTABS;

include "inc/header.php";

?>

<center>
<?
switch ($userAction) {
	case "DateTime":
		include "adminDateTimeSettings.php";
		break;
	case "Network":
		include "adminNetworkSettings.php";
		break;
	case "Services":
		include "adminServicesSettings.php";
		break;
}
?>

</center>
<br/>
<?include "inc/footer.php";?>
</body>
</html>
