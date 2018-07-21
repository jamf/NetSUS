#!/bin/bash

case $1 in

getdhcpstatus)
SERVICE=dhcpd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

gettftpstatus)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if service tftpd-hpa status 2>/dev/null | grep -q running ; then
		echo "true"
	else
		echo "false"
	fi
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		if systemctl status tftp | grep -q running ; then
			echo "true"
		else
			echo "false"
		fi
	else
		if service xinetd status | grep -q running && chkconfig | sed 's/[ \t]//g' | grep tftp | grep -q ':on' ; then
			echo "true"
		else
			echo "false"
		fi
	fi
fi
;;

getnfsstatus)
SERVICE=nfsd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

getafpstatus)
SERVICE=afpd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

startsmb)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	SERVICE=smbd
	if [ "$(which systemctl 2>&-)" != '' ]; then
		update-rc.d $SERVICE enable > /dev/null 2>&1
	else
		rm -f /etc/init/$SERVICE.override
	fi
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	SERVICE=smb
	chkconfig $SERVICE on > /dev/null 2>&1
fi
service $SERVICE start 2>&-
;;

startafp)
SERVICE=netatalk
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	update-rc.d $SERVICE enable > /dev/null 2>&1
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig $SERVICE on > /dev/null 2>&1
fi
service $SERVICE start 2>&-
;;

stopafp)
SERVICE=netatalk
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	update-rc.d $SERVICE disable > /dev/null 2>&1
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig $SERVICE off > /dev/null 2>&1
fi
service $SERVICE stop 2>&-
rm -rf /srv/NetBootClients/*
;;

esac