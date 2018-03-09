#!/bin/bash

case $1 in

getnettype)
interface=$(ip addr show to 0.0.0.0/0 scope global | sed -e :a -e '$!N;s/\n[[:blank:]]/ /;ta' -e 'P;D' | awk -F ': ' '{print $2}')
if [ -f "/etc/network/interfaces" ]; then
	if [ "$(grep -i static /etc/network/interfaces)" != '' ]; then
		echo "static"
	else
		echo "dhcp"
	fi
else
	if [ "$(grep -i BOOTPROTO=none /etc/sysconfig/network-scripts/ifcfg-${interface})" != '' ]; then
		echo "static"
	else
		echo "dhcp"
	fi
fi
;;

getip)
echo $(ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub("/.*","","g",$2) }')
;;

getnetmask)
prefix=$(ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub(" ","","g",$2) }' | cut -d / -f 2)
complete=$((${prefix}/8))
partial=$((${prefix}%8))
for i in $(seq 0 3)
do
	if [ ${i} -lt ${complete} ]; then
		netmask=${netmask}255
	elif [ ${i} -eq ${complete} ]; then
		set 2 $((8-${partial})) 1
		while [ ${2} -gt 0 ]; do
			set ${1} $((${2}-1)) $((${1}*${3}))
		done
		netmask=${netmask}$((256-${3}))
	else
		netmask=${netmask}0
	fi
	if [ ${i} -lt 3 ]; then
		netmask=${netmask}.
	fi
done
echo ${netmask}
;;

getgateway)
echo $(ip route show to 0.0.0.0/0 | awk '/default / { print gensub("/.*","","g",$3) }')
;;

setip)
address=$2
netmask=$3
gateway=$4
dns1=$5
dns2=$6
interface=$(ip addr show to 0.0.0.0/0 scope global | sed -e :a -e '$!N;s/\n[[:blank:]]/ /;ta' -e 'P;D' | awk -F ': ' '{print $2}')
if [ -f "/etc/network/interfaces" ]; then
	echo "# Created by JSS Appliance Admin" > /etc/network/interfaces
	echo auto lo >> /etc/network/interfaces
	echo iface lo inet loopback >> /etc/network/interfaces
	echo auto $interface >> /etc/network/interfaces
	echo iface $interface inet static >> /etc/network/interfaces
	echo address $address >> /etc/network/interfaces
	echo netmask $netmask >> /etc/network/interfaces
	echo gateway $gateway >> /etc/network/interfaces
	echo dns-nameservers $dns1 $dns2 >> /etc/network/interfaces
	service networking restart 2>&-
else
	echo "# Created by JSS Appliance Admin" > /etc/sysconfig/network-scripts/ifcfg-$interface
	echo DEVICE=$interface >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo BOOTPROTO=none >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo ONBOOT=yes >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo NM_CONTROLLED=no >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo NETMASK=$netmask >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo IPADDR=$address >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo GATEWAY=$gateway >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo DEFROUTE=yes >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo IPV6INIT=no >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo DNS1=$dns1 >> /etc/sysconfig/network-scripts/ifcfg-$interface
	if [ "$dns2" != '' ]; then
		echo DNS2=$dns2 >> /etc/sysconfig/network-scripts/ifcfg-$interface
	fi
	service network restart 2>&-
fi
;;

setdhcp)
interface=$(ip addr show to 0.0.0.0/0 scope global | sed -e :a -e '$!N;s/\n[[:blank:]]/ /;ta' -e 'P;D' | awk -F ': ' '{print $2}')
if [ -f "/etc/network/interfaces" ]; then
	echo "# Created by JSS Appliance Admin" > /etc/network/interfaces
	echo auto lo >> /etc/network/interfaces
	echo iface lo inet loopback >> /etc/network/interfaces
	echo auto $interface >> /etc/network/interfaces
	echo iface $interface inet dhcp >> /etc/network/interfaces
	service networking restart 2>&-
else
	echo "# Created by JSS Appliance Admin" > /etc/sysconfig/network-scripts/ifcfg-$interface
	echo DEVICE=$interface >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo BOOTPROTO=dhcp >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo ONBOOT=yes >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo NM_CONTROLLED=no >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo DEFROUTE=yes >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo IPV6INIT=no >> /etc/sysconfig/network-scripts/ifcfg-$interface
	echo PEERDNS=yes >> /etc/sysconfig/network-scripts/ifcfg-$interface
	service network restart 2>&-
fi
;;

getdns)
unset nameservers
for i in $(cat /etc/resolv.conf | grep nameserver | cut -d ' ' -f 2); do
	if [ "$nameservers" = '' ]; then
		nameservers="$i"
	else
		nameservers="$nameservers $i";
	fi
done
echo $nameservers
;;

setdns)
dns1=$2
dns2=$3
echo "# Created by JSS Appliance Admin" > /etc/resolv.conf
echo nameserver $dns1 >> /etc/resolv.conf
if [ "$dns2" != '' ]; then
	echo nameserver $dns2 >> /etc/resolv.conf
fi
;;

#Get the local time
getlocaltime)
echo $(date)
;;

#Get the time server that is set
gettimeserver)
if [ -f "/etc/ntp/step-tickers" ]; then
	echo $(cat /etc/ntp/step-tickers 2>/dev/null | grep -v "^$" | grep -m 1 -v '#')
else
	echo $(cat /etc/cron.daily/ntpdate 2>/dev/null | awk '{print $2}')
fi
;;

#Set the time server
settimeserver)
newTimeServer=$2
if [ -f "/etc/ntp/step-tickers" ]; then
	currentTimeServer=$(cat /etc/ntp/step-tickers | grep -v "^$" | grep -m 1 -v '#')
	if [ "$currentTimeServer" != "$newTimeServer" ]; then
		echo "# List of NTP servers used by the ntpdate service." > /etc/ntp/step-tickers
		echo $newTimeServer >> /etc/ntp/step-tickers
	fi
