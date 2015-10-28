#/bin/bash
# This script controls the flow of the webadmin installation
pathToScript=$0
detectedOS=$1


# Logger
source logger.sh

logEvent "Starting Web Application Installation"
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    if [ $(lsb_release -rs) == '12.04' ] || [ $(lsb_release -rs) == '14.04' ]; then
        apt-get -qq -y install whois >> $logFile
    else
        apt-get -qq -y install mkpasswd >> $logFile
    fi
fi

if [[ "$detectedOS" == 'Ubuntu' ]]; then
    apt-get -qq -y install php5 >> $logFile
    apt-get -qq -y install dialog >> $logFile
    apt-get -qq -y install python-m2crypto >> $logFile
    apt-get -qq -y install python-pycurl >> $logFile
    apt-get -qq -y install gawk >> $logFile
    
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
	if ! rpm -qa "dialog" | grep -q "dialog" ; then
		yum install dialog -y -q >> $logFile
	fi
	if ! rpm -qa "mod_ssl" | grep -q "mod_ssl" ; then
		yum install mod_ssl -y -q >> $logFile
	fi
	if ! rpm -qa "php" | grep -q "php" ; then
		yum install php -y -q >> $logFile
	fi
	if ! rpm -qa "php-xml" | grep -q "php-xml" ; then
		yum install php-xml -y -q >> $logFile
	fi
	if ! rpm -qa "ntpdate" | grep -q "ntpdate" ; then
		yum install ntpdate -y -q >> $logFile
	fi
    chkconfig httpd on
    chkconfig ntpdate on
fi

if [[ "$detectedOS" == 'CentOS' ]]; then
    echo "0.centos.pool.ntp.org" >> /etc/ntp/step-tickers
fi

if [[ "$detectedOS" == 'RedHat' ]]; then
    echo "0.rhel.pool.ntp.org" >> /etc/ntp/step-tickers
fi

#Preparing the firewall in case it is enabled later
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    #HTTP(S)
    ufw allow 443/tcp >> $logFile
    ufw allow 80/tcp >> $logFile
    #SMB
    ufw allow 139/tcp >> $logFile
    ufw allow 445/tcp >> $logFile
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
	if [ -f "/usr/bin/firewall-cmd" ]; then
		firewall-cmd --zone=public --add-port=443/tcp
		firewall-cmd --zone=public --add-port=443/tcp --permanent
		firewall-cmd --zone=public --add-port=80/tcp
		firewall-cmd --zone=public --add-port=80/tcp --permanent
		firewall-cmd --zone=public --add-port=139/tcp
		firewall-cmd --zone=public --add-port=139/tcp --permanent
		firewall-cmd --zone=public --add-port=445/tcp
		firewall-cmd --zone=public --add-port=445/tcp --permanent
	else
    	#HTTP(S)
    	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:https' ; then
        	iptables -I INPUT -p tcp --dport 443 -j ACCEPT
    	fi
    	if ! iptables -L | grep ACCEPT | grep -v 'tcp dpt:https' | grep -q 'tcp dpt:http' ; then
        	iptables -I INPUT -p tcp --dport 80 -j ACCEPT
    	fi
    	#SMB
    	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:netbios-ssn' ; then
        	iptables -I INPUT -p tcp --dport 139 -j ACCEPT
    	fi
    	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:microsoft-ds' ; then
        	iptables -I INPUT -p tcp --dport 445 -j ACCEPT
    	fi
    	service iptables save
    fi
fi



if [[ "$detectedOS" == 'Ubuntu' ]]; then
	cp -R ./etc/* /etc/ >> $logFile
	chmod +x /etc/rc.local >> $logFile
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
	#Update php.ini
	sed -i 's/short_open_tag =.*/short_open_tag = On/' /etc/php.ini
	sed -i 's/max_execution_time =.*/max_execution_time = 3600/' /etc/php.ini
	sed -i 's/max_input_time =.*/max_input_time = 3600/' /etc/php.ini
	sed -i 's/post_max_size =.*/post_max_size = 1024M/' /etc/php.ini
	sed -i 's/upload_max_filesize =.*/upload_max_filesize = 1024M/' /etc/php.ini
	#Update, don't replace rc.local
	echo 'openvt -s -c 8 /var/appliance/dialog.sh' >> /etc/rc.d/rc.local
fi

