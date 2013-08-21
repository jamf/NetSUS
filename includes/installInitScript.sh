#!/bin/bash

# Init Script Installer Function
installInitScript()
{
	if [[ `which update-rc.d 2>&-` != "" ]]; then
		update-rc.d $1 defaults
	elif [[ -f "/usr/sbin/update-rc.d" ]]; then
		/usr/sbin/update-rc.d $1 defaults
	elif [[ -f "/sbin/chkconfig" ]]; then
		/sbin/chkconfig --add --level 345 $1
	else
		logEvent "Error: Unable to find init.d script installer (update-rc.d or chkconfig)"
		exit 1
	fi
}
