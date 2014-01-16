#!/bin/bash
# This script controls the flow of the Linux JSS installation

######### Requirements Checking - Root #########

if [[ "$(id -u)" != "0" ]]; then
	echo "The NetSUS Installer needs to be run as root or using sudo."
	exit 1
fi

# Needed for systems with secure umask settings
OLD_UMASK=`umask`
umask 022

# Create NetSUS directory (needed immediately for logging)
if [ ! -d "/var/appliance/" ]; then
mkdir /var/appliance/
fi

# Create NetSUS directory (needed immediately for logging)
if [ ! -d "/var/appliance/logs/" ]; then
mkdir /var/appliance/logs/
fi

# Logger
source logger.sh

######### Requirements Checking #########

logEvent "Starting the NetSUS Installation"
logEvent "Checking installation requirements..."

failedAnyChecks=0
# Check for Ubuntu
bash testUbuntuRequirements.sh
if [[ $? -ne 0 ]]; then
	failedAnyChecks=1
fi

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

if [ -d "/var/appliance/" ]; then
	upgrade=true
else
	upgrade=false
fi


# Prompt user for permission to continue with the installation
	echo "
The following will be installed
* Appliance Web Interface
* NetBoot Server
* Software Updates Server
"
	
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

# Install Web Interface
bash webadminInstall.run 
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi

# Install NetBoot
bash netbootInstall.run 
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi

# Install SUS
bash susInstall.run 
if [[ $? -ne 0 ]]; then
	umask $OLD_UMASK
	exit 1
fi

#Post Cleanup Tasks
#Disables IPv6
echo "#disable ipv6" >> /etc/sysctl.conf
echo "net.ipv6.conf.all.disable_ipv6 = 1" >> /etc/sysctl.conf
echo "net.ipv6.conf.default.disable_ipv6 = 1" >> /etc/sysctl.conf
echo "net.ipv6.conf.lo.disable_ipv6 = 1" >> /etc/sysctl.conf


logEvent ""
logEvent "The NetSUS has been installed."
if [ ! $upgrade = true ]; then
	logEvent "Verify that port 443 and 80 are not blocked by a firewall."
	logEvent ""
	logEvent "Note: IP Helpers are required if using NetBoot across subnets."
	logEvent "The NetBoot folder name can not contain any spaces"
	logEvent ""
fi



if [ $upgrade = true ]; then
	logEvent "If you are upgrading NetSUS, you can simply start using it."
else
    logEvent "To complete the installation, open a web browser and navigate to https://${HOSTNAME}:443/."
fi


case $standalone in
[yY])
	echo "Restarting Services..."
	/etc/init.d/networking restart > /dev/null 2>&1
	/etc/init.d/apache2 restart > /dev/null 2>&1
	/etc/init.d/netatalk restart > /dev/null 2>&1
	/etc/init.d/smbd restart > /dev/null 2>&1
	/etc/init.d/tftpd-hpa restart > /dev/null 2>&1
	/etc/init.d/openbsd-inetd restart > /dev/null 2>&1

	logEvent "If you are installing NetSUS for the first time, please follow the documentation for setup instructions."
	;;
[nN])
	chmod +x /etc/init.d/applianceFirstRun
	update-rc.d applianceFirstRun defaults
	cp -R ./etc/* /etc/
	rm /etc/udev/rules.d/70-*
	rm /etc/resolv.conf
	echo "Shutting Down....."
	shutdown -P now
	;;
esac

umask $OLD_UMASK
exit 0
