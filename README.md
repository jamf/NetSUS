
# NetSUS Downloads

Installer:
[https://github.com/jamf/NetSUS/releases/download/5.0.2/NetSUSLPInstaller_5.0.2.run](https://github.com/jamf/NetSUS/releases/download/5.0.2/NetSUSLPInstaller_5.0.2.run)

OVA:
[https://github.com/jamf/NetSUS/releases/download/5.0.2/NetSUSLP_5.0.2.ova](https://github.com/jamf/NetSUS/releases/download/5.0.2/NetSUSLP_5.0.2.ova)

# Deprecation Notice

NetBoot and Software Update services have been deprecated by Apple, and as such this project will no longer be receiving further updates.

# What is NetSUS?

The NetSUSLP allows you to host an internal software update server (SUS), a NetBoot server, file shares, and a LDAP Proxy server **all on the same Linux system**. For a list of supported Linux distributions see [Requirements](#requirements).

<p align="center"><img src="docs/images/attachments/dashboard.png" height="400"></p>

* **Web Application** - The NetSUSLP includes a web application that can be used to easily manage your NetBoot, Software Update Servers and File Shares as well as your LDAP Proxy. The dashboard page is shown above.

* **File Sharing** - Use the NetSUSLP as a file share distribution point for Jamf Pro. You can share files using SMB, AFP, and HTTP.

* **Software Update Server** - Unlike a standard SUS, the SUS hosted by the NetSUSLP allows you to control which software updates should be installed on each computer in your organization.

* **NetBoot Server** - The NetSUSLP allows you to host a NetBoot image. You can boot computers to a NetBoot image in place of a recovery partition or external drive when imaging.

* **LDAP Proxy** - Use the NetSUSLP as a lightweight proxy that acts as a middleware layer between LDAP clients and LDAP directory servers.

## Documentation

For a getting started guide and step-by-step walkthroughs check out the **[documentation for the current release](docs/README.md)**

## <a name="requirements"></a>Requirements

#### Supported Linux distributions:

* Ubuntu 14.04 LTS Server
* Ubuntu 16.04 LTS Server (Recommended)
* Ubuntu 18.04 LTS Server
* Red Hat Enterprise Linux (RHEL) 6.4 or later
* CentOS 6.4 or later

#### To install the NetSUSLP using an installer, you need:

* The NetSUSLP Installer (.run), available from the [Releases](https://github.com/jamf/NetSUS/releases) page.
* 500 GB of disk space available 
* 1 GB of RAM

#### To set up the NetSUSLP as an appliance, you need:

* The OVA file for the NetSUSLP, available from the [Releases](https://github.com/jamf/NetSUS/releases) page.
* Virtualization software that supports Open Virtualization Format 
* 500 GB of disk space available
* 2 GB of RAM

#### If you are running a Kinobi Patch Server:

* Kinobi 1.0 is incompatible with NetSUS 5.0.2. If you are running Kinobi 1.0, you will need to install Kinobi 1.1 or later (1.3.2 is recommended) after upgrading to NetSUS 5.0.2, available from:
[https://github.com/mondada/kinobi/releases/download/1.3.2/KinobiInstaller_1.3.2.run](https://github.com/mondada/kinobi/releases/download/1.3.2/KinobiInstaller_1.3.2.run)

**Only Intel-based Macs can use a NetBoot server hosted by the NetSUSLP.**
