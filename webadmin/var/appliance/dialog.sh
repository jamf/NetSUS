#!/bin/sh 

unset detectedOS
if [ -f "/usr/bin/lsb_release" ]; then
	ubuntuVersion=`lsb_release -s -d`

	case $ubuntuVersion in
		*"Ubuntu 14.04"*)
			detectedOS="Ubuntu"
			;;
		*"Ubuntu 12.04"*)
			detectedOS="Ubuntu"
			;;
		*"Ubuntu 10.04"*)
			detectedOS="Ubuntu"
			;;
	esac
fi

if [ -f "/etc/system-release" ] &&  [ -z "${detectedOS}" ]; then
	case "$(readlink /etc/system-release)" in
		"centos-release")
			detectedOS="CentOS"
			;;
		"redhat-release")
			detectedOS="RedHat"
			;;
	esac
fi

if [ "${detectedOS}" != 'Ubuntu' ] && [ "${detectedOS}" != 'RedHat' ] && [ "${detectedOS}" != 'CentOS' ]; then
	detectedOS='Unknown'
fi

message="\n
'''''''\n
^-0-0-^\n
+----oOO--------------------------------------+\n
| Welcome to the NetSUS Appliance.\n
| OS: ${detectedOS}\n
| To Login to the NetSUS Appliance:\n
| https://`ifconfig | grep "Ethernet" -A 1 | grep "inet addr" | awk '{ print $2 }' | sed s/addr://g`/webadmin\n 
| Username: webadmin\n
| Password: webadmin\n
+-----------oOO-------------------------------+\n
|--|--|\n
 || ||\n
ooO Ooo\n\n\n
For more information visit https://jamfnation.jamfsoftware.com/\n"
okpressed=0
while [ "$okpressed" != "1" ]; do
	dialog --no-shadow --timeout 20 --title "NetSUS Appliance" --msgbox "$message" 30 80
	if [ "$?" = "0" ]; then
		okpressed=1
		chvt 1
	fi
done