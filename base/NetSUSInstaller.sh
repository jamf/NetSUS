#!/bin/bash
# This script controls the flow of the Linux NetSUS installation

######### Requirements Checking - Root #########

if [[ "$(id -u)" != "0" ]]; then
	echo "The NetSUS Installer needs to be run as root or using sudo."
	exit 1
fi

# Needed for systems with secure umask settings
OLD_UMASK=`umask`
umask 022

# Check for an existing installation
if [ -d "/var/appliance" ]; then
	upgrade=true
else
	upgrade=false
fi

# Create NetSUS directory (needed immediately for logging)
if [ ! -d "/var/appliance/logs" ]; then
	mkdir -p /var/appliance/logs
fi

# Logger
source logger.sh

######### Requirements Checking #########

logEvent "Starting the NetSUS Installation"
logEvent "Checking installation requirements..."

failedAnyChecks=0
# Check for Valid OS
. testOSRequirements.sh

logEvent $detectedOS


# Check for a 64-bit OS
bash test64bitRequirements.sh 
if [[ $? -ne 0 ]]; then
	failedAnyChecks=1
fi


# Abort if we failed any checks
if [[ $failedAnyChecks -ne 0 ]]; then
	logEvent "Aborting installation due to unsatisfied requirements."
	if [[ $FLAGS = "-n" ]]; then
		echo "Installation failed.  See $logFile for more details."
	fi
	echo "Installation failed.  See $logFile for more details."
	umask $OLD_UMASK
	exit 1
fi

logEvent "Passed all requirements checking!"

######### Verification #########
# Prompt user for type of installation
	echo "
Is this a standalone installation?
Answer yes unless you are creating an image of the appliance to deploy in multiple locations
"

	read -t 1 -n 100000 devnull # This clears any accidental input from stdin
	
	while [[ $REPLY != [yYnN] ]]; do
		read -n1 -p "Standalone?  (y/n): "
		echo ""
	done
    standalone=$REPLY


# Prompt user for permission to continue with the installation
if [[ $detectedOS == 'Ubuntu' ]]; then
	echo "
The following will be installed
* Appliance Web Interface
* NetBoot Server
* Software Updates Server
* LDAP Proxy Server
"
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	echo "
The following will be installed
* Appliance Web Interface
* NetBoot Server
* Software Updates Server
"
fi

	
	read -t 1 -n 100000 devnull # This clears any accidental input from stdin
	REPLY=""
	while [[ $REPLY != [yYnN] ]]; do
		read -n1 -p "Proceed?  (y/n): "
		echo ""
	done
	if [[ $REPLY = [nN] ]]; then
		logEvent "Aborting..."
		umask $OLD_UMASK
		exit 0
	else
		logEvent "Installing..."
	fi



######### Sub-installers #########

#Initial Cleanup tasks

# Set SELinux policy
if sestatus | grep -q enforcing ; then
	logEvent "Setting SELINUX mode to permissive"
	echo "A restart of the system will be required before using the NetSUS"
	sed -i "s/SELINUX=enforcing/SELINUX=permissive/" /etc/selinux/config
fi
if [ -f "/selinux/enforce" ]; then
	echo 0 > /selinux/enforce
	echo
fi

if [[ $detectedOS == 'Ubuntu' ]]; then
	apt-get update
fi


# Install Web Interface
bash webadminInstall.run -- $detectedOS
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi

# Install NetBoot
bash netbootInstall.run -- $detectedOS
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi

# Install SUS
bash susInstall.run -- $detectedOS
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi

# Install LDAP Proxy
if [[ $detectedOS == 'Ubuntu' ]]; then
bash LDAPProxyInstall.run -- $detectedOS
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi
fi

#Post Cleanup Tasks
#Disables IPv6

echo "# Disable IPv6
net.ipv6.conf.all.disable_ipv6 = 1
net.ipv6.conf.default.disable_ipv6 = 1
net.ipv6.conf.lo.disable_ipv6 = 1" >> /etc/sysctl.conf


logEvent ""
logEvent "The NetSUSLP has been installed."
if [ ! $upgrade = true ]; then
	logEvent "Verify that port 443 and 80 are not blocked by a firewall."
	logEvent ""
	logEvent "Note: IP Helpers are required if using NetBoot across subnets."
	logEvent "The NetBoot folder name can not contain any spaces"
	logEvent ""
fi



if [ $upgrade = true ]; then
	logEvent "If you are upgrading NetSUSLP, you can simply start using it."
else
    logEvent "To complete the installation, open a web browser and navigate to https://${HOSTNAME}:443/."
fi

# Need to check service names for RedHat
case $standalone in
[yY])
if [[ $detectedOS == 'Ubuntu' ]]; then
	echo "Updating Services..."
	service apparmor restart
	service slapd stop > /dev/null 2>&1
	service networking restart > /dev/null 2>&1
	service apache2 restart > /dev/null 2>&1
	service netatalk stop > /dev/null 2>&1
	service smbd stop > /dev/null 2>&1
	service tftpd-hpa stop > /dev/null 2>&1
	service openbsd-inetd stop > /dev/null 2>&1
	echo manual > /etc/init/slapd.override
	echo manual > /etc/init/netatalk.override
	echo manual > /etc/init/smbd.override
	echo manual > /etc/init/tftpd-hpa.override
	echo manual > /etc/init/openbsd-inetd.override

	logEvent "If you are installing NetSUSLP for the first time, please follow the documentation for setup instructions."
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
    service httpd restart
    service smb stop
    chkconfig tftp off
    service xinetd restart
    service netatalk stop
    chkconfig smb off
    chkconfig netatalk off
fi

	;;
[nN])
if [[ $detectedOS == 'Ubuntu' ]]; then
	chmod +x /etc/init.d/applianceFirstRun
    #Need to update for RedHat
	update-rc.d applianceFirstRun defaults
	cp -R ./etc/* /etc/
	rm /etc/udev/rules.d/70-*
	rm /etc/resolv.conf
	echo "NetSUSLP installation complete."
	echo "Type: \"shutdown -P now\" to Shut Down."
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
    rm -rf /etc/ssh/ssh_host_*
    rm -rf /etc/udev/rules.d/70-*
    sed -i '/HWADDR=/d' /etc/sysconfig/network-scripts/ifcfg-eth0
    find /var/log -type f -delete
    rm -f install.log*
	echo "NetSUSLP installation complete."
	echo "Type: \"poweroff\" to Shut Down."
fi
	;;
esac

rm -f "$0"
umask $OLD_UMASK
exit 0
