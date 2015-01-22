#!/bin/bash
# This script controls the flow of the SUS installation
pathToScript=$0
detectedOS=$1

# Logger
source logger.sh

logEvent "Starting SUS Installation"

if [[ $detectedOS == 'Ubuntu' ]]; then
    apt-get -qq -y install php5 >> $logFile
    apt-get -qq -y install curl >> $logFile
fi

if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	if ! rpm -qa "mod_ssl" | grep -q "mod_ssl" ; then
		yum install mod_ssl -y -q >> $logFile
	fi
	if ! rpm -qa "php" | grep -q "php" ; then
		yum install php -y -q >> $logFile
	fi
	if ! rpm -qa "php-xml" | grep -q "php-xml" ; then
		yum install php-xml -y -q >> $logFile
	fi
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

cp -R ./var/* /var/

#Set perms on SUS sync
chmod +x /var/appliance/sus_sync.py

# create our own apache rewrite include file (which will also be updated by sus_sync.py)
cat > /var/appliance/conf/apache-sus-rewrites.conf <<ZHEREDOC
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{HTTP_USER_AGENT} Darwin/8
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/index.sucatalog
	RewriteCond %{HTTP_USER_AGENT} Darwin/9
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/others/index-leopard.merged-1.sucatalog
	RewriteCond %{HTTP_USER_AGENT} Darwin/10
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/others/index-leopard-snowleopard.merged-1.sucatalog
	RewriteCond %{HTTP_USER_AGENT} Darwin/11
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/others/index-lion-snowleopard-leopard.merged-1.sucatalog
	RewriteCond %{HTTP_USER_AGENT} Darwin/12
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/others/index-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
	RewriteCond %{HTTP_USER_AGENT} Darwin/13
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/others/index-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
	RewriteCond %{HTTP_USER_AGENT} Darwin/14
	RewriteRule ^/index\.sucatalog$ http://%{HTTP_HOST}/content/catalogs/others/index-10.10-10.9-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog
</IfModule>
ZHEREDOC
chown www-data /var/appliance/conf/apache-sus-rewrites.conf
chmod u=rw,go=r /var/appliance/conf/apache-sus-rewrites.conf


# Enable apache rewrite rules
if [[ $detectedOS == 'Ubuntu' ]]; then
    a2enmod rewrite >> $logFile
fi

#Point Apache to SUS
#TODO - This will not take into account if the installer is run again
if [[ $detectedOS == 'Ubuntu' ]]; then
	if [ -f "/etc/apache2/sites-enabled/000-default" ]; then
		apacheConfigFile="/etc/apache2/sites-enabled/000-default"
	elif [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
		apacheConfigFile="/etc/apache2/sites-enabled/000-default.conf"
	else
		apacheConfigFile=""
	fi
	if [[ -n "${apacheConfigFile}" ]]; then
		# Remove any entries from old installations
		sed -i '/{HTTP_USER_AGENT} Darwin/d' "${apacheConfigFile}"
		sed -i '/sucatalog/d' "${apacheConfigFile}"
		sed -i '/\/apache-sus-rewrites.conf$/d' "${apacheConfigFile}"
		# Remove empty <IfModule mod_rewrite.c> sections
		sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' "${apacheConfigFile}"
		# Configure our DocumentRoot
    	sed -i "s/DocumentRoot.*/DocumentRoot \/srv\/SUS\/html\//g" "${apacheConfigFile}"
   		# Additional Ubuntu 14 configuration
    	if [[ "${apacheConfigFile}" == "/etc/apache2/sites-enabled/000-default.conf" ]]; then
	    	sed -i '/[[:space:]]*<Directory \/srv\/SUS\//,/[[:space:]]*<\/Directory>/d' "${apacheConfigFile}"
	    	sed -i "s'</VirtualHost>'\t<Directory /srv/SUS/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>'g" "${apacheConfigFile}"
        fi
		# add our own Include directive
		sed -i "s|</VirtualHost>||" "${apacheConfigFile}"
		cat >>"${apacheConfigFile}" <<ZHEREDOC
	Include /var/appliance/conf/apache-sus-rewrites.conf
</VirtualHost>
ZHEREDOC
	else
		logEvent "Error: No Ubuntu Apache Config File Found."
	fi
elif [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	apacheConfigFile="/etc/httpd/conf/httpd.conf"
	sed -i '/{HTTP_USER_AGENT} Darwin/d' "${apacheConfigFile}"
	sed -i '/sucatalog/d' "${apacheConfigFile}"
	sed -i '/\/apache-sus-rewrites.conf$/d' "${apacheConfigFile}"
	sed -i 's:var/www/html:/srv/SUS/html:' "${apacheConfigFile}"
	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' "${apacheConfigFile}"
	# add our own Include directive
	sed -i "s|</VirtualHost>||" "${apacheConfigFile}"
	cat >>"${apacheConfigFile}" <<ZHEREDOC
	Include /var/appliance/conf/apache-sus-rewrites.conf
</VirtualHost>
ZHEREDOC
fi

logEvent "OK"
logEvent "Finished deploying the appliance web application"

exit 0
