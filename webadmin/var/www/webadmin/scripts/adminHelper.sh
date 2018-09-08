#!/bin/bash

case $1 in

getifaces)
echo $(ip link | sed -e :a -e '$!N;s/\n[[:blank:]]/ /;ta' -e 'P;D' | grep -w 'BROADCAST' | awk -F ': ' '{print $2}')
;;

getstate)
# $2: interface
echo $(ip link show ${2} | grep BROADCAST | grep -o 'state.*' | awk '{print $2}')
;;

gethwaddr)
# $2: interface
echo $(ip link show ${2} | grep -iw ether | awk '{print $2}' | tr '[[:upper:]]' '[[:lower:]]')
;;

getmethod)
# $2: interface
if [ -f "/etc/netplan/01-netcfg.yaml" ]; then
	dhcp4=$(sed -n "/^    ${2}:/,/^    [a-z]/p" /etc/netplan/01-netcfg.yaml | grep 'dhcp4:' | awk '{print $NF}')
	if [ "${dhcp4}" = 'yes' ] || [ "${dhcp4}" = 'true' ]; then
		echo dhcp
	else
		echo static
	fi
elif [ -f "/etc/network/interfaces" ]; then
	echo $(grep -wi "^iface.*${2}.*inet" /etc/network/interfaces | awk '{print $NF}')
elif [ -f "/etc/sysconfig/network-scripts/ifcfg-${2}" ]; then
	echo $(grep -i "^BOOTPROTO" /etc/sysconfig/network-scripts/ifcfg-${2} | sed -e 's/[^a-z]//g')
fi
;;

getipaddr)
# $2: interface
echo $(ip addr show ${2} | grep -w inet | awk '{print $2}' | cut -d / -f 1)
;;

getmask)
# $2: interface
prefix=$(ip addr show ${2} | grep -w inet | awk '{print $2}' | cut -d / -f 2)
if [ "${prefix}" != '' ]; then
	complete=$((${prefix}/8))
	partial=$((${prefix}%8))
	for i in $(seq 0 3); do
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
fi
echo ${netmask}
;;

getgateway)
# $2: interface
if [ -f "/etc/netplan/01-netcfg.yaml" ]; then
	dhcp4=$(sed -n "/^    ${2}:/,/^    [a-z]/p" /etc/netplan/01-netcfg.yaml | grep 'dhcp4:' | awk '{print $NF}')
	if [ "${dhcp4}" = 'yes' ] || [ "${dhcp4}" = 'true' ]; then
		gateway=$(netplan ip leases ${2} | grep ROUTER | cut -d = -f 2)
	else
		gateway=$(sed -n "/^    ${2}:/,/^    [a-z]/p" /etc/netplan/01-netcfg.yaml | grep 'gateway4:' | awk '{print $NF}')
	fi
elif [ -f "/etc/network/interfaces" ]; then
	gateway=$(sed -n "/^iface ${2}/,/^iface/p" /etc/network/interfaces | grep gateway | awk '{print $NF}')
	if [ "${gateway}" = '' ]; then
		gateway=$(grep 'routers' /var/lib/dhcp/dhclient.${2}.leases | tail -1 | awk '{print $NF}' | sed -e 's/;//g')
	fi		
elif [ -f "/etc/sysconfig/network-scripts/ifcfg-${2}" ]; then
	gateway=$(grep -i "^GATEWAY" /etc/sysconfig/network-scripts/ifcfg-${2} | cut -d = -f 2)
	if [ "${gateway}" = '' ]; then
		gateway=$(grep 'routers' $(ls -1 -t /var/lib/dhclient/dhclient*${2}*.lease* | head -1) | tail -1 | awk '{print $NF}' | sed -e 's/;//g')
	fi
fi
echo ${gateway}
;;

