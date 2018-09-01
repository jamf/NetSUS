#!/bin/bash

nbdir="/srv/NetBoot/NetBootSP0"

case $1 in

getNBImageInfo)
# $2: NBI
/var/appliance/nbi_settings.py "${nbdir}/${2}" json
;;

getNBIproperty)
# $2: NBI
# $3: Key
/var/appliance/nbi_settings.py "${nbdir}/${2}" read "${3}"
;;

setNBIproperty)
# $2: NBI
# $3: Key
# $4: Value
/var/appliance/nbi_settings.py "${nbdir}/${2}" write "${3}" "${4}"
;;

installdhcpdconf)
mv /etc/dhcpd.conf /etc/dhcpd.conf.bak
mv /var/appliance/conf/dhcpd.conf.new /etc/dhcpd.conf
;;

setnbimage)
# $2: NBI
index=$(/var/appliance/nbi_settings.py "${nbdir}/${2}" read "Index")
isinstall=$(/var/appliance/nbi_settings.py "${nbdir}/${2}" read "IsInstall")
if [ "$isinstall" = 'True' ]; then
	isinstall=8
else
	isinstall=0
fi
kind=$(/var/appliance/nbi_settings.py "${nbdir}/${2}" read "Kind")
name=$(/var/appliance/nbi_settings.py "${nbdir}/${2}" read "Name")
rootpath=$(/var/appliance/nbi_settings.py "${nbdir}/${2}" read "RootPath")
type=$(/var/appliance/nbi_settings.py "${nbdir}/${2}" read "Type")
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
sed -i 's|filename ".*";|filename "'${2}'/i386/booter";|' /etc/dhcpd.conf
if [ "$type" = 'NFS' ]; then
	sed -i 's|option root-path.*|option root-path "nfs:'${root_path_ip}':${nbdir}:'${2}'/'${rootpath}'";|' /etc/dhcpd.conf
else
	sed -i 's|option root-path.*|option root-path "http://'${root_path_ip}'/NetBoot/NetBootSP0/'${2}'/'${rootpath}'";|' /etc/dhcpd.conf
fi
;;

getdhcpstatus)
if ps acx | grep -v grep | grep -q dhcpd ; then
	echo "true"
else
	echo "false"
fi
;;

getbsdpstatus)
SERVICE=pybsdp
if service $SERVICE status 2>&- | grep -q running ; then
	echo "true"
else
	echo "false"
fi
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
if pgrep -x "$SERVICE" > /dev/null; then
	echo "true"
else
	echo "false"
fi
;;

getafpstatus)
SERVICE=afpd
if pgrep -x "$SERVICE" > /dev/null; then
	echo "true"
else
	echo "false"
fi
;;

startdhcp)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	cp -f /var/appliance/configurefornetboot /etc/network/if-up.d/configurefornetboot
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	cp -f /var/appliance/configurefornetboot /sbin/ifup-local
fi
/var/appliance/configurefornetboot
;;

stopdhcp)
if [ -f "/etc/network/if-up.d/configurefornetboot" ]; then
	rm -f /etc/network/if-up.d/configurefornetboot
fi
if [ -f "/sbin/ifup-local" ]; then
	rm -f /sbin/ifup-local
fi
killall dhcpd > /dev/null 2>&1
;;

startbsdp)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	update-rc.d pybsdp enable > /dev/null 2>&1
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig pybsdp on > /dev/null 2>&1
fi
service pybsdp start 2>&-
;;

stopbsdp)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	update-rc.d pybsdp disable > /dev/null 2>&1
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig pybsdp off > /dev/null 2>&1
fi
service pybsdp stop 2>&-
;;

starttftp)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		update-rc.d tftpd-hpa enable > /dev/null 2>&1
	else
		rm -f /etc/init/tftpd-hpa.override
	fi
	service tftpd-hpa start 2>&-
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig tftp on > /dev/null 2>&1
	if [ "$(which systemctl 2>&-)" != '' ]; then
		service tftp start 2>&-
	else
		service xinetd restart 2>&-
	fi
fi
;;

stoptftp)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		update-rc.d tftpd-hpa disable > /dev/null 2>&1
	else
		echo manual > /etc/init/tftpd-hpa.override
	fi
	service tftpd-hpa stop 2>&-
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig tftp off > /dev/null 2>&1
	if [ "$(which systemctl 2>&-)" != '' ]; then
		service tftp stop 2>&-
	else
		service xinetd restart 2>&-
	fi
fi
;;

startnfs)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		systemctl enable nfs-server > /dev/null 2>&1
		service nfs-server start 2>&-
	else
		update-rc.d nfs-kernel-server enable > /dev/null 2>&1
		service nfs-kernel-server start 2>&-
	fi
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig nfs on > /dev/null 2>&1
	service nfs start 2>&-
fi
;;

stopnfs)
if [ "$(which update-rc.d 2>&-)" != '' ]; then
	if [ "$(which systemctl 2>&-)" != '' ]; then
		systemctl disable nfs-server > /dev/null 2>&1
		service nfs-server stop 2>&-
	else
		update-rc.d nfs-kernel-server disable > /dev/null 2>&1
		service nfs-kernel-server stop 2>&-
	fi
elif [ "$(which chkconfig 2>&-)" != '' ]; then
	chkconfig nfs off > /dev/null 2>&1
	service nfs stop 2>&-
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

esac