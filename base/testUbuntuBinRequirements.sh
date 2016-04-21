#!/bin/sh

source logger.sh

logEventNoNewLine "Checking for required Ubuntu binaries"

# checking for policycoreutils
pcucheck=`dpkg -s policycoreutils | awk '/Package: / {print $2}'`
[[ "${pcucheck}" != "policycoreutils" ]] && { sudo apt-get install policycoreutils; }

# checking for gawk
pcucheck=`dpkg -s gawk | awk '/Package: / {print $2}'`
[[ "${pcucheck}" != "gawk" ]] && { sudo apt-get install gawk; }

exit 0