getnameservers)
if [ -f "/etc/netplan/01-netcfg.yaml" ]; then
	dhcp4=$(sed -n "/^    ${2}:/,/^    [a-z]/p" /etc/netplan/01-netcfg.yaml | grep 'dhcp4:' | awk '{print $NF}')
	if [ "${dhcp4}" = 'yes' ] || [ "${dhcp4}" = 'true' ]; then
		nameservers=$(netplan ip leases ${2} | grep DNS | cut -d = -f 2| sed -e 's/,/ /g')
	else
		nameservers=$(sed -n "/^    ${2}:/,/^    [a-z]/p" /etc/netplan/01-netcfg.yaml | sed -e "1,/nameservers:/d" | grep 'addresses:' | sed -e 's/.*\[//' -e 's/\].*//' -e 's/,/ /g')
	fi
elif [ -f "/etc/network/interfaces" ]; then
	nameservers=$(sed -n "/^iface ${2}/,/^iface/p" /etc/network/interfaces | grep nameservers | awk -F 'nameservers' '{print $NF}')
	if [ "${nameservers}" = '' ]; then
		nameservers=$(grep 'domain-name-servers' /var/lib/dhcp/dhclient.${2}.leases | tail -1 | awk -F 'servers' '{print $NF}' | sed -e 's/[;,]/ /g')
	fi		
elif [ -f "/etc/sysconfig/network-scripts/ifcfg-${2}" ]; then
	nameservers=$(grep -i "^DNS" /etc/sysconfig/network-scripts/ifcfg-${2} | cut -d = -f 2)
	if [ "${nameservers}" = '' ]; then
		nameservers=$(grep 'domain-name-servers' $(ls -1 -t /var/lib/dhclient/dhclient*${2}*.lease* | head -1) | tail -1 | awk -F 'servers' '{print $NF}' | sed -e 's/[;,]/ /g')
	fi
fi
echo ${nameservers}
;;

setiface)
# $2: interface
# $3: method
# $4: address
# $5: netmask
# $6: gateway
# $7: dns1
# $8: dns2
if [ -f "/etc/netplan/01-netcfg.yaml" ]; then
	if grep -q "^    ${2}:" /etc/netplan/01-netcfg.yaml; then
		sed -i '/^    '${2}:'/,/^    [a-z]/ {/^    '${2}'/n;/^    [a-z]/!d}' /etc/netplan/01-netcfg.yaml
	else
		echo "    ${2}:" >> /etc/netplan/01-netcfg.yaml
	fi
	if [ "${3}" = 'static' ]; then
		if [ "${8}" != '' ]; then
			sed -i '/^    '${2}'/a\          addresses: \['${7}','${8}'\]' /etc/netplan/01-netcfg.yaml
		elif [ "${7}" != '' ]; then
			sed -i '/^    '${2}'/a\          addresses: \['${7}'\]' /etc/netplan/01-netcfg.yaml
		fi
		if [ "${7}" != '' ]; then
			sed -i '/^    '${2}'/a\      nameservers:' /etc/netplan/01-netcfg.yaml
		fi
		if [ "${6}" != '0.0.0.0' ]; then
			sed -i '/^    '${2}'/a\      gateway4: '${6}'' /etc/netplan/01-netcfg.yaml
		fi
		p=0
		for i in $(echo ${5} | sed -e "s/\./ /g"); do
			case $i in
				255) p=$((${p}+8));;
				254) p=$((${p}+7));;
				252) p=$((${p}+6));;
				248) p=$((${p}+5));;
				240) p=$((${p}+4));;
				224) p=$((${p}+3));;
				192) p=$((${p}+2));;
				128) p=$((${p}+1));;
				0);;
			esac
		done
		sed -i '/^    '${2}'/a\        - '${4}'/'${p}'' /etc/netplan/01-netcfg.yaml
		sed -i '/^    '${2}'/a\      addresses:' /etc/netplan/01-netcfg.yaml
	else
		sed -i '/^    '${2}'/a\      dhcp4: yes' /etc/netplan/01-netcfg.yaml
	fi
	netplan apply
	sleep 1
