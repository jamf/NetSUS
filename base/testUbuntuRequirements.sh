#!/bin/sh

source logger.sh

logEventNoNewLine "Checking for a Ubuntu OS..."

if [ -f "/usr/bin/lsb_release" ]; then

ubuntuVersion=`lsb_release -s -d`

case $ubuntuVersion in
*Ubuntu\ 12.04*)
exit 0
;;
*Ubuntu\ 10.04*)
exit 0
;;
esac

logEvent "Error: Did not detect Ubuntu (Detected $ubuntuVersion)."



exit 1

else
logEvent "Error: Did not detect Ubuntu."
exit 1
fi