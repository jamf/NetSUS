#!/bin/bash
# This script controls the flow of the webadmin installation

log "Starting Web Application Installation"

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
	apt_install gawk
	apt_install ufw
	apt_install openssh-server
	apt_install parted
	apt_install whois
	apt_install dialog
	apt_install python-m2crypto
	apt_install python-pycurl
	apt_install libapache2-mod-php5
	apt_install libapache2-mod-php
	apt_install lvm2
	apt_install apache2-utils
	apt_install php5-ldap
	apt_install php-ldap
	apt_install php-xml
	apt_install php-zip
	if [ ! -f "/etc/systemd/timesyncd.conf" ]; then
		apt_install ntp
	fi
	www_user=www-data
	www_service=apache2
elif [[ $(which yum 2>&-) != "" ]]; then
	yum_install python-pycurl
	yum_install parted
	yum_install dmidecode
	yum_install psmisc
	yum_install dialog
	yum_install lsof
	yum_install lvm2
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

# Initial configuration of time zone
if [[ $(which timedatectl 2>&-) != "" ]]; then
	if ! timedatectl --no-pager list-timezones | grep -q "$(timedatectl | grep 'Time.*zone' | cut -d : -f 2 | awk '{print $1}')"; then
		timedatectl set-timezone "America/New_York"
	fi
fi

# Initial configuration of the network time server
if [ -f "/etc/ntp/step-tickers" ]; then
	timeServer=$(grep -v "^$" /etc/ntp/step-tickers | grep -m 1 -v '#')
	if [[ $timeServer == "" ]]; then
		if [[ $(readlink /etc/system-release) == "centos-release" ]]; then
			timeServer=0.centos.pool.ntp.org
		else
			timeServer=0.rhel.pool.ntp.org
		fi
		echo $timeServer >> /etc/ntp/step-tickers
	fi
else
	timeServer=$(cat /etc/cron.daily/ntpdate 2>/dev/null | awk '{print $NF}')
	if [ -f "/etc/ntp.conf" ]; then
		i=0
		for j in $(sed -e '/fallback/q' /etc/ntp.conf | grep '^server\|^pool' | awk '{print $2}'); do
			if [ $i -gt 0 ]; then
				sed -i "/$j/d" /etc/ntp.conf
			fi
			let i++
		done
		if [[ $timeServer != "" ]]; then
			sed -i "0,/^server/{s/^server.*/server $timeServer/}" /etc/ntp.conf
			sed -i "0,/^pool/{s/^pool.*/pool $timeServer iburst/}" /etc/ntp.conf
			rm -f /etc/cron.daily/ntpdate
		fi
		service ntp restart >> $logFile 2>&1
	else
		sed -i 's/#NTP=/NTP=/' /etc/systemd/timesyncd.conf
		if [[ $timeServer == "" ]]; then
			timeServer=$(grep '^NTP=' /etc/systemd/timesyncd.conf | cut -d = -f 2 | awk '{print $1}')
		fi
		if [[ $timeServer == "" ]]; then
			timeServer=0.ubuntu.pool.ntp.org
		fi
		sed -i "s/^NTP=.*/NTP=$timeServer/" /etc/systemd/timesyncd.conf
		systemctl restart systemd-timesyncd
	fi
fi
if [[ $(which ntpdate 2>&-) != "" ]]; then
	ntpdate $timeServer >> $logFile 2>&1
fi

# Enable console dialog
cp ./resources/dialog.sh /var/appliance/dialog.sh >> $logFile
chmod +x /var/appliance/dialog.sh >> $logFile
if [ -f "/etc/rc.d/rc.local" ]; then
	rc_local=/etc/rc.d/rc.local
else
	rc_local=/etc/rc.local
	if [ ! -f "/etc/rc.local" ]; then
		echo '#!/bin/sh -e' > /etc/rc.local
		echo >> /etc/rc.local
		chmod +x /etc/rc.local
	fi
