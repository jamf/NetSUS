#!/bin/bash

if [[ $(which apt-get 2>&-) != "" ]]; then

	if ! grep -q '127.0.1.1' /etc/hosts; then
		sed -i "/127.0.0.1/a 127.0.1.1	$(hostname)" /etc/hosts
	fi
	if ! grep -q "$(hostname)" /etc/hosts; then
		sed -i "s/127.0.1.1.*/127.0.1.1	$(hostname)/" /etc/hosts
	fi

	logNoNewLine "Checking for required Ubuntu binaries..."

	# Ensure that the package lists are re-created to avoid installation failure
	# rm -rf /var/lib/apt/lists/*
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
