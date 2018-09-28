#!/bin/bash
# This script controls the flow of the Linux NetSUS installation

export PATH="/bin:$PATH"

netsusdir=/var/appliance

#==== Check Requirements - Root User ======================#

if [[ "$(id -u)" != "0" ]]; then
  echo "The NetSUS Installer needs to be run as root or using sudo."
  exit 1
fi

# Needed for systems with secure umask settings
OLD_UMASK=$(umask)
umask 022

clean-exit() {
  umask "$OLD_UMASK"
  exit 0
}

clean-fail() {
  umask "$OLD_UMASK"
  exit 1
}

# Check for an existing installation
if [ -d "/var/appliance" ]; then
  upgrade=true
else
  upgrade=false
fi

# Create NetSUS directory (needed immediately for logging)
mkdir -p $netsusdir/logs

source utils/logger.sh

#==== Parse Arguments =====================================#

export INTERACTIVE=true

while getopts "hny" ARG
do
  case $ARG in
    h)
    echo "Usage: $0 [-y]"
    echo "-y    Activates non-interactive mode, which will silently install the NetSUS without any prompts"
    echo "-h    Shows this message"
    exit 0
    ;;
    n)
    logCritical "The -n flag is deprecated and will be removed in a future version.
                 Please use -y instead."
    export INTERACTIVE=false
    ;;
    y)
    export INTERACTIVE=false
    ;;
  esac
done

#==== Check Requirements ==================================#

log "Starting the NetSUS Installation"
log "Checking installation requirements..."

# Check for a 64-bit OS
bash checks/test64bitRequirements.sh || clean-fail

failedAnyChecks=0
# Check for Valid OS
bash checks/testOSRequirements.sh || failedAnyChecks=1

# Check for required binaries
bash checks/testBinRequirements.sh || failedAnyChecks=1

# Abort if we failed any checks
if [[ $failedAnyChecks -ne 0 ]]; then
  log "Aborting installation due to unsatisfied requirements."
  if [[ $INTERACTIVE = true ]]; then
    # shellcheck disable=SC2154
    echo "Installation failed.  See $logFile for more details."
  fi
  clean-fail
fi

log "Passed all requirements"

#==== Prompt for Confirmation =============================#

if [[ $INTERACTIVE = true ]]; then
# Prompt user for permission to continue with the installation
  echo "
The following will be installed
* Appliance Web Interface
* File Sharing
* Software Update Server
* NetBoot Server
* LDAP Proxy
"

  # shellcheck disable=SC2162,SC2034
  read -t 1 -n 100000 devnull # This clears any accidental input from stdin

  while [[ $REPLY != [yYnN] ]]; do
    # shellcheck disable=SC2162
    read -n1 -p "Proceed?  (y/n): "
    echo ""
  done
  if [[ $REPLY = [nN] ]]; then
    log "Aborting..."
    clean-exit
  else
    log "Installing..."
  fi
else
  log "Installing..."
fi

#==== Initial Cleanup tasks ===============================#

# Set SELinux policy
if sestatus | grep -q enforcing ; then
  log "Setting SELINUX mode to permissive"
  sed -i "s/SELINUX=enforcing/SELINUX=permissive/" /etc/selinux/config
  setenforce permissive
fi

#==== Service Status ======================================#

# Determine running services to (re)start post installation
if [[ $(which update-rc.d 2>&-) != "" ]]; then
  if service smbd status 2>&- | grep -q running; then
    start_smb="true"
  fi
  if service tftpd-hpa status 2>/dev/null | grep -q running; then
    start_tftp="true"
  fi
elif [[ $(which chkconfig 2>&-) != "" ]]; then
  if service smb status 2>&- | grep -q running; then
    start_smb="true"
  fi
  if [[ $(which systemctl 2>&-) != "" ]]; then
    if systemctl status tftp | grep -q running; then
      start_tftp="true"
    fi
  else
    if service xinetd status | grep -q running && chkconfig | sed 's/[ \t]//g' | grep tftp | grep -q ':on'; then
      start_tftp="true"
    fi
  fi
fi
if service pybsdp status 2>&- | grep -q running; then
  start_bsdp="true"
fi
if ps acx | grep -v grep | grep -q dhcpd; then
  start_bsdp="true"
fi
if pgrep -x "afpd" > /dev/null; then
  start_afp="true"
fi
if pgrep -x "nfsd" > /dev/null; then
  start_nfs="true"
fi
if pgrep -x "slapd" > /dev/null; then
  start_ldap="true"
fi

#==== Install Components ==================================#

bash install-webadmin.sh || clean-fail
bash install-netboot.sh || clean-fail
bash install-sus.sh || clean-fail
bash install-proxy.sh || clean-fail

#==== Post Cleanup tasks ==================================#

# Disable IPv6
if grep -q 'net.ipv6.conf.lo.disable_ipv6' /etc/sysctl.conf; then
  sed -i '/Disable IPv6/d' /etc/sysctl.conf
  sed -i '/net.ipv6.conf.all.disable_ipv6/d' /etc/sysctl.conf
  sed -i '/net.ipv6.conf.default.disable_ipv6/d' /etc/sysctl.conf
  sed -i '/net.ipv6.conf.lo.disable_ipv6/d' /etc/sysctl.conf
fi

log ""
log "The NetSUSLP has been installed."
if [ ! $upgrade = true ]; then
  log "Verify that port 443 and 80 are not blocked by a firewall."
  log ""
  log "Note: IP Helpers are required if using NetBoot across subnets."
  log "The NetBoot folder name can not contain any spaces"
  log ""
fi

