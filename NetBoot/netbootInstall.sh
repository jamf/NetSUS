#!/bin/bash
# This script controls the flow of the NetBoot installation

log "Starting NetBoot Installation"

apt_install() {
	if [[ $(apt-cache -n search ^${1}$ | awk '{print $1}' | grep ^${1}$) == "$1" ]] && [[ $(dpkg -s $1 2>&- | awk '/Status: / {print $NF}') != "installed" ]]; then
		apt-get -qq -y install $1 >> $logFile 2>&1
		if [[ $? -ne 0 ]]; then
			exit 1
		fi
	fi
}

yum_install() {
	if yum -q list $1 &>- && [[ $(rpm -qa $1) == "" ]] ; then
		yum install $1 -y -q >> $logFile 2>&1
		if [[ $? -ne 0 ]]; then
			exit 1
		fi
	fi
}

# Install required software
if [[ $(which apt-get 2>&-) != "" ]]; then
	export DEBIAN_FRONTEND=noninteractive
	echo "samba-common samba-common/do_debconf boolean false" | debconf-set-selections
	apt_install samba
	unset DEBIAN_FRONTEND
	apt_install tftpd-hpa
	# apt_install openbsd-inetd
	apt_install netatalk
	apt_install nfs-kernel-server
	apt_install python-configparser
elif [[ $(which yum 2>&-) != "" ]]; then
	yum_install avahi
	yum_install samba
	yum_install samba-client
	yum_install tftp-server
	release=$(rpm -q --queryformat '%{RELEASE}' rpm | cut -d '.' -f 2)
	if [[ $release == "el6" ]] && [[ $(rpm -qa netatalk) == "" ]]; then
		yum localinstall ./resources/netatalk-2.2.0-2.el6.x86_64.rpm -y -q >> $logFile
		if [[ $? -ne 0 ]]; then
			exit 1
		fi
	fi
	if [[ $release == "el7" ]] && [[ $(rpm -qa libdb4) == "" ]]; then
		yum localinstall ./resources/libdb4-4.8.30-21.fc26.x86_64.rpm -y -q >> $logFile
		if [[ $? -ne 0 ]]; then
			exit 1
		fi
		yum localinstall ./resources/netatalk-2.2.3-9.fc20.x86_64.rpm -y -q >> $logFile
		if [[ $? -ne 0 ]]; then
			exit 1
		fi
		sed -i 's/.*- -tcp -noddp -uamlist uams_dhx.so.*/- -tcp -noddp -uamlist uams_dhx.so,uams_dhx2_passwd.so/' /etc/netatalk/afpd.conf
	fi
	yum_install nfs-utils
	yum_install vim-common
	chkconfig messagebus on >> $logFile 2>&1
	chkconfig avahi-daemon on >> $logFile 2>&1
	chkconfig rpcbind on >> $logFile 2>&1
    service messagebus start >> $logFile 2>&1
    service avahi-daemon start >> $logFile 2>&1
    service rpcbind start >> $logFile 2>&1
fi

# Prepare the firewall in case it is enabled later
if [[ $(which ufw 2>&-) != "" ]]; then
	# HTTP
	ufw allow 80/tcp >> $logFile
	# SMB
	ufw allow 139/tcp >> $logFile
	ufw allow 445/tcp >> $logFile
	# AFP
	ufw allow 548/tcp >> $logFile
	# DHCP
	ufw allow 67/udp >> $logFile
	# TFTP
	ufw allow 69/udp >> $logFile
	# NFS
	ufw allow 111/tcp >> $logFile
	ufw allow 111/udp >> $logFile
	ufw allow 892/tcp >> $logFile
	ufw allow 892/udp >> $logFile
	ufw allow 2049/tcp >> $logFile
	ufw allow 2049/udp >> $logFile
	ufw allow 32769/udp >> $logFile
	ufw allow 32803/tcp >> $logFile