fi
sed -i '/TERM/d' $rc_local
sed -i '/dialog.sh/d' $rc_local
sed -i '/exit 0/d' $rc_local
echo 'rm -f /var/appliance/.shutdownMessage
TERM=linux
export TERM
openvt -s -c 8 /var/appliance/dialog.sh
exit 0' >> $rc_local
chmod +x $rc_local

# Configure php
if [ -f "/etc/php/7.2/apache2/php.ini" ]; then
	php_ini=/etc/php/7.2/apache2/php.ini
elif [ -f "/etc/php/7.0/apache2/php.ini" ]; then
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

# Add Patch Server Components, if detected
if [ -f '/var/www/html/webadmin/patchTitles.php' ]; then
	# Menu
	sed -i '/$pageURI == "sharing.php"/i\
				<li id="patch" class="<?php echo ($conf->getSetting("patch") == "enabled" ? ($pageURI == "patchTitles.php" ? "active" : "") : "hidden"); ?>"><a href="patchTitles.php"><span class="netsus-icon icon-patch marg-right"></span>Patch Definitions</a></li>' /var/www/html/webadmin/inc/header.php

	# Dashboard
	if [ -f '/var/www/html/webadmin/scripts/patchHelper.sh' ]; then
		sed -i '1,/panel panel-default panel-main/ {/panel panel-default panel-main/i\
				<div class="panel panel-default panel-main <?php echo ($conf->getSetting("showpatch") == "false" ? "hidden" : ""); ?>">\
					<div class="panel-heading">\
						<strong>Patch Definitions</strong>\
					</div>\
<?php\
include "inc/dbConnect.php";\
if (isset($pdo)) {\
	$title_count = $pdo->query("SELECT COUNT(id) FROM titles")->fetchColumn();\
}\
\
function patchExec($cmd) {\
	return shell_exec("sudo /bin/sh scripts/patchHelper.sh ".escapeshellcmd($cmd)." 2>&1");\
}\
\
if ($conf->getSetting("kinobi_url") != "" && $conf->getSetting("kinobi_token") != "") {\
	$ch = curl_init();\
	curl_setopt($ch, CURLOPT_URL, $conf->getSetting("kinobi_url"));\
	curl_setopt($ch, CURLOPT_POST, true);\
	curl_setopt($ch, CURLOPT_POSTFIELDS, "token=".$conf->getSetting("kinobi_token"));\
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\
	$result = curl_exec($ch);\
	curl_close ($ch);\
	$token = json_decode($result, true);\
}\
?>\
\
					<div class="panel-body">\
						<div class="row">\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2 dashboard-item">\
								<a href="patchTitles.php">\
									<p><img src="images/settings/PatchManagement.png" alt="Patch Management"></p>\
								</a>\
							</div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>SSL Enabled</strong></h5>\
									<span class="text-muted"><?php echo (trim(patchExec("getSSLstatus")) == "true" ? "Yes" : "No") ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Hostname</strong></h5>\
									<span class="text-muted" style="word-break: break-all;"><?php echo $_SERVER["HTTP_HOST"]."/v1.php"; ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
\
							<div class="clearfix visible-xs-block visible-sm-block"></div>\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Number of Titles</strong></h5>\
									<span class="text-muted"><?php echo $title_count; ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
