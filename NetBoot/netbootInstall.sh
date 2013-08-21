#!/bin/bash
# This script controls the flow of the SUS installation
pathToScript=$0
pathToPackage=$1
targetLocation=$2
targetVolume=$3

# Logger
source logger.sh

logEvent "Starting NetBoot Installation"

apt-get -qq -y install samba >> $logFile
apt-get -qq -y install tftpd-hpa >> $logFile
apt-get -qq -y install openbsd-inetd >> $logFile
apt-get -qq -y install netatalk >> $logFile

if [ ! -d "/var/db/" ]; then
    mkdir /var/db/
fi

if [ ! -d "/srv/NetBoot/" ]; then
    mkdir /srv/NetBoot/
fi

if [ ! -d "/srv/NetBoot/NetBootSP0" ]; then
    mkdir /srv/NetBoot/NetBootSP0/
fi

if [ ! -d "/srv/NetBootClients/" ]; then
    mkdir /srv/NetBootClients/
fi

killall dhcpd >> $logFile 2>&1 
cp -R ./etc/* /etc/
cp -R ./usr/* /usr/
cp -R ./var/* /var/

#Create Apache Share for NetBoot
sed -i "s'</VirtualHost>'\tAlias /NetBoot/ \"/srv/NetBoot/\"\n\t<Directory /srv/NetBoot/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tOrder allow,deny\n\t\tallow from all\n\t</Directory>\n</VirtualHost>'g" /etc/apache2/sites-enabled/000-default


#Creates the accounts to be used for the different services
if [ "$(getent passwd smbuser)" ]; then
    echo "smbuser already exists"
else
useradd -d /dev/null -s /dev/null smbuser >> $logFile
echo smbuser:smbuser1 | chpasswd
fi

#Needs normal user creation for AFP mount to work proper
if [ ! -d "/home/afpuser/" ]; then
    mkdir /home/afpuser
fi
if [ "$(getent passwd afpuser)" ]; then
    echo "afpuser already exists"
else
    useradd afpuser -d /home/afpuser >> $logFile
    echo afpuser:afpuser1 | chpasswd
    chown afpuser:afpuser /home/afpuser/ >> $logFile
fi

#Change SMB setting for guest access
sed -i "s/map to guest = bad user/map to guest = never/g" /etc/samba/smb.conf

#Change SMB settings to allow for a symlink in an app or pkg
sed '/global/ a\
unix extensions = no' /etc/samba/smb.conf > /tmp/smb.conf
mv /tmp/smb.conf /etc/samba/smb.conf

#Create the SMB share for NetBoot
echo "Creating the SMB share for NetBoot..."
(echo smbuser; echo smbuser) | smbpasswd -s -a smbuser
echo "[NetBoot]" >> /etc/samba/smb.conf
echo "comment = NetBoot" >> /etc/samba/smb.conf
echo "path = /srv/NetBoot/NetBootSP0" >> /etc/samba/smb.conf
echo "browseable = no" >> /etc/samba/smb.conf
echo "guest ok = no" >> /etc/samba/smb.conf
echo "read only = yes" >> /etc/samba/smb.conf
echo "create mask = 0755" >> /etc/samba/smb.conf
echo "write list = smbuser" >> /etc/samba/smb.conf
echo "valid users = smbuser" >> /etc/samba/smb.conf
chown smbuser /srv/NetBoot/NetBootSP0/

#Make the afpuser the owner of the NetBootClients share
chown afpuser /srv/NetBootClients/ >> $logFile

logEvent "OK"

logEvent "Finished deploying the appliance web application"

exit 0