cp -R ./var/* /var/ >> $logFile

chmod +x /var/appliance/dialog.sh >> $logFile

#Get the user running the installer and write it to the conf file if it doesnt exist
if [ ! -f "/var/appliance/conf/appliance.conf.xml" ]; then
	shelluser=`env | grep SUDO_USER | sed 's/SUDO_USER=//g'`
	mkdir /var/appliance/conf/
	echo '<?xml version="1.0" encoding="utf-8"?><webadminSettings><shelluser>'$shelluser'</shelluser></webadminSettings>' > /var/appliance/conf/appliance.conf.xml
	if [[ "$detectedOS" == 'Ubuntu' ]]; then
		chown www-data /var/appliance/conf/appliance.conf.xml
	fi
	if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
		chown apache /var/appliance/conf/appliance.conf.xml
	fi
fi

#Remove default it works page
if [ -f "/var/www/index.html" ]; then
	rm /var/www/index.html
fi 
if [ -f "/var/www/html/index.html" ]; then
	rm /var/www/html/index.html
fi 

#Prevent writes to the webadmin's helper script

chmod -wr /var/www/webadmin/scripts/adminHelper.sh >> $logFile
chown root:root /var/www/webadmin/scripts/adminHelper.sh >> $logFile
chmod u+rx /var/www/webadmin/scripts/adminHelper.sh >> $logFile

#Allow the webadmin from webadmin to invoke the helper script
if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    sed -i 's/^\(Defaults *requiretty\)/#\1/' /etc/sudoers
    echo "apache ALL=(ALL) NOPASSWD: /bin/sh scripts/adminHelper.sh *" > /etc/sudoers.d/webadmin
    chmod 0440 /etc/sudoers.d/webadmin
fi

if [[ "$detectedOS" == 'Ubuntu' ]]; then
    echo "www-data ALL=(ALL) NOPASSWD: /bin/sh scripts/adminHelper.sh *" >> /etc/sudoers
    chmod 0440 /etc/sudoers
fi


# Enable apache on SSL, only needed on Ubuntu
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    a2enmod ssl >> $logFile
    a2ensite default-ssl >> $logFile
fi

if [[ "$detectedOS" == 'Ubuntu' ]]; then
	if [ -f "/etc/apache2/sites-enabled/000-default" ]; then
		sed -i 's/SSLProtocol all -SSLv2/SSLProtocol all -SSLv2 -SSLv3/' /etc/apache2/mods-enabled/ssl.conf
    	sed -i 's#<VirtualHost _default_:443>#<VirtualHost _default_:443>\n\t<Directory /var/www/webadmin/>\n\t\tOptions None\n\t\tAllowOverride None\n\t</Directory>#' /etc/apache2/sites-enabled/default-ssl
	fi
	if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
		sed -i 's/SSLProtocol all/SSLProtocol all -SSLv3/' /etc/apache2/mods-enabled/ssl.conf
    	mv -f /var/www/index.php /var/www/html/
    	if [ -d '/var/www/html/webadmin' ]; then
			rm -rf '/var/www/html/webadmin'
    	fi
    	mv -f /var/www/webadmin /var/www/html/
	fi
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    sed -i 's/#\?DocumentRoot.*/DocumentRoot "\/var\/www\/html"/' /etc/httpd/conf.d/ssl.conf
    sed -i 's/SSLProtocol all -SSLv2/SSLProtocol all -SSLv2 -SSLv3/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/\(^.*ssl_access_log.*$\)/#\1/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/\(^.*ssl_request_log.*$\)/#\1/' /etc/httpd/conf.d/ssl.conf
	sed -i 's/\(^.*SSL_PROTOCOL.*$\)/#\1/' /etc/httpd/conf.d/ssl.conf
	sed -i '/\(^.*SSL_PROTOCOL.*$\)/ a\CustomLog logs/ssl_access_log \\\
          "%h %l %u %t \\\"%r\\\" %>s %b \\\"%{Referer}i\\\" \\\"%{User-Agent}i\\\""' /etc/httpd/conf.d/ssl.conf
    mv -f /var/www/index.php /var/www/html/
    if [ -d '/var/www/html/webadmin' ]; then
		rm -rf '/var/www/html/webadmin'
    fi
    mv -f /var/www/webadmin /var/www/html/
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    sed -i "s/Ubuntu/\`cat \/etc\/system-release | awk -F ' release ' '{print \$1}'\`/" /var/appliance/dialog.sh
fi

# Restart apache
logEvent "Restarting apache..."
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    /etc/init.d/apache2 restart >> $logFile
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    service httpd restart >> $logFile
fi
logEvent "OK"

logEvent "Finished deploying the appliance web application"

exit 0