else
	currentTimeServer=$(cat /etc/cron.daily/ntpdate 2>/dev/null | awk '{print $2}')
	if [ "$currentTimeServer" != "$newTimeServer" ]; then
		echo "server $newTimeServer" > /etc/cron.daily/ntpdate
	fi
fi
ntpdate $newTimeServer
;;

# *** Timezone retrieval done through PHP
# Set time zone
settz)
timezone=$2
if [ "$(which timedatectl 2>&-)" != '' ] && [ "$(which dpkg-reconfigure 2>&-)" != '' ]; then
	echo $(timedatectl set-timezone $timezone && dpkg-reconfigure --frontend noninteractive tzdata)
else
	echo ZONE=\"$timezone\" > /etc/sysconfig/clock && ln -sf /usr/share/zoneinfo/$timezone /etc/localtime
fi
;;

sethostname)
newname=$2
oldname=$(hostname)
sed -i "s/$oldname/$newname/g" /etc/hosts
if [ -f "/etc/hostname" ]; then
	echo $newname > /etc/hostname
fi
if [ -f "/etc/sysconfig/network" ]; then
	sed -i "s/$oldname/$newname/g" /etc/sysconfig/network
fi
hostname $newname
service avahi-daemon restart 2>&-
;;

# Admin services commands
#restartsmb)
#if [ "$(which update-rc.d 2>&-)" != '' ]; then
#	SERVICE=smbd
#elif [ "$(which chkconfig 2>&-)" != '' ]; then
#	SERVICE=smb
#fi
#service $SERVICE restart 2>&-
#;;

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

#restartafp)
#SERVICE=netatalk
#service $SERVICE restart 2>&-
#rm -rf /srv/NetBootClients/*
#;;

#getnbimages)
#result='<option value="">Nothing to Enable</option>'
#for item in $(ls /srv/NetBoot/NetBootSP0 2>/dev/null); do
#	if [ -f "/srv/NetBoot/NetBootSP0/$item/"*.dmg ] || [ -f "/srv/NetBoot/NetBootSP0/$item/"*.sparseimage ]; then
#		if [ -f "/srv/NetBoot/NetBootSP0/$item/i386/booter" ]; then
#			echo '<option value="'$item'">'$item'</option>'
#			unset result
#		fi
#	fi
#done
#if [ -n "$result" ]; then
#	echo "$result"
#fi
#;;

