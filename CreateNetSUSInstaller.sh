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

#mkdir temp
#cp -R base temp
#cp -R NetBoot temp
#cp -R SUS temp
#cp -R webadmin temp
#cp -R LDAPProxy temp
#cp -R includes/* temp/base/
#cp -R includes/* temp/NetBoot/
#cp -R includes/* temp/SUS/
#cp -R includes/* temp/webadmin/
#cp -R includes/* temp/LDAPProxy/
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
cp -R NetBoot/usr/local/sbin temp/installer/resources/dhcp
cp -R NetBoot/var/appliance/conf/dhcpd.conf temp/installer/resources/dhcpd.conf
cp -R NetBoot/var/appliance/configurefornetboot temp/installer/resources/configurefornetboot
cp -R NetBoot/var/appliance/libdb4-4.8.30-21.fc26.x86_64.rpm temp/installer/resources/libdb4-4.8.30-21.fc26.x86_64.rpm
cp -R NetBoot/var/appliance/netatalk-2.2.0-2.el6.x86_64.rpm temp/installer/resources/netatalk-2.2.0-2.el6.x86_64.rpm
cp -R NetBoot/var/appliance/netatalk-2.2.3-9.fc20.x86_64.rpm temp/installer/resources/netatalk-2.2.3-9.fc20.x86_64.rpm
cp -R SUS/susInstall.sh temp/installer/install-sus.sh
cp -R SUS/var/appliance/sus_sync.py temp/installer/resources/sus_sync.py
cp -R SUS/var/lib/reposado temp/installer/resources/reposado
cp -R webadmin/webadminInstall.sh temp/installer/install-webadmin.sh
cp -R webadmin/var/appliance/dialog.sh temp/installer/resources/dialog.sh
cp -R webadmin/var/www temp/installer/resources/html
if [ -x /usr/bin/xattr ]; then find temp -exec xattr -c {} \; ;fi # Remove OS X extended attributes
find temp -name .DS_Store -delete # Clean out .DS_Store files
find temp -name .svn | xargs rm -Rf # Clean out SVN garbage


# Generate NetBoot App sub-installer
#timeEcho "Creating NetBoot sub-installer..."
#bash makeself/makeself.sh temp/NetBoot/ temp/base/netbootInstall.run "NetBoot Installer" "bash netbootInstall.sh" > /dev/null

# Generate SUS sub-installer
#timeEcho "Creating SUS sub-installer..."
#bash makeself/makeself.sh temp/SUS/ temp/base/susInstall.run "SUS Installer" "bash susInstall.sh" > /dev/null

# Generate webadmin sub-installer
#timeEcho "Creating webadmin sub-installer..."
#bash makeself/makeself.sh temp/webadmin/ temp/base/webadminInstall.run "WebAdmin Installer" "bash webadminInstall.sh" > /dev/null

# Generate LDAP Proxy sub-installer
#timeEcho "Creating LDAP Proxy sub-installer..."
#bash makeself/makeself.sh temp/LDAPProxy/ temp/base/LDAPProxyInstall.run "LDAP Proxy Installer" "bash LDAPProxyInstall.sh" > /dev/null

# Generate final installer
timeEcho "Creating final installer..."
#bash makeself/makeself.sh temp/base/ NetSUSLPInstaller.run "NetSUSLP Installer" "bash NetSUSInstaller.sh"
bash makeself/makeself.sh temp/installer/ NetSUSLPInstaller.run "NetSUSLP Installer" "bash install.sh"

timeEcho "Cleaning up..."
#cp temp/*/*.run .  # Uncomment this if you want to test the sub-installers outside of the main installer
rm -Rf temp 2>&1 > /dev/null
timeEcho "Finished creating the NetSUS Installer.  "

exit 0