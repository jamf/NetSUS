#!/bin/bash

if [[ $(which apt-get 2>&-) != "" ]]; then

	logNoNewLine "Checking for required Ubuntu binaries..."

	# Ensure that the package lists are re-created to avoid installation failure
	rm -rf /var/lib/apt/lists/*
	# Update package lists
	apt-get -q update >> $logFile
	if [[ $? -ne 0 ]]; then
		log "Error: Failed to update package index files."
		exit 1
	fi

	# Checking for policycoreutils
	if [[ $(dpkg -s policycoreutils 2>&- | awk '/Status: / {print $NF}') != "installed" ]]; then
		apt-get -qq -y install policycoreutils >> $logFile
		if [[ $? -ne 0 ]]; then
			log "Error: Failed to install policycoreutils."
			exit 1
		fi
	fi

	log "OK"

fi

exit 0