elif [[ $(which firewall-cmd 2>&-) != "" ]]; then
	# HTTP
	firewall-cmd --zone=public --add-port=80/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=80/tcp --permanent >> $logFile 2>&1
	# SMB
	firewall-cmd --zone=public --add-port=139/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=139/tcp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=445/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=445/tcp --permanent >> $logFile 2>&1
	# AFP
	firewall-cmd --zone=public --add-port=548/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=548/tcp --permanent >> $logFile 2>&1
	# DHCP
	firewall-cmd --zone=public --add-port=67/udp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=67/udp --permanent >> $logFile 2>&1
	# TFTP
	firewall-cmd --zone=public --add-port=69/udp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=69/udp --permanent >> $logFile 2>&1
	# NFS
	firewall-cmd --zone=public --add-port=111/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=111/tcp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=111/udp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=111/udp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=892/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=892/tcp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=892/udp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=892/udp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=2049/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=2049/tcp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=2049/udp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=2049/udp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=32769/udp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=32769/udp --permanent >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=32803/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=32803/tcp --permanent >> $logFile 2>&1
else
	# HTTP
	if iptables -L | grep DROP | grep -v 'tcp dpt:https' | grep -q 'tcp dpt:http' ; then
		iptables -D INPUT -p tcp --dport 80 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -v 'tcp dpt:https' | grep -q 'tcp dpt:http' ; then
		iptables -I INPUT -p tcp --dport 80 -j ACCEPT
	fi
	# SMB
	if iptables -L | grep DROP | grep -q 'tcp dpt:netbios-ssn' ; then
		iptables -D INPUT -p tcp --dport 139 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:netbios-ssn' ; then
		iptables -I INPUT -p tcp --dport 139 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'tcp dpt:microsoft-ds' ; then
		iptables -D INPUT -p tcp --dport 445 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:microsoft-ds' ; then
		iptables -I INPUT -p tcp --dport 445 -j ACCEPT
	fi
	# AFP
	if iptables -L | grep DROP | grep -q 'tcp dpt:afpovertcp' ; then
		iptables -D INPUT -p tcp --dport 548 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:afpovertcp' ; then
		iptables -I INPUT -p tcp --dport 548 -j ACCEPT
	fi
	# DHCP
	if iptables -L | grep DROP | grep -q 'udp dpt:bootps' ; then
		iptables -D INPUT -p udp --dport 67 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:bootps' ; then
		iptables -I INPUT -p udp --dport 67 -j ACCEPT
	fi
	# TFTP
	if iptables -L | grep DROP | grep -q 'udp dpt:tftp' ; then
		iptables -D INPUT -p udp --dport 69 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:tftp' ; then
		iptables -I INPUT -p udp --dport 69 -j ACCEPT
	fi
	# NFS
	if iptables -L | grep DROP | grep -q 'tcp dpt:sunrpc' ; then
		iptables -D INPUT -p tcp --dport 111 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:sunrpc' ; then
		iptables -I INPUT -p tcp --dport 111 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'udp dpt:sunrpc' ; then
		iptables -D INPUT -p udp --dport 111 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:sunrpc' ; then
		iptables -I INPUT -p udp --dport 111 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'tcp dpt:892' ; then
		iptables -D INPUT -p tcp --dport 892 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:892' ; then
		iptables -I INPUT -p tcp --dport 892 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'udp dpt:892' ; then
		iptables -D INPUT -p udp --dport 892 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:892' ; then
		iptables -I INPUT -p udp --dport 892 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'tcp dpt:nfs' ; then
		iptables -D INPUT -p tcp --dport 2049 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:nfs' ; then
		iptables -I INPUT -p tcp --dport 2049 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'udp dpt:nfs' ; then
		iptables -D INPUT -p udp --dport 2049 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:nfs' ; then
		iptables -I INPUT -p udp --dport 2049 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'udp dpt:filenet-rpc' ; then
		iptables -D INPUT -p udp --dport 32769 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:filenet-rpc' ; then
		iptables -I INPUT -p udp --dport 32769 -j ACCEPT
	fi
	if iptables -L | grep DROP | grep -q 'tcp dpt:32803' ; then
		iptables -D INPUT -p tcp --dport 32803 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:32803' ; then
		iptables -I INPUT -p tcp --dport 32803 -j ACCEPT
	fi
	service iptables save >> $logFile 2>&1
