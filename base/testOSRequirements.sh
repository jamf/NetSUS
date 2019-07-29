#!/bin/bash

logNoNewLine "Checking for a supported OS..."

if [ -f "/etc/os-release" ]; then
	source /etc/os-release
elif [ -e "/etc/system-release" ]; then
	NAME=$(sed -e 's/ release.*//' /etc/system-release)
	PRETTY_NAME=$(sed -e 's/ release//' /etc/system-release)
	VERSION_ID=$(sed -e 's/.*release //;s/ .*//' /etc/system-release)
fi
if [[ -z "$NAME" ]]; then
	NAME=$(uname -s)
fi

case $NAME in
"Ubuntu")
	if [[ "$VERSION_ID" == "14.04" ]] || [[ "$VERSION_ID" == "16.04" ]] || [[ "$VERSION_ID" == "18.04" ]] ; then
		log "$PRETTY_NAME found"
		exit 0
	else
		log "Error: $NAME version must be 14.04, 16.04 or 18.04 (Detected $VERSION_ID)."
		exit 1
	fi
;;
"Red Hat Enterprise Linux"*|"CentOS"*)
	IFS='.'
	VERSION_ARR=( $VERSION_ID )
	unset IFS
	if [[ ${VERSION_ARR[0]} -eq 6 ]] && [[ ${VERSION_ARR[1]} -gt 3 ]] || [[ ${VERSION_ARR[0]} -gt 6 ]] ; then
		log "$PRETTY_NAME found"
		exit 0
	else
		log "Error: $NAME version must be 6.4 or later (Detected $VERSION_ID)."
		exit 1
	fi
	if yum repolist | grep repolist | grep -q ': 0'; then
        log "Error: This system is does not have any available repositories."
		exit 1
    fi
;;
*)
	release=$(rpm -q --queryformat '%{RELEASE}' rpm | cut -d '.' -f 2)
	if [[ $release == "el6" ]] || [[ $release == "el7" ]] || [[ $release == "el8" ]] ; then
		IFS='.'
		VERSION_ARR=( $VERSION_ID )
		unset IFS
		if [[ ${VERSION_ARR[0]} -eq 6 ]] && [[ ${VERSION_ARR[1]} -gt 3 ]] || [[ ${VERSION_ARR[0]} -gt 6 ]] ; then
			log "$PRETTY_NAME found"
			log "Warning: $NAME is a Red Hat Enterprise Linux variant, proceed with caution."
			exit 0
		else
			log "Error: $NAME version must be 6.4 or later (Detected $VERSION_ID)."
			exit 1
		fi
	fi
	log "Error: Did not detect a valid Ubuntu/Red Hat/CentOS install (Detected $NAME)."
	exit 1
;;
esac
