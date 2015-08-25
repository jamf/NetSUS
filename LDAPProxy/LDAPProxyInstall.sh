#!/bin/bash
# This script controls the flow of the LDAP Proxy installation
pathToScript=$0
detectedOS=$1

# Logger
source logger.sh

logEvent "Starting LDAP Proxy Installation"
if [[ $detectedOS == 'Ubuntu' ]]; then
	export DEBIAN_FRONTEND=noninteractive
	echo -e " \
slapd    slapd/internal/generated_adminpw    password   netsuslp
slapd    slapd/password2    password    netsuslp
slapd    slapd/internal/adminpw    password netsuslp
slapd    slapd/password1    password    netsuslp
" | sudo debconf-set-selections
    apt-get -qq -y install slapd >> $logFile
    export DEBIAN_FRONTEND=
fi

if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	if ! rpm -qa "*openldap-servers*" | grep -q "openldap-servers" ; then
		yum install openldap-servers -y -q >> $logFile
	fi
fi

if [[ $detectedOS == 'Ubuntu' ]]; then
    rm -rf /etc/ldap/slapd.d/ >> $logFile
	cp -R ./etc/* /etc/
	sed -i "s/SLAPD_SERVICES=\"ldap:\/\/\/ ldapi:\/\/\/\"/SLAPD_SERVICES=\"ldap:\/\/\/ ldapi:\/\/\/ ldaps:\/\/\/\"/g" /etc/default/slapd
fi

if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	rm -rf /etc/openldap/slapd.d/ >> $logFile
	cp -R ./etc/ldap/slapdyum.conf /etc/openldap/slapd.conf
	sed -i "s/SLAPD_URLS=\"ldapi:\/\/\/ ldap:\/\/\/\"/SLAPD_URLS=\"ldapi:\/\/\/ ldap:\/\/\/\ ldaps:\/\/\/\"/g" /etc/sysconfig/slapd
fi

cp -R ./var/* /var/

if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	rm /var/appliance/conf/slapd.conf
	mv /var/appliance/conf/slapdyum.conf /var/appliance/conf/slapd.conf
else
	rm /var/appliance/conf/slapdyum.conf
fi


if [[ $detectedOS == 'Ubuntu' ]]; then
	cp /etc/ssl/certs/ssl-cert-snakeoil.pem /var/appliance/conf/appliance.chain.pem
	cp /etc/ssl/certs/ssl-cert-snakeoil.pem /var/appliance/conf/appliance.certificate.pem
	cp /etc/ssl/private/ssl-cert-snakeoil.key /var/appliance/conf/appliance.private.key
	chown openldap /var/appliance/conf/appliance.private.key
fi
if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	cp /etc/pki/tls/certs/server-chain.crt /var/appliance/conf/appliance.chain.pem
	cp /etc/pki/tls/certs/localhost.crt /var/appliance/conf/appliance.certificate.pem
	cp /etc/pki/tls/private/localhost.key /var/appliance/conf/appliance.private.key
	chown ldap /var/appliance/conf/appliance.private.key
	rm -rf /etc/openldap/certs/
	mkdir /etc/openldap/certs
	modutil -create -dbdir /etc/openldap/certs -force
	openssl pkcs12 -inkey /var/appliance/conf/appliance.private.key -in /var/appliance/conf/appliance.certificate.pem -export -out /tmp/openldap.p12 -nodes -name 'LDAP-Certificate' -password pass:
	certutil -A -d /etc/openldap/certs -n "CA Chain" -t CT,, -a -i /var/appliance/conf/appliance.chain.pem
	pk12util -i /tmp/openldap.p12 -d /etc/openldap/certs -W ""
	rm /tmp/openldap.p12
	chown -R ldap:ldap /etc/openldap/certs/
fi






logEvent "OK"

logEvent "Finished deploying the LDAP Proxy"

exit 0