elif [ -f "/etc/network/interfaces" ]; then
	sed -i '/^iface '${2}'/,/^auto/ {/^iface '${2}'/n;/^auto/!d}' /etc/network/interfaces
	if grep -q "^iface ${2}" /etc/network/interfaces; then
		sed -i "s/^iface ${2}.*/iface ${2} inet ${3}/" /etc/network/interfaces
	else
		echo "iface ${2} inet ${3}" >> /etc/network/interfaces
	fi
	if ! grep -q "^auto ${2}" /etc/network/interfaces; then
		sed -i "/^iface ${2}/i auto ${2}" /etc/network/interfaces
	fi
	sed -i "/^iface ${2}/a
" /etc/network/interfaces
	if [ "${3}" = 'static' ]; then
		if [ "${8}" != '' ]; then
			sed -i "/^iface ${2}/a\	dns-nameservers ${7} ${8}" /etc/network/interfaces
		elif [ "${7}" != '' ]; then
			sed -i "/^iface ${2}/a\	dns-nameservers ${7}" /etc/network/interfaces
		fi
		if [ "${6}" != '0.0.0.0' ]; then
			sed -i "/^iface ${2}/a\	gateway ${6}" /etc/network/interfaces
		fi
		sed -i "/^iface ${2}/a\	netmask ${5}" /etc/network/interfaces
		sed -i "/^iface ${2}/a\	address ${4}" /etc/network/interfaces
	fi
elif [ -d "/etc/sysconfig/network-scripts" ]; then
	echo "DEVICE=${2}" > /etc/sysconfig/network-scripts/ifcfg-${2}
	echo "ONBOOT=yes" >> /etc/sysconfig/network-scripts/ifcfg-${2}
	echo "NM_CONTROLLED=no" >> /etc/sysconfig/network-scripts/ifcfg-${2}
	if [ "${3}" = 'static' ]; then
		echo "BOOTPROTO=none" >> /etc/sysconfig/network-scripts/ifcfg-${2}
		echo "NETMASK=${5}" >> /etc/sysconfig/network-scripts/ifcfg-${2}
		echo "IPADDR=${4}" >> /etc/sysconfig/network-scripts/ifcfg-${2}
		if [ "${6}" != '0.0.0.0' ]; then
			echo "GATEWAY=${6}" >> /etc/sysconfig/network-scripts/ifcfg-${2}
		fi
		if [ "${7}" != '' ]; then
			echo "DNS1=${7}" >> /etc/sysconfig/network-scripts/ifcfg-${2}
		fi
		if [ "${8}" != '' ]; then
			echo "DNS2=${8}" >> /etc/sysconfig/network-scripts/ifcfg-${2}
		fi
	else
		echo "BOOTPROTO=dhcp" >> /etc/sysconfig/network-scripts/ifcfg-${2}
	fi
fi
;;

getproxy)
server=$(grep -i '^http_proxy=' /etc/environment | sed -e 's|^http_proxy=http://||' -e 's|/$||' -e 's|.*@||' -e 's|:| |')
userpass=$(grep -i '^http_proxy=' /etc/environment | grep '@' | sed -e 's|^http_proxy=http://||' -e 's|/$||' -e 's/\(.*\)@.*/\1/' -e 's/\(.*\):/\1 /')
echo ${server} ${userpass}
;;

