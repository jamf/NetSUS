#!/bin/bash

NAMESERVERLIST=""

if [ -f "/usr/bin/lsb_release" ]; then

ubuntuVersion=`lsb_release -s -d`

case $ubuntuVersion in
	*Ubuntu\ 14.04*)
	detectedOS="Ubuntu"
;;
	*Ubuntu\ 12.04*)
	detectedOS="Ubuntu"
;;
	*Ubuntu\ 10.04*)
	detectedOS="Ubuntu"
;;
esac

elif [ -f "/etc/system-release" ]; then

case "$(readlink /etc/system-release)" in
"centos-release")
    detectedOS="CentOS"    
    ;;
"redhat-release")
    detectedOS="RedHat"
    ;;
esac

else
	echo "Error detecting OS"
fi


case $1 in

getnettype) 
if [ "$detectedOS" = 'Ubuntu' ]; then
	if [ -n "$(grep -i static /etc/network/interfaces)" ]; then echo "static"; else echo "dhcp"; fi
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if [ -n "$(grep -i BOOTPROTO=none /etc/sysconfig/network-scripts/ifcfg-eth0)" ]; then echo "static"; else echo "dhcp"; fi

fi
;;
getip) echo `ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub("/.*","","g",$2) }'`;;
getnetmask) 
if [ -f "/usr/bin/ipcalc" ]; then
	echo `ipcalc -m $(ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub(" ","","g",$2) }') | cut -d = -f 2`
else
	echo `ifconfig eth0 | grep 'inet addr' | cut -d ':' -f 4 | cut -d ' ' -f 1`
fi
;;
getgateway) echo `ip route show to 0.0.0.0/0 | awk '/default / { print gensub("/.*","","g",$3) }'`;;
setip) 
if [ "$detectedOS" = 'Ubuntu' ]; then
	echo "# Created by JSS Appliance Admin" > /etc/network/interfaces
	echo "auto lo" >> /etc/network/interfaces
	echo "iface lo inet loopback" >> /etc/network/interfaces
	echo "auto eth0" >> /etc/network/interfaces
	echo "iface eth0 inet static" >> /etc/network/interfaces
	echo "address $2" >> /etc/network/interfaces
	echo "netmask $3" >> /etc/network/interfaces
	echo "gateway $4" >> /etc/network/interfaces
	service networking restart
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	UUID=`grep -i UUID= /etc/sysconfig/network-scripts/ifcfg-eth0`
	echo "# Created by JSS Appliance Admin" > /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "DEVICE=eth0" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "TYPE=Ethernet" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "$UUID" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "ONBOOT=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "NM_CONTROLLED=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "BOOTPROTO=none" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "IPADDR=$2" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "NETMASK=$3" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "GATEWAY=$4" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "DEFROUTE=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "IPV4_FAILURE_FATAL=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "IPV6INIT=no" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "NAME=\"System eth0\"" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	service network restart
fi
;;
setdhcp)
if [ "$detectedOS" = 'Ubuntu' ]; then
	echo "# Created by JSS Appliance Admin" > /etc/network/interfaces
	echo "auto lo" >> /etc/network/interfaces
	echo "iface lo inet loopback" >> /etc/network/interfaces
	echo "auto eth0" >> /etc/network/interfaces
	echo "iface eth0 inet dhcp" >> /etc/network/interfaces
	service networking restart
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	UUID=`grep -i UUID= /etc/sysconfig/network-scripts/ifcfg-eth0`
	echo "# Created by JSS Appliance Admin" > /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "DEVICE=eth0" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "TYPE=Ethernet" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "$UUID" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "ONBOOT=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "NM_CONTROLLED=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "BOOTPROTO=dhcp" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "DEFROUTE=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "PEERDNS=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "PEERROUTES=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "IPV4_FAILURE_FATAL=yes" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "IPV6INIT=no" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	echo "NAME=\"System eth0\"" >> /etc/sysconfig/network-scripts/ifcfg-eth0
	service network restart