fi

# Configure tftp
if [ -f "/etc/default/tftpd-hpa" ]; then
	sed -i 's:/var/lib/tftpboot:/srv/NetBoot/NetBootSP0:' /etc/default/tftpd-hpa
fi
if [ -f "/etc/xinetd.d/tftp" ]; then
	sed -i 's:/var/lib/tftpboot:/srv/NetBoot/NetBootSP0:' /etc/xinetd.d/tftp
	sed -i '/disable/ s/yes/no/' /etc/xinetd.d/tftp
fi
if [ -f "/usr/lib/systemd/system/tftp.service" ]; then
	sed -i 's:/var/lib/tftpboot:/srv/NetBoot/NetBootSP0:' /usr/lib/systemd/system/tftp.service
fi
if [ -f "/lib/systemd/system/tftp.service" ]; then
	sed -i 's:/var/lib/tftpboot:/srv/NetBoot/NetBootSP0:' /lib/systemd/system/tftp.service
fi

# Create netboot directories
if [ ! -d "/srv/NetBoot/NetBootSP0" ]; then
    mkdir -p /srv/NetBoot/NetBootSP0
fi
if [ ! -d "/srv/NetBootClients" ]; then
    mkdir /srv/NetBootClients
fi

# Install and configure dhcp
killall dhcpd >> $logFile 2>&1
if [ ! -d "/var/appliance/conf" ]; then
	mkdir -p /var/appliance/conf
fi
cp ./resources/dhcpd.conf /var/appliance/conf/ >> $logFile
cp ./resources/configurefornetboot /var/appliance/ >> $logFile

if [ ! -d "/var/db"  ]; then
    mkdir /var/db