log "To complete the installation, open a web browser and navigate to https://${HOSTNAME}:443/."

if [ -f "/etc/network/if-up.d/configurefornetboot" ]; then
  rm -f /etc/network/if-up.d/configurefornetboot
fi
if [ -f "/sbin/ifup-local" ]; then
  rm -f /sbin/ifup-local
fi
killall dhcpd > /dev/null 2>&1
if [[ $(which update-rc.d 2>&-) != "" ]]; then
  service apparmor restart >> $logFile 2>&1
  service apache2 restart >> $logFile 2>&1
  if [[ $start_smb == "true" ]]; then
    if [[ $(which systemctl 2>&-) != "" ]]; then
      update-rc.d smbd enable >> $logFile 2>&1
    else
      rm -f /etc/init/smbd.override
    fi
    service smbd start >> $logFile 2>&1
  else
    if [[ $(which systemctl 2>&-) != "" ]]; then
      update-rc.d smbd disable >> $logFile 2>&1
    else
      echo manual > /etc/init/smbd.override
    fi
    service smbd stop >> $logFile 2>&1
  fi
  if [[ $start_tftp == "true" ]]; then
    if [ "$(which systemctl 2>&-)" != '' ]; then
      update-rc.d tftpd-hpa enable >> $logFile 2>&1
    else
      rm -f /etc/init/tftpd-hpa.override
	fi
    service tftpd-hpa start >> $logFile 2>&1
  else
    if [[ $(which systemctl 2>&-) != "" ]]; then
      update-rc.d tftpd-hpa disable >> $logFile 2>&1
    else
      echo manual > /etc/init/tftpd-hpa.override
    fi
    service tftpd-hpa stop >> $logFile 2>&1
  fi
  if [[ $start_bsdp == "true" ]]; then
	update-rc.d pybsdp enable >> $logFile 2>&1
	service pybsdp start >> $logFile 2>&1
  else
	update-rc.d pybsdp disable >> $logFile 2>&1
	service pybsdp stop >> $logFile 2>&1
  fi
  if [[ $start_afp == "true" ]]; then
	update-rc.d netatalk enable >> $logFile 2>&1
    service netatalk start >> $logFile 2>&1
  else
    update-rc.d netatalk disable >> $logFile 2>&1
    service netatalk stop >> $logFile 2>&1
  fi
  if [[ $start_nfs == "true" ]]; then
    if [[ $(which systemctl 2>&-) != "" ]]; then
      systemctl enable nfs-server >> $logFile 2>&1
      service nfs-server start >> $logFile 2>&1
    else
      update-rc.d nfs-kernel-server enable >> $logFile 2>&1
      service nfs-kernel-server start >> $logFile 2>&1
    fi
  else
    if [[ $(which systemctl 2>&-) != "" ]]; then
      systemctl disable nfs-server >> $logFile 2>&1
      service nfs-server stop >> $logFile 2>&1
    else
      update-rc.d nfs-kernel-server disable >> $logFile 2>&1
      service nfs-kernel-server stop >> $logFile 2>&1
    fi
  fi
  if [[ $start_ldap == "true" ]]; then
    update-rc.d slapd enable >> $logFile 2>&1
    service slapd start >> $logFile 2>&1
  else
    update-rc.d slapd disable >> $logFile 2>&1
    service slapd stop >> $logFile 2>&1
  fi
  log "If you are installing NetSUSLP for the first time, please follow the documentation for setup instructions."
elif [[ $(which chkconfig 2>&-) != "" ]]; then
  service httpd restart >> $logFile 2>&1
  if [[ $start_smb == "true" ]]; then
    chkconfig smb on >> $logFile 2>&1
    service smb start >> $logFile 2>&1
  else
    chkconfig smb off >> $logFile 2>&1
    service smb stop >> $logFile 2>&1
  fi
  if [[ $start_tftp == "true" ]]; then
    chkconfig tftp on >> $logFile 2>&1
    if [ "$(which systemctl 2>&-)" != '' ]; then
      service tftp start >> $logFile 2>&1
     else
       service xinetd restart >> $logFile 2>&1
     fi
  else
    chkconfig tftp off >> $logFile 2>&1
    if [ "$(which systemctl 2>&-)" != '' ]; then
      service tftp stop >> $logFile 2>&1
     else
       service xinetd restart >> $logFile 2>&1
     fi
  fi
  if [[ $start_bsdp == "true" ]]; then
    chkconfig pybsdp on >> $logFile 2>&1
    service pybsdp start >> $logFile 2>&1
  else
    #chkconfig pybsdp off >> $logFile 2>&1
    service pybsdp stop >> $logFile 2>&1
  fi
  if [[ $start_afp == "true" ]]; then
    chkconfig netatalk on >> $logFile 2>&1
    service netatalk start >> $logFile 2>&1
  else
    chkconfig netatalk off >> $logFile 2>&1
    service netatalk stop >> $logFile 2>&1
  fi
  if [[ $start_nfs == "true" ]]; then
    chkconfig nfs on >> $logFile 2>&1
    service nfs start >> $logFile 2>&1
  else
    chkconfig nfs off >> $logFile 2>&1
    service nfs stop >> $logFile 2>&1
  fi
  if [[ $start_ldap == "true" ]]; then
    chkconfig slapd on >> $logFile 2>&1
    service slapd start >> $logFile 2>&1
  else
    chkconfig slapd off >> $logFile 2>&1
    service slapd stop >> $logFile 2>&1
  fi
  log "If you are installing NetSUSLP for the first time, please follow the documentation for setup instructions."
fi

clean-exit