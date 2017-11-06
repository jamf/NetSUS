#!/bin/bash

logNoNewLine "Checking for a 64-bit OS..."

archVersion=$(uname -m)

if [[ ${archVersion} != 'x86_64' && ${archVersion} != 'ia64' ]]; then
	log "Error: Did not detect a 64-bit kernel (Detected $archVersion)."
	exit 1
fi

log "OK"

exit 0
