#!/bin/bash
# This script generates a new Appliance Installer

timeEcho() {
	echo $(date "+[%Y-%m-%d %H:%M:%S]: ") "$1"
}

alias md5='md5 -r'
alias md5sum='md5 -r'

echo ""
timeEcho "Building NetSUSLP Installer..."

# Clean-up old files
rm -f NetSUSInstaller.run 2>&1 > /dev/null
rm -Rf temp 2>&1 > /dev/null

mkdir -p temp/installer/checks
mkdir -p temp/installer/resources
mkdir -p temp/installer/utils
cp -R base/NetSUSInstaller.sh temp/installer/install.sh
cp -R base/test64bitRequirements.sh temp/installer/checks/test64bitRequirements.sh
cp -R base/testOSRequirements.sh temp/installer/checks/testOSRequirements.sh
cp -R base/testUbuntuBinRequirements.sh temp/installer/checks/testBinRequirements.sh
cp -R includes/logger.sh temp/installer/utils/logger.sh
cp -R LDAPProxy/etc/ldap/* temp/installer/resources
cp -R LDAPProxy/LDAPProxyInstall.sh temp/installer/install-proxy.sh
cp -R NetBoot/netbootInstall.sh temp/installer/install-netboot.sh
cp -R NetBoot/etc/init.d/pybsdp temp/installer/resources/pybsdp.ubuntu
cp -R NetBoot/etc/rc.d/init.d/pybsdp temp/installer/resources/pybsdp.rhel
cp -R NetBoot/usr/local/lib temp/installer/resources/lib
cp -R NetBoot/usr/local/sbin temp/installer/resources/dhcp
rm -f temp/installer/resources/dhcp/pybsdp
cp -R NetBoot/usr/local/sbin/pybsdp temp/installer/resources/pybsdp
cp -R NetBoot/var/appliance/conf/dhcpd.conf temp/installer/resources/dhcpd.conf
cp -R NetBoot/var/appliance/configurefornetboot temp/installer/resources/configurefornetboot
cp -R NetBoot/var/appliance/libdb4-4.8.30-21.fc26.x86_64.rpm temp/installer/resources/libdb4-4.8.30-21.fc26.x86_64.rpm
cp -R NetBoot/var/appliance/nbi_settings.py temp/installer/resources/nbi_settings.py
cp -R NetBoot/var/appliance/netatalk-2.2.0-2.el6.x86_64.rpm temp/installer/resources/netatalk-2.2.0-2.el6.x86_64.rpm
cp -R NetBoot/var/appliance/netatalk-2.2.3-9.fc20.x86_64.rpm temp/installer/resources/netatalk-2.2.3-9.fc20.x86_64.rpm
cp -R SUS/susInstall.sh temp/installer/install-sus.sh
cp -R SUS/var/appliance/* temp/installer/resources
cp -R SUS/var/lib/reposado temp/installer/resources/reposado
mv -f temp/installer/resources/reposado/preferences.plist temp/installer/resources/preferences.plist
cp -R webadmin/webadminInstall.sh temp/installer/install-webadmin.sh
cp -R webadmin/var/appliance/dialog.sh temp/installer/resources/dialog.sh
cp -R webadmin/var/www temp/installer/resources/html
if [ -x "/usr/bin/xattr" ]; then find temp -exec xattr -c {} \; ;fi # Remove OS X extended attributes
find temp -name .DS_Store -delete # Clean out .DS_Store files
find temp -name .svn | xargs rm -Rf # Clean out SVN garbage


# Generate final installer
timeEcho "Creating final installer..."
bash makeself/makeself.sh temp/installer/ NetSUSLPInstaller.run "NetSUSLP Installer" "bash install.sh"

timeEcho "Cleaning up..."
rm -Rf temp 2>&1 > /dev/null
timeEcho "Finished creating the NetSUS Installer.  "

exit 0