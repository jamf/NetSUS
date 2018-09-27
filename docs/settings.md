# Settings
Walkthroughs for options on the **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> page.


## Disabling the GUI
The User Menu has the functionality to disable the web interface.

1. Log in to the NetSUSLP web application.

2. In the top-right corner of the page, click **Users** <img height="20" src="images/thumbnails/user_menu.png"> .

3. In the drop down list select "Disable GUI".

4. Click **Disable** in the confirmation prompt. You will be immediately logged out and the "WebAdmin GUI is disabled" status message will be displayed.


## Enabling the GUI
The NetSUSLP allows you to enable the WebAdmin GUI from the command line.

1. Log in to the NetSUSLP as a user with sudo privileges, using ssh or via the console.

2. Execute the following command:

		sudo /var/www/html/webadmin/scripts/adminHelper.sh enablegui

3. Refresh the NetSUSLP in your browser.


## Active Directory Login
The NetSUSLP allows you to configure the web interface to authenticate using Active Directory LDAP.

1. Log in to the NetSUSLP web application.

2. In the top-right corner of the page, click **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> .

3. In the "System" section, click **Accounts** <img height="20" src="images/thumbnails/user_icon.png"> .

4. Click the "Web Interface" tab.

5. Click the link for the Active Directory domain.

6. Enter the Active Directory LDAP information in the modal dialog.\
   Note: Checking the **Use SSL** checkbox will automatically change the port to the default value.

7. Click **Save**. A notification will appear regarding groups.

8. Click the **+ Add** button in the upper-right of the table.

9. Enter the Active Directory group name for NetSUSLP administration in the modal dialog.

10. Click **Save**.


## Network Settings
The Network settings allow you to configure the following:

* Hostname
* Network type (DHCP or static) 
* IP address
* Netmask
* Gateway
* DNS servers 
* Enabling/Disabling SSH
* Enabling/Disabling Firewall

1. Log in to the NetSUSLP web application.

2. In the side navigation menu or in the mobile dropdown menu, click **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> .

3. In the "System" section, click **Network** <img height="20" src="images/thumbnails/network_icon.png">.

4. Configure the settings on the pane.

5. Click **Save**.


## Date/Time Settings
The Date/Time settings allow you to do the following:

* View the current time on the NetSUSLP. 
* Change the current time zone on the NetSUSLP. 
* Use a network time server to synchronize the date/time.

1. Log in to the NetSUSLP web application.

2. In the side navigation menu or in the mobile dropdown menu, click **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> .

3. In the "System" section, click **Date/Time** <img height="20" src="images/thumbnails/clock_icon.png"> .

4. Configure the settings on the pane.

5. Click **Save**.


## Certificates Settings
Certificates Settings allows you to modify the server settings with either a Tomcat or Slapd certificate to be used for communication with the NetSUSLP.

1. Log in to the NetSUSLP web application.

2. In the side navigation menu or in the mobile dropdown menu, click **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> .

3. In the "System" section, click **Certificates** <img height="30" src="images/thumbnails/certificates_icon.png"> .

4. If you wish to create a CSR, update the Common Name field and click "Create". A zip archive will download containing a new private key and related signing request.

5. Enter the "Private Key", "Certificate", and "Chain" fields with the appropriate unencrypted certificate information.
	
    <img src="images/attachments/certificates.png" width="500">

6. Click **Save**.

7. Restart the NetSUSLP.


## Logs Settings
The Logs settings allows you to select and view the system log files on the NetSUSLP.

1. Log in to the NetSUSLP web application.

2. In the side navigation menu or in the mobile dropdown menu, click **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> .

3. In the "System" section, click **Logs** <img height="20" src="images/thumbnails/logs_icon.png">.

4. Select the log file you wish to view.

5. Enter the number of lines (from the end) of the log file you wish to see. If this is left blank, the entire log is displayed.

5. Click **Display**.


## Storage Settings
The Storage settings allows you to expand the logical disk volume on the NetSUSLP, if the VMDK has been expanded.

1. Shut down the NetSUSLP.

2. Expand the VMDK of the NetSUSLP from within the hypervisor.

3. Start up the NetSUSLP.

4. Log in to the NetSUSLP web application.

5. In the side navigation menu or in the mobile dropdown menu, click **Settings** <img height="20" src="images/thumbnails/settings_menu.png"> .

6. In the "System" section, click **Storage** <img height="20" src="images/thumbnails/storage_icon.png">.

7. If there is sufficient space available, the Resize button will be enabled, click **Resize**.

8. Restart the NetSUSLP for the additional storage to become available.