fi
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
if [ "$detectedOS" = 'Ubuntu' ]; then
	if [ -f /etc/cron.daily/ntpdate ]; then
		echo $(cat /etc/cron.daily/ntpdate | awk '{print $2}')
	fi
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if [ -f /etc/ntp/step-tickers ]; then
		echo $(cat /etc/ntp/step-tickers | grep -m 1 -v '#')
	fi
fi
;;

#Set the time server
settimeserver)
if [ "$detectedOS" = 'Ubuntu' ]; then
	if [ -f /etc/cron.daily/ntpdate ]; then
		currentTimeServer=`cat /etc/cron.daily/ntpdate | awk '{print $2}'`
	fi
	if [ "$currentTimeServer" != $2 ]; then
		echo "server $2" > /etc/cron.daily/ntpdate
		/usr/sbin/ntpdate $2
	fi
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if [ -f /etc/ntp/step-tickers ]; then
		currentTimeServer=`cat /etc/ntp/step-tickers | grep -m 1 -v '#'`
	fi
	if [ "$currentTimeServer" != $2 ]; then
		echo "$2" > /etc/ntp/step-tickers
		/usr/sbin/ntpdate $2
	fi
fi
;;

# *** Timezone retrieval done through PHP
# Set time zone
settz)
if [ "$detectedOS" = 'Ubuntu' ]; then
	echo `echo $2 > /etc/timezone && dpkg-reconfigure --frontend noninteractive tzdata`
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	echo ZONE=\"$2\" > /etc/sysconfig/clock && ln -sf /usr/share/zoneinfo/$2 /etc/localtime
fi
;;
sethostname)
if [ "$detectedOS" = 'Ubuntu' ]; then
	oldname=`hostname`
	sed -i "s/$oldname/$2/g" /etc/hosts
	/bin/hostname $2 && echo $2 > /etc/hostname && service avahi-daemon restart
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	oldname=`hostname`
	sed -i "s/$oldname/$2/g" /etc/sysconfig/network
	/bin/hostname $2 && service avahi-daemon restart
fi
;;


# Admin services commands
restartsmb)
if [ "$detectedOS" = 'Ubuntu' ]; then
	service smbd restart
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	service smb restart
fi
;;

