#!/bin/bash
# This script controls the flow of the LDAP Proxy installation
pathToScript=$0
detectedOS=$1

# Logger
source logger.sh

logEvent "Starting LDAP Proxy Installation"
if [[ $detectedOS == 'Ubuntu' ]]; then
    apt-get -qq -y install slapd >> $logFile
fi

#if [[ $detectedOS == 'CentOS' ]] || [[ $detectedOS == 'RedHat' ]]; then
	#if ! rpm -qa "*db47*" | grep -q "db47" ; then
	#	yum install compat-db47 -y -q >> $logFile
	#fi

    #chkconfig netatalk on
    #service smb start

#fi

if [[ $detectedOS == 'Ubuntu' ]]; then
    rm -rf /etc/ldap/slapd.d/ >> $logFile
	cp -R ./etc/* /etc/
fi

cp -R ./var/* /var/

cp /etc/ssl/certs/ssl-cert-snakeoil.pem /var/appliance/conf/appliance.chain.pem
cp /etc/ssl/certs/ssl-cert-snakeoil.pem /var/appliance/conf/appliance.certificate.pem
cp /etc/ssl/private/ssl-cert-snakeoil.key /var/appliance/conf/appliance.private.key
chown openldap /var/appliance/conf/appliance.private.key


#if [ ! -d "/srv/NetBoot/NetBootSP0" ]; then
#    mkdir -p /srv/NetBoot/NetBootSP0
#fi




logEvent "OK"

logEvent "Finished deploying the LDAP Proxy"

exit 0
