#/bin/bash
# This script controls the flow of the webadmin installation
pathToScript=$0
pathToPackage=$1
targetLocation=$2
targetVolume=$3

# Logger
source logger.sh

logEvent "Starting Web Application Installation"

if [ $(lsb_release -rs) == '12.04' ]; then
	apt-get -qq -y install whois >> $logFile
else
	apt-get -qq -y install mkpasswd >> $logFile
fi
apt-get -qq -y install php5 >> $logFile
apt-get -qq -y install dialog >> $logFile
apt-get -qq -y install python-m2crypto >> $logFile
apt-get -qq -y install python-pycurl >> $logFile

#Configure the firewall to block anything but the web app to start with
#ufw enable
#HTTP(S)
ufw allow 443/tcp >> $logFile
ufw allow 80/tcp >> $logFile
#SMB
ufw allow 139/tcp >> $logFile
ufw allow 445/tcp >> $logFile

cp -R ./etc/* /etc/ >> $logFile
cp -R ./var/* /var/ >> $logFile

chmod +x /var/appliance/dialog.sh >> $logFile
chmod +x /etc/rc.local >> $logFile

#Get the user running the installer and write it to the conf file if it doesnt exist
if [ ! -f "/var/appliance/conf/appliance.conf.xml" ]; then
	shelluser=`env | grep SUDO_USER | sed 's/SUDO_USER=//g'`
	mkdir /var/appliance/conf/
	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><webadminSettings><shelluser>$shelluser</shelluser></webadminSettings>" > /var/appliance/conf/appliance.conf.xml
	chown www-data /var/appliance/conf/appliance.conf.xml
fi

#Remove default it works page
if [ -f "/var/www/index.html" ]; then
	rm /var/www/index.html
fi 

#Prevent writes to the webadmin's helper script
chmod -wr /var/www/webadmin/scripts/adminHelper.sh >> $logFile
chown root:root /var/www/webadmin/scripts/adminHelper.sh >> $logFile
chmod u+r /var/www/webadmin/scripts/adminHelper.sh >> $logFile
		
#Allow the webadmin from webadmin to invoke the helper script
echo "www-data ALL=(ALL) NOPASSWD: /bin/sh scripts/adminHelper.sh *" >> /etc/sudoers

# Enable apache on SSL
a2enmod ssl >> $logFile
a2ensite default-ssl >> $logFile

sed -i 's#<VirtualHost _default_:443>#<VirtualHost _default_:443>\n\t<Directory /var/www/webadmin/>\n\t\tOptions None\n\t\tAllowOverride None\n\t</Directory>#' /etc/apache2/sites-enabled/default-ssl

# Restart apache
logEvent "Restarting apache..."
/etc/init.d/apache2 restart >> $logFile

logEvent "OK"

logEvent "Finished deploying the appliance web application"

exit 0

