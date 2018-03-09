#!/bin/bash
# This script controls the flow of the webadmin installation

log "Starting Web Application Installation"

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
	apt_install gawk
	apt_install ufw
	apt_install parted
	apt_install whois
	apt_install dialog
	apt_install python-m2crypto
	apt_install python-pycurl
	apt_install libapache2-mod-php5
	apt_install libapache2-mod-php
	apt_install apache2-utils
	apt_install php5-ldap
	apt_install php-ldap
	apt_install php-xml
	www_user=www-data
	www_service=apache2
elif [[ $(which yum 2>&-) != "" ]]; then
	yum_install python-pycurl
	yum_install parted
	yum_install dmidecode
	yum_install psmisc
	yum_install dialog
	yum_install lsof
	yum_install m2crypto
	yum_install ntpdate
	yum_install mod_ssl
	yum_install php
	yum_install php-xml
	yum_install php-ldap
	chkconfig httpd on >> $logFile 2>&1
	chkconfig ntpdate on >> $logFile 2>&1
	www_user=apache
	www_service=httpd
fi

# Prepare the firewall in case it is enabled later
if [[ $(which ufw 2>&-) != "" ]]; then
	# HTTPS
	ufw allow 443/tcp >> $logFile
	# SSH
	ufw allow 22/tcp >> $logFile
elif [[ $(which firewall-cmd 2>&-) != "" ]]; then
	# HTTP(S)
	firewall-cmd --zone=public --add-port=443/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=443/tcp --permanent >> $logFile 2>&1
else
	# HTTP(S)
	if iptables -L | grep DROP | grep -q 'tcp dpt:https' ; then
		iptables -D INPUT -p tcp --dport 443 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:https' ; then
		iptables -I INPUT -p tcp --dport 443 -j ACCEPT
	fi
	service iptables save >> $logFile 2>&1
fi

# Initial configuration of the network time server
if [ -f "/etc/ntp/step-tickers" ]; then
	currentTimeServer=$(cat /etc/ntp/step-tickers | grep -v "^$" | grep -m 1 -v '#')
	if [[ $currentTimeServer == "" ]]; then
		if [[ $(readlink /etc/system-release) == "centos-release" ]]; then
			currentTimeServer=0.centos.pool.ntp.org
		else
			currentTimeServer=0.rhel.pool.ntp.org
		fi
		echo $currentTimeServer >> /etc/ntp/step-tickers
	fi
else
	currentTimeServer=$(cat /etc/cron.daily/ntpdate 2>/dev/null | awk '{print $NF}')
	if [[ $currentTimeServer == "" ]]; then
		echo "server 0.ubuntu.pool.ntp.org" > /etc/cron.daily/ntpdate
	fi
fi
if [[ $currentTimeServer != "" ]]; then
	ntpdate $currentTimeServer >> $logFile 2>&1
fi

# Enable console dialog
cp ./resources/dialog.sh /var/appliance/dialog.sh >> $logFile
chmod +x /var/appliance/dialog.sh >> $logFile
if [ -f "/etc/rc.d/rc.local" ]; then
	rc_local=/etc/rc.d/rc.local
else
	rc_local=/etc/rc.local
fi
sed -i '/TERM/d' $rc_local
sed -i '/dialog.sh/d' $rc_local
sed -i '/exit 0/d' $rc_local
echo 'TERM=linux
export TERM
openvt -s -c 8 /var/appliance/dialog.sh
exit 0' >> $rc_local
chmod +x $rc_local

# Configure php
if [ -f "/etc/php/7.0/apache2/php.ini" ]; then
	php_ini=/etc/php/7.0/apache2/php.ini
elif [ -f "/etc/php5/apache2/php.ini" ]; then
	php_ini=/etc/php5/apache2/php.ini
elif [ -f "/etc/php.ini" ]; then
	php_ini=/etc/php.ini
else
	log "Error: Failed to locate php.ini"
	exit 1
fi
sed -i 's/^disable_functions =.*/disable_functions =/' $php_ini
sed -i 's/max_execution_time =.*/max_execution_time = 3600/' $php_ini
sed -i 's/max_input_time =.*/max_input_time = 3600/' $php_ini
sed -i 's/^; max_input_vars =.*/max_input_vars = 10000/' $php_ini
sed -i 's/post_max_size =.*/post_max_size = 1024M/' $php_ini
sed -i 's/upload_max_filesize =.*/upload_max_filesize = 1024M/' $php_ini
sed -i 's/^session.gc_probability =.*/session.gc_probability = 1/' $php_ini