fi
touch /var/db/dhcpd.leases
cp ./resources/dhcp/* /usr/local/sbin/ >> $logFile

# Update netatalk configuration
if [ -f "/etc/default/netatalk" ]; then
	sed -i 's:.*ATALK_NAME=.*:ATALK_NAME=`/bin/hostname --short`:' /etc/default/netatalk
	sed -i 's:.*AFPD_MAX_CLIENTS=.*:AFPD_MAX_CLIENTS=200:' /etc/default/netatalk
	sed -i 's:.*AFPD_GUEST=.*:AFPD_GUEST=nobody:' /etc/default/netatalk
	sed -i 's:.*ATALKD_RUN=.*:ATALKD_RUN=no:' /etc/default/netatalk
	sed -i 's:.*PAPD_RUN=.*:PAPD_RUN=no:' /etc/default/netatalk
	sed -i 's:.*TIMELORD_RUN=.*:TIMELORD_RUN=no:' /etc/default/netatalk
	sed -i 's:.*A2BOOT_RUN=.*:A2BOOT_RUN=yes:' /etc/default/netatalk
	sed -i 's:.*ATALK_BGROUND=.*:ATALK_BGROUND=no:' /etc/default/netatalk
	sed -i '/"NetBoot"/d' /etc/netatalk/AppleVolumes.default
	sed -i '/End of File/d' /etc/netatalk/AppleVolumes.default
	echo '# End of File' >> /etc/netatalk/AppleVolumes.default
	sed -i '/End of File/ i\
/srv/NetBootClients/$i "NetBoot" allow:afpuser rwlist:afpuser options:upriv preexec:"mkdir -p /srv/NetBootClients/$i/NetBoot001" postexec:"rm -rf /srv/NetBootClients/$i"' /etc/netatalk/AppleVolumes.default
fi
if [ -f "/etc/netatalk/netatalk.conf" ]; then
	if ! grep -q '\- \-setuplog "default log_info /var/log/afpd.log"' /etc/netatalk/afpd.conf; then
		echo '- -setuplog "default log_info /var/log/afpd.log"' >> /etc/netatalk/afpd.conf
	fi
	sed -i 's:.*ATALK_NAME=.*:ATALK_NAME=`/bin/hostname --short`:' /etc/netatalk/netatalk.conf
	sed -i 's:.*AFPD_MAX_CLIENTS=.*:AFPD_MAX_CLIENTS=200:' /etc/netatalk/netatalk.conf
	sed -i 's:.*AFPD_GUEST=.*:AFPD_GUEST=nobody:' /etc/netatalk/netatalk.conf
	sed -i 's:.*ATALKD_RUN=.*:ATALKD_RUN=no:' /etc/netatalk/netatalk.conf
	sed -i 's:.*PAPD_RUN=.*:PAPD_RUN=no:' /etc/netatalk/netatalk.conf
	sed -i 's:.*TIMELORD_RUN=.*:TIMELORD_RUN=no:' /etc/netatalk/netatalk.conf
	sed -i 's:.*A2BOOT_RUN=.*:A2BOOT_RUN=yes:' /etc/netatalk/netatalk.conf
	sed -i 's:.*ATALK_BGROUND=.*:ATALK_BGROUND=no:' /etc/netatalk/netatalk.conf
	sed -i '/"NetBoot"/d' /etc/netatalk/AppleVolumes.default
	sed -i '/End of File/d' /etc/netatalk/AppleVolumes.default
	echo '# End of File' >> /etc/netatalk/AppleVolumes.default
	sed -i '/End of File/ i\
/srv/NetBootClients/$i "NetBoot" allow:afpuser rwlist:afpuser options:upriv cnidscheme:dbd ea:sys preexec:"mkdir -p /srv/NetBootClients/$i/NetBoot001" postexec:"rm -rf /srv/NetBootClients/$i"' /etc/netatalk/AppleVolumes.default
fi

# Create Apache Share for NetBoot
if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
	# Remove any entries from old installations
	sed -i '/[[:space:]]*Alias \/NetBoot\/ "\/srv\/NetBoot\/"/,/[[:space:]]*<\/Directory>/d' /etc/apache2/sites-enabled/000-default.conf
	sed -i "s'</VirtualHost>'\tAlias /NetBoot/ \"/srv/NetBoot/\"\n\t<Directory /srv/NetBoot/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>'g" /etc/apache2/sites-enabled/000-default.conf
fi
if [ -f "/etc/httpd/conf/httpd.conf" ]; then
	# Remove any entries from old installations
    sed -i '/[[:space:]]*Alias \/NetBoot\/ "\/srv\/NetBoot\/"/,/[[:space:]]*<\/Directory>/d' /etc/httpd/conf/httpd.conf
    if httpd -v | grep version | grep -q '2.2'; then 
    	echo '
    	Alias /NetBoot/ "/srv/NetBoot/"' >> /etc/httpd/conf/httpd.conf
    	echo '
    	<Directory "/srv/NetBoot">
    	Options Indexes FollowSymLinks MultiViews
    	AllowOverride None
    	Order allow,deny
    	Allow from all
    	</Directory>' >> /etc/httpd/conf/httpd.conf
    else
    	echo '
    	Alias /NetBoot/ "/srv/NetBoot/"' >> /etc/httpd/conf/httpd.conf
    	echo '
    	<Directory "/srv/NetBoot">
    	Options Indexes FollowSymLinks MultiViews
    	AllowOverride None
    	Require all granted
    	</Directory>' >> /etc/httpd/conf/httpd.conf
    fi
fi

# Create the accounts to be used for the different services
if [[ $(getent passwd smbuser) != "" ]]; then
    echo "smbuser already exists"
else
	useradd -c 'NetBoot Admin' -d /dev/null -g users -s $(which nologin) smbuser >> $logFile 2>&1
	echo smbuser:smbuser1 | chpasswd
	(echo smbuser1; echo smbuser1) | smbpasswd -s -a smbuser >> $logFile 2>&1
fi

# Needs normal user creation for AFP mount to work properly
if [[ $(getent passwd afpuser) != "" ]]; then
    echo "afpuser already exists"
else
	useradd -c 'NetBoot User' -d /home/afpuser -g users -m -s /bin/sh afpuser >> $logFile
	echo afpuser:afpuser1 | chpasswd
fi
if [ ! -d "/home/afpuser" ]; then
    mkdir /home/afpuser
    chown afpuser:users /home/afpuser >> $logFile
fi

# Configure nfs
if [ -f "/etc/default/nfs-kernel-server" ]; then
	sed -i 's/.*RPCMOUNTDOPTS.*/RPCMOUNTDOPTS="--port 892"/' /etc/default/nfs-kernel-server
	touch /etc/modprobe.d/lockd.conf
	sed -i '/^lockd/d' /etc/modules
	echo "lockd" >> /etc/modules