setproxy)
# $2: proxy_url
sed -i '/^http_proxy=/d' /etc/environment
sed -i '/^https_proxy=/d' /etc/environment
sed -i '/^ftp_proxy=/d' /etc/environment
sed -i '/^Acquire::http::proxy/d' /etc/apt/apt.conf 2>/dev/null
sed -i '/^Acquire::https::proxy/d' /etc/apt/apt.conf 2>/dev/null
sed -i '/^Acquire::ftp::proxy/d' /etc/apt/apt.conf 2>/dev/null
sed -i '/^proxy=/d' /etc/yum.conf 2>/dev/null
sed -i '/^proxy_username=/d' /etc/yum.conf 2>/dev/null
sed -i '/^proxy_password=/d' /etc/yum.conf 2>/dev/null
if [ "${2}" != '' ]; then
	echo "http_proxy=http://${2}/" >> /etc/environment
	echo "https_proxy=https://${2}/" >> /etc/environment
	echo "ftp_proxy=ftp://${2}/" >> /etc/environment
	if [ -f "/etc/apt/apt.conf" ]; then
		echo "Acquire::http::proxy \"http://${2}/\";" >> /etc/apt/apt.conf
		echo "Acquire::https::proxy \"https://${2}/\";" >> /etc/apt/apt.conf
		echo "Acquire::ftp::proxy \"ftp://${2}/\";" >> /etc/apt/apt.conf
	fi
	if [ -f "/etc/yum.conf" ]; then
		proxy=$(echo "${2}" sed -e 's/.*@//')
		username=$(echo "${2}" | grep '@' | sed -e 's/:.*//')
		password=$(echo "${2}" | grep '@' | sed -e 's/\(.*\)@.*/\1/' -e 's/[^:]*://')
		echo "proxy=http://${proxy}" >> /etc/yum.conf
		if [ "${username}" != '' ]; then
			echo "proxy_username=${username}" >> /etc/yum.conf
			echo "proxy_password=${password}" >> /etc/yum.conf
		fi
	fi
fi
;;

#Get the local time
getlocaltime)
echo $(date +"%a %b %d %H:%M:%S %Y")
;;

#Set the local time
setlocaltime)
# $2: date
date -s "$2"
;;

#Get the time server that is set
gettimeserver)
if [ -f "/etc/ntp/step-tickers" ]; then
	timeserver=$(grep -v "^$" /etc/ntp/step-tickers 2>/dev/null | grep -m 1 -v '#')
elif [ -f "/etc/ntp.conf" ]; then
	timeserver=$(grep -m 1 '^server\|^pool' /etc/ntp.conf | awk '{print $2}')
elif [ -f "/etc/systemd/timesyncd.conf" ]; then
	timeserver=$(grep '^NTP=' /etc/systemd/timesyncd.conf | cut -d = -f 2 | awk '{print $1}')
fi
echo $timeserver
;;

#Set the time server
settimeserver)
# $2: timeserver
if [ -f "/etc/ntp/step-tickers" ]; then
	echo "# List of NTP servers used by the ntpdate service." > /etc/ntp/step-tickers
	echo $2 >> /etc/ntp/step-tickers
	ntpdate $2
elif [ -f "/etc/ntp.conf" ]; then
	sed -i "0,/^server/{s/^server.*/server $2/}" /etc/ntp.conf
	sed -i "0,/^pool/{s/^pool.*/pool $2 iburst/}" /etc/ntp.conf
	service ntp restart 2>&-
elif [ -f "/etc/systemd/timesyncd.conf" ]; then
	sed -i "s/^NTP=.*/NTP=$2/" /etc/systemd/timesyncd.conf
	systemctl restart systemd-timesyncd
fi
;;

# Get time zone
gettimezone)
if [ "$(which timedatectl 2>&-)" != '' ]; then
	echo $(timedatectl | grep 'Time.*zone' | cut -d : -f 2 | awk '{print $1}')
else
	echo $(cat /etc/sysconfig/clock | awk -F '\"' '{print $2}')
fi

;;

# Set time zone
settimezone)
# $2: timezone
if [ "$(which timedatectl 2>&-)" != '' ]; then
	timedatectl set-timezone $2
else
	echo ZONE=\"$2\" > /etc/sysconfig/clock
	cp /usr/share/zoneinfo/$2 /etc/localtime
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