startsmb)
if [ "$detectedOS" = 'Ubuntu' ]; then
	service smbd start
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	service smb start
fi
;;
restartafp)
service netatalk restart
rm -rf /srv/NetBootClients/*
;;
getnbimages)
IFS=$'\n'
nbis=`ls /srv/NetBoot/NetBootSP0 2>/dev/null`
unset IFS
if [ ${#nbis[@]} -gt 0 ]; then
	i=0
	for item in ${nbis[@]}; do
		if [ ! -d "/srv/NetBoot/NetBootSP0/${item}" ]
		then
			unset nbis[i]
		fi
		let i++
	done
fi
if [ ${#nbis[@]} -gt 0 ]; then
	for image in ${nbis[@]}; do
		echo '<option value="'${image}'">'${image}'</option>'
	done
else
	echo '<option value="">Nothing to Enable</option>'
fi
;;

#Needs updating if we do multiple NetBoot images
setnbimages)
if [ "$detectedOS" = 'Ubuntu' ]; then
	#AFP
	ufw allow 548/tcp
	#DHCP
	ufw allow 67/udp
	#TFTP
	ufw allow 69/udp
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if [ -f "/usr/bin/firewall-cmd" ]; then
		firewall-cmd --zone=public --add-port=548/tcp
		firewall-cmd --zone=public --add-port=548/tcp --permanent
		firewall-cmd --zone=public --add-port=67/udp
		firewall-cmd --zone=public --add-port=67/udp --permanent
		firewall-cmd --zone=public --add-port=69/udp
		firewall-cmd --zone=public --add-port=69/udp --permanent
	else
		if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:afpovertcp' ; then
			iptables -I INPUT -p tcp --dport 548 -j ACCEPT
			iptables -D INPUT -p tcp --dport 548 -j DROP
		fi
		if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:bootps' ; then
			iptables -I INPUT -p udp --dport 67 -j ACCEPT
			iptables -D INPUT -p udp --dport 67 -j DROP
		fi
		if ! iptables -L | grep ACCEPT | grep -q 'udp dpt:tftp' ; then
			iptables -I INPUT -p udp --dport 69 -j ACCEPT
			iptables -D INPUT -p udp --dport 69 -j DROP
		fi
    	service iptables save
    fi
fi
dmgfile=`ls "/srv/NetBoot/NetBootSP0/${2}/"*.dmg 2>/dev/null`
if [ -n "${dmgfile}" ]; then
	finaldmg=`echo ${dmgfile} | sed "s:/srv/NetBoot/NetBootSP0/${2}/::g"`
else
	exit 1
fi
plistfile=`ls "/srv/NetBoot/NetBootSP0/${2}/"*.plist 2>/dev/null`
if [ -n "${plistfile}" ]; then
	finalplist=`echo ${plistfile} | sed "s:/srv/NetBoot/NetBootSP0/${2}/::g"`
fi
if python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${2}/${finalplist}')" &>/dev/null; then
	if [ $(python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${2}/${finalplist}')['IsInstall']") = "True" ]; then
		isinstall=8
	else
		isinstall=0
	fi
	chmod +w "/srv/NetBoot/NetBootSP0/${2}/${finalplist}"
	if [ "$3" != "" ]; then
        python /var/www/html/webadmin/scripts/netbootname.py "$3" "/srv/NetBoot/NetBootSP0/${2}/${finalplist}"
    else
        defaultname=$(basename "$2" .nbi)
        python /var/www/html/webadmin/scripts/netbootname.py "$defaultname" "/srv/NetBoot/NetBootSP0/${2}/${finalplist}"
    fi
	kind=`python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${2}/${finalplist}')['Kind']"`
	index=`python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${2}/${finalplist}')['Index']"`
	indexhex=`printf "%x" ${index} | tr "[:lower:]" "[:upper:]"`
	while [ ${#indexhex} -lt 4 ]; do
		indexhex=0${indexhex}
	done
	imageid="${isinstall}${kind}:00:$(echo ${indexhex} | cut -c 1,2):$(echo ${indexhex} | cut -c 3,4)"
	name=`python -c "import plistlib; print plistlib.readPlist('/srv/NetBoot/NetBootSP0/${2}/${finalplist}')['Name']"`
	listlenhex=`printf "%x" $((${#name}+5)) | tr "[:lower:]" "[:upper:]"`
	while [ ${#listlenhex} -lt 2 ]; do
		listlenhex=0${listlenhex}
	done
	namelenhex=`printf "%x" ${#name} | tr "[:lower:]" "[:upper:]"`
	while [ ${#namelenhex} -lt 2 ]; do
		namelenhex=0${namelenhex}
	done
	namehex=`echo ${name} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/:0A://g'`
else
	imageid="01:00:02:0E"
	listlenhex="11"
	namelenhex="0C"
	namehex="46:61:75:78:20:4E:65:74:42:6F:6F:74"
fi
curimageid=`grep 'option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04' /etc/dhcpd.conf | sed 's/option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04://g' | sed 's/ //g' | sed 's/\t//g' | cut -c1-11`
sed -i "s/${curimageid}/${imageid}/g" /etc/dhcpd.conf
sed -i "s/01:01:01:04:02:FF:FF:07:04:${imageid}:08:04:${imageid}:09:.*/01:01:01:04:02:FF:FF:07:04:${imageid}:08:04:${imageid}:09:${listlenhex}:${imageid}:${namelenhex}:${namehex};/" /etc/dhcpd.conf
sed -i "s:/NetBoot/NetBootSP0/.*\";:/NetBoot/NetBootSP0/${2}/${finaldmg}\";:g" /etc/dhcpd.conf
sed -i "s:filename \".*\";:filename \"${2}/i386/booter\";:g" /etc/dhcpd.conf
if [ "$detectedOS" = 'Ubuntu' ]; then
	cp -f /var/appliance/configurefornetboot /etc/network/if-up.d/configurefornetboot
	service tftpd-hpa start
	service smbd start
	service netatalk start
	service openbsd-inetd start
	rm -f /etc/init/netatalk.override
	rm -f /etc/init/smbd.override
	rm -f /etc/init/tftpd-hpa.override
	rm -f /etc/init/openbsd-inetd.override
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	cp -f /var/appliance/configurefornetboot /sbin/ifup-local
	chkconfig tftp on
	service xinetd restart
	service netatalk start
	service smb start
    chkconfig smb on
    chkconfig netatalk on
