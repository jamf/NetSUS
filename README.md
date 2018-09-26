
# NetSUS Downloads

Installer:
[https://www.dropbox.com/s/byd1yhktv3vk3cw/NetSUSLPInstaller_5.0.run?dl=0](https://www.dropbox.com/s/byd1yhktv3vk3cw/NetSUSLPInstaller_5.0.run?dl=0)

OVA:
[https://www.dropbox.com/s/ovbtxqn7vi9tujk/NetSUSLP_5.0.ova?dl=0](https://www.dropbox.com/s/ovbtxqn7vi9tujk/NetSUSLP_5.0.ova?dl=0)


# What is NetSUS?

The NetBoot/SUS/LP server allows you to host an internal software update server (SUS), a NetBoot server, file shares, and a LDAP Proxy server **all on the same Linux system**. For a list of supported Linux distributions see [Requirements](#requirements).

<p align="center"><img src="docs/images/attachments/dashboard.png" height="400"></p>

* **Web Application** - The NetBoot/SUS/LP server includes a web application that can be used to easily manage your NetBoot, Software Update Servers and File Shares as well as your LDAP Proxy. The dashboard page is shown above.

* **File Sharing** - Use the NetBoot/SUS/LP server as a file share distribution point for Jamf Pro. You can share files using SMB, AFP, and HTTP.

* **Software Update Server** - Unlike a standard SUS, the SUS hosted by the NetBoot/SUS/LP server allows you to control which software updates should be installed on each computer in your organization.

* **NetBoot Server** - The NetBoot/SUS/LP server allows you to host a NetBoot image. You can boot computers to a NetBoot image in place of a recovery partition or external drive when imaging.

* **LDAP Proxy** - Use the NetBoot/SUS/LP server as a lightweight proxy that acts as a middleware layer between LDAP clients and LDAP directory servers.

## Documentation

For a getting started guide and step-by-step walkthroughs check out the **[documentation for the current release](docs/README.md)**

## <a name="requirements"></a>Requirements

#### Supported Linux distributions:

* Ubuntu 14.04 LTS Server
* Ubuntu 16.04 LTS Server
* Ubuntu 18.04 LTS Server (Experimental)
* Red Hat Enterprise Linux (RHEL) 6.4 or later
* CentOS 6.4 or later

#### To install the NetBoot/SUS/LP server using an installer, you need:

* The NetBoot/SUS/LP Server Installer (.run), available at:  
<https://www.jamf.com/jamf-nation/third-party-products/180/netboot-sus-appliance?view=info>
* 500 GB of disk space available 
* 1 GB of RAM

#### To set up the NetBoot/SUS/LP server as an appliance, you need:

* The OVA file for the NetBoot/SUS/LP server, available at:
<https://www.jamf.com/jamf-nation/third-party-products/180/netboot-sus-appliance?view=info>
* Virtualization software that supports Open Virtualization Format 
* 500 GB of disk space available
* 2 GB of RAM

**Only Intel-based Macs can use a NetBoot server hosted by the NetBoot/SUS/LP server.**