#Needs updating if we do multiple NetBoot images
setnbimages)
nbi=$2
if python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')" >/dev/null 2>&1; then
	index=$(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')['Index']" 2>&-)
	isinstall=$(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')['IsInstall']" 2>&-)
	kind=$(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')['Kind']" 2>&-)
	name=$(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')['Name']" 2>&-)
	rootpath=$(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')['RootPath']" 2>&-)
	type=$(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${nbi}/NBImageInfo.plist')['Type']" 2>&-)
fi
if [ "$index" = '' ]; then
	index=526
fi
if [ "$isinstall" = 'True' ]; then
	isinstall=8
else
	isinstall=0
fi
if [ "$kind" = '' ]; then
	kind=1
fi
if [ "$name" = '' ]; then
	name=$(basename $nbi .nbi)
	if [ "$name" = '' ]; then
		name='Faux NetBoot'
	fi
fi
if [ "$rootpath" = '' ]; then
	if [ -f "/srv/NetBoot/NetBootSP0/${nbi}/"*.dmg ]; then
		rootpath=$(basename "/srv/NetBoot/NetBootSP0/${nbi}/"*.dmg)
	elif [ -f "/srv/NetBoot/NetBootSP0/${nbi}/"*.sparseimage ]; then
		rootpath=$(basename "/srv/NetBoot/NetBootSP0/${nbi}/"*.sparseimage)
	else
		exit 1
	fi
fi
index_hex=$(printf "%x" ${index} | tr "[:lower:]" "[:upper:]")
while [ ${#index_hex} -lt 4 ]; do
	index_hex=0${index_hex}
done
boot_image_id="${isinstall}${kind}:00:$(echo ${index_hex} | cut -c 1,2):$(echo ${index_hex} | cut -c 3,4)"
length_hex=$(printf "%x" $((${#name}+5)) | tr "[:lower:]" "[:upper:]")
while [ ${#length_hex} -lt 2 ]; do
	length_hex=0${length_hex}
done
count_hex=$(printf "%x" ${#name} | tr "[:lower:]" "[:upper:]")
while [ ${#count_hex} -lt 2 ]; do
	count_hex=0${count_hex}
done
name_hex=$(echo ${name} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/:0A://g')
boot_image_list=${length_hex}:${boot_image_id}:${count_hex}:${name_hex}
cur_image_id=$(grep '04:02:FF:FF:07:04' /etc/dhcpd.conf | sed 's/.*04:02:FF:FF:07:04://g' | cut -c1-11)
root_path_ip=$(grep 'root-path' /etc/dhcpd.conf | grep -o '[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*')
sed -i "s/${cur_image_id}/${boot_image_id}/g" /etc/dhcpd.conf
sed -i "s/04:02:FF:FF:07:04:.*/04:02:FF:FF:07:04:${boot_image_id}:08:04:${boot_image_id}:09:${boot_image_list};/" /etc/dhcpd.conf
sed -i 's|filename ".*";|filename "'${nbi}'/i386/booter";|' /etc/dhcpd.conf
if [ "$type" = 'NFS' ]; then
	sed -i 's|option root-path.*|option root-path "nfs:'${root_path_ip}':/srv/NetBoot/NetBootSP0:'${nbi}'/'${rootpath}'";|' /etc/dhcpd.conf
else
	sed -i 's|option root-path.*|option root-path "http://'${root_path_ip}'/NetBoot/NetBootSP0/'${nbi}'/'${rootpath}'";|' /etc/dhcpd.conf
fi
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		# update-rc.d smbd enable > /dev/null 2>&1
		update-rc.d tftpd-hpa enable > /dev/null 2>&1
		# systemctl enable openbsd-inetd > /dev/null 2>&1
		systemctl enable nfs-server > /dev/null 2>&1
		service nfs-server start 2>&-
	else
		# rm -f /etc/init/smbd.override
		rm -f /etc/init/tftpd-hpa.override
		# update-rc.d openbsd-inetd enable > /dev/null 2>&1
		update-rc.d nfs-kernel-server enable > /dev/null 2>&1
		service nfs-kernel-server start 2>&-
	fi
	update-rc.d netatalk enable > /dev/null 2>&1
	# service smbd start 2>&-
	service tftpd-hpa start 2>&-
	# service openbsd-inetd start 2>&-
	cp -f /var/appliance/configurefornetboot /etc/network/if-up.d/configurefornetboot
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	# chkconfig smb on > /dev/null 2>&1
	chkconfig tftp on > /dev/null 2>&1
	chkconfig nfs on > /dev/null 2>&1
	chkconfig netatalk on > /dev/null 2>&1
	# service smb start 2>&-
	if [ "$(which systemctl 2>&-)" != '' ]; then
		service tftp start 2>&-
	else
		service xinetd restart 2>&-
	fi
	service nfs start 2>&-
	cp -f /var/appliance/configurefornetboot /sbin/ifup-local
fi
service netatalk start 2>&-
/var/appliance/configurefornetboot
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

disablenetboot)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		# update-rc.d smbd disable > /dev/null 2>&1
		update-rc.d tftpd-hpa disable > /dev/null 2>&1
		# systemctl disable openbsd-inetd > /dev/null 2>&1
		systemctl disable nfs-server > /dev/null 2>&1
		service nfs-server stop 2>&-
	else
		# echo manual > /etc/init/smbd.override
		echo manual > /etc/init/tftpd-hpa.override
		# update-rc.d openbsd-inetd disable > /dev/null 2>&1
		update-rc.d nfs-kernel-server disable > /dev/null 2>&1
		service nfs-kernel-server stop 2>&-
	fi
	update-rc.d netatalk disable > /dev/null 2>&1
	# service smbd stop 2>&-
	service tftpd-hpa stop 2>&-
	# service openbsd-inetd stop 2>&-
	rm -f /etc/network/if-up.d/configurefornetboot
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig netatalk off > /dev/null 2>&1
	# chkconfig smb off > /dev/null 2>&1
	chkconfig tftp off > /dev/null 2>&1
	chkconfig nfs off > /dev/null 2>&1
	# service smb stop 2>&-
	if [ "$(which systemctl 2>&-)" != '' ]; then
		service tftp stop 2>&-
	else
		service xinetd restart 2>&-
	fi
	service nfs stop 2>&-
	rm -f /sbin/ifup-local
fi
service netatalk stop 2>&-
killall dhcpd > /dev/null 2>&1
;;

getnetbootstatus)
SERVICE=dhcpd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

getldapproxystatus)
SERVICE=slapd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

touchconf)
conf="$2"
if [ "$(getent passwd www-data)" != '' ]; then
	www_user=www-data
elif [ "$(getent passwd apache)" != '' ]; then
	www_user=apache
fi
touch "$conf"
chown $www_user "$conf"
chmod u+w "$conf"
;;

resetsmbpw)
password=$2
echo smbuser:$password | chpasswd
(echo $password; echo $password) | smbpasswd -s -a smbuser
;;

#Needs updating if we host more than one Netboot Image
resetafppw)
password=$2
echo afpuser:$password | chpasswd
ip=$(ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub("/.*","","g",$2) }')
afppw=$(echo ${password} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/')
afppwlen=$(echo ${afppw} | sed 's/://g' | tr -d ' ' | wc -c)
afppwlen=$(expr ${afppwlen} / 2)
iphex=$(echo ${ip} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/')
num=$(echo ${iphex} | sed 's/://g' | wc -c)
num=$(expr ${num} / 2)
num=$(expr ${num} + 23)
num=$(expr ${num} + ${afppwlen})
lengthhex=$(awk -v dec=${num} 'BEGIN { n=split(dec,d,"."); for(i=1;i<=n;i++) printf ":%02X",d[i]; print "" }')
newafp=61:66:70:3A:2F:2F:61:66:70:75:73:65:72:3A:${afppw}
imageid=$(grep '04:02:FF:FF:07:04' /var/appliance/conf/dhcpd.conf | sed 's/.*04:02:FF:FF:07:04://g' | cut -c1-11)
sed -i "s/01:01:02:08:04:${imageid}:80:.*/01:01:02:08:04:${imageid}:80${lengthhex}:${newafp}:40:${iphex}:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /var/appliance/conf/dhcpd.conf
if [ -f "/etc/dhcpd.conf" ]; then
	imageid=$(grep '04:02:FF:FF:07:04' /etc/dhcpd.conf | sed 's/.*04:02:FF:FF:07:04://g' | cut -c1-11)
	sed -i "s/01:01:02:08:04:${imageid}:80:.*/01:01:02:08:04:${imageid}:80${lengthhex}:${newafp}:40:${iphex}:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /etc/dhcpd.conf
fi
killall dhcpd > /dev/null 2>&1
/usr/local/sbin/dhcpd > /dev/null 2>&1
;;

changeshelluser)
username=$2
password=$3
usermod -l $username $password
;;

changeshellpass)
username=$2
password=$3
echo $username:$password | chpasswd
;;

installslapdconf)
if [ -d "/etc/ldap" ]; then
	mv /etc/ldap/slapd.conf /etc/ldap/slapd.conf.bak
	mv /var/appliance/conf/slapd.conf.new /etc/ldap/slapd.conf
fi
if [ -d "/etc/openldap" ]; then
	mv /etc/openldap/slapd.conf /etc/openldap/slapd.conf.bak
	mv /var/appliance/conf/slapd.conf.new /etc/openldap/slapd.conf
fi
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
branches=$(/var/lib/reposado/repoutil --branches)
echo $branches
;;

createBranch)
branch=$2
cbranch=$(/var/lib/reposado/repoutil --new-branch $branch)
echo $cbranch
;;

deleteBranch)
branch=$2
dbranch=$(echo y | /var/lib/reposado/repoutil --delete-branch $branch)
echo $dbranch
;;

#listBranch)
#branch=$2
#/var/lib/reposado/repoutil --list-branch $branch
#;;

prodinfo)
id=$2
/var/lib/reposado/repoutil --info $id
;;

addtobranch)
product=$2
branch=$3
abranch=$(/var/lib/reposado/repoutil --add=$product $branch)
echo $abranch
;;

reposync)
/var/appliance/sus_sync.py > /dev/null 2>&1 &
;;

repopurge)
/var/lib/reposado/repoutil --purge-product=all-deprecated > /dev/null 2>&1 &
;;

removefrombranch)
product=$2
branch=$3
rbranch=$(/var/lib/reposado/repoutil --remove-product=$product $branch)
echo $rbranch
;;

setbaseurl)
baseurl=$2
sed -i '/LocalCatalogURLBase/{n;d}' /var/lib/reposado/preferences.plist
sed -i "/LocalCatalogURLBase/ a\
    <string>$baseurl<\/string>" /var/lib/reposado/preferences.plist
;;

#diskusage)
#if [ -e "/etc/system-release" ]; then
#	echo $(df -H / | awk '{print $3}' | sed 's/Used//g' | tr -d "\n")
#else
#	echo $(df -H --type=ext4 | awk '{print $4}' | sed 's/Avail//g' | tr -d "\n")
#fi
#;;

netbootusage)
echo $(du -h /srv/NetBoot/NetBootSP0/ | tail -1 | awk '{print $1}')
;;

shadowusage)
echo $(du -h /srv/NetBootClients/ | tail -1 | awk '{print $1}')
;;

#freemem)
#echo $(free -m | grep Mem | awk '{print $4}')
#;;

#sususage)
#du -h /srv/SUS | tail -1 | awk '{print $1}'
#;;

lastsussync)
echo $(ls -alt /srv/SUS/html/content/catalogs/*.sucatalog* 2>/dev/null | head -1 | awk '{print $6" "$7}')
;;

afpconns)
echo $(ss | grep afpovertcp | wc | awk '{print $1}')
;;

smbconns)
echo $(ss | grep microsoft-ds | wc | awk '{print $1}')
;;

getsyncstatus)
SERVICE=repo_sync
if ps ax | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
;;

getsussize)
echo $(du -h /srv/SUS/ | tail -1 | awk '{print $1}')
;;

numofbranches)
echo $(/var/lib/reposado/repoutil --branches | wc | awk '{print $1}')
;;

rootBranch)
branch=$2
catalogArray="/srv/SUS/html/content/catalogs/others/index*_${branch}.sucatalog"
for i in ${catalogArray}; do
	catalogName="$(basename "${i}" "_${branch}.sucatalog")"
	cp "/srv/SUS/html/content/catalogs/others/${catalogName}_${branch}.sucatalog" "/srv/SUS/html/${catalogName}.sucatalog"
done
cp "/srv/SUS/html/content/catalogs/index_${branch}.sucatalog" "/srv/SUS/html/index.sucatalog"
;;

addsch)
hour=$2
crontab -l > /tmp/mycron
sed -i '/sus_sync.py/d' /tmp/mycron
echo "00 $hour * * * /var/appliance/sus_sync.py > /dev/null 2>&1" >> /tmp/mycron
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
# $2: JSS URL
# $3: Allow untrusted SSL certificate
logFile="/usr/local/jds/logs/jdsinstaller.log"
if [ "$3" = 'True' ]; then
	result=$(/usr/local/sbin/jamfds createConf -k -url $2 2>&1)
else
	result=$(/usr/local/sbin/jamfds createConf -url $2 2>&1)
fi
if [ $? -ne 0 ]; then
	echo "$result" | sed -e 's/^error: //'
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Failed to create configuration file" >> $logFile
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Check /usr/local/jds/logs/jamf.log for more information" >> $logFile
else
	echo "Created configuration file for $2"
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Created configuration file for $2" >> $logFile
fi
;;

JSSenroll)
logFile="/usr/local/jds/logs/jdsinstaller.log"
if [ -d "/etc/apache2/sites-enabled" ]; then
	conf="/etc/apache2/sites-enabled/jds.conf"
	www_service=apache2
fi
if [ -d "/etc/httpd/conf.d" ]; then
	conf="/etc/httpd/conf.d/jds.conf"
	www_service=httpd
fi
echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Configuring site..." >> $logFile
echo "<VirtualHost *:443>" > $conf
echo "	SSLEngine on" >> $conf
echo "</VirtualHost>" >> $conf
echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Writing API RewriteRule..." >> $logFile
sed -i 's#<VirtualHost \*:443>#<VirtualHost \*:443>\n\tRewriteEngine on\n\tRewriteRule ^/jds/api/([0-9a-z/]*)$ /jds/api.php?call=$2 [QSA,NC]#' $conf
if [ -f "/etc/apache2/sites-enabled/jds.conf" ]; then
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Disabling Indexes on API..." >> $logFile
	sed -i 's#<VirtualHost \*:443>#<VirtualHost \*:443>\n\t<Directory /var/www/jds/>\n\t\tSSLVerifyClient require\n\t\tOptions None\n\t\tAllowOverride None\n\t</Directory>#' $conf
fi
if [ -f "/etc/httpd/conf.d/jds.conf" ]; then
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Disabling Indexes on API..." >> $logFile
	sed -i 's#<VirtualHost \*:443>#<VirtualHost \*:443>\n\t<Directory /var/www/html/jds/>\n\t\tSSLVerifyClient require\n\t\tOptions None\n\t\tAllowOverride None\n\t</Directory>#' $conf
fi
result=$(/usr/local/sbin/jamfds enroll -uri $2 -u $3 -p $4 2>&1)
if [ $? -ne 0 ]; then
	echo "$result" | sed -e 's/^error: //'
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Failed to enroll" >> $logFile
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Check /usr/local/jds/logs/jamf.log for more information" >> $logFile
	rm -f $conf
	exit
else
	echo "Enrolment complete"
	echo "$(date '+[%Y-%m-%d %H:%M:%S]:') Enrolment complete" >> $logFile
	/usr/local/sbin/jamfds policy > /dev/null 2>&1
fi
service $www_service reload > /dev/null 2>&1
;;

checkin)
/usr/local/sbin/jamfds policy > /dev/null 2>&1
;;

JSSinventory)
/usr/local/sbin/jamfds inventory > /dev/null 2>&1
;;

#enableAvahi)
#if [ "$(which apt-get 2>&-)" != '' ]; then
#	if [ "$(dpkg -s avahi-daemon 2>&- | awk '/Status: / {print $NF}')" != 'installed' ]; then
#		apt-get -q update
#		apt-get -qq -y install avahi-daemon
#	fi
#	if [ "$(which systemctl 2>&-)" != '' ]; then
#		systemctl enable avahi-daemon > /dev/null 2>&1
#	else
#		rm -f /etc/init/avahi-daemon.override
#	fi
#elif [ "$(which yum 2>&-)" != '' ]; then
#	chkconfig messagebus on > /dev/null 2>&1
#	service messagebus start 2>&-
#	chkconfig avahi-daemon on > /dev/null 2>&1
#fi
#service avahi-daemon start 2>&-
#;;

getSSHstatus)
SERVICE=ssh
if [ -e "/etc/system-release" ]; then
	SERVICE=sshd
fi
if service $SERVICE status 2>&- | grep -q running ; then
	echo "true"
else
	echo "false"
fi
;;

enableSSH)
if [ "$(which apt-get 2>&-)" != '' ]; then
	SERVICE=ssh
	if [ "$(dpkg -s openssh-server 2>&- | awk '/Status: / {print $NF}')" != 'installed' ]; then
		apt-get -q update
		apt-get -qq -y install openssh-server
	fi
	if [ "$(which systemctl 2>&-)" != '' ]; then
		update-rc.d $SERVICE enable > /dev/null 2>&1
	else
		rm -f /etc/init/$SERVICE.override
	fi
elif [ "$(which yum 2>&-)" != '' ]; then
	SERVICE=sshd
	if [ "$(rpm -qa openssh-server)" = '' ]; then
		yum install openssh-server -y -q
	fi
	chkconfig $SERVICE on > /dev/null 2>&1
fi
service $SERVICE start 2>&-
;;

disableSSH)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	SERVICE=ssh
	if [ "$(which systemctl 2>&-)" != '' ]; then
		update-rc.d $SERVICE disable > /dev/null 2>&1
	else
		echo manual > /etc/init/$SERVICE.override
	fi
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	SERVICE=sshd
	chkconfig $SERVICE off > /dev/null 2>&1
fi
service $SERVICE stop 2>&-
;;

getFirewallstatus)
if [ "$(which ufw 2>&-)" != '' ]; then
	if ufw status | grep -q inactive ; then
		echo "false"
	else
		echo "true"
	fi
elif [ "$(which firewalld 2>&-)" != '' ]; then
	if service firewalld status 2>&- | grep -q running ; then
		echo "true"
	else
		echo "false"
	fi
else
	if service iptables status 2>&- | grep -q running ; then
		echo "true"
	else
		echo "false"
	fi
fi
;;

updateCert)
if [ -f "/etc/ssl/certs/ssl-cert-snakeoil.pem" ]; then
	cp /var/appliance/conf/appliance.certificate.pem /etc/ssl/certs/ssl-cert-snakeoil.pem
	cp /var/appliance/conf/appliance.private.key /etc/ssl/private/ssl-cert-snakeoil.key
	mkdir -p /etc/apache2/ssl.crt/
	cp /var/appliance/conf/appliance.chain.pem /etc/apache2/ssl.crt/server-ca.crt
	chown openldap /var/appliance/conf/appliance.private.key
	sed -i --follow-symlinks "s/#SSLCertificateChainFile \/etc\/apache2\/ssl.crt\/server-ca.crt/SSLCertificateChainFile \/etc\/apache2\/ssl.crt\/server-ca.crt/g" /etc/apache2/sites-enabled/default-ssl.conf
fi
if [ -f "/etc/pki/tls/certs/localhost.crt" ]; then
	cp /var/appliance/conf/appliance.certificate.pem /etc/pki/tls/certs/localhost.crt
	cp /var/appliance/conf/appliance.private.key /etc/pki/tls/private/localhost.key
	cp /var/appliance/conf/appliance.chain.pem /etc/pki/tls/certs/server-chain.crt
	sed -i "s/#SSLCertificateChainFile \/etc\/pki\/tls\/certs\/server-chain.crt/SSLCertificateChainFile \/etc\/pki\/tls\/certs\/server-chain.crt/g" /etc/httpd/conf.d/ssl.conf
	chown ldap /var/appliance/conf/appliance.private.key
	rm -rf /etc/openldap/certs/
	mkdir /etc/openldap/certs
	modutil -create -dbdir /etc/openldap/certs -force
	openssl pkcs12 -inkey /var/appliance/conf/appliance.private.key -in /var/appliance/conf/appliance.certificate.pem -export -out /tmp/openldap.p12 -nodes -name 'LDAP-Certificate' -password pass:
	certutil -A -d /etc/openldap/certs -n "CA Chain" -t CT,, -a -i /var/appliance/conf/appliance.chain.pem
	expect -c 'log_user 0; spawn pk12util -i /tmp/openldap.p12 -d /etc/openldap/certs -W ""; expect "Enter new password: "; send "netsuslp\r"; expect "Re-enter password: "; send "netsuslp\r"'
	rm -f /tmp/openldap.p12
	chown -R ldap:ldap /etc/openldap/certs/
	service slapd restart 2>&-
fi
;;

enableFirewall)
if [ "$(which ufw 2>&-)" != '' ]; then
	ufw --force enable
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	if [ "$(which firewalld 2>&-)" != '' ]; then
		chkconfig firewalld on > /dev/null 2>&1
		service firewalld start 2>&-
	else
		chkconfig iptables on > /dev/null 2>&1
		service iptables start 2>&-
	fi
fi
;;

disableFirewall)
if [ "$(which ufw 2>&-)" != '' ]; then
	ufw disable
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	if [ "$(which firewalld 2>&-)" != '' ]; then
		chkconfig firewalld off > /dev/null 2>&1
		service firewalld stop 2>&-
	else
		chkconfig iptables off > /dev/null 2>&1
		service iptables stop 2>&-
	fi
fi
;;

restart)
reboot
;;

shutdown)
poweroff
;;

# New functions

enablegui)
sed -i 's:<webadmingui>.*</webadmingui>::' /var/appliance/conf/appliance.conf.xml
;;

# SUS
getsusproxyhost)
echo $(grep 'proxy =' /var/lib/reposado/preferences.plist | cut -d \" -f 2 | cut -d : -f 1)
;;

getsusproxyport)
echo $(grep 'proxy =' /var/lib/reposado/preferences.plist | cut -d \" -f 2 | cut -d : -f 2)
;;

getsusproxyuser)
echo $(grep 'proxy-user =' /var/lib/reposado/preferences.plist | cut -d \" -f 2 | cut -d : -f 1)
;;

getsusproxypass)
echo $(grep 'proxy-user =' /var/lib/reposado/preferences.plist | cut -d \" -f 2 | cut -d : -f 2)
;;

setsusproxy)
# $2: proxyhost
# $3: proxyport
# $4: proxyuser
# $5: proxypass
sed -i '/proxy =/d' /var/lib/reposado/preferences.plist
sed -i '/proxy-user =/d' /var/lib/reposado/preferences.plist
if [ "$(python -c "import plistlib; print plistlib.readPlist('/var/lib/reposado/preferences.plist')['AdditionalCurlOptions']" 2>/dev/null)" = '[]' ]; then
	python -c "import plistlib; p = plistlib.readPlist('/var/lib/reposado/preferences.plist'); del p['AdditionalCurlOptions']; plistlib.writePlist(p, '/var/lib/reposado/preferences.plist')"
fi
if [ "$3" != '' ]; then
	python /var/www/html/webadmin/scripts/susproxy.py "proxy = \"$2:$3\"" "/var/lib/reposado/preferences.plist"
fi
if [ "$5" != '' ]; then
	python /var/www/html/webadmin/scripts/susproxy.py "proxy-user = \"$4:$5\"" "/var/lib/reposado/preferences.plist"
fi
;;

# NetBoot
getNBIproperty)
# $2: NBI
# $3: Property
plistfile=$(ls "/srv/NetBoot/NetBootSP0/${2}/"*.plist 2>/dev/null)
if [ "$plistfile" != '' ]; then
	value=$(python -c "import plistlib; print plistlib.readPlist('${plistfile}')['${3}']" 2>/dev/null)
fi
echo "${value}"
;;

setNBIproperties)
Image=$2
Name="$(echo $3 | sed -e 's/\\//g')"
Description="$(echo $4 | sed -e 's/\\//g')"
Type=$5
Index=$6
SupportsDiskless=$7
if [ -f "/srv/NetBoot/NetBootSP0/${Image}/"*.dmg ]; then
	RootPath=$(basename "/srv/NetBoot/NetBootSP0/${Image}/"*.dmg)
elif [ -f "/srv/NetBoot/NetBootSP0/${Image}/"*.sparseimage ]; then
	RootPath=$(basename "/srv/NetBoot/NetBootSP0/${Image}/"*.sparseimage)
else
	exit 1
fi
python /var/www/html/webadmin/scripts/nbiproperties.py "/srv/NetBoot/NetBootSP0/${Image}/NBImageInfo.plist" "$RootPath" "$Name" "$Description" $Type $Index $SupportsDiskless
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

# Certificates
getSSLCertificate)
openssl x509 -text -noout -in /var/appliance/conf/appliance.certificate.pem | grep '\(Issuer:\|Subject:\|Not After\)' | sed -e 's/.*Subject:/Owner:/' | sed -e 's/.*Issuer:/Issuer:/' | sed -e 's/.*Not After :/Expires:/'
;;

createCsr)
cn="$2"
if [ "$3" != '' ]; then
	ou="$3"
else
	ou="."
fi
if [ "$4" != '' ]; then
	o="$4"
else
	o="."
fi
if [ "$5" != '' ]; then
	l="$5"
else
	l="."
fi
if [ "$6" != '' ]; then
	st="$6"
else
	st="."
fi
if [ "$7" != '' ]; then
	c="$7"
else
	c="."
fi
openssl genrsa -out /tmp/private.key 2048 > /dev/null 2>&1
(echo "$c"; echo "$st"; echo "$l"; echo "$o"; echo "$ou"; echo "$cn"; echo; echo; echo;) | openssl req -new -key /tmp/private.key -out /tmp/certreq.csr > /dev/null 2>&1
if [ "$(getent passwd www-data)" != '' ]; then
	www_user=www-data
elif [ "$(getent passwd apache)" != '' ]; then
	www_user=apache
fi
chown $www_user /tmp/private.key /tmp/certreq.csr
;;

# Storage
allowResize)
pvname=$(pvdisplay | grep 'PV Name' | awk '{print $NF}')
if ! file $pvname | grep -q "block special"; then
	echo "ERROR: $pvname is not a block device"
	exit
fi
lvname=$(lvdisplay | grep 'LV Path' | head -n 1 | grep -v swap | awk '{print $NF}')
lvdisplay $lvname > /dev/null
if [ $? -ne 0 ]; then
	echo "ERROR: $lvname is not a logical volume"
	exit
fi
device=$(echo $pvname | sed -e 's/[0-9]//g')
if ! fdisk -u -l $device | grep $device | tail -1 | grep $pvname | grep -q "Linux LVM"; then
	echo "ERROR: $pvname is not the last volume on $device"
	exit
fi
partition=$(echo $pvname | sed -e 's/[^0-9]//g')
if ! parted $device --script unit s print | grep -Pq "^\s$partition\s+.+?[^,]+?lvm\s*$"; then
	echo "ERROR: $pvname has additional flags set"
	exit
fi
total=$(parted $device --script unit B print | grep $device | awk '{print $NF}' | tr -d 'B')
end=$(parted $device --script unit B print | grep -P "^\s$partition\s+.+?[^,]+?lvm\s*$" | awk '{print $3}' | tr -d 'B')
free=$(($total-$end))
echo $(($free/1024/1024))
;;

resizeDisk)
pvname=$(pvdisplay | grep 'PV Name' | awk '{print $NF}')
echo "Resizing $pvname"
echo
if ! file $pvname | grep -q "block special"; then
	echo "$pvname is not a block device"
	exit 1
fi
lvname=$(lvdisplay | grep 'LV Path' | head -n 1 | grep -v swap | awk '{print $NF}')
lvdisplay $lvname > /dev/null
if [ $? -ne 0 ]; then
	echo "$lvname is not a logical volume"
	exit 1
fi
device=$(echo $pvname | sed -e 's/[0-9]//g')
if ! fdisk -u -l $device | grep $device | tail -1 | grep $pvname | grep -q "Linux LVM"; then
	echo "$pvname is not the last volume on $device"
	exit 1
fi
partition=$(echo $pvname | sed -e 's/[^0-9]//g')
if ! parted $device --script unit s print | grep -Pq "^\s$partition\s+.+?[^,]+?lvm\s*$"; then
	echo "$pvname has additional flags set"
	exit 1
fi
echo "Current partition layout of $device:"
echo
parted $device --script unit GB print
start=$(fdisk -u -l $device | grep $pvname | awk '{print $2}')
if parted $device --script unit s print | grep -qP "^\s$partition\s+.+?logical.+$"; then
	echo "Detected LVM residing on a logical partition"
	echo
	ext_partition=$(parted $device --script unit s print | grep extended | grep -Po '^\s\d\s' | tr -d ' ')
	ext_start=$(parted $device --script unit s print | grep extended | awk '{print $2}' | tr -d 's')
	parted $device --script rm $ext_partition >/dev/null 2>&1
	parted $device --script "mkpart extended ${ext_start}s -1s" >/dev/null 2>&1
	parted $device --script "set $ext_partition lba off" >/dev/null 2>&1
	parted $device --script "mkpart logical ext2 ${start}s -1s" >/dev/null 2>&1
else
	parted $device --script rm $partition >/dev/null 2>&1
	parted $device --script "mkpart primary ext2 ${start}s -1s" >/dev/null 2>&1
fi
parted $device --script set $partition lvm on
echo "New partition layout of $device:"
echo
parted $device --script unit GB print
echo "Creating script to resize the filesystem at next boot"
echo
echo '#!/bin/bash' > /root/resizefs.sh
if [ -f "/etc/rc.d/rc.local" ]; then
	rc_local=/etc/rc.d/rc.local
else
	rc_local=/etc/rc.local
fi
echo "
pvresize $pvname
lvextend --extents +100%FREE $lvname --resizefs
sed -ri 's/^#(exit 0)$/\1/' $rc_local
sed -i '/\/root\/resizefs.sh/d' $rc_local
rm -f \$0" >> /root/resizefs.sh
chmod +x /root/resizefs.sh
echo '/root/resizefs.sh' >> $rc_local
sed -ri 's/^(exit 0)$/#\1/' $rc_local
echo "A restart is required for changes to take effect"
;;

# Logs
displayLogList)
jdsLogList=$(find /usr/local/jds/logs -type f -exec file {} \; 2>/dev/null | grep '\(ASCII\|Unicode\) text' | awk -F : '{print $1}' | sort)
jssLogList=$(find /usr/local/jss/logs -type f -exec file {} \; 2>/dev/null | grep '\(ASCII\|Unicode\) text' | awk -F : '{print $1}' | sort)
tomcatLogList=$(find /usr/local/jss/tomcat/logs -type f -exec file {} \; 2>/dev/null | grep '\(ASCII\|Unicode\) text' | awk -F : '{print $1}' | sort)
applianceLogList=$(find /var/appliance/logs -type f -exec file {} \; 2>/dev/null | grep '\(ASCII\|Unicode\) text' | awk -F : '{print $1}' | sort)
varLogList=$(find /var/log \( \! -path /var/log/sudo-io/* \) -a -type f -exec file {} \; 2>/dev/null | grep '\(ASCII\|Unicode\) text' | awk -F : '{print $1}' | sort)
echo $jdsLogList $jssLogList $tomcatLogList $applianceLogList $varLogList
;;

displayLog)
log_path=$2
lines=$3
if [ "$3" != '' ]; then
	tail -n $lines $log_path
else
	cat $log_path
fi
;;

flushLogList)
jssLogList=$(find /usr/local/jss/logs -type f 2>/dev/null | sort)
tomcatLogList=$(find /usr/local/jss/tomcat/logs -type f 2>/dev/null | sort)
applianceLogList=$(find /var/appliance/logs -type f 2>/dev/null | sort)
varLogList=$(find /var/log \( \! -path /var/log/sudo-io/* \) -a -type f 2>/dev/null | sort)
echo $jssLogList $tomcatLogList $applianceLogList $varLogList
;;

flushLog)
log_path=$2
if [ "$log_path" != '' ]; then
	logFile=$(echo $2 | sed -e 's/.gz$//' | sed -e 's/[.0-9$]//g')
	if [ "$log_path" = "$logFile" ]; then
		cat /dev/null > $log_path
	else
		rm -f $log_path
	fi
fi
;;

# Services commands
getsmbstatus)
SERVICE=smbd
if [ -e "/etc/system-release" ]; then
	SERVICE=smb
fi
if service $SERVICE status 2>&- | grep -q running ; then
	echo "true"
else
	echo "false"
fi
;;

stopsmb)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	SERVICE=smbd
	if [ "$(which systemctl 2>&-)" != '' ]; then
		update-rc.d $SERVICE disable > /dev/null 2>&1
	else
		echo manual > /etc/init/$SERVICE.override
	fi
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	SERVICE=smb
	chkconfig $SERVICE off > /dev/null 2>&1
fi
service $SERVICE stop 2>&-
;;

getafpstatus)
SERVICE=afpd
if ps acx | grep -v grep | grep -q $SERVICE ; then
	echo "true"
else
	echo "false"
fi
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

# System Information
getName)
if [ -f "/etc/os-release" ]; then
	. /etc/os-release
elif [ -e "/etc/system-release" ]; then
	NAME=$(sed -e 's/ release.*//' /etc/system-release)
fi
echo "$NAME"
;;

getHomeUrl)
if [ -f "/etc/os-release" ]; then
	. /etc/os-release
	if [ "$NAME" = 'Ubuntu' ] && [ "$HOME_URL" = '' ]; then
		HOME_URL="http://www.ubuntu.com/"
	fi
elif [ -e "/etc/system-release" ]; then
	if [ "$(readlink /etc/system-release)" = 'redhat-release' ]; then
		HOME_URL="https://www.redhat.com/"
	fi
	if [ "$(readlink /etc/system-release)" = 'centos-release' ]; then
		HOME_URL="https://www.centos.org/"
	fi
fi
echo "$HOME_URL"
;;

getInstallType)
if [ "$(which apt-get 2>&-)" != '' ]; then
	echo "apt-get"
