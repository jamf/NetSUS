#!/bin/bash
# This script controls the flow of the LDAP Proxy installation

log "Starting LDAP Proxy Installation"

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
	export DEBIAN_FRONTEND=noninteractive
	echo -e " \
slapd    slapd/internal/generated_adminpw    password   netsuslp
slapd    slapd/password2    password    netsuslp
slapd    slapd/internal/adminpw    password netsuslp
slapd    slapd/password1    password    netsuslp
" | sudo debconf-set-selections
	apt_install slapd
	unset DEBIAN_FRONTEND
elif [[ $(which yum 2>&-) != "" ]]; then
	yum_install openldap-servers
	yum_install expect
fi

# Prepare the firewall in case it is enabled later
if [[ $(which ufw 2>&-) != "" ]]; then
	# LDAP
	ufw allow 389/tcp >> $logFile
elif [[ $(which firewall-cmd 2>&-) != "" ]]; then
	# LDAP
	firewall-cmd --zone=public --add-port=389/tcp >> $logFile 2>&1
	firewall-cmd --zone=public --add-port=389/tcp --permanent >> $logFile 2>&1
else
	# LDAP
	if iptables -L | grep DROP | grep -q 'tcp dpt:ldap' ; then
		iptables -D INPUT -p tcp --dport 389 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:ldap' ; then
		iptables -I INPUT -p tcp --dport 389 -j ACCEPT
	fi
	service iptables save >> $logFile 2>&1
fi

# Create appliance configuration directory
if [ ! -d "/var/appliance/conf" ]; then
	mkdir /var/appliance/conf
fi

# Configure slapd
if [ -d "/etc/ldap" ] && [ "$(getent passwd openldap)" != "" ]; then
	rm -rf /etc/ldap/slapd.d/ >> $logFile
	cp ./resources/slapd.conf /etc/ldap/slapd.conf >> $logFile
	cp ./resources/slapd.conf /var/appliance/conf/slapd.conf >> $logFile
	sed -i '/\/var\/appliance\/conf\//d' /etc/apparmor.d/usr.sbin.slapd
	sed -i -e '/<abstractions\/ssl_certs>/{:a;n;/^$/!ba;i\  \/var\/appliance\/conf\/ r,\n  \/var\/appliance\/conf\/* r,' -e '}' /etc/apparmor.d/usr.sbin.slapd
	sed -i "s/SLAPD_SERVICES=\"ldap:\/\/\/ ldapi:\/\/\/\"/SLAPD_SERVICES=\"ldap:\/\/\/ ldapi:\/\/\/ ldaps:\/\/\/\"/g" /etc/default/slapd
	cp /etc/ssl/certs/ssl-cert-snakeoil.pem /var/appliance/conf/appliance.chain.pem
	cp /etc/ssl/certs/ssl-cert-snakeoil.pem /var/appliance/conf/appliance.certificate.pem
	cp /etc/ssl/private/ssl-cert-snakeoil.key /var/appliance/conf/appliance.private.key
	chown openldap /var/appliance/conf/appliance.private.key
fi
if [ -d "/etc/openldap" ] && [ "$(getent passwd ldap)" != "" ]; then
	rm -rf /etc/openldap/slapd.d/ >> $logFile
	cp ./resources/slapdyum.conf /etc/openldap/slapd.conf >> $logFile
	cp ./resources/slapdyum.conf /var/appliance/conf/slapd.conf >> $logFile
	if [ -f "/etc/sysconfig/slapd" ]; then
		sed -i "s/SLAPD_URLS=\"ldapi:\/\/\/ ldap:\/\/\/\"/SLAPD_URLS=\"ldapi:\/\/\/ ldap:\/\/\/\ ldaps:\/\/\/\"/g" /etc/sysconfig/slapd
	fi
	cp /etc/pki/tls/certs/localhost.crt /var/appliance/conf/appliance.chain.pem
	cp /etc/pki/tls/certs/localhost.crt /var/appliance/conf/appliance.certificate.pem
	cp /etc/pki/tls/private/localhost.key /var/appliance/conf/appliance.private.key
	chown ldap /var/appliance/conf/appliance.private.key
	rm -rf /etc/openldap/certs/
	mkdir /etc/openldap/certs
	modutil -create -dbdir /etc/openldap/certs -force
	openssl pkcs12 -inkey /var/appliance/conf/appliance.private.key -in /var/appliance/conf/appliance.certificate.pem -export -out /tmp/openldap.p12 -nodes -name 'LDAP-Certificate' -password pass:
	certutil -A -d /etc/openldap/certs -n "CA Chain" -t CT,, -a -i /var/appliance/conf/appliance.chain.pem
	expect -c 'log_user 0; spawn pk12util -i /tmp/openldap.p12 -d /etc/openldap/certs -W ""; expect "Enter new password: "; send "netsuslp\r"; expect "Re-enter password: "; send "netsuslp\r"' 2>/dev/null
	rm -f /tmp/openldap.p12
	chown -R ldap:ldap /etc/openldap/certs/
fi

log "OK"

log "Finished deploying the LDAP Proxy"

exit 0