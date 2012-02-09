#!/bin/sh 

message="\n
'''''''\n
^-0-0-^\n
+----oOO--------------------------------------+\n
| Welcome to the NetBoot/SUS Appliance.\n
| OS: Ubuntu 10.04 LTS\n
| To Login to the NetBoot/SUS Appliance:\n
| https://`hostname`.local/webadmin\n
| https://`ifconfig | grep "Ethernet" -A 1 | grep "inet addr" | awk '{ print $2 }' | sed s/addr://g`/webadmin\n 
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
dialog --no-shadow --timeout 20 --title "NetBoot/SUS Appliance" --msgbox "$message" 30 80
if [ "$?" = "0" ]; then
okpressed=1
chvt 1
fi
done