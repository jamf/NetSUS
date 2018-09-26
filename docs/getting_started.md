## Requirements

To install the NetSUS server using an installer, you need:

* The NetSUS Server Installer (.run), available at:
<https://www.jamf.com/jamf-nation/third-party-products/180/netboot-sus-appliance?view=info>
* One of the following operating systems:
	* Ubuntu 14.04 LTS Server
	* Ubuntu 16.04 LTS Server (Recommended)
	* Ubuntu 18.04 LTS Server
	* Red Hat Enterprise Linux (RHEL) 6.4 or later
	* CentOS 6.4 or later
* 500 GB of disk space available 
* 1 GB of RAM

To set up the NetSUS server as an appliance, you need:

* The OVA file for the NetSUS server, available at:
<https://www.jamf.com/jamf-nation/third-party-products/180/netboot-sus-appliance?view=info>
* Virtualization software that supports Open Virtualization Format 
* 500 GB of disk space available
* 2 GB of RAM

To host a NetBoot server using the NetSUS server, you need a NetBoot image (.nbi folder). For more information, see the following Knowledge Base article:

[Creating a NetBoot Image and Setting Up a NetBoot Server](https://www.jamf.com/jamf-nation/articles/307/creating-a-netboot-image-and-setting-up-a-netboot-server)

**Only Intel-based Macs can use a NetBoot server hosted by the NetSUS server.**

## Service Ports (TCP/UDP) Used by the NetSUS Server

Depending on how your network infrastructure is setup, you may need to configure your firewalls/switches to allow your Mac clients access to various service ports on the NetSUS server.

**For NetBoot:**

* BSDP (for discovery of NetBoot server and images) listens on UDP ports 67 and 68
* TFTP (used to download the initial booter/kernel of the selected NetBoot set) listens on UDP port 69
* HTTP (alternative to NFS - used to serve the NetBoot disk image itself once the client has booted) listens on TCP port 80
* NFS (alternative to HTTP - used to serve the NetBoot disk image itself once the client has booted) listens on ports 111, 892 and 2049 over TCP and UDP for both. On CentOS/RHEL the NFS server also uses TCP port 32803 and UDP port 32769 for the lockd Daemon
* AFP (used as shadow storage for diskless NetBoot sets) listens on TCP port 548
* SMB (used to provide a network share for you to upload your NetBoot sets) listens on TCP ports 139 and 445

Note that to NetBoot across different subnets on your network, you'll need to set up IP Helpers on your managed switches to pass the required DHCP traffic over the client and server's subnets.

**For File Sharing:**

* AFP listens on TCP port 548
* SMB listens on TCP ports 139 and 445
* HTTP listens on TCP port 80
* HTTPS listens on TCP port 443

**For Software Update Server:**

* HTTP listens on TCP port 80
* HTTPS listens on TCP port 443

The Software Update Server uses Reposado to sync content from Apple, so it must connect to Apple's software update services - Apple provide some guidance here: https://support.apple.com/en-us/HT202943.

**For LDAP Proxy**

* TCP ports 389 (unencrypted LDAP) and possibly 636 (secure LDAP over TLS)

**For Administrative Access**

* HTTPS (web based administration interface) listens on TCP port 443
* SSH (secure shell console login) listens on TCP port 22

## Installing the NetSUS Server Using an Installer
1. Copy the NetSUS Installer (.run) to the server on which you plan to install the NetSUS server.

2. Log in to the server as a user with superuser privileges.

3. Initiate the installer by executing a command similar to the following:

		sudo /path/to/NetSUSLPInstaller.run
	
4. Type "y" to proceed.

5. Go to `https://myhostname.local/webadmin` to access the NetSUS server web application. Once the NetSUS server is installed, it is recommended that you log in to the web application and change all usernames and passwords associated with the server. For more information, see [Accounts](accounts.md).

## Setting Up the NetSUS Server as an Appliance
To set up the NetSUS server as an appliance, import the OVA file for the NetSUS server into a virtualization software product. This creates an Ubuntu VM with no services configured. The first time you power on the VM, the URL for the NetSUS server web application appears in the console.

Once the NetSUS server is set up as an appliance, it is recommended that you log in to the web application and change all usernames and passwords associated with the server. For more information, see [Accounts](accounts.md).