<?php if (isset($token["expires"])) { ?>\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Subscription Expires</strong></h5>\
									<span class="text-muted"><?php echo date("Y-m-d H:i:s", $token["expires"]); ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
<?php } ?>\
						</div>\
						<!-- /Row -->\
					</div>\
				</div>\

}' /var/www/html/webadmin/dashboard.php
	else
		sed -i '1,/panel panel-default panel-main/ {/panel panel-default panel-main/i\
				<div class="panel panel-default panel-main <?php echo ($conf->getSetting("showpatch") == "false" ? "hidden" : ""); ?>">\
					<div class="panel-heading">\
						<strong>Patch Definitions</strong>\
					</div>\
<?php\
// SSL Enabled\
$ch = curl_init();\
curl_setopt($ch, CURLOPT_URL, "https://".$_SERVER["HTTP_HOST"]);\
curl_setopt($ch, CURLOPT_CERTINFO, true);\
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);\
curl_exec($ch);\
$certinfo = curl_getinfo($ch);\
curl_close ($ch);\
\
// Patch Titles\
include "inc/patch/functions.php";\
include "inc/patch/database.php";\
if (isset($pdo)) {\
	$title_count = $pdo->query("SELECT COUNT(id) FROM titles WHERE enabled = 1")->fetchColumn();\
}\
\
// Suscription\
$subs = $kinobi->getSetting("subscription");\
if (!empty($subs["url"]) && !empty($subs["token"])) {\
	$subs_resp = fetchJsonArray($subs["url"], $subs["token"]);\
}\
?>\
\
					<div class="panel-body">\
						<div class="row">\
<?php if ($conf->getSetting("patch") == "enabled") { ?>\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2 dashboard-item">\
								<a href="patchTitles.php">\
									<p><img src="images/settings/PatchManagement.png" alt="Patch Management"></p>\
								</a>\
							</div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>SSL Enabled</strong></h5>\
									<span class="text-muted"><?php echo (empty($certinfo["certinfo"]) ? "No" : "Yes"); ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Hostname</strong></h5>\
									<span class="text-muted" style="word-break: break-all;"><?php echo $_SERVER["HTTP_HOST"]."/v1.php"; ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
\
							<div class="clearfix visible-xs-block visible-sm-block"></div>\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Number of Titles</strong></h5>\
									<span class="text-muted"><?php echo $title_count; ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
<?php if (isset($subs_resp["expires"])) { ?>\
\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Subscription Expires</strong></h5>\
									<span class="text-muted"><?php echo date("Y-m-d H:i:s", $subs_resp["expires"]); ?></span>\
								</div>\
							</div>\
							<!-- /Column -->\
<?php }\
} else { ?>\
							<!-- Column -->\
							<div class="col-xs-4 col-md-2 dashboard-item">\
								<a href="patchSettings.php">\
									<p><img src="images/settings/PatchManagement.png" alt="Patch Management"></p>\
								</a>\
							</div>\
							<!-- /Column -->\
\
							<!-- Column -->\
							<div class="col-xs-8 col-md-10">\
								<div class="bs-callout bs-callout-default">\
									<h5><strong>Configure Patch Definitions</strong> <small>to provide an external patch source for Jamf Pro.</small></h5>\
									<button type="button" class="btn btn-default btn-sm" onClick="document.location.href=patchSettings.php">Patch Definitions Settings</button>\
								</div>\
							</div>\
							<!-- /Column -->\
<?php } ?>\
						</div>\
						<!-- /Row -->\
					</div>\
				</div>\

}' /var/www/html/webadmin/dashboard.php
	fi

	# Settings
	sed -i '/<a href="sharingSettings.php">/i\
					<a href="patchSettings.php">\
						<p><img src="images/settings/PatchManagement.png" alt="Patch Definitions"></p>\
						<p>Patch Definitions</p>\
					</a>\
				</div>\
				<!-- /Column -->\
				<!-- Column -->\
				<div class="col-xs-3 col-sm-2 settings-item">' /var/www/html/webadmin/settings.php
fi

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
if ! grep -q 'scripts/adminHelper.sh' /etc/sudoers.d/webadmin 2>/dev/null; then
	echo "$www_user ALL=(ALL) NOPASSWD: /bin/sh scripts/adminHelper.sh *" >> /etc/sudoers.d/webadmin
	chmod 0440 /etc/sudoers.d/webadmin
fi

# Prevent writes to the webadmin's sus helper script
chown root:root /var/www/html/webadmin/scripts/susHelper.sh >> $logFile
chmod a-wr /var/www/html/webadmin/scripts/susHelper.sh >> $logFile
chmod u+rx /var/www/html/webadmin/scripts/susHelper.sh >> $logFile

# Allow the webadmin from webadmin to invoke the sus helper script
if ! grep -q 'scripts/susHelper.sh' /etc/sudoers.d/webadmin 2>/dev/null; then
	echo "$www_user ALL=(ALL) NOPASSWD: /bin/sh scripts/susHelper.sh *" >> /etc/sudoers.d/webadmin
	chmod 0440 /etc/sudoers.d/webadmin
