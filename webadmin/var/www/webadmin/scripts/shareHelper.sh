#!/bin/bash

case $1 in

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

smbconns)
echo $(ss | grep microsoft-ds | wc | awk '{print $1}')
;;

getafpstatus)
SERVICE=afpd
if pgrep -x "$SERVICE" > /dev/null; then
	echo "true"
else
	echo "false"
fi
;;

afpconns)
echo $(ss | grep afpovertcp | wc | awk '{print $1}')
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

getSMBshares)
for i in $(grep include /etc/samba/smb.conf | grep -v '\(#\|;\)' | awk '{print $NF}'); do
	if [ -e $i ]; then
		name=$(sed -n -e 's/.*\[\(.*\)\]/\1/p' $i)
		folder=$(sed -n -e 's/^.*path.*=//p' $i | sed -e 's/^[ \t]*//;s/[ \t]*$//')
		rwlist=$(sed -n -e 's/^.*write list.*=//p' $i | sed -e 's/^[ \t]*//;s/[ \t]*$//;s/ /,/g')
		allow=$(sed -n -e 's/^.*valid users.*=//p' $i | sed -e 's/^[ \t]*//;s/[ \t]*$//;s/ /,/g')
		echo $name:$folder:$rwlist:$allow
	fi
done
;;

getAFPshares)
grep -v "\(#\|^~\|^:\|^$\)" /etc/netatalk/AppleVolumes.default | while read i; do
	folder=$(echo "$i" | awk '{print $1}')
	name=$(echo "$i" | awk -F \" '{print $2}')
	# allow=$(echo "$i" | grep -ow "allow:[a-z0-9_-,]*" | cut -d : -f 2)
	allow=$(echo "$i"  | awk -F 'allow:' '{print $NF}' | awk '{print $1}')
	# rwlist=$(echo "$i" | grep -ow "rwlist:[a-z0-9_-,]*" | cut -d : -f 2)
	rwlist=$(echo "$i" | awk -F 'rwlist:' '{print $NF}' | awk '{print $1}')
	echo $name:$folder:$rwlist:$allow
done
;;

getHTTPshare)
# $2: path
if [ -d "/etc/apache2/sites-enabled" ]; then
	conf=$(grep -R "Alias.*$2" /etc/apache2/sites-enabled | awk -F : '{print $1}')
fi
if [ -d "/etc/httpd/conf.d" ]; then
	conf=$(grep -R "Alias.*$2" /etc/httpd/conf.d | awk -F : '{print $1}')
fi
if [ "$conf" != '' ]; then
	echo "true"
fi
;;

addSMBshare)
# $2: Share Name
# $3: Share Path
# $4: Read / Write List
# $5: Read Only List
conf=$(echo "$2" | sed -e 's/ //g' | tr [:upper:] [:lower:]).conf
if [ ! -e "${3}" ]; then
	mkdir -p "${3}"
	chown ${4}:users "${3}"
fi
rwlist=$(echo $4 | sed -e 's/,/ /g')
rolist=$(echo $5 | sed -e 's/,/ /g')
echo "[${2}]
    comment = ${2}
    path = ${3}
    browseable = yes
    guest ok = no
    read only = yes
    create mask = 0755
    write list = ${rwlist}
    valid users = ${rwlist} ${rolist}" > /etc/samba/conf.d/${conf}
sed -i "/#.*$2/d" /etc/samba/smb.conf
sed -i "/include.*$conf/d" /etc/samba/smb.conf
echo "# ${2}
    include = /etc/samba/conf.d/${conf}" >> /etc/samba/smb.conf
;;

addAFPshare)
# $2: Share Name
# $3: Share Path
# $4: Read / Write List
# $5: Read Only List
if [ ! -e "${3}" ]; then
	mkdir -p "${3}"
	chown ${4}:users "${3}"
fi
allow=$(echo "allow:"$4 $5 | sed -e 's/ /,/g')
if [ "$4" != '' ]; then
	rwlist=$(echo "rwlist:"$4)
fi
if [ "$5" != '' ]; then
	rolist=$(echo "rolist:"$5)
fi
sed -i "/\"${2}\"/d" /etc/netatalk/AppleVolumes.default
echo "${3} \"${2}\" ${allow} ${rwlist} ${rolist} perm:0755 cnidscheme:dbd ea:sys" >> /etc/netatalk/AppleVolumes.default
sed -i '/End of File/d' /etc/netatalk/AppleVolumes.default
echo '# End of File' >> /etc/netatalk/AppleVolumes.default
;;

addHTTPshare)
# $2: Share Name
# $3: Share Path
if [ -d "/etc/apache2/sites-enabled" ]; then
	conf=/etc/apache2/sites-enabled/001-$(echo "$2" | sed -e 's/ //g' | tr [:upper:] [:lower:]).conf
fi
if [ -d "/etc/httpd/conf.d" ]; then
	conf=/etc/httpd/conf.d/$(echo "$2" | sed -e 's/ //g' | tr [:upper:] [:lower:]).conf
fi
if [ ! -e "${3}" ]; then
	mkdir -p "${3}"
fi
if [ "$conf" != '' ]; then
    if httpd -v 2>/dev/null | grep version | grep -q '2.2'; then
		echo "Alias /${2}/ \"${3}/\"

<Directory ${3}/>
	Options FollowSymLinks MultiViews
	AllowOverride None
	Order allow,deny
	Allow from all
</Directory>" > ${conf}
	else
		echo "Alias /${2}/ \"${3}/\"

<Directory ${3}/>
	Options FollowSymLinks MultiViews
	AllowOverride None
	Require all granted
</Directory>" > ${conf}
	fi
fi
if [ -d "/etc/apache2/sites-enabled" ]; then
	service apache2 reload
fi
if [ -d "/etc/httpd/conf.d" ]; then
	service httpd reload
fi
;;

delSMBshare)
# $2: Share Name
conf=$(grep -R "\[$2\]" /etc/samba/conf.d | awk -F : '{print $1}' | awk -F / '{print $NF}')
sed -i "/#.*$2/d" /etc/samba/smb.conf
if [ "$conf" != '' ]; then
	sed -i "/include.*$conf/d" /etc/samba/smb.conf
	rm -f /etc/samba/conf.d/${conf}
fi
;;

delAFPshare)
# $2: Share Name
sed -i "/\"${2}\"/d" /etc/netatalk/AppleVolumes.default
;;

delHTTPshare)
# $2: Share Name
if [ -d "/etc/apache2/sites-enabled" ]; then
	conf=$(grep -R "Alias /$2/" /etc/apache2/sites-enabled | awk -F : '{print $1}')
fi
if [ -d "/etc/httpd/conf.d" ]; then
	conf=$(grep -R "Alias /$2/" /etc/httpd/conf.d | awk -F : '{print $1}')
fi
if [ "$conf" != '' ]; then
	rm -f ${conf}
fi
if [ -d "/etc/apache2/sites-enabled" ]; then
	service apache2 reload
fi
if [ -d "/etc/httpd/conf.d" ]; then
	service httpd reload
fi
;;

delShareData)
# $2: Share Path
if [ "$2" != '' ]; then
	rm -rf "$2"
fi
;;

esac
