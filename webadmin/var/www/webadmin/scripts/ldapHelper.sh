#!/bin/bash

case $1 in

getldapproxystatus)
SERVICE=slapd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

disableproxy)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	update-rc.d slapd disable > /dev/null 2>&1
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig slapd off > /dev/null 2>&1
fi
service slapd stop 2>&-
;;

enableproxy)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	update-rc.d slapd enable > /dev/null 2>&1
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig slapd on > /dev/null 2>&1
fi
service slapd start 2>&-
;;

esac