# LDAP Proxy ServerThe LDAP Proxy Server is a proxy server that allows you to expose an access point to an LDAP Server. In doing so it allows you to adjust the distinguished name to whatever you choose, as well as allows you to put multiple LDAP Servers and sections under the same distinguished name.
1. Log in to the NetBoot/SUS/LP server web application.2. Click **LDAP Proxy Server**.On a smartphone, this option is in the pop-up menu.3. Enter "Exposed Distinguished Name" that you intend to use to reach the proxy. For more information, see below.4. Enter "Real Distinguished Name" that you use to connect to the LDAP Server. For more information, see below. 

5. Enter "LDAP URL" with port of the LDAP Server. For more information, see below.6. Click "Add"

	```
	NEED IMAGE HERE
	```7. Enter as many other LDAP Connections as you want to configure. 

8. Click "Enable LDAP Proxy".


## Exposed Distinguished Name

This is the distinguished name of name of the Proxy, which will serve as your exposed access point. This can be named however you wish. For example: 

You want to name your Proxy as `proxy.company.com`, you enter `DC=proxy,DC=company,DC=com`

**Note**: Pointing multipile LDAP servers to a single Proxy URL is currently not supported on the NetBoot/SUS/LP. 

## Real Distinguished Name

This is the distinguished name of your LDAP that the Proxy is pointing to. This must be named according to your LDAP. For example:

Your LDAP's URL is `ad.company.com`, you enter `DC=ad,DC=company,DC=com`

If you want to point your Proxy to a more specefic section of your LDAP, you can add additional attributes along with the DC (Domain Component) attributes. For example: `DC=ad,DC=company,DC=com,OU=people`

You can also add mutltiple LDAP servers to a Proxy. 