resetafppw)
# $2: password
echo afpuser:${2} | chpasswd
if [ -f "/etc/pybsdp.conf" ]; then
	sed -i "s/netbootpass.*/netbootpass = ${2}/" /etc/pybsdp.conf
fi
if service pybsdp status 2>&- | grep -q running ; then
	service pybsdp restart 2>&-
fi
# if [ -f "/var/appliance/conf/dhcpd.conf" ]; then
# 	ip=$(ip addr show to 0.0.0.0/0 scope global | awk '/[[:space:]]inet / { print gensub("/.*","","g",$2) }')
# 	afppw=$(echo ${2} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/')
# 	afppwlen=$(echo ${afppw} | sed 's/://g' | tr -d ' ' | wc -c)
# 	afppwlen=$(expr ${afppwlen} / 2)
# 	iphex=$(echo ${ip} | xxd -c 1 -ps -u | tr '\n' ':' | sed 's/0A://g' | sed 's/\(.*\)./\1/')
# 	num=$(echo ${iphex} | sed 's/://g' | wc -c)
# 	num=$(expr ${num} / 2)
# 	num=$(expr ${num} + 23)
# 	num=$(expr ${num} + ${afppwlen})
# 	lengthhex=$(awk -v dec=${num} 'BEGIN { n=split(dec,d,"."); for(i=1;i<=n;i++) printf ":%02X",d[i]; print "" }')
# 	newafp=61:66:70:3A:2F:2F:61:66:70:75:73:65:72:3A:${afppw}
# 	imageid=$(grep '04:02:FF:FF:07:04' /var/appliance/conf/dhcpd.conf | sed 's/.*04:02:FF:FF:07:04://g' | cut -c1-11)
# 	sed -i "s/01:01:02:08:04:${imageid}:80:.*/01:01:02:08:04:${imageid}:80${lengthhex}:${newafp}:40:${iphex}:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /var/appliance/conf/dhcpd.conf
# fi
# if [ -f "/etc/dhcpd.conf" ]; then
# 	imageid=$(grep '04:02:FF:FF:07:04' /etc/dhcpd.conf | sed 's/.*04:02:FF:FF:07:04://g' | cut -c1-11)
# 	sed -i "s/01:01:02:08:04:${imageid}:80:.*/01:01:02:08:04:${imageid}:80${lengthhex}:${newafp}:40:${iphex}:2F:4E:65:74:42:6F:6F:74:81:11:4E:65:74:42:6F:6F:74:30:30:31:2F:53:68:61:64:6F:77;/g" /etc/dhcpd.conf
# fi
# if ps acx | grep -v grep | grep -q dhcpd ; then
# 	killall dhcpd > /dev/null 2>&1
# 	/usr/local/sbin/dhcpd > /dev/null 2>&1
# fi
;;

addshelluser)
# $2: Username
# $3: Full Name
# $4: Account Type
if [ "$4" = 'Administrator' ] || [ "$4" = 'Standard' ]; then
	useradd -c "$3" -d /home/$2 -m -s $(which bash) $2
else
	useradd -c "$3" -d /dev/null -s $(which nologin) $2
fi
;;

changeshelluser)
# $2: User Name
# $3: Full Name
# $4: Home Directory
# $5: New User Name
# $6: Login Shell
# $7: User ID
if [ "$4" != '/dev/null' ]; then
	usermod -c "$3" -d $4 -l $5 -m -s $(which $6) -u $7 $2
else
	usermod -c "$3" -l $5 -s $(which $6) -u $7 $2
fi
groupmod -n $5 $2 2>/dev/null
if [ ! -e $4 ]; then
	mkdir -p $4
	chown $5:$5 $4
fi
;;

addshelladmin)
# $2: Username
if [ "$(getent group wheel)" = '' ]; then
	usermod -a -G adm,sudo,lpadmin,sambashare $2
else
	usermod -a -G wheel $2
fi
;;

remshelladmin)
# $2: Username
if [ "$(getent group wheel)" = '' ]; then
	deluser $2 adm
	deluser $2 sudo
	deluser $2 lpadmin
	deluser $2 sambashare
