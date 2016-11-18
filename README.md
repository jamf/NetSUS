
# NetSUS Downloads

Installer:
https://github.com/jamf/NetSUS/releases/download/4.1.0/NetSUSLPInstaller_4.1.0.run.zip

OVA:
https://github.com/jamf/NetSUS/releases/download/4.1.0/NetSUSLP_4.1.0.ova.zip


# What is NetSUS?

The NetBoot/SUS/LP server allows you to host an internal software update server (SUS), a NetBoot server, and a LDAP Proxy server **all on the same Linux system**. For a list of supported Linux distributions see [Requirements](#requirements).

<p align="center"><img src="docs/images/attachments/dashboard.png" height="270"></p>

* **Software Update Server** - Unlike a standard SUS, the SUS hosted by the NetBoot/SUS/LP server allows you to control which software updates should be installed on each computer in your organization.

* **NetBoot Server** - The NetBoot/SUS/LP server allows you to host a NetBoot image. You can boot computers to a NetBoot image in place of a recovery partition or external drive when imaging.

* **LDAP Proxy** - Use the NetBoot/SUS/LP server as a lightweight proxy that acts as a middleware layer between LDAP clients and LDAP directory servers.

* **Web Application** - The NetBoot/SUS/LP server includes a web application that can be used to easily manage your NetBoot and Software Update Servers as well as your LDAP Proxy. The dashboard page is shown above.

## Documentation

For a getting started guide and step-by-step walkthroughs check out the **[documentation for the current release](docs/README.md)**

## <a name="requirements"></a>Requirements

#### Supported Linux distributions:

* Ubuntu 10.04 LTS Server
* Ubuntu 12.04 LTS Server
* Ubuntu 14.04 LTS Server
* Red Hat Enterprise Linux (RHEL) 6.4 or later
* CentOS 6.4 or later

#### To install the NetBoot/SUS/LP server using an installer, you need:

* The NetBoot/SUS/LP Server Installer (.run), available at:  
<https://jamfnation.jamfsoftware.com/viewProduct.html?id=180&view=info>
* 500 GB of disk space available 
* 1 GB of RAM

#### To set up the NetBoot/SUS/LP server as an appliance, you need:

* The OVA file for the NetBoot/SUS/LP server, available at:
<https://jamfnation.jamfsoftware.com/viewProduct.html?id=180&view=info>
* Virtualization software that supports Open Virtualization Format 
* 500 GB of disk space available
* 2 GB of RAM

**Only Intel-based Macs can use a NetBoot server hosted by the NetBoot/SUS/LP server.**
