#!/bin/bash
# This script controls the flow of the SUS installation

log "Starting SUS Installation"

apt_install() {
	if [[ $(apt-cache -n search ^${1}$ | awk '{print $1}' | grep ^${1}$) == "$1" ]] && [[ $(dpkg -s $1 2>&- | awk '/Status: / {print $NF}') != "installed" ]]; then
		apt-get -qq -y install $1 >> $logFile 2>&1
		if [[ $? -ne 0 ]]; then
			log "Failed to install ${1}"
			exit 1
		fi
	fi
}

yum_install() {
	if yum -q list $1 &>- && [[ $(rpm -qa $1) == "" ]] ; then
		yum install $1 -y -q >> $logFile 2>&1
		if [[ $? -ne 0 ]]; then
			log "Failed to install ${1}"
			exit 1
		fi
	fi
}

# Install required software
if [[ $(which apt-get 2>&-) != "" ]]; then
	apt_install libapache2-mod-php5
	apt_install libapache2-mod-php
	apt_install php-xml
	apt_install curl
elif [[ $(which yum 2>&-) != "" ]]; then
	yum_install mod_ssl
	yum_install php
	yum_install php-xml
fi

# Prepare the firewall in case it is enabled later
if [[ $(which ufw 2>&-) != "" ]]; then
	# HTTP
	ufw allow 80/tcp >> $logFile
elif [[ $(which firewall-cmd 2>&-) != "" ]]; then
	# HTTP
	firewall-cmd --zone=public --add-port=80/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=80/tcp --permanent >> $logFile 2>&1
else
	# HTTP
	if iptables -L | grep DROP | grep -v 'tcp dpt:https' | grep -q 'tcp dpt:http' ; then
		iptables -D INPUT -p tcp --dport 80 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -v 'tcp dpt:https' | grep -q 'tcp dpt:http' ; then
		iptables -I INPUT -p tcp --dport 80 -j ACCEPT
	fi
	service iptables save >> $logFile 2>&1
fi

# Create SUS directories
if [ ! -d "/var/appliance" ]; then
	mkdir /var/appliance
fi
if [ ! -d "/var/lib/reposado" ]; then
	mkdir /var/lib/reposado
fi
if [ ! -d "/srv/SUS/metadata" ]; then
	mkdir -p /srv/SUS/metadata
fi
if [ ! -d "/srv/SUS/html/content/catalogs" ]; then
	mkdir -p /srv/SUS/html/content/catalogs
fi

# Install reposado
cp -R ./resources/reposado/* /var/lib/reposado/ >> $logFile 2>&1
if [ ! -f "/var/lib/reposado/preferences.plist" ]; then
	cp ./resources/preferences.plist /var/lib/reposado/
fi

# Install scripts
cp ./resources/sus_info.py /var/appliance/ >> $logFile 2>&1
cp ./resources/sus_prefs.py /var/appliance/ >> $logFile 2>&1
cp ./resources/sus_sync.py /var/appliance/ >> $logFile 2>&1

# Set perms on scripts
chmod +x /var/appliance/sus_info.py
chmod +x /var/appliance/sus_prefs.py
chmod +x /var/appliance/sus_sync.py

# Enable apache rewrite rules
if [[ $(which a2enmod 2>&-) != "" ]]; then
	a2enmod rewrite >> $logFile
fi

# Point Apache to SUS

if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
	# Remove any entries from old installations
	sed -i "s:DocumentRoot.*:DocumentRoot /var/www/html:g" /etc/apache2/sites-enabled/000-default.conf
	sed -i '/[[:space:]]*<Directory \/srv\/SUS\//,/[[:space:]]*<\/Directory>/d' /etc/apache2/sites-enabled/000-default.conf
	sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/apache2/sites-enabled/000-default.conf
	sed -i '/sucatalog/d' /etc/apache2/sites-enabled/000-default.conf
	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/apache2/sites-enabled/000-default.conf
	# Add SUS configuration
	echo '<Directory /var/www/html/>
	AllowOverride All
</Directory>

Alias /content/ "/srv/SUS/html/content/"
<Directory /srv/SUS/html/content/>
	Options Indexes FollowSymLinks MultiViews
	AllowOverride None
	Require all granted
</Directory>

<Directory /srv/SUS/html/content/catalogs/>
	AllowOverride All
</Directory>' > /etc/apache2/sites-enabled/000-sus.conf
fi
if [ -f "/etc/httpd/conf/httpd.conf" ]; then
	# Remove any entries from old installations
	sed -i 's:/srv/SUS/html:/var/www/html:' /etc/httpd/conf/httpd.conf
	sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/httpd/conf/httpd.conf
	sed -i '/sucatalog/d' /etc/httpd/conf/httpd.conf
	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/httpd/conf/httpd.conf
	# Add SUS configuration
    if httpd -v 2>/dev/null | grep version | grep -q '2.2'; then
    	sed -i '/<Directory "\/var\/www\/html">/,/<\/Directory>/{s/AllowOverride None/AllowOverride All/g}' /etc/httpd/conf/httpd.conf
    	echo 'Alias /content/ "/srv/SUS/html/content/"
<Directory /srv/SUS/html/content/>
	Options Indexes FollowSymLinks MultiViews
	AllowOverride None
	Order allow,deny
	Allow from all
</Directory>

<Directory /srv/SUS/html/content/catalogs/>
	AllowOverride All
</Directory>' > /etc/httpd/conf.d/sus.conf
    else
    	echo '<Directory /var/www/html/>
	AllowOverride All
</Directory>

Alias /content/ "/srv/SUS/html/content/"
<Directory /srv/SUS/html/content/>
	Options Indexes FollowSymLinks MultiViews
	AllowOverride None
	Require all granted
</Directory>

<Directory /srv/SUS/html/content/catalogs/>
	AllowOverride All
</Directory>' > /etc/httpd/conf.d/sus.conf
	fi
fi

cp ./resources/htaccess_webroot /var/www/html/.htaccess >> $logFile 2>&1
cp ./resources/htaccess_catalogs /srv/SUS/html/content/catalogs/.htaccess >> $logFile 2>&1

# Relocate default catalogs
mv /srv/SUS/html/*.sucatalog /var/www/html/ 2>/dev/null

log "OK"

log "Finished deploying SUS"

exit 0