#!/bin/bash

NAMESERVERLIST=""

case $1 in

getnettype) if [ -n "$(grep -i static /etc/network/interfaces)" ]; then echo "static"; else echo "dhcp"; fi;;
getip) echo `ifconfig eth0 | grep 'inet addr' | cut -d ':' -f 2 | cut -d ' ' -f 1`;;
getnetmask) echo `ifconfig eth0 | grep 'inet addr' | cut -d ':' -f 4 | cut -d ' ' -f 1`;;
getgateway) echo `ip route | grep -i default | cut -d ' ' -f 3`;;
setip) echo "# Created by JSS Appliance Admin" > /etc/network/interfaces
echo "auto lo" >> /etc/network/interfaces
echo "iface lo inet loopback" >> /etc/network/interfaces
echo "auto eth0" >> /etc/network/interfaces
echo "iface eth0 inet static" >> /etc/network/interfaces
echo "address $2" >> /etc/network/interfaces
echo "netmask $3" >> /etc/network/interfaces
echo "gateway $4" >> /etc/network/interfaces
/etc/init.d/networking restart
;;
setdhcp) echo "# Created by JSS Appliance Admin" > /etc/network/interfaces
echo "auto lo" >> /etc/network/interfaces
echo "iface lo inet loopback" >> /etc/network/interfaces
echo "auto eth0" >> /etc/network/interfaces
echo "iface eth0 inet dhcp" >> /etc/network/interfaces
/etc/init.d/networking restart
;;
getdns)
	for line in $(cat /etc/resolv.conf | grep nameserver | cut -d ' ' -f 2);
		do
			if [ -n $NAMESERVERLIST ]; then
				NAMESERVERLIST="$NAMESERVERLIST $line";
			else
				NAMESERVERLIST="$line"
		fi
	done
	echo $NAMESERVERLIST
	;;
setdns) echo "# Created by JSS Appliance Admin" > /etc/resolv.conf
echo "nameserver $2" >> /etc/resolv.conf
echo "nameserver $3" >> /etc/resolv.conf
;;

#Get the local time
getlocaltime) echo $(/bin/date);;

#Get the time server that is set
gettimeserver)
	if [ -f /etc/cron.daily/ntpdate ]; then
		echo $(cat /etc/cron.daily/ntpdate | awk '{print $2}')
	fi
	;;

#Set the time server
settimeserver)
	if [ -f /etc/cron.daily/ntpdate ]; then
		currentTimeServer=`cat /etc/cron.daily/ntpdate | awk '{print $2}'`
	fi
	if [ "$currentTimeServer" != $2 ]; then
		echo "server $2" > /etc/cron.daily/ntpdate
		/usr/sbin/ntpdate $2
	fi
	;;

# *** Timezone retrieval done through PHP
# Set time zone
settz) echo `echo $2 > /etc/timezone && dpkg-reconfigure --frontend noninteractive tzdata`;;
sethostname) 
	oldname=`hostname`
	sed -i "s/$oldname/$2/g" /etc/hosts
	/bin/hostname $2 && echo $2 > /etc/hostname && service avahi-daemon restart;;