fi
/var/appliance/configurefornetboot
;;

disableproxy)
service slapd stop
if [ "$detectedOS" = 'Ubuntu' ]; then
	#LDAP
	ufw deny 389/tcp
	echo manual > /etc/init/slapd.override
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	chkconfig slapd off
	if [ -f "/usr/bin/firewall-cmd" ]; then
		firewall-cmd --zone=public --remove-port=389/tcp
		firewall-cmd --zone=public --remove-port=389/tcp --permanent
	else
		if ! iptables -L | grep DROP | grep -q 'tcp dpt:ldaps' ; then
			iptables -I INPUT -p tcp --dport 389 -j DROP
			iptables -D INPUT -p tcp --dport 389 -j ACCEPT
		fi
    	service iptables save
    fi

fi
;;

enableproxy)
service slapd start
if [ "$detectedOS" = 'Ubuntu' ]; then
	#LDAP
	rm -f /etc/init/slapd.override
	ufw allow 389/tcp
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	chkconfig slapd on
	if [ -f "/usr/bin/firewall-cmd" ]; then
		firewall-cmd --zone=public --add-port=389/tcp
		firewall-cmd --zone=public --add-port=389/tcp --permanent
	else
		if ! iptables -L | grep ACCEPT | grep -q 'tcp dpt:ldaps' ; then
			iptables -I INPUT -p tcp --dport 389 -j ACCEPT
			iptables -D INPUT -p tcp --dport 389 -j DROP
		fi
    	service iptables save
	fi
fi
;;

disablenetboot)
service netatalk stop
if [ "$detectedOS" = 'Ubuntu' ]; then
	#AFP
	ufw deny 548/tcp
	#DHCP
	ufw deny 67/udp
	#TFTP
	ufw deny 69/udp
	rm /etc/network/if-up.d/configurefornetboot
	service tftpd-hpa stop
	service smbd stop
	service netatalk stop
	service openbsd-inetd stop
	echo manual > /etc/init/netatalk.override
	echo manual > /etc/init/smbd.override
	echo manual > /etc/init/tftpd-hpa.override
	echo manual > /etc/init/openbsd-inetd.override
	rm -f /etc/network/if-up.d/configurefornetboot
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if [ -f "/usr/bin/firewall-cmd" ]; then
		firewall-cmd --zone=public --remove-port=548/tcp
		firewall-cmd --zone=public --remove-port=548/tcp --permanent
		firewall-cmd --zone=public --remove-port=67/udp
		firewall-cmd --zone=public --remove-port=67/udp --permanent
		firewall-cmd --zone=public --remove-port=69/udp
		firewall-cmd --zone=public --remove-port=69/udp --permanent
	else
		if ! iptables -L | grep DROP | grep -q 'tcp dpt:afpovertcp' ; then
			iptables -I INPUT -p tcp --dport 548 -j DROP
			iptables -D INPUT -p tcp --dport 548 -j ACCEPT
		fi
		if ! iptables -L | grep DROP | grep -q 'udp dpt:bootps' ; then
			iptables -I INPUT -p udp --dport 67 -j DROP
			iptables -D INPUT -p udp --dport 67 -j ACCEPT
		fi
		if ! iptables -L | grep DROP | grep -q 'udp dpt:tftp' ; then
			iptables -I INPUT -p udp --dport 69 -j DROP
			iptables -D INPUT -p udp --dport 67 -j ACCEPT
		fi
    	service iptables save
	fi
	service smb stop
    chkconfig tftp off
    service xinetd restart
    service netatalk stop
    chkconfig smb off
    chkconfig netatalk off
	rm -f /sbin/ifup-local
fi
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

