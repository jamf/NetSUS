# NetSUS Changelog

## 5.0

* Updated user interface to align with Jamf Pro UI
* Appliance Operating System is now Ubuntu 16.04.5
* Added (experimental) support for Ubuntu 18.04 LTS
* Added notifications for recommended actions
* Improved Active Directory (LDAP) integration for web administration
* Added ability to create/manage local system accounts
* Added support for multiple network interfaces.\
  Note: Bonded interfaces are not supported in the UI
* Added ability to configure global proxy
* Improved ntp syncronization in Date/Time
* Added timezone picker map in Date/Time
* Improved SSL certificate interface
* Added full chain validation checks for SSL certificates
* New storage management interface for expanding LVM volumes
* Improved log viewer
* Added ability to download contents of log viewer
* Added dedicated settings pages for services
* Settings gear is contextually aware of service
* Added ability to enable/disable services
* Added ability to show/hide services in Dashboard
* Added ability to select SUS catalogs to sync
* Added ability to add custom SUS catalogs (beta/seed)
* Added ability to publish SUS catalogs over https
* Improved URL re-write functionality for SUS catalogs
* Added support for multiple NetBoot images
* Improved NetBoot image property editing
* Improved validation checks for LDAP proxy configuration
* Added service status messages to LDAP proxy
* Added File Sharing service
* Added ability to create smb/afp/http shares
* Added ability to manage users for shares

## 4.2.1

* Added High Sierra support for SUS
* Updated reposado to latest version
* Fixed an issue where certain Sierra build would NetBoot extremely slowly

## 4.2

* Added Sierra support for SUS
* Updated reposado to latest version
* Added validation for SUS Base URL and Branch name(s) with live feedback
* Added (missing) option for SUS sync at 9:00 AM
* Improved detection of the last SUS sync date and time
* Added proxy configuration to SUS
* Added validation for NetBoot Image Name, Subnet and Netmask with live feedback
* Added checks for NetBoot supporting services
* Provisioned for NFS support for NetBoot Images
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

* The NetSUS can now be installed on RHEL and CentOS

## 2.0

* Added the option to install the NetSUS using an installer
* Updated the NetSUS web application GUI to match the JSS v9.0 and later
* The NetBoot server hosted by the NetSUS now uses HTTP instead of NFS
* Updated the version of Reposado that is used by the NetSUS
