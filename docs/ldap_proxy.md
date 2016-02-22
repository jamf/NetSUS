# LDAP Proxy ServerThe LDAP Proxy Server is a proxy server that allows you to expose an access point to an LDAP Server. In doing so it allows you to adjust the distinguished name to whatever you choose, as well as allows you to put multiple LDAP Servers and sections under the same distinguished name.
1. Log in to the NetBoot/SUS/LP server web application.2. Click **LDAP Proxy Server**.On a smartphone, this option is in the pop-up menu.3. Enter "Exposed Distinguished Name" that you intend to use to reach the proxy.4. Enter "Real Distinguished Name" that you use to connect to the LDAP Server. 

5. Enter "LDAP URL" with port of the LDAP Server.6. Click "Add"

	```
	NEED IMAGE HERE
	```7. Enter as many other LDAP Connections as you want to configure. 

8. Click "Enable LDAP Proxy".

## Using the LDAP Proxy Server with the Casper Suite
**Note**: The instructions in this section are for the Casper Suite v9.0 or later. To add a LDAP Proxy Server to the JSS you will be adding the server as an LDAP Server.
1. Log into the JSS with a user that can add an LDAP Server.2. In the top-right corner of the page, click **Settings**.3. Then click **LDAP Servers**.4. Then click **New**.5. Then click the **Configure Manually** radio button.6. Then click **Next**.7. Then configure the LDAP Server normally
	**Note**: Your Distinguished Names now match what you entered for "Exposed Distinguished Name" in the LDAP Proxy. Pay attention to the port and SSL Verification as it will be dependent on how you configured your certificate on the NetBoot/SUS/LP Server as well.8. Then click **Save**.

9. Then click Test.10. Test your connection and if you configured it right, it should work.