getldapproxystatus)
SERVICE='slapd'

if ps ax | grep -v grep | grep $SERVICE > /dev/null
then
    echo "true"
else
    echo "false"
fi
;;

touchconf)
touch "$2"
if [ "$detectedOS" = 'Ubuntu' ]; then
	chown www-data "$2"
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	chown apache "$2"
fi
chmod u+w "$2"
;;

resetsmbpw)
echo smbuser:$2 | chpasswd
(echo $2; echo $2) | smbpasswd -s -a smbuser
;;


#Needs updating if we host more than one Netboot Image
resetafppw)
echo afpuser:$2 | chpasswd

ip=`ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub("/.*","","g",$2) }'`
afppw=`echo ${2} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/'`
afppwlen=`echo ${afppw} | sed 's/://g' | tr -d ' ' | wc -c`
afppwlen=`expr ${afppwlen} / 2`

iphex=`echo ${ip} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/'`
num=`echo ${iphex} | sed 's/://g' | wc -c`
num=`expr ${num} / 2`
num=`expr ${num} + 23`
num=`expr ${num} + ${afppwlen}`
lengthhex=`awk -v dec=${num} 'BEGIN { n=split(dec,d,"."); for(i=1;i<=n;i++) printf ":%02X",d[i]; print "" }'`

newafp=61:66:70:3A:2F:2F:61:66:70:75:73:65:72:3A:${afppw}

imageid=`grep 'option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04' /var/appliance/conf/dhcpd.conf | sed 's/option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04://g' | sed 's/ //g' | sed 's/\t//g' | cut -c1-11`

sed -i "s/01:01:02:08:04:${imageid}:80:.*/01:01:02:08:04:${imageid}:80${lengthhex}:${newafp}:40:${iphex}:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /var/appliance/conf/dhcpd.conf
if [ -f "/etc/dhcpd.conf" ]; then
	imageid=`grep 'option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04' /etc/dhcpd.conf | sed 's/option vendor-encapsulated-options 01:01:01:04:02:FF:FF:07:04://g' | sed 's/ //g' | sed 's/\t//g' | cut -c1-11`
	sed -i "s/01:01:02:08:04:${imageid}:80:.*/01:01:02:08:04:${imageid}:80${lengthhex}:${newafp}:40:${iphex}:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /etc/dhcpd.conf
fi
killall dhcpd > /dev/null 2>&1
/usr/local/sbin/dhcpd > /dev/null 2>&1
;;

changeshelluser)
usermod -l $2 $3
;;

changeshellpass)
echo $2:$3 | chpasswd
;;

installslapdconf)
if [ "$detectedOS" = 'Ubuntu' ]; then
	mv /etc/ldap/slapd.conf /etc/ldap/slapd.conf.bak
	mv /var/appliance/conf/slapd.conf.new /etc/ldap/slapd.conf
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
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
repopurge)
/var/lib/reposado/repoutil --purge-product=all-deprecated > /dev/null 2>&1 &
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
if [ "$detectedOS" = 'Ubuntu' ]; then
	echo `df -H --type=ext4 | awk '{print $4}' | sed 's/Avail//g' | tr -d "\n"`
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	echo `df -H / | awk '{print $3}' | sed 's/Used//g' | tr -d "\n"`
fi 
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
echo `ss | grep afpovertcp | wc | awk '{print $1}'`
;;
smbconns)
echo `ss | grep microsoft-ds | wc | awk '{print $1}'`
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
catalogArray="/srv/SUS/html/content/catalogs/others/index*_${2}.sucatalog"
for i in ${catalogArray}; do
catalogName="$(basename "${i}" "_${2}.sucatalog")"
cp "/srv/SUS/html/content/catalogs/others/${catalogName}_${2}.sucatalog" "/srv/SUS/html/${catalogName}.sucatalog"
done
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
if [ "$detectedOS" = 'Ubuntu' ]; then
	apt-get update
	apt-get -qq -y install avahi-daemon
