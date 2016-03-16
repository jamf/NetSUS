<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$errorMessage = "";
$statusMessage = "";

$currentBranch = "";
if (isset($_GET['branch']) && $_GET['branch'] != "")
{
	$currentBranch = $_GET['branch'];
}

$title = "Manage packages for branch: $currentBranch";

include "inc/header.php";

if(isset($_POST['removePackages']))
{
	foreach($_POST['packages'] as $value)
	{
		$status = suExec("removefrombranch $value $currentBranch")."<br/>\n";
		if (strpos($status, "doesn't exist!") !== FALSE) // There was an error
		{
			echo $status."<br/>\n";
		}
	}
}

if(isset($_POST['applyPackages']))
{
	suExec("deleteBranch \"$currentBranch\"");
	suExec("createBranch \"$currentBranch\"");
	$num = 0;
	$packages = "";
	foreach($_POST['packages'] as $value)
	{
		$packages .= "$value ";
		$num++;
	}
	$status = suExec("addtobranch \"$packages\"".$currentBranch)."<br/>\n";
	$statusMessage = "Added $num packages to &quot;$currentBranch&quot;";

	if (isset($_POST['autosync']))
	{
		$conf->addAutosyncBranch($currentBranch);
	}
	else
	{
		$conf->deleteAutosyncBranch($currentBranch);
	}
	
	if (isset($_POST['rootbranch'])) {
        $conf->setSetting("rootbranch", $currentBranch);
        suExec("rootBranch \"$currentBranch\"");
	}
}



/*
 * Do the package list look-up now so we can generate the array in JavaScript:
 */

$packagestr = trim(suExec("getSUSlist"));
$packages = explode("\n", $packagestr);
$formattedpackages = array();
foreach ($packages as $key => $value)
{
	if ($value == "") continue;

	$packagearr = formatPackage($value);

	$parts = explode("%", $value);
	$checked = "";
	$pkgbranchlist = str_replace("'", "", str_replace("]", "", str_replace("[", "", $packagearr[4])));
	foreach(explode(",",$pkgbranchlist) as $pkgbranchname)
	{
		if ($pkgbranchname == $currentBranch)
		{
			$checked = "checked=\\\"checked\\\"";
		}
	}
	
	$formattedpackages[$packagearr[0]] = $packagearr[1]."%".$packagearr[2]."%".$packagearr[3]."%".$checked;
}
uksort($formattedpackages);
$formattedpackages = array_reverse($formattedpackages, TRUE);

/*
 * Done with package list retrieval
 */


?>

<script type="text/javascript">
var pkgCheckedList = new Array();
<?php
foreach($formattedpackages as $key => $value)
{
	$parts = explode("%", $value);
	echo "pkgCheckedList[\"$key\"] = ".($parts[3] != "" ? "true" : "false").";\n";
}
?>

var pkgDeprecatedList = new Array();
<?php
foreach($formattedpackages as $key => $value)
{
	$parts = explode("%", $value);
	echo "pkgDeprecatedList[\"$key\"] = ".(strpos($parts[1],'Deprecated') !== false ? "true" : "false").";\n";
}
?>

function selectAllVisible()
{
	var boxes = document.branchPackages;
	for (i = 0; i < boxes.length; i++)
	{
		if (boxes.elements[i].name != "rootbranch" && boxes.elements[i].name != "autosync")
		{
			boxes.elements[i].checked = true;
			checkBox(boxes.elements[i].value, boxes.elements[i].checked);
		}
	}
}

function clearAllVisible()
{
	var boxes = document.branchPackages;
	for (i = 0; i < boxes.length; i++)
	{
		if (boxes.elements[i].name != "rootbranch" && boxes.elements[i].name != "autosync")
		{
			boxes.elements[i].checked = false;
			checkBox(boxes.elements[i].value, boxes.elements[i].checked);
		}
	}
}

function clearAllDeprecated()
{
	var boxes = document.branchPackages;
	for (i = 0; i < boxes.length; i++)
	{
		if (boxes.elements[i].className == "deprecated")
		{
			boxes.elements[i].checked = false;
			checkBox(boxes.elements[i].value, boxes.elements[i].checked);
		}
	}
}

function checkBox(id, checked)
{
	pkgCheckedList[id] = checked;
}

