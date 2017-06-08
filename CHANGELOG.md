# NetSUS Changelog

## 4.2

* Added Sierra support for SUS
* Updated reposado to latest version
* Added validation for SUS Base URL and Branch name(s) with live feedback
* Added (missing) option for SUS sync at 9:00 AM
* Improved detection of the last SUS sync date and time
* Added validation for NetBoot Image Name, Subnet and Netmask with live feedback
* Updated service controls for TFTP on RHEL/CentOS
* Added validation for Hostname, IP Address, Netmask, Gateway and DNS Servers with live feedback
* Added functionality to dynamically determine primary network interface, to allow for variations
* Updated network configuration to persistently configure static DNS on Ubuntu
* Updated service controls for SSH and Firewall
* Updated timezone configuration
* Added validation for Network Time Server with live feedback
* Added functionality to create CSR (and new Private Key) in webadmin GUI
* Added field descriptions for certificates
* Added functionality to view logs in the webadmin GUI
* Added Web UI for expanding the primary volume, when the underlying VMDK is expanded
* Added functionality to enable/disable AFP service
* Added functionality to enable/disable SMB service
* Updated about page to reflect OS and installed packages
* Updated Jamf Nation links
* Removed support for Ubuntu 10.04 and 12.04 (both are EOL)
* Added support for Ubuntu 16.04
* Removed hard-coded OS checks, replaced with detection of binaries or configuration files
* Improved detection/installation of supporting software
* Installer now updates existing files in-place, rather than overwriting with templates
* Updated mechanisms used for Ubuntu service controls to ensure services are correctly enabled / disabled
* Firewall rule configuration removed from adminHelper.sh, all firewall rules are pre-configured during installation
* Added 'enablegui' option to adminHelper.sh to easily re-enable webadmin GUI
* Fixed an issue where the LDAP Proxy prompts for a password during installation

## 4.1

* New and improved User Interface and minor changes to the User Experience
* Added LDAP administration group login support
* Added ability to rename the advertised NetBoot name
* Documentation updated, improved, and converted to markdown format

## 4.0

* Renamed to NetBoot/SUS/LP (NetSUSLP) for reference to LDAP Proxy
* Added El Capitan support for SUS
* Added firewall functionality with port managing for running NetSUSLP services by using app armor
* Added ability to disabled WebAdmin interface
* Added LDAP Proxy functionality with the use of slapd
* Added GAWK installation for WebAdmin on Ubuntu operating systems
* Added functionality to only enable services as needed
* Added functionality to update Ubuntu apt-get repository to prevent failures on service installation
* Added certificate page to allow tomcat or slapd certificates, and configured an installation to use a self-signed certificate
* Changed NetBoot page to enable SMB for uploading a NetBoot file, and then disable it when it is not in use
* OVA updated to use 2GB of memory and hard drive space increased to use 300 GB of hard drive space

## 3.0

* The NetBoot/SUS/LP Server can now be installed on RHEL and CentOS

## 2.0

* Added the option to install the NetBoot/SUS/LP server using an installer
* Updated the NetBoot/SUS/LP server web application GUI to match the JSS v9.0 and later
* The NetBoot server hosted by the NetBoot/SUS/LP server now uses HTTP instead of NFS
* Updated the version of Reposado that is used by the NetBoot/SUS/LP server