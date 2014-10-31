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
	if ! rpm -qa "*db47*" | grep -q "db47" ; then
		yum install compat-db47 -y -q >> $logFile
	fi
	if ! rpm -qa "perl" | grep -q "perl" ; then
		yum install perl -y -q >> $logFile
	fi
	cp ./var/appliance/netatalk-2.2.0-2.el6.x86_64.rpm /var/appliance/netatalk-2.2.0-2.el6.x86_64.rpm
	if ! rpm -qa "netatalk" | grep -q "netatalk" ; then
		rpm -i -v "/var/appliance/netatalk-2.2.0-2.el6.x86_64.rpm" >> $logFile
	fi
	if ! rpm -qa "avahi" | grep -q "avahi" ; then
		yum install avahi -y -q >> $logFile
	fi
	if ! rpm -qa "samba" | grep -q "samba" ; then
		yum install samba -y -q >> $logFile
	fi
	if ! rpm -qa "samba-client" | grep -q "samba-client" ; then
		yum install samba-client -y -q >> $logFile
	fi
	if ! rpm -qa "tftp-server" | grep -q "tftp-server" ; then
		yum install tftp-server -y -q >> $logFile
	fi
	if ! rpm -qa "vim-common" | grep -q "vim-common" ; then
		yum install vim-common -y -q >> $logFile
	fi
    chkconfig netatalk on
    chkconfig smb on
    chkconfig tftp on
    service smb start
    service xinetd start
    service messagebus start
    service avahi-daemon start
    service netatalk start
	sed -i 's:/var/lib/tftpboot:/srv/NetBoot/NetBootSP0:' /etc/xinetd.d/tftp
	sed -i "s:disable\t\t\t= yes:disable\t\t\t= no:" /etc/xinetd.d/tftp
fi

if [ ! -d "/var/db" ]; then
    mkdir /var/db
fi

if [ ! -d "/srv/NetBoot/NetBootSP0" ]; then
    mkdir -p /srv/NetBoot/NetBootSP0
fi

if [ ! -d "/srv/NetBootClients" ]; then
    mkdir /srv/NetBootClients
fi

killall dhcpd >> $logFile 2>&1
if [[ $detectedOS == 'Ubuntu' ]]; then
	cp -R ./etc/* /etc/
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	# Configure netatalk
	if ! grep -q '\- \-setuplog "default log_info /var/log/afpd.log"' /etc/netatalk/afpd.conf; then
		echo '- -setuplog "default log_info /var/log/afpd.log"' >> /etc/netatalk/afpd.conf
	fi
    # Remove any entries from old installations
	sed -i '/"NetBoot"/d' /etc/netatalk/AppleVolumes.default
	echo '/srv/NetBootClients/$i "NetBoot" allow:afpuser rwlist:afpuser options:upriv cnidscheme:dbd ea:sys preexec:"mkdir -p /srv/NetBootClients/$i/NetBoot001" postexec:"rm -rf /srv/NetBootClients/$i"' >> /etc/netatalk/AppleVolumes.default
	sed -i 's/#AFPD_MAX_CLIENTS=.*/AFPD_MAX_CLIENTS=200/' /etc/netatalk/netatalk.conf
	sed -i 's:#ATALK_NAME=.*:ATALK_NAME=`/bin/hostname --short`:' /etc/netatalk/netatalk.conf
	sed -i 's/#AFPD_GUEST=.*/AFPD_GUEST=nobody/' /etc/netatalk/netatalk.conf
	sed -i 's/#ATALKD_RUN=.*/ATALKD_RUN=no/' /etc/netatalk/netatalk.conf
	sed -i 's/#PAPD_RUN=.*/PAPD_RUN=no/' /etc/netatalk/netatalk.conf
	sed -i 's/#TIMELORD_RUN=.*/TIMELORD_RUN=no/' /etc/netatalk/netatalk.conf
	sed -i 's/#A2BOOT_RUN=.*/A2BOOT_RUN=yes/' /etc/netatalk/netatalk.conf
	sed -i 's/#ATALK_BGROUND=.*/ATALK_BGROUND=no/' /etc/netatalk/netatalk.conf
fi
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
    # Create httpd include for NetBoot
echo 'Alias /NetBoot/ "/srv/NetBoot/"

<Directory "/srv/NetBoot">
    Options Indexes FollowSymLinks MultiViews
    AllowOverride None
    Order allow,deny
    Allow from all
</Directory>

<LocationMatch "/NetBoot/">
    Options -Indexes
    ErrorDocument 403 /error/noindex.html
</LocationMatch>' > /etc/httpd/conf.d/netboot.conf
fi
#Creates the accounts to be used for the different services
if [ "$(getent passwd smbuser)" ]; then
    echo "smbuser already exists"
else
	useradd -c 'NetBoot Admin' -d /dev/null -g users -s /sbin/nologin smbuser >> $logFile
	echo smbuser:smbuser1 | chpasswd
	(echo smbuser1; echo smbuser1) | smbpasswd -s -a smbuser
fi

#Needs normal user creation for AFP mount to work proper
if [ "$(getent passwd afpuser)" ]; then
    echo "afpuser already exists"
else
	useradd -c 'NetBoot User' -d /home/afpuser -g users -m -s /bin/sh afpuser >> $logFile
	echo afpuser:afpuser1 | chpasswd
fi
if [ ! -d "/home/afpuser" ]; then
    mkdir /home/afpuser
    chown afpuser:users /home/afpuser >> $logFile
fi

#Change SMB setting for guest access
sed -i "s/map to guest = bad user/map to guest = never/g" /etc/samba/smb.conf

#Change SMB settings to allow for a symlink in an app or pkg
if ! grep -q 'unix extensions' /etc/samba/smb.conf ; then
	sed -i '/\[global\]/ a\\tunix extensions = no' /etc/samba/smb.conf
fi
#Change SMB setting to eliminate CUPS errors
sed -i 's:;\tprintcap name = lpstat:\tprintcap name = /dev/null:' /etc/samba/smb.conf
sed -i 's/;\tprinting = cups/\tprinting = bsd/' /etc/samba/smb.conf

#Create the SMB share for NetBoot
if ! grep -q '\[NetBoot\]' /etc/samba/smb.conf ; then
	mkdir -p /etc/samba/conf.d
	printf '\t[NetBoot]
\tcomment = NetBoot
\tpath = /srv/NetBoot/NetBootSP0
\tbrowseable = no
\tguest ok = no
\tread only = yes
\tcreate mask = 0755
\twrite list = smbuser
\tvalid users = smbuser
' > /etc/samba/conf.d/netboot.conf
fi
if ! grep -q 'NetBoot' /etc/samba/smb.conf ; then
printf '

# NetBoot Share
\tinclude = /etc/samba/conf.d/netboot.conf
' >> /etc/samba/smb.conf
fi

chown smbuser /srv/NetBoot/NetBootSP0 >> $logFile

#Make the afpuser the owner of the NetBootClients share
chown afpuser /srv/NetBootClients >> $logFile

logEvent "OK"

logEvent "Finished deploying NetBoot"

exit 0