fi
;;
getSSHstatus)
if [ "$detectedOS" = 'Ubuntu' ]; then
	if service ssh status 2>/dev/null | grep -q running ; then
		echo "true"
	else
		echo "false"
	fi
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if service sshd status 2>/dev/null | grep -q running ; then
		echo "true"
	else
		echo "false"
	fi
fi
;;
enableSSH)
if [ "$detectedOS" = 'Ubuntu' ]; then
	if ! dpkg --list | grep -q 'openssh-server'; then
		apt-get update
		apt-get -qq -y install openssh-server
	fi
	if [ -e /etc/init/ssh.override ];then
		rm -f /etc/init/ssh.override
	fi
	service ssh start
	ufw allow 22/tcp
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if ! rpm -qa "openssh-server" | grep -q "openssh-server" ; then
		yum -y install openssh-server
	fi
	chkconfig sshd on
	service sshd start
fi
;;
disableSSH)
if [ "$detectedOS" = 'Ubuntu' ]; then
	echo manual > /etc/init/ssh.override
	service ssh stop
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	chkconfig sshd off
	service sshd stop
fi
;;

getFirewallstatus)
if [ "$detectedOS" = 'Ubuntu' ]; then
        if ufw status | grep inactive ; then
                echo "false"
        else
                echo "true"
        fi
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	if service iptables status 2>/dev/null | grep -q running ; then
		echo "true"
	else
		echo "false"
	fi
fi
;;
updateCert)
if [ "$detectedOS" = 'Ubuntu' ]; then
	cp /var/appliance/conf/appliance.certificate.pem /etc/ssl/certs/ssl-cert-snakeoil.pem
	cp /var/appliance/conf/appliance.private.key /etc/ssl/private/ssl-cert-snakeoil.key
	mkdir -p /etc/apache2/ssl.crt/	
	cp /var/appliance/conf/appliance.chain.pem /etc/apache2/ssl.crt/server-ca.crt
	chown openldap /var/appliance/conf/appliance.private.key
	sed -i "s/#SSLCertificateChainFile \/etc\/apache2\/ssl.crt\/server-ca.crt/SSLCertificateChainFile \/etc\/apache2\/ssl.crt\/server-ca.crt/g" /etc/apache2/sites-enabled/default-ssl.conf
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	cp /var/appliance/conf/appliance.certificate.pem /etc/pki/tls/certs/localhost.crt
	cp /var/appliance/conf/appliance.private.key /etc/pki/tls/private/localhost.key	
	cp /var/appliance/conf/appliance.chain.pem /etc/pki/tls/certs/server-chain.crt
	chown ldap /var/appliance/conf/appliance.private.key
	sed -i "s/#SSLCertificateChainFile \/etc\/pki\/tls\/certs\/server-chain.crt/SSLCertificateChainFile \/etc\/pki\/tls\/certs\/server-chain.crt/g" /etc/httpd/conf.d/ssl.conf
	rm -rf /etc/openldap/certs/
	mkdir /etc/openldap/certs
	modutil -create -dbdir /etc/openldap/certs -force
	openssl pkcs12 -inkey /var/appliance/conf/appliance.private.key -in /var/appliance/conf/appliance.certificate.pem -export -out /tmp/openldap.p12 -nodes -name 'LDAP-Certificate' -password pass:
	certutil -A -d /etc/openldap/certs -n "CA Chain" -t CT,, -a -i /var/appliance/conf/appliance.chain.pem
	pk12util -i /tmp/openldap.p12 -d /etc/openldap/certs -W ""
	rm /tmp/openldap.p12
	chown -R ldap:ldap /etc/openldap/certs/
	service slapd restart
fi
;;
enableFirewall)
if [ "$detectedOS" = 'Ubuntu' ]; then
	ufw --force enable
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	chkconfig iptables on
	service iptables start
fi
;;
disableFirewall)
if [ "$detectedOS" = 'Ubuntu' ]; then
	ufw disable
fi
if [ "$detectedOS" = 'CentOS' ] || [ "$detectedOS" = 'RedHat' ]; then
	chkconfig iptables off
	service iptables stop
fi
;;
restart)
reboot
;;
shutdown)
poweroff
;;
esac