else
	deluser $2 wheel
fi
;;

changeshellpass)
# $2: user_name
# $3: password
echo $2:"$3" | chpasswd
(echo "$3"; echo "$3") | smbpasswd -s -a $2
;;

delshelluser)
# $2: Username
# $3: Delete Home
if [ "$3" = 'true' ]; then
	userdel -r $2
else
	userdel $2
fi
;;

getShellList)
echo $(which bash 2>/dev/null) $(which tcsh 2>/dev/null) $(which sh 2>/dev/null) $(which csh 2>/dev/null) $(which zsh 2>/dev/null) $(which ksh 2>/dev/null) $(which nologin 2>/dev/null) $(which false 2>/dev/null)
;;

diskusage)
echo $(df --local --total | grep '^total' | awk '{print $2":"$3":"$4}')
;;

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
	sed -i "s/#SSLCertificateChainFile \/etc\/apache2\/ssl.crt\/server-ca.crt/SSLCertificateChainFile \/etc\/apache2\/ssl.crt\/server-ca.crt/g" /etc/apache2/sites-enabled/default-ssl.conf
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

getconns)
echo $(($(ss | grep microsoft-ds | wc | awk '{print $1}') + $(ss | grep afpovertcp | wc | awk '{print $1}')))
;;

restart)
echo "Restarting" > /var/appliance/.applianceShutdown
shutdown -r 1
;;

shutdown)
echo "Shutting Down" > /var/appliance/.applianceShutdown
shutdown -P 1
;;

getshutdownstaus)
SERVICE=shutdown
if pgrep -x "$SERVICE" > /dev/null || [ -f "/run/nologin" ]; then
	echo "true"
else
	echo "false"
fi
;;

enablegui)
sed -i 's:<webadmingui>.*</webadmingui>::' /var/appliance/conf/appliance.conf.xml
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
resizeStatus)
pvname=$(pvdisplay -c 2>/dev/null | cut -d : -f 1)
if [ "$pvname" = '' ]; then
	echo "ERROR: No suitable physical volumes found."
	exit
fi
if ! file $pvname | grep -q "block special"; then
	echo "ERROR: $pvname is not a block device."
	exit
fi
lvpath=$(lvdisplay -c 2>/dev/null | grep -v swap | cut -d : -f 1)
if [ "$lvpath" = '' ]; then
	echo "ERROR: No logical volumes found."
	exit
fi
lvdisplay $lvpath > /dev/null
if [ $? -ne 0 ]; then
	echo "ERROR: $lvpath is not a logical volume."
	exit
fi
device=$(echo $pvname | sed -e 's/[0-9]//g')
if ! fdisk -u -l $device | grep $device | tail -1 | grep $pvname | grep -q "Linux LVM"; then
	echo "ERROR: $pvname is not the last volume on $device."
	exit
fi
partition=$(echo $pvname | sed -e 's/[^0-9]//g')
if ! parted $device --script unit s print | grep -Pq "^\s$partition\s+.+?[^,]+?lvm\s*$"; then
	echo "ERROR: $pvname has additional flags set."
	exit
fi
total=$(parted $device --script unit B print | grep $device | awk '{print $NF}' | tr -d 'B')
end=$(parted $device --script unit B print | grep -P "^\s$partition\s+.+?[^,]+?lvm\s*$" | awk '{print $3}' | tr -d 'B')
free=$(($total-$end))
echo $total:$end:$free
;;

resizeDisk)
pvname=$(pvdisplay -c 2>/dev/null | cut -d : -f 1)
if [ "$pvname" = '' ]; then
	echo "No suitable physical volumes found."
	exit
fi
echo "Resizing $pvname"
echo
if ! file $pvname | grep -q "block special"; then
	echo "$pvname is not a block device."
	exit 1
