#!/bin/sh

source logger.sh

logEventNoNewLine "Checking for a supported OS..."

if [ -f "/usr/bin/lsb_release" ]; then

ubuntuVersion=`lsb_release -s -d`

case $ubuntuVersion in
*Ubuntu\ 12.04*)
export detectedOS="Ubuntu"
;;
*Ubuntu\ 10.04*)
export detectedOS="Ubuntu"
;;
"*")
logEvent "Error: Did not detect a valid Ubuntu OS install."
failedAnyChecks=1
;;
esac


elif [ -f "/etc/system-release" ]; then

case "$(readlink /etc/system-release)" in
"centos-release")
    export detectedOS="CentOS"
    ;;
"redhat-release")
    if subscription-manager list | grep Status | grep -q 'Not Subscribed' ; then
        logevent "This system is not registered to Red Hat Subscription Management."
        failedAnyChecks=1
    fi
    export detectedOS="RedHat"
    ;;
"*")
    logEvent "Error: Did not detect a valid RedHat/Cent OS install."
    failedAnyChecks=1
    ;;
esac

else
logEvent "Error: Did not detect a valid OS."
failedAnyChecks=1
fi