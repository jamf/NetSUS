<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$ldaperror = "";
$ldapsuccess = "";


$title = "LDAP";


if (isset($_POST['saveLDAPConfiguration']) && isset($_POST['server']) && isset($_POST['domain']))
{
        if ($_POST['server'] == "")
        {
                $ldaperror = "Specify LDAP server.";
        }
        else if ($_POST['domain'] == "")
        {
                $ldaperror = "Specify a domain.";
        }
        else {
                $conf->setSetting("ldapserver", $_POST['server']);
                $conf->setSetting("ldapdomain", $_POST['domain']);
                $ldapsuccess = "Saved LDAP configuration.";
        }
}

if (isset($_POST['addadmin']) && isset($_POST['cn']) && $_POST['cn'] != "")
{
        $conf->addAdmin($_POST['cn']);
}

if (isset($_GET['deleteAdmin']) && $_GET['deleteAdmin'] != "")
{
        $conf->deleteAdmin($_GET['deleteAdmin']);
}
include "inc/header.php";


?>

<script>
function validateLDAPAdmin()
{
        if (document.getElementById("cn").value != "")
                document.getElementById("addadmin").disabled = false;
        else
                document.getElementById("addadmin").disabled = true;
}
</script>


<?php if ($ldaperror != "") { ?>
        <?php echo "<div class=\"errorMessage\">ERROR: " . $ldaperror . "</div>" ?>
<?php } ?>

<?php if ($ldapsuccess != "") { ?>
        <?php echo "<div class=\"successMessage\">" . $ldapsuccess . "</div>" ?></span>
<?php } ?>

<h2>LDAP</h2>

<div id="form-wrapper">

        <form action="ldap.php" method="post" name="LDAP" id="LDAP">

                <div id="form-inside">
                        <input type="hidden" name="userAction" value="LDAP">

                        <span class="label">Server</span>
                        <input type="text" name="server" id="server" value="<?php echo $conf->getSetting('ldapserver'); ?>" />
                        <br>

                        <span class="label">Domain</span>
                        <input type="text" name="domain" id="domain" value="<?php echo $conf->getSetting('ldapdomain'); ?>" />
                        <br>

                        <input type="submit" value="Save" name="saveLDAPConfiguration" id="saveLDAPConfiguration" class="insideActionButton" />
                        <br>
                        <br>


                        <span class="label">Administration Groups</span>
                        <input type="text" name="cn" id="cn" value="" onKeyUp="validateLDAPAdmin();" onChange="validateLDAPAdmin();" />
                        <input type="submit" name="addadmin" id="addadmin" class="insideActionButton" value="Add" disabled="disabled" />
                        <br>
                        <table class="branchesTable">
                                <tr>
                                        <th>Group Name</th>
                                        <th></th>
                                </tr>
                                <?php foreach($conf->getAdmins() as $key => $value) { ?>
                                <tr class="<?php ($key % 2 == 0 ? "object0" : "object1"); ?>">
                                        <td><?php echo $value['cn']?></td>
                                        <td><a href="ldap.php?service=LDAP&deleteAdmin=<?php echo urlencode($value['cn'])?>">Delete</a>
                                </tr>
                                <? } ?>
                        </table>

                </div>

                <div id="form-buttons">

                        <div id="read-buttons">

                                <input type="button" id="back-button" name="action" class="alternativeButton" value="Back" onclick="document.location.href='settings.php'">

                        </div>

                </div>

        </form>

</div>


<?php include("inc/footer.php"); ?>

