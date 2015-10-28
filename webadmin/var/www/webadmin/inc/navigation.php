<!-- Mobile Only Navigation -->
    <div id="mobile-nav" class="showmobile">
      <select onchange="javascript:mobileNavigationChange()" id="mobile-nav-choice">

        <option value="SUS.php" <?php if ($pageURI == "SUS.php") { echo "selected"; } ?>>Software Update Server</option>

        <option value="netBoot.php" <?php if ($pageURI == "netBoot.php") { echo "selected"; } ?>>NetBoot Server</option>
        
        <option value="LDAPProxy.php" <?php if ($pageURI == "LDAPProxy.php") { echo "selected"; } ?>>LDAP Proxy</option>

        <option value="settings.php" <?php if ($pageURI == "settings.php") { echo "selected"; } ?>>Settings</option>

        <option value="about.php" <?php if ($pageURI == "about.php") { echo "selected"; } ?>>About</option>

      </select>
    </div>
    

    <!-- open sidebar -->
    <div id="sidebar"  class="">
      <ul id="sidebar-nav">

        <li class="<?php if ($pageURI == "SUS.php") { echo "active"; } ?>"><a href="SUS.php">Software Update Server</a></li>

        <li class="<?php if ($pageURI == "netBoot.php") { echo "active"; } ?>"><a href="netBoot.php">NetBoot Server</a></li>
        
        <li class="<?php if ($pageURI == "LDAPProxy.php") { echo "active"; } ?>"><a href="LDAPProxy.php">LDAP Proxy</a></li>

        <li class="<?php if ($pageURI == "settings.php") { echo "active"; } ?>"><a href="settings.php">Settings</a></li>
        
		<li class="divider"></li>

        <li class="<?php if ($pageURI == "about.php") { echo "active"; } ?>"><a href="about.php">About</a></li>
	    
      </ul>
    </div>
    <!-- close sidebar -->
