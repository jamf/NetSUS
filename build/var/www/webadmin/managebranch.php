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
natcasesort($formattedpackages);

/*
 * Done with package list retrieval
 */


?>

<script type="text/javascript">
var pkgCheckedList = new Array();
<?
foreach($formattedpackages as $key => $value)
{
	$parts = explode("%", $value);
	echo "pkgCheckedList[\"$key\"] = ".($parts[3] != "" ? "true" : "false").";\n";
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
<?
		foreach($formattedpackages as $key => $value)
		{
			echo "		pkgList[\"$key\"] = \"".$value."\";\n";
		}
?>

		for (key in pkgList)
		{
			var value = pkgList[key].replace("%", " ");
			var checked = "";
			if (search == "" || pattern.test(value))
			{
				pieces = pkgList[key].split("%");
				checked = (pkgCheckedList[key] ? "checked=\"checked\"" : "");
				tableContents += "<tr id=\"tr_"+key+"\" class=\""+(num % 2 == 0 ? "object0" : "object1")+"\">";
				tableContents += "<td style=\"padding: 2px;\"><input type=\"checkbox\" name=\"packages[]\" id=\""+key+"\" value=\""+key+"\" "+checked+" onClick=\"javascript:checkBox(this.value, this.checked);\"/></td>";
				tableContents += "<td nowrap style=\"padding: 2px; width: 100%;\">"+pieces[0]+"</td>";
				tableContents += "<td nowrap style=\"padding: 2px;\"><a id=\""+num+"\" onmouseover=\"javascript:CustomOver(getPackageDetails('"+key+"'), '1', '1');\" onmouseout=\"return nd();\"><img src=\"images/objectInfo.png\" alt=\"Package Details\"/></a></td>";
				tableContents += "<td nowrap style=\"padding: 2px;\">"+pieces[1]+"</td>";
				tableContents += "<td nowrap style=\"padding: 2px;\">"+pieces[2]+"</td>";
				tableContents += "</tr>";
				num++;
			}
		}

		if (num > 0)
		{
			tableHTML += "<table class=\"objectList\" id=\"packageTable\" border=\"1\" style=\"width: 100%;\">";
			tableHTML += "<tr class=\"objectHeader\">";
			tableHTML += "<td nowrap class=\"objectHeader\">&nbsp;</td>";
			tableHTML += "<td nowrap class=\"objectHeader\" style=\"width: 100%;\">Name</td>";
			tableHTML += "<td nowrap class=\"objectHeader\">&nbsp;</td>";
			tableHTML += "<td nowrap class=\"objectHeader\">Version</td>";
			tableHTML += "<td nowrap class=\"objectHeader\">&nbsp;</td>";
			tableHTML += "</tr>";
			tableHTML += tableContents;
			tableHTML += "</table>";
		}
		else
		{
			tableHTML += "<p>No matches</p>";
		}

		document.getElementById("packageTable").innerHTML = tableHTML;
	}
	catch (err)
	{
		//alert(err);
	}
}
</script>

<form action="managebranch.php?branch=<?=$currentBranch?>" method="post" name="branchPackages" id="branchPackages">
	<input type="hidden" name="userAction" value="branchPackages">
	<table style="border: 0px; width: 100%;" class="formLabel">
		<?php 
		if ($errorMessage != "")
		{
		?>
		<tr>
			<td class="error"><?=$errorMessage?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<?php
		}
		else if ($statusMessage != "")
		{
		?>
		<tr>
			<td><font class="formLabel"><?=$statusMessage?></font></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<?
		}
		?>
		<tr>
			<td>
				<label for="chooseBranch">Change to branch: </label>
				<select name="chooseBranch" id="chooseBranch" style="min-width:40%;" onChange="javascript:location.href='managebranch.php?branch='+this.value">
				<?
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
					<option value="<?=$branch?>" <?=($currentBranch == $branch ? "selected=\"selected\"" : "")?>><?=$branch?></option>
					<?
				}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<table style="border: 0px; padding: 0px; width:100%;">
				<tr>
				<td>
				<input type="button" name="selectAll" id="selectAll" value="Select All" onClick="javascript:selectAllVisible();"/>
				<input type="button" name="clearAll" id="clearAll" value="Clear All" onClick="javascript:clearAllVisible();"/>
				</td>
				<td style="text-align:right; min-width:70%;">
				<label for="filterBy">Filter packages by:</label>
				<input type="text" name="filterBy" id="filterBy" style="min-width:50%;" onKeyUp="javascript:filterPackages();"/>
				</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table class="objectList" id="packageTable" border="1" style="width: 100%;">
<? /* Auto-filled by JavaScript */ ?>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<input type="button" name="selectAll" id="selectAll" value="Select All" onClick="javascript:selectAllVisible()"/>
				<input type="button" name="clearAll" id="clearAll" value="Clear All" onClick="javascript:clearAllVisible()"/>
			</td>
		</tr>
        <tr>
                <td style="text-align: left; width:100%;">
                <br>
                <input type="checkbox" name="autosync" value="autosync"
                <?if ($conf->containsAutosyncBranch($currentBranch))
                {
                	echo "checked=\"checked\"";
                }?> /> Automatically Enable New Updates</td></tr>
        <tr>
                <td style="text-align: left; width:100%;">
                <input type="checkbox" name="rootbranch"
				value="rootbranch"
				<?if ($conf->getSetting("rootbranch") == $currentBranch)
				{
					echo "checked=\"checked\"";
				}?> /> Root Branch</td></tr>
		<tr>
        	<td style="text-align: right; width:100%;">
	        	<input type="submit" value=" Apply " name="applyPackages" id="applyPackages" onClick="javascript:document.getElementById('filterBy').value=''; filterPackages(); return true;"/>
			</td>
		</tr>
	</table>
</form>

<p style="text-align:center;"><input type="button" value="Return to SUS Administration" onClick="javascript:location.href='admin.php?service=SUS';"/></p>

<script type="text/javascript">
filterPackages();
</script>

<?php

include "inc/footer.php";        

?>
</body>
</html>
