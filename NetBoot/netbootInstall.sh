#!/bin/bash
# This script controls the flow of the SUS installation
pathToScript=$0
detectedOS=$1

# Logger
source logger.sh

logEvent "Starting NetBoot Installation"
if [[ $detectedOS == 'Ubuntu' ]]; then
	apt-get -qq -y install samba >> $logFile
	apt-get -qq -y install tftpd-hpa >> $logFile
	apt-get -qq -y install openbsd-inetd >> $logFile
	apt-get -qq -y install netatalk >> $logFile
fi

if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	yum install avahi -y -q >> $logFile
	rpm -i -v "http://dl.fedoraproject.org/pub/epel/6/x86_64/netatalk-2.2.0-2.el6.x86_64.rpm" >> $logFile
	yum install samba -y -q >> $logFile
	yum install tftp-server -y -q >> $logFile
	chkconfig netatalk on
	chkconfig smb on
	chkconfig tftp on
	service messagebus start
    service avahi-daemon start
	service smb start
	service xinetd start
	service netatalk start
	sed -i 's/\/var\/lib\/tftpboot/\/srv\/NetBoot\/NetBootSP0/' /etc/xinetd.d/tftp
fi

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
if [[ $detectedOS == 'Ubuntu' ]]; then
	# Remove any entries from old installations
	if [ -f "/etc/apache2/sites-enabled/000-default" ]; then
		sed -i '/[[:space:]]*Alias \/NetBoot\/ "\/srv\/NetBoot\/"/,/[[:space:]]*<\/Directory>/d' /etc/apache2/sites-enabled/000-default
	
		sed -i "s'</VirtualHost>'\tAlias /NetBoot/ \"/srv/NetBoot/\"\n\t<Directory /srv/NetBoot/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tOrder allow,deny\n\t\tallow from all\n\t</Directory>\n</VirtualHost>'g" /etc/apache2/sites-enabled/000-default
	fi
	if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
		sed -i '/[[:space:]]*Alias \/NetBoot\/ "\/srv\/NetBoot\/"/,/[[:space:]]*<\/Directory>/d' /etc/apache2/sites-enabled/000-default.conf
	
		sed -i "s'</VirtualHost>'\tAlias /NetBoot/ \"/srv/NetBoot/\"\n\t<Directory /srv/NetBoot/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>'g" /etc/apache2/sites-enabled/000-default.conf
	fi
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	# Remove any entries from old installations
	sed -i '/[[:space:]]*Alias \/NetBoot\/ "\/srv\/NetBoot\/"/,/[[:space:]]*<\/Directory>/d' /etc/httpd/conf/httpd.conf
	
	echo '
	Alias /NetBoot/ "/srv/NetBoot/"' >> /etc/httpd/conf/httpd.conf
	echo '
	<Directory "/srv/NetBoot">
	Options Indexes FollowSymLinks MultiViews
	AllowOverride None
	Order allow,deny
	Allow from all
	</Directory>' >> /etc/httpd/conf/httpd.conf
fi
#Creates the accounts to be used for the different services
if [ "$(getent passwd smbuser)" ]; then
	echo "smbuser already exists"
else
	useradd -d /dev/null -s /dev/null smbuser >> $logFile
	echo smbuser:smbuser1 | chpasswd
	(echo smbuser1; echo smbuser1) | smbpasswd -s -a smbuser
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
if ! grep -q 'unix extensions' /etc/samba/smb.conf ; then
	sed -i '/\[global\]/ a\
unix extensions = no' /etc/samba/smb.conf
fi

#Create the SMB share for NetBoot
if ! grep -q '\[NetBoot\]' /etc/samba/smb.conf ; then
	printf '

\t[NetBoot]
\tcomment = NetBoot
\tpath = /srv/NetBoot/NetBootSP0
\tbrowseable = no
\tguest ok = no
\tread only = yes
\tcreate mask = 0755
\twrite list = smbuser
\tvalid users = smbuser' >> /etc/samba/smb.conf
fi

chown smbuser /srv/NetBoot/NetBootSP0/

#Make the afpuser the owner of the NetBootClients share
chown afpuser /srv/NetBootClients/ >> $logFile

logEvent "OK"

logEvent "Finished deploying NetBoot"

exit 0
