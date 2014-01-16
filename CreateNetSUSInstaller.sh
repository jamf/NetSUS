#!/bin/bash
# This script generates a new Appliance Installer

timeEcho() {
	echo $(date "+[%Y-%m-%d %H:%M:%S]: ") "$1"
}

alias md5='md5 -r'
alias md5sum='md5 -r'

echo ""
timeEcho "Building NetSUSDP Installer..."

# Clean-up old files
rm netsusinstaller.run 2>&1 > /dev/null
rm -Rf temp 2>&1 > /dev/null

mkdir temp
cp -R base temp
cp -R NetBoot temp
cp -R SUS temp
cp -R webadmin temp
cp -R includes/* temp/base/
cp -R includes/* temp/NetBoot/
cp -R includes/* temp/SUS/
cp -R includes/* temp/webadmin/
find temp -name .svn | xargs rm -Rf # Clean out SVN garbage


# Generate NetBoot App sub-installer
timeEcho "Creating NetBoot sub-installer..."
bash makeself/makeself.sh temp/NetBoot/ temp/base/netbootInstall.run "NetBoot Installer" "bash netbootInstall.sh" > /dev/null

# Generate SUS sub-installer
timeEcho "Creating SUS sub-installer..."
bash makeself/makeself.sh temp/SUS/ temp/base/susInstall.run "SUS Installer" "bash susInstall.sh" > /dev/null

# Generate webadmin sub-installer
timeEcho "Creating webadmin sub-installer..."
bash makeself/makeself.sh temp/webadmin/ temp/base/webadminInstall.run "WebAdmin Installer" "bash webadminInstall.sh" > /dev/null

# Generate final installer
timeEcho "Creating final installer..."
bash makeself/makeself.sh temp/base/ NetSUSInstaller.run "NetSUS Installer" "bash NetSUSInstaller.sh"

timeEcho "Cleaning up..."
#cp temp/*/*.run .  # Uncomment this if you want to test the sub-installers outside of the main installer
rm -Rf temp 2>&1 > /dev/null
timeEcho "Finished creating the NetSUS Installer.  "

exit 0