# Get the user running the installer and write it to the conf file if it doesnt exist
if [ ! -f "/var/appliance/conf/appliance.conf.xml" ]; then
	shelluser=$(env | grep SUDO_USER | sed 's/SUDO_USER=//g')
	# If SUDO_USER is empty this is probably being installed in the root account and we will need to create the shelluser if it doesn't exist
	if [[ $shelluser == "" ]] || [[ $shelluser == "root" ]]; then
		shelluser=shelluser
		if [[ $(getent passwd shelluser) == "" ]]; then
			if [[ $(getent group wheel) == "" ]]; then
				groupadd shelluser
				useradd -d /home/shelluser -g shelluser -G adm,cdrom,sudo,dip,plugdev,lpadmin,sambashare -m -s /bin/bash shelluser
			else
				useradd -c 'shelluser' -d /home/shelluser -G wheel -m -s /bin/bash shelluser
				sed -i '/NOPASSWD/!s/.*%wheel/%wheel/' /etc/sudoers
			fi
		fi
	fi
	mkdir -p /var/appliance/conf/
	echo '<?xml version="1.0" encoding="utf-8"?><webadminSettings><shelluser>'$shelluser'</shelluser></webadminSettings>' > /var/appliance/conf/appliance.conf.xml
	chown $www_user /var/appliance/conf/appliance.conf.xml
fi

# Remove default it works page
rm -f /var/www/html/index.html

# Install the webadmin interface
cp -R ./resources/html/* /var/www/html/ >> $logFile

# Prevent writes to the webadmin's helper script
chown root:root /var/www/html/webadmin/scripts/adminHelper.sh >> $logFile
chmod a-wr /var/www/html/webadmin/scripts/adminHelper.sh >> $logFile
chmod u+rx /var/www/html/webadmin/scripts/adminHelper.sh >> $logFile

# Allow the webadmin from webadmin to invoke the helper script
sed -i '/scripts\/adminHelper.sh/d' /etc/sudoers
sed -i 's/^\(Defaults *requiretty\)/#\1/' /etc/sudoers
if [[ $(grep "^#includedir /etc/sudoers.d" /etc/sudoers) == "" ]] ; then
	echo "#includedir /etc/sudoers.d" >> /etc/sudoers
fi
echo "$www_user ALL=(ALL) NOPASSWD: /bin/sh scripts/adminHelper.sh *" > /etc/sudoers.d/webadmin
chmod 0440 /etc/sudoers.d/webadmin

# Enable apache on SSL, dav and dav_fs, only needed on Ubuntu
if [[ $(which a2enmod 2>&-) != "" ]]; then
	# Previous NetSUS versions created a file where it should be a symlink
	if [ ! -L /etc/apache2/mods-enabled/ssl.conf ]; then
		rm -f /etc/apache2/mods-enabled/ssl.conf
	fi
	sed -i 's/SSLProtocol all/SSLProtocol all -SSLv3/' /etc/apache2/mods-available/ssl.conf
	a2enmod ssl >> $logFile
	a2ensite default-ssl >> $logFile
	a2enmod dav >> $logFile
	a2enmod dav_fs >> $logFile
fi

if [ -f "/etc/httpd/conf.d/ssl.conf" ]; then
	sed -i 's/#\?DocumentRoot.*/DocumentRoot "\/var\/www\/html"/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/SSLProtocol all -SSLv2/SSLProtocol all -SSLv2 -SSLv3/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/\(^.*ssl_access_log.*$\)/#\1/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/\(^.*ssl_request_log.*$\)/#\1/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/\(^.*SSL_PROTOCOL.*$\)/#\1/' /etc/httpd/conf.d/ssl.conf
	sed -i '/\(^.*SSL_PROTOCOL.*$\)/ a\CustomLog logs/ssl_access_log \\\
          "%h %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\""' /etc/httpd/conf.d/ssl.conf
fi

# Restart apache
log "Restarting apache..."
service $www_service restart >> $logFile 2>&1

log "OK"

log "Finished deploying the appliance web application"

exit 0