fi

# Prevent writes to the webadmin's netboot helper script
chown root:root /var/www/html/webadmin/scripts/netbootHelper.sh >> $logFile
chmod a-wr /var/www/html/webadmin/scripts/netbootHelper.sh >> $logFile
chmod u+rx /var/www/html/webadmin/scripts/netbootHelper.sh >> $logFile

# Allow the webadmin from webadmin to invoke the netboot helper script
if ! grep -q 'scripts/netbootHelper.sh' /etc/sudoers.d/webadmin 2>/dev/null; then
	echo "$www_user ALL=(ALL) NOPASSWD: /bin/sh scripts/netbootHelper.sh *" >> /etc/sudoers.d/webadmin
	chmod 0440 /etc/sudoers.d/webadmin
fi

# Prevent writes to the webadmin's share helper script
chown root:root /var/www/html/webadmin/scripts/shareHelper.sh >> $logFile
chmod a-wr /var/www/html/webadmin/scripts/shareHelper.sh >> $logFile
chmod u+rx /var/www/html/webadmin/scripts/shareHelper.sh >> $logFile

# Allow the webadmin from webadmin to invoke the share helper script
if ! grep -q 'scripts/shareHelper.sh' /etc/sudoers.d/webadmin 2>/dev/null; then
	echo "$www_user ALL=(ALL) NOPASSWD: /bin/sh scripts/shareHelper.sh *" >> /etc/sudoers.d/webadmin
	chmod 0440 /etc/sudoers.d/webadmin
fi

# Prevent writes to the webadmin's LDAP helper script
chown root:root /var/www/html/webadmin/scripts/ldapHelper.sh >> $logFile
chmod a-wr /var/www/html/webadmin/scripts/ldapHelper.sh >> $logFile
chmod u+rx /var/www/html/webadmin/scripts/ldapHelper.sh >> $logFile

# Allow the webadmin from webadmin to invoke the LDAP helper script
if ! grep -q 'scripts/ldapHelper.sh' /etc/sudoers.d/webadmin 2>/dev/null; then
	echo "$www_user ALL=(ALL) NOPASSWD: /bin/sh scripts/ldapHelper.sh *" >> /etc/sudoers.d/webadmin
	chmod 0440 /etc/sudoers.d/webadmin
fi

# Disable directory listing for webadmin
if [ -f "/etc/apache2/apache2.conf" ]; then
	sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/' /etc/apache2/apache2.conf
fi
if [ -f "/etc/httpd/conf/httpd.conf" ]; then
	sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/' /etc/httpd/conf/httpd.conf
fi

# Enable apache on SSL, dav and dav_fs, only needed on Ubuntu
if [[ $(which a2enmod 2>&-) != "" ]]; then
	# Previous NetSUS versions created a file where it should be a symlink
	if [ ! -L /etc/apache2/mods-enabled/ssl.conf ]; then
		rm -f /etc/apache2/mods-enabled/ssl.conf
	fi
	sed -i 's/SSLProtocol all/SSLProtocol all -SSLv3/' /etc/apache2/mods-available/ssl.conf
	a2enmod ssl >> $logFile
	a2ensite default-ssl >> $logFile
	# a2enmod dav >> $logFile
	# a2enmod dav_fs >> $logFile
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

# Enable SSL for LDAP
if [ -f "/etc/ldap/ldap.conf" ]; then
	sed -i '/^TLS_REQCERT/d' /etc/ldap/ldap.conf
	sed -i '/TLS_CACERT/a TLS_REQCERT	allow' /etc/ldap/ldap.conf
fi
if [ -f "/etc/openldap/ldap.conf" ]; then
	sed -i '/^TLS_REQCERT/d' /etc/openldap/ldap.conf
	sed -i '/TLS_CACERTDIR/a TLS_REQCERT	allow' /etc/openldap/ldap.conf
fi

# Restart apache
log "Restarting apache..."
service $www_service restart >> $logFile 2>&1

log "OK"

log "Finished deploying the appliance web application"

exit 0