function filterPackages()
{
	try
	{
		var pkgList = new Array();
		var search = document.getElementById("filterBy").value;
		var pattern = new RegExp(search, "mi");
		var num = 0;
		var tableHTML = "";
		var tableContents = "";
<?php
		foreach($formattedpackages as $key => $value)
		{
			echo "		pkgList[\"$key\"] = \"".$value."\";\n";
		}
?>

		for (key in pkgList)
		{
			var value = pkgList[key].replace("%", " ");
			var checked = "";
			var deprecated = "";
			if (search == "" || pattern.test(value))
			{
				pieces = pkgList[key].split("%");
				checked = (pkgCheckedList[key] ? "checked=\"checked\"" : "");
				deprecated = (pkgDeprecatedList[key] ? " class=\"deprecated\"" : "");
				tableContents += "<tr id=\"tr_"+key+"\" class=\""+(num % 2 == 0 ? "object0" : "object1")+"\">";
				tableContents += "<td nowrap><input type=\"checkbox\" name=\"packages[]\" id=\""+key+"\" value=\""+key+"\" "+checked+deprecated+" onClick=\"javascript:checkBox(this.value, this.checked);\"/></td>";
				tableContents += "<td>"+pieces[0]+"</td>";
				tableContents += "<td nowrap><a id=\""+num+"\" onmouseover=\"javascript:CustomOver(getPackageDetails('"+key+"'), '1', '1');\" onmouseout=\"return nd();\"><img src=\"images/objectInfo.png\" alt=\"Package Details\"/></a></td>";
				tableContents += "<td nowrap>"+pieces[1]+"</td>";
				tableContents += "<td nowrap>"+pieces[2]+"</td>";
				tableContents += "</tr>";
				num++;
			}
		}

		if (num > 0)
		{
			tableHTML += "<table id=\"packageTable\" border=\"1\">";
			tableHTML += "<thead>";
			tableHTML += "<tr>";
			tableHTML += "<th>&nbsp;</th>";
			tableHTML += "<th>Name</th>";
			tableHTML += "<th>&nbsp;</th>";
			tableHTML += "<th>Version</th>";
			tableHTML += "<th>Date</th>";
			tableHTML += "</tr>";
			tableHTML += "</thead>";
			tableHTML += "<tbody>";
			tableHTML += tableContents;
			tableHTML += "</tbody>";
			tableHTML += "</table>";
		}
		else
		{
			tableHTML += "No matches";
		}

		document.getElementById("packageTable").innerHTML = tableHTML;
	}
	catch (err)
	{
		//alert(err);
	}
}
</script>

<?php 
if ($errorMessage != "")
{
?>
<div class="errorMessage"><?php echo $errorMessage?></div>
<?php
}
else if ($statusMessage != "")
{
?>
<div class="successMessage"><?php echo $statusMessage?></div>
<?php
}
?>

<div id="form-wrapper">

	<form action="managebranch.php?branch=<?php echo $currentBranch?>" method="post" name="branchPackages" id="branchPackages">

		<div id="form-inside">

			<input type="hidden" name="userAction" value="branchPackages">

			<span class="label">Branch Displayed: 
			<select name="chooseBranch" id="chooseBranch" onChange="javascript:location.href='managebranch.php?branch='+this.value">
				<?php
				$branchstr = trim(suExec("getBranchlist"));
				$branches = explode(" ",$branchstr);
				if (count($branches) == 0)
					echo "<tr><td>No branches</td></tr>\n";
				else
				{
					sort($branches);
				}
				foreach($branches as $branch)
				{
					?>
					<option value="<?php echo $branch?>" <?php echo ($currentBranch == $branch ? "selected=\"selected\"" : "")?>><?php echo $branch?></option>
					<?php
				}
				?>
			</select>
			</span>


			<label for="autosync" class="label">
			<input type="checkbox" name="autosync" value="autosync"
	      <?php if ($conf->containsAutosyncBranch($currentBranch))
	      {
	      	echo "checked=\"checked\"";
	      }?> />
      Automatically Enable New Updates</label>

      <label for="rootbranch" class="label">
		  <input type="checkbox" name="rootbranch" value="rootbranch"
						<?php if ($conf->getSetting("rootbranch") == $currentBranch)
						{
							echo "checked=\"checked\"";
						}?> />
			Use as Root Branch</label>
			<br>

			<span class="label">Filter updates by:
				<input type="text" name="filterBy" id="filterBy" style="min-width:20%; margin-top:-3px;" onKeyUp="javascript:filterPackages();"/>
			</span>

			<input type="button" name="selectAll" id="selectAll" class="insideActionButton" value="Select All" onClick="javascript:selectAllVisible();"/>
			<input type="button" name="clearAll" id="clearAll" class="insideActionButton" value="Clear All" onClick="javascript:clearAllVisible();"/>
			<input type="button" name="clearDeprecated" id="clearDeprecated" class="insideActionButton" value="Clear All Deprecated" onClick="javascript:clearAllDeprecated();"/>


			<table id="packageTable" style="width:90%;">
				<?php /* Auto-filled by JavaScript */ ?>
			</table>

			<input type="button" name="selectAll" id="selectAll" class="insideActionButton" value="Select All" onClick="javascript:selectAllVisible();"/>
			<input type="button" name="clearAll" id="clearAll" class="insideActionButton" value="Clear All" onClick="javascript:clearAllVisible();"/>
			<input type="button" name="clearDeprecated" id="clearDeprecated" class="insideActionButton" value="Clear All Deprecated" onClick="javascript:clearAllDeprecated();"/>

			<br>
			<br>

			<input type="submit" value=" Apply " name="applyPackages" id="applyPackages" class="insideActionButton" onClick="javascript:document.getElementById('filterBy').value=''; filterPackages(); return true;"/>

		</div> <!-- end #form-inside -->
	</form>

	<div id="form-buttons">

		<div id="read-buttons">

			<input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='SUS.php'">

		</div>

	</div>

</div> <!-- end #form-wrapper -->

<script>
filterPackages();
</script>

<?php

include "inc/footer.php";        

?>
