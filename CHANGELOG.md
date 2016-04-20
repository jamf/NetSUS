# NetSUS Changelog

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