fi
if [ -f "/etc/sysconfig/nfs" ]; then
	if grep -q LOCKD_TCPPORT /etc/sysconfig/nfs; then
		sed -i 's/.*LOCKD_TCPPORT.*/LOCKD_TCPPORT=32803/' /etc/sysconfig/nfs
		sed -i 's/.*LOCKD_UDPPORT.*/LOCKD_UDPPORT=32769/' /etc/sysconfig/nfs
		sed -i 's/.*MOUNTD_PORT.*/MOUNTD_PORT=892/' /etc/sysconfig/nfs
	else
		sed -i 's/.*RPCMOUNTDOPTS.*/RPCMOUNTDOPTS="-p 892"/' /etc/sysconfig/nfs
	fi
fi
if [ -f "/etc/modprobe.d/lockd.conf" ]; then
	if ! grep -q nlm_tcpport /etc/modprobe.d/lockd.conf; then
		echo "options lockd nlm_tcpport=32803" >> /etc/modprobe.d/lockd.conf
	fi
	sed -i 's/.*nlm_tcpport.*/options lockd nlm_tcpport=32803/' /etc/modprobe.d/lockd.conf
	if ! grep -q nlm_udpport /etc/modprobe.d/lockd.conf; then
		echo "options lockd nlm_udpport=32769" >> /etc/modprobe.d/lockd.conf
	fi 
	sed -i 's/.*nlm_udpport.*/options lockd nlm_udpport=32769/' /etc/modprobe.d/lockd.conf
fi
sed -i "/NetBootSP0/d" /etc/exports
echo "/srv/NetBoot/NetBootSP0 *(ro,no_subtree_check,no_root_squash,insecure)" >> "/etc/exports"
exportfs -a

# Configure samba
# Change SMB setting for guest access
sed -i "s/map to guest = bad user/map to guest = never/g" /etc/samba/smb.conf
# Change SMB settings to allow for a symlink in an app or pkg
if ! grep -q 'unix extensions' /etc/samba/smb.conf ; then
	sed -i '/\[global\]/ a\\tunix extensions = no' /etc/samba/smb.conf
fi
# Change SMB setting to eliminate CUPS errors
sed -i 's:;\tprintcap name = lpstat:\tprintcap name = /dev/null:' /etc/samba/smb.conf
sed -i 's/;\tprinting = cups/\tprinting = bsd/' /etc/samba/smb.conf

# Create the SMB share for NetBoot
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

# Make the smbuser the owner of the NetBootSP0 share
chown smbuser /srv/NetBoot/NetBootSP0 >> $logFile

# Make the afpuser the owner of the NetBootClients share
chown afpuser /srv/NetBootClients >> $logFile

log "OK"

log "Finished deploying NetBoot"

exit 0