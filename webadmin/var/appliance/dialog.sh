#!/bin/sh 

detectedOS=`lsb_release -s -d 2>/dev/null | sed -e 's/"//g'`
if [ -z "$detectedOS" ]
then
	detectedOS=`cat /etc/system-release`
fi

ip=`ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub("/.*","","g",$2) }'`
if [ -z "$ip" ]
then
	ip="0.0.0.0"
fi

message="\n
'''''''\n
^-0-0-^\n
+----oOO--------------------------------------+\n
| Welcome to the NetSUS Appliance.\n
| OS: $detectedOS\n
| To Login to the NetSUS Appliance:\n
| https://$ip/webadmin\n 
| Username: webadmin\n
| Password: webadmin\n
+-----------oOO-------------------------------+\n
|--|--|\n
 || ||\n
ooO Ooo\n\n\n
For more information visit https://jamfnation.jamfsoftware.com/\n"
okpressed=0
while [ "$okpressed" != "1" ]
do
dialog --no-shadow --timeout 20 --title "NetSUS Appliance" --msgbox "$message" 30 80
if [ "$?" = "0" ]; then
okpressed=1
chvt 1
fi
done