# Admin services commands
restartsmb) /etc/init.d/smbd restart;;
restartafp)
/etc/init.d/netatalk restart
rm -rf /srv/NetBootClients/*
;;
getnbimages)
for file in /srv/NetBoot/NetBootSP0/*
do
   if [ -d $file ]; then
      nbis=$nbis" "`echo $file | sed s/"\/srv\/NetBoot\/NetBootSP0\/"//g`
   
   fi
done
if [ "$nbis" != "" ]; then
        for image in $nbis; do  
                echo "<option value=\"$image\">"$image"</option>"
        done
else
	echo "<option value="">"Nothing to Enable"</option>"
fi
;;
setnbimages)
killall dhcpd
for files in /srv/NetBoot/NetBootSP0/$2/*
do
dmgfile=`echo $files | grep dmg`
if [ "$dmgfile" != "" ]
then
finaldmg=`echo $dmgfile | sed "s/\/srv\/NetBoot\/NetBootSP0\/$2\///g"`
fi
done
sed -i "s/\/srv\/NetBoot\/NetBootSP0:.*\";/\/srv\/NetBoot\/NetBootSP0:$2\/$finaldmg\";/g" /etc/dhcpd.conf
sed -i "s/filename \".*\";/filename \"$2\/i386\/booter\";/g" /etc/dhcpd.conf
ip=`ifconfig | grep eth0 -A 1 | grep 'inet addr' | awk '{print $2}' | sed 's/addr://g'`
netmask=`ifconfig | grep eth0 -A 1 | grep 'inet addr' | awk '{print $4}' | sed 's/Mask://g'`
ipsub=`echo $ip | awk '{split($0,array,".")} END {print array[1], array[2], array[3]}' | sed 's/ /./g'`
ipdec=`awk -v dec=$ip 'BEGIN{n=split(dec,d,".");for(i=1;i<=n;i++) printf ":%02X",d[i];print ""}'`


curafp=`cat /etc/dhcpd.conf | grep "01:01:02:08:04:01:00:02:0E:80" | sed 's/option vendor-encapsulated-options 01:01:02:08:04:01:00:02:0E:80:.*:61:66:70:75:73:65:72:3A://g' | awk -F40 '{print $1}' | tr -d ' ' | sed 's/\(.*\)./\1/'`
afppw=`cat /etc/dhcpd.conf | grep "01:01:02:08:04:01:00:02:0E:80" | sed 's/option vendor-encapsulated-options 01:01:02:08:04:01:00:02:0E:80:.*:61:66:70:75:73:65:72:3A://g' | sed 's/://g' | awk -F40 '{print $1}' | tr -d ' ' | wc -c`
afppwlen=`expr $afppw "/" 2`

iphex=`echo $ip | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/'`
num=`echo $iphex | sed 's/://g' | wc -c`
num=`expr $num "/" 2`
num=`expr $num "+" 23`
num=`expr $num "+" $afppwlen`
lengthhex=`awk -v dec=$num 'BEGIN{n=split(dec,d,".");for(i=1;i<=n;i++) printf ":%02X",d[i];print ""}'`

sed -i "s/01:01:02:08:04:01:00:02:0E:80:.*/01:01:02:08:04:01:00:02:0E:80$lengthhex:61:66:70:3A:2F:2F:61:66:70:75:73:65:72:3A:$curafp:40:$iphex:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /etc/dhcpd.conf


sed -i "s/7, 12) = 08:04:01:00:02:0E:03:04.*)/7, 12) = 08:04:01:00:02:0E:03:04$ipdec)/g" /etc/dhcpd.conf
sed -i "s/7, 12) = 03:04.*:08:04:01:00:02:0E)/7, 12) = 03:04$ipdec:08:04:01:00:02:0E)/g" /etc/dhcpd.conf
sed -i "s/next-server.*;/next-server $ip;/g" /etc/dhcpd.conf
sed -i "s/nfs:.*:\/srv\/NetBoot\/NetBootSP0:/nfs:$ip:\/srv\/NetBoot\/NetBootSP0:/g" /etc/dhcpd.conf
cp /var/appliance/configurefornetboot /etc/network/if-up.d/
/usr/local/sbin/dhcpd
;;

disablenetboot)
rm /etc/network/if-up.d/configurefornetboot
killall dhcpd
;;

getnetbootstatus)
SERVICE='dhcpd'
 
if ps ax | grep -v grep | grep $SERVICE > /dev/null
then
    echo "true"
else
    echo "false"
fi
;;

touchconf)
touch "$2"
chown www-data "$2"
chmod u+w "$2"
;;

resetsmbpw)
echo smbuser:$2 | chpasswd
(echo $2; echo $2) | smbpasswd -s -a smbuser
;;

resetafppw)
echo afpuser:$2 | chpasswd

ip=`ifconfig | grep eth0 -A 1 | grep 'inet addr' | awk '{print $2}' | sed 's/addr://g'`
afppw=`echo $2 | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/'`
afppwlen=`echo $afppw | sed 's/://g' | tr -d ' ' | wc -c`
afppwlen=`expr $afppwlen "/" 2`

iphex=`echo $ip | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/'`
num=`echo $iphex | sed 's/://g' | wc -c`
num=`expr $num "/" 2`
num=`expr $num "+" 23`
num=`expr $num "+" $afppwlen`
lengthhex=`awk -v dec=$num 'BEGIN{n=split(dec,d,".");for(i=1;i<=n;i++) printf ":%02X",d[i];print ""}'`


