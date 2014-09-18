#!/bin/sh

source logger.sh

unset detectedOS
logEventNoNewLine "Checking for a supported OS..."

if [ -f "/usr/bin/lsb_release" ]; then

ubuntuVersion=`lsb_release -s -d`

case $ubuntuVersion in
*"Ubuntu 14.04"*)
detectedOS="Ubuntu"
export detectedOS
;;
*"Ubuntu 12.04"*)
detectedOS="Ubuntu"
export detectedOS
;;
*"Ubuntu 10.04"*)
detectedOS="Ubuntu"
export detectedOS
;;
esac

fi

if [ -f "/etc/system-release" ] &&  [ -z "${detectedOS}" ]; then

case "$(readlink /etc/system-release)" in
"centos-release")
    detectedOS="CentOS"
    export detectedOS
    ;;
"redhat-release")
	if subscription-manager list | grep Status | grep -q 'Not Subscribed' ; then
		dependencies=( php php-xml mod_ssl ntpdate dialog avahi netatalk samba tftp-server vim-common )
		for dependency in "${dependencies[@]}" ; do
			if ! rpm -qa "$dependency" | grep -q "$dependency" ; then
				logevent "This system is not registered to Red Hat Subscription Management."
				failedAnyChecks=1
				break
			fi
		done
	fi
    detectedOS="RedHat"
    export detectedOS
    ;;
esac

fi

if [ "${detectedOS}" != 'Ubuntu' ] && [ "${detectedOS}" != 'RedHat' ] && [ "${detectedOS}" != 'CentOS' ]; then
	logEvent "Error: Did not detect a valid Ubuntu/RedHat/Cent OS install."
	failedAnyChecks=1
fi