fi
lvpath=$(lvdisplay -c 2>/dev/null | grep -v swap | cut -d : -f 1)
if [ "$lvpath" = '' ]; then
	echo "No logical volumes found."
	exit
fi
lvdisplay $lvpath > /dev/null
if [ $? -ne 0 ]; then
	echo "$lvpath is not a logical volume."
	exit 1
fi
device=$(echo $pvname | sed -e 's/[0-9]//g')
if ! fdisk -u -l $device | grep $device | tail -1 | grep $pvname | grep -q "Linux LVM"; then
	echo "$pvname is not the last volume on $device."
	exit 1
fi
partition=$(echo $pvname | sed -e 's/[^0-9]//g')
if ! parted $device --script unit s print | grep -Pq "^\s$partition\s+.+?[^,]+?lvm\s*$"; then
	echo "$pvname has additional flags set."
	exit 1
fi
echo "Current partition layout of $device:"
echo
parted $device --script unit GB print
start=$(fdisk -u -l $device | grep $pvname | awk '{print $2}')
if parted $device --script unit s print | grep -qP "^\s$partition\s+.+?logical.+$"; then
	echo "Detected LVM residing on a logical partition."
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
echo "Creating script to resize the filesystem at next boot."
echo
echo '#!/bin/bash' > /root/resizefs.sh
if [ -f "/etc/rc.d/rc.local" ]; then
	rc_local=/etc/rc.d/rc.local
else
	rc_local=/etc/rc.local
fi
echo "
pvresize $pvname
lvextend --extents +100%FREE $lvpath --resizefs
sed -i '/\/root\/resizefs.sh/d' $rc_local
rm -f \$0" >> /root/resizefs.sh
chmod +x /root/resizefs.sh
sed -i '/^exit 0/d' $rc_local
echo '/root/resizefs.sh' >> $rc_local
echo 'exit 0' >> $rc_local
echo "A restart is required for changes to take effect."
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

getSSLstatus)
if [ -f "/etc/ssl/certs/ssl-cert-snakeoil.pem" ]; then
	issuer=$(openssl x509 -issuer -noout -in /etc/ssl/certs/ssl-cert-snakeoil.pem | awk '{print $NF}')
	subject=$(openssl x509 -subject -noout -in /etc/ssl/certs/ssl-cert-snakeoil.pem | awk '{print $NF}')
fi
if [ -f "/etc/pki/tls/certs/localhost.crt" ]; then
	issuer=$(openssl x509 -issuer -noout -in /etc/pki/tls/certs/localhost.crt | awk '{print $NF}')
	subject=$(openssl x509 -subject -noout -in /etc/pki/tls/certs/localhost.crt | awk '{print $NF}')
fi
if [ "${issuer}" != "${subject}" ]; then
	echo "true"
fi
;;

getDirSize)
# $2: Directory Path
if [ "$2" != '' ]; then
	dirSize=$(du -s "$2" 2>/dev/null | awk '{print $1}')
fi
if [ "$dirSize" != '' ]; then
	echo "$dirSize"
else
	echo "0"
fi
;;

validCertKey)
# $2: private key
# $3: certificate
key_modulus=$(openssl rsa -noout -modulus -in $2)
crt_modulus=$(openssl x509 -noout -modulus -in $3)
if [ "$key_modulus" = "$crt_modulus" ]; then
	echo "true"
else
	echo "false"
fi
;;

validCertChain)
# $2: ca bundle
# $3: certificate
echo "$(openssl verify -CAfile $2 $3 | grep '^error')"
;;

backupConf)
if [ "$(getent passwd www-data)" != '' ]; then
	www_user=www-data
elif [ "$(getent passwd apache)" != '' ]; then
	www_user=apache
fi
tar czf /tmp/backup.tar.gz /var/appliance/conf/ /var/lib/reposado/preferences.plist
chown $www_user /tmp/backup.tar.gz
chmod u+w /tmp/backup.tar.gz
;;

esac