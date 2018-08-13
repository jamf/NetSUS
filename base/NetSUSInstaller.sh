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
#echo "
## Disable IPv6
#net.ipv6.conf.all.disable_ipv6 = 1
#net.ipv6.conf.default.disable_ipv6 = 1
#" >> /etc/sysctl.conf

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

if [[ $(which update-rc.d 2>&-) != "" ]]; then
  service apparmor restart >> $logFile 2>&1
  service apache2 restart >> $logFile 2>&1
  service slapd stop >> $logFile 2>&1
  service netatalk stop >> $logFile 2>&1
  service smbd stop >> $logFile 2>&1
  service tftpd-hpa stop >> $logFile 2>&1
  # service openbsd-inetd stop >> $logFile 2>&1
  update-rc.d slapd disable >> $logFile 2>&1
  update-rc.d netatalk disable >> $logFile 2>&1
  if [[ $(which systemctl 2>&-) != "" ]]; then
    update-rc.d smbd disable >> $logFile 2>&1
    update-rc.d tftpd-hpa disable >> $logFile 2>&1
    systemctl disable nfs-server >> $logFile 2>&1
    # systemctl disable openbsd-inetd >> $logFile 2>&1
    service nfs-server stop >> $logFile 2>&1
  else
    echo manual > /etc/init/smbd.override
    echo manual > /etc/init/tftpd-hpa.override
    update-rc.d nfs-kernel-server disable >> $logFile 2>&1
    # update-rc.d openbsd-inetd disable >> $logFile 2>&1
    service nfs-kernel-server stop >> $logFile 2>&1
  fi
  log "If you are installing NetSUSLP for the first time, please follow the documentation for setup instructions."
elif [[ $(which chkconfig 2>&-) != "" ]]; then
  service httpd restart >> $logFile 2>&1
  chkconfig tftp off >> $logFile 2>&1
  chkconfig nfs off > /dev/null 2>&1
  #if [ -f "/etc/sysconfig/xinetd" ]; then
  #  service xinetd restart >> $logFile 2>&1
  #fi
  log "If you are installing NetSUSLP for the first time, please follow the documentation for setup instructions."
fi

clean-exit