newafp=61:66:70:3A:2F:2F:61:66:70:75:73:65:72:3A:$afppw
sed -i "s/01:01:02:08:04:01:00:02:0E:80:.*/01:01:02:08:04:01:00:02:0E:80$lengthhex:$newafp:40:$iphex:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /etc/dhcpd.conf
sed -i "s/01:01:02:08:04:01:00:02:0E:80:.*/01:01:02:08:04:01:00:02:0E:80$lengthhex:$newafp:40:$iphex:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /var/appliance/conf/dhcpd.conf
killall dhcpd
/usr/local/sbin/dhcpd
;;

changeshelluser)
usermod -l $2 $3
;;

changeshellpass)
usermod -p `mkpasswd $3` $2
;;

installdhcpdconf)
mv /etc/dhcpd.conf /etc/dhcpd.conf.bak
mv /var/appliance/conf/dhcpd.conf.new /etc/dhcpd.conf
;;
getSUSlist)
/var/lib/reposado/repoutil --products
/var/lib/reposado/repoutil --updates
;;
getBranchlist)
branches=`/var/lib/reposado/repoutil --branches`
echo $branches
;;
createBranch)
cbranch=`/var/lib/reposado/repoutil --new-branch $2`
echo $cbranch
;;
deleteBranch)
dbranch=`echo y | /var/lib/reposado/repoutil --delete-branch $2`
echo $dbranch
;;
listBranch)
/var/lib/reposado/repoutil --list-branch $2
;;
prodinfo)
/var/lib/reposado/repoutil --info $2
;;
addtobranch)
abranch=`/var/lib/reposado/repoutil --add=$2 $3`
echo $abranch
;;
reposync)
/var/appliance/sus_sync.py > /dev/null & 
;;
removefrombranch)
rbranch=`/var/lib/reposado/repoutil --remove-product=$2 $3`
echo $rbranch
;;
setbaseurl)
sed -i '/LocalCatalogURLBase/{n;d}' /var/lib/reposado/preferences.plist
sed -i "/LocalCatalogURLBase/ a\
    <string>$2<\/string>" /var/lib/reposado/preferences.plist
;;
diskusage)
echo `df --type=ext4 | awk '{print $4}' | sed 's/Available//g' | tr -d "\n"`
;;
netbootusage)
echo `du -h /srv/NetBoot/NetBootSP0/ | tail -1 | awk '{print $1}'`
;;
shadowusage)
echo `du -h /srv/NetBootClients/ | tail -1 | awk '{print $1}'`
;;
freemem)
echo `free -m | grep Mem | awk '{print $4}'`
;;
sususage)
du -h /srv/SUS | tail -1 | awk '{print $1}'
;;
lastsussync)
echo `ls -al /srv/SUS/html/content/catalogs/ | grep index.sucatalog | head -1 | awk '{print $6" "$7}'`
;;
afpconns)
echo `netstat | grep afpovertcp | wc | awk '{print $1}'`
;;
smbconns)
echo `netstat | grep microsoft-ds | wc | awk '{print $1}'`
;;
getsyncstatus)
SERVICE='repo_sync'
if ps ax | grep -v grep | grep $SERVICE > /dev/null
then
echo "true"
else
echo "false"
fi
;;
getsussize)
echo `du -h /srv/SUS/ | tail -1 | awk '{print $1}'`
;;
numofbranches)
echo `/var/lib/reposado/repoutil --branches | wc | awk '{print $1}'`
;;
rootBranch)
cp "/srv/SUS/html/content/catalogs/others/index-leopard.merged-1_$2.sucatalog" "/srv/SUS/html/index-leopard.merged-1.sucatalog"
cp "/srv/SUS/html/content/catalogs/others/index-lion-snowleopard-leopard.merged-1_$2.sucatalog" "/srv/SUS/html/index-lion-snowleopard-leopard.merged-1.sucatalog"
cp "/srv/SUS/html/content/catalogs/others/index-leopard-snowleopard.merged-1_$2.sucatalog" "/srv/SUS/html/index-leopard-snowleopard.merged-1.sucatalog"
cp "/srv/SUS/html/content/catalogs/index_$2.sucatalog" "/srv/SUS/html/index.sucatalog"
;;
addsch)
crontab -l > /tmp/mycron
sed -i '/sus_sync.py/d' /tmp/mycron
echo "00 $2 * * * /var/appliance/sus_sync.py" >> /tmp/mycron
crontab /tmp/mycron
rm /tmp/mycron
;;
delsch)
crontab -l > /tmp/mycron
sed -i '/sus_sync.py/d' /tmp/mycron
crontab /tmp/mycron
rm /tmp/mycron
;;
esac