elif [ "$(which yum 2>&-)" != '' ]; then
	echo "yum"
fi
;;

getHttpsPort)
if [ -d "/etc/apache2/sites-enabled" ]; then
	PORT=$(grep _default_ /etc/apache2/sites-available/default-ssl.conf | cut -d : -f 2 | sed -e 's/[^0-9]//g')
fi
if [ -d "/etc/httpd/conf.d" ]; then
	PORT=$(grep _default_ /etc/httpd/conf.d/ssl.conf | cut -d : -f 2 | sed -e 's/[^0-9]//g')
fi
echo $PORT
;;

setHttpsPort)
# $2: PORT
if [ -d "/etc/apache2/sites-enabled" ]; then
	sed -i "/Listen 80$/! s/Listen.*/Listen $2/g" /etc/apache2/ports.conf
	sed -i --follow-symlinks "s/<VirtualHost _default_:.*>/<VirtualHost _default_:$2>/" /etc/apache2/sites-available/default-ssl.conf
fi
if [ -d "/etc/httpd/conf.d" ]; then
	sed -i "s/Listen.*https/Listen $2 https/" /etc/httpd/conf.d/ssl.conf
	sed -i "s/<VirtualHost _default_:.*>/<VirtualHost _default_:$2>/" /etc/httpd/conf.d/ssl.conf
fi
if [ "$(which ufw 2>&-)" != '' ]; then
	ufw allow $2/tcp
elif [ "$(which firewall-cmd 2>&-)" != '' ]; then
	firewall-cmd --zone=public --add-port=$2/tcp
	firewall-cmd --zone=public --add-port=$2/tcp --permanent
else
	service=$(grep -w $2/tcp /etc/services | awk '{print $1}')
	if [ "$service" = '' ]; then
		service=$2
	fi
	if iptables -L | grep DROP | grep -wq "tcp dpt:$service" ; then
		iptables -D INPUT -p tcp --dport $2 -j DROP
	fi
	if ! iptables -L | grep ACCEPT | grep -wq "tcp dpt:$service" ; then
		iptables -I INPUT -p tcp --dport $2 -j ACCEPT
	fi
	service iptables save
fi
;;

getPortsInUse)
echo $(lsof -i -P -n | grep LISTEN | awk -F : '{print $NF}' | awk '{print $1}' | sort -n -u)
;;

esac