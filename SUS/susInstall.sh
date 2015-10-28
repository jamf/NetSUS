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

# Enable apache rewrite rules
if [[ $detectedOS == 'Ubuntu' ]]; then
    a2enmod rewrite >> $logFile
fi
#Point Apache to SUS
#TODO - This will not take into account if the installer is run again

if [[ $detectedOS == 'Ubuntu' ]]; then
	if [ -f "/etc/apache2/sites-enabled/000-default" ]; then
    	sed -i "s/DocumentRoot.*/DocumentRoot \/srv\/SUS\/html\//g" /etc/apache2/sites-enabled/000-default
    fi
	if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
    	sed -i "s/DocumentRoot.*/DocumentRoot \/srv\/SUS\/html\//g" /etc/apache2/sites-enabled/000-default.conf
    	sed -i '/[[:space:]]*<Directory \/srv\/SUS\//,/[[:space:]]*<\/Directory>/d' /etc/apache2/sites-enabled/000-default.conf
    	sed -i "s'</VirtualHost>'\t<Directory /srv/SUS/>\n\t\tOptions Indexes FollowSymLinks MultiViews\n\t\tAllowOverride None\n\t\tRequire all granted\n\t</Directory>\n</VirtualHost>'g" /etc/apache2/sites-enabled/000-default.conf
    fi
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	# Remove any entries from old installations
	sed -i 's:/srv/SUS/html:/var/www/html:' /etc/httpd/conf/httpd.conf
	sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/httpd/conf/httpd.conf
	sed -i '/sucatalog/d' /etc/httpd/conf/httpd.conf
	sed -i 's/\/var\/www\/html/\/srv\/SUS\/html/' /etc/httpd/conf/httpd.conf	
fi
if [[ $detectedOS == 'Ubuntu' ]]; then
if [ -f "/etc/apache2/sites-enabled/000-default" ]; then
	sed -i "s|</VirtualHost>||" /etc/apache2/sites-enabled/000-default

	# Remove any entries from old installations
	sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/apache2/sites-enabled/000-default
	sed -i '/sucatalog/d' /etc/apache2/sites-enabled/000-default


	cat >>/etc/apache2/sites-enabled/000-default <<ZHEREDOC
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
    	</IfModule>

</VirtualHost>
ZHEREDOC

	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/apache2/sites-enabled/000-default
fi
if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
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
    	</IfModule>

</VirtualHost>
ZHEREDOC

	# Remove empty <IfModule mod_rewrite.c> sections
	sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/apache2/sites-enabled/000-default.conf
fi
fi

if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
# Remove any entries from old installations
sed -i '/{HTTP_USER_AGENT} Darwin/d' /etc/httpd/conf/httpd.conf
sed -i '/sucatalog/d' /etc/httpd/conf/httpd.conf

echo '
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
</IfModule>' >> /etc/httpd/conf/httpd.conf

# Remove empty <IfModule mod_rewrite.c> sections
sed -i 'N;N;s/\n[[:space:]]*<IfModule mod_rewrite.c>\n[[:space:]]*RewriteEngine On\n[[:space:]]*<\/IfModule>//;P;D' /etc/httpd/conf/httpd.conf
fi



logEvent "OK"

logEvent "Finished deploying the appliance web application"

exit 0

