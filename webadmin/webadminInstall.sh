#/bin/bash
# This script controls the flow of the webadmin installation
pathToScript=$0
detectedOS=$1


# Logger
source logger.sh

logEvent "Starting Web Application Installation"
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    if [ $(lsb_release -rs) == '12.04' ]; then
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
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    yum install dialog -y -q >> $logFile
    yum install mod_ssl -y -q >> $logFile
    yum install php -y -q >> $logFile
    yum install php-xml -y -q >> $logFile
    yum install ntpdate -y -q >> $logFile
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




cp -R ./etc/* /etc/ >> $logFile
cp -R ./var/* /var/ >> $logFile

chmod +x /var/appliance/dialog.sh >> $logFile
chmod +x /etc/rc.local >> $logFile

#Get the user running the installer and write it to the conf file if it doesnt exist
if [ ! -f "/var/appliance/conf/appliance.conf.xml" ]; then
	shelluser=`env | grep SUDO_USER | sed 's/SUDO_USER=//g'`
	mkdir /var/appliance/conf/
	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><webadminSettings><shelluser>$shelluser</shelluser></webadminSettings>" > /var/appliance/conf/appliance.conf.xml
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
fi


# Enable apache on SSL, only needed on Ubuntu
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    a2enmod ssl >> $logFile
    a2ensite default-ssl >> $logFile
fi

if [[ "$detectedOS" == 'Ubuntu' ]]; then
    sed -i 's#<VirtualHost _default_:443>#<VirtualHost _default_:443>\n\t<Directory /var/www/webadmin/>\n\t\tOptions None\n\t\tAllowOverride None\n\t</Directory>#' /etc/apache2/sites-enabled/default-ssl
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    mv -f /var/www/index.php /var/www/html/
    if [ -d '/var/www/html/webadmin' ]; then
		rm -rf '/var/www/html/webadmin'
    fi
    mv -f /var/www/webadmin /var/www/html/
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    sed -i "s/Ubuntu/\`cat \/etc\/system-release | awk -F ' release ' '{print \$1}'\`/" /var/appliance/dialog.sh
    echo "openvt -s -c 8 /var/appliance/dialog.sh" >> /etc/rc.local
    sed -i "s/cat \/etc\/timezone/cat \/etc\/sysconfig\/clock | awk -F '\\\\\"' '{print \$2}'/" /var/www/html/webadmin/inc/functions.php
fi

# Restart apache
logEvent "Restarting apache..."
if [[ "$detectedOS" == 'Ubuntu' ]]; then
    /etc/init.d/apache2 restart >> $logFile
fi

if [[ "$detectedOS" == 'CentOS' ]] || [[ "$detectedOS" == 'RedHat' ]]; then
    /etc/init.d/httpd restart >> $logFile
fi
logEvent "OK"

logEvent "Finished deploying the appliance web application"

exit 0

