#!/bin/bash
# This script controls the flow of the SUS installation

log "Starting SUS Installation"

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
	apt_install libapache2-mod-php5
	apt_install libapache2-mod-php
	apt_install php-xml
	apt_install curl
fi
if [[ $(which yum 2>&-) != "" ]]; then
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
cp ./resources/sus_sync.py /var/appliance/ >> $logFile 2>&1
cp -R ./resources/reposado/* /var/lib/reposado/ >> $logFile 2>&1

# Set perms on SUS sync
chmod +x /var/appliance/sus_sync.py

# Enable apache rewrite rules
if [[ $(which a2enmod 2>&-) != "" ]]; then
	a2enmod rewrite >> $logFile
fi

# Point Apache to SUS
# TODO - This will not take into account if the installer is run again

if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
	sed -i "s:DocumentRoot.*:DocumentRoot /srv/SUS/html/:g" /etc/apache2/sites-enabled/000-default.conf
	sed -i '/[[:space:]]*<Directory \/srv\/SUS\//,/[[:space:]]*<\/Directory>/d' /etc/apache2/sites-enabled/000-default.conf
	sed -i "s'</VirtualHost>'\t<Directory /srv/SUS/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>'g" /etc/apache2/sites-enabled/000-default.conf
	sed -i "s|</VirtualHost>||" /etc/apache2/sites-enabled/000-default.conf
	# Remove any entries from old installations
	sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/apache2/sites-enabled/000-default.conf
	sed -i '/sucatalog/d' /etc/apache2/sites-enabled/000-default.conf
	cat >>/etc/apache2/sites-enabled/000-default.conf <<ZHEREDOC
	<IfModule mod_rewrite.c>
		RewriteEngine On
		RewriteCond %{HTTP_USER_AGENT} Darwin/9
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/10
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-leopard-snowleopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/11
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/12
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/13
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/14
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/15
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/16
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
	</IfModule>

</VirtualHost>
ZHEREDOC
	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/apache2/sites-enabled/000-default.conf
fi
if [ -f "/etc/httpd/conf/httpd.conf" ]; then
	# Remove any entries from old installations
	sed -i 's:/srv/SUS/html:/var/www/html:' /etc/httpd/conf/httpd.conf
	sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/httpd/conf/httpd.conf
	sed -i '/sucatalog/d' /etc/httpd/conf/httpd.conf
	sed -i 's:/var/www/html:/srv/SUS/html:' /etc/httpd/conf/httpd.conf	
	cat >>/etc/httpd/conf/httpd.conf <<ZHEREDOC
	<IfModule mod_rewrite.c>
		RewriteEngine On
		RewriteCond %{HTTP_USER_AGENT} Darwin/9
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/10
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-leopard-snowleopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/11
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/12
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/13
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/14
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/15
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
		RewriteCond %{HTTP_USER_AGENT} Darwin/16
		RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/index-10.12-10.11-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
	</IfModule>
ZHEREDOC
	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/httpd/conf/httpd.conf
fi

log "OK"

log "Finished deploying SUS"

exit 0