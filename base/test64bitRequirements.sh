#!/bin/sh

source logger.sh

logEventNoNewLine "Checking for a 64-bit OS..."

archVersion=`uname -m`

if [[ ${archVersion} != 'x86_64' && ${archVersion} != 'ia64' ]]; then
	logEvent "Error: Did not detect a 64-bit kernel (Detected $archVersion)."
	exit 1
fi

logEvent "OK"

exit 0 