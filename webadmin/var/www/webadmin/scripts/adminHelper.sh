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
			if [ -n "$NAMESERVERLIST" ]; then
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

#Needs updating if we do multiple NetBoot images
setnbimages)
#AFP
ufw allow 548/tcp
#DHCP
ufw allow 67/udp
#TFTP
ufw allow 69/udp
for files in /srv/NetBoot/NetBootSP0/$2/*
do
dmgfile=`echo $files | grep dmg`
if [ "$dmgfile" != "" ]
then
finaldmg=`echo $dmgfile | sed "s/\/srv\/NetBoot\/NetBootSP0\/$2\///g"`
fi
done
sed -i "s/\/NetBoot\/NetBootSP0\/.*\";/\/NetBoot\/NetBootSP0\/$2\/$finaldmg\";/g" /etc/dhcpd.conf
sed -i "s/filename \".*\";/filename \"$2\/i386\/booter\";/g" /etc/dhcpd.conf
cp /var/appliance/configurefornetboot /etc/network/if-up.d/
/var/appliance/configurefornetboot
;;

disablenetboot)
#AFP
ufw deny 548/tcp
#DHCP
ufw deny 67/udp
#TFTP
ufw deny 69/udp
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


#Needs updating if we change image id or host more than one Netboot Image
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
imageid=`cat /var/appliance/conf/dhcpd.conf | grep "option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04" | sed 's/option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04://g' | sed 's/ //g' | sed 's/\t//g' | cut -c1-11`

newafp=61:66:70:3A:2F:2F:61:66:70:75:73:65:72:3A:$afppw

if [ -f "/etc/dhcpd.conf" ]; then
sed -i "s/01:01:02:08:04:$imageid:80:.*/01:01:02:08:04:$imageid:80$lengthhex:$newafp:40:$iphex:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /etc/dhcpd.conf
fi
sed -i "s/01:01:02:08:04:$imageid:80:.*/01:01:02:08:04:$imageid:80$lengthhex:$newafp:40:$iphex:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /var/appliance/conf/dhcpd.conf
killall dhcpd > /dev/null 2>&1
/usr/local/sbin/dhcpd > /dev/null 2>&1
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
/var/appliance/sus_sync.py > /dev/null 2>&1 &
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
echo `df -H --type=ext4 | awk '{print $4}' | sed 's/Avail//g' | tr -d "\n"`
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
echo `ls -lt /srv/SUS/html/content/catalogs/ /srv/SUS/html/content/catalogs/others/ | grep '\.sucatalog$' | head -1 | awk '{print $6" "$7}'`
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
cp "/srv/SUS/html/content/catalogs/others/index-mountainlion-lion-snowleopard-leopard.merged-1_$2.sucatalog" "/srv/SUS/html/index-mountainlion-lion-snowleopard-leopard.merged-1.sucatalog"
cp "/srv/SUS/html/content/catalogs/index_$2.sucatalog" "/srv/SUS/html/index.sucatalog"
;;
addsch)
crontab -l > /tmp/mycron
sed -i '/sus_sync.py/d' /tmp/mycron
echo "00 $2 * * * /var/appliance/sus_sync.py > /dev/null 2>&1" >> /tmp/mycron
crontab /tmp/mycron
rm /tmp/mycron
;;
delsch)
crontab -l > /tmp/mycron
sed -i '/sus_sync.py/d' /tmp/mycron
crontab /tmp/mycron
rm /tmp/mycron
;;
JSScreateConf)
if [ ! -d "/usr/local/sbin/" ]; then
mkdir /usr/local/sbin/ > /dev/null
fi
cd /usr/local/sbin/
curl -s $3 --connect-timeout 15 -L -o /usr/local/sbin/jamfds $2/bin/jamfds
echo $?
if [ -f "/usr/local/sbin/jamfds" ]; then
chmod +x /usr/local/sbin/jamfds
conf=`/usr/local/sbin/jamfds createConf $3 -url $2`
fi
;;
JSSenroll)
if [ -f "/usr/local/sbin/jamfds" ]; then
enroll=`/usr/local/sbin/jamfds enroll -username $2 -password $3 -uri $4`
echo $enroll
else
echo "jamf Binary not found"
fi
;;
checkin)
if [ -f "/usr/local/sbin/jamfds" ]; then
checkin=`/usr/local/sbin/jamfds policy`
echo $checkin
else
echo "jamf Binary not found"
fi
;;
JSSinventory)
inventory=`/usr/local/sbin/jamfds inventory`
echo $inventory
;;
enableAvahi)
apt-get -qq -y install avahi-daemon
;;
enableSSH)
apt-get -qq -y install openssh-server
ufw allow 22/tcp
;;
esac