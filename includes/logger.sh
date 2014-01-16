#!/bin/bash

export logFile="/var/appliance/logs/applianceinstaller.log"

# Logger function
logEvent(){
	if [ ! -f "$logFile" ]; then
		mkdir -p "$(dirname $logFile)"
	fi
	echo $1
	echo $(date "+[%Y-%m-%d %H:%M:%S]: ") $1 >> $logFile
}

logEventCritical(){
	if [ ! -f "$logFile" ]; then
		mkdir -p "$(dirname $logFile)"
	fi
	echo $1
	echo $(date "+[%Y-%m-%d %H:%M:%S]: ") $1 >> $logFile
}

logEventNoNewLine(){
	if [ ! -f "$logFile" ]; then
		mkdir -p "$(dirname $logFile)"
	fi
    	echo -n $1
	echo $(date "+[%Y-%m-%d %H:%M:%S]: ") $1 >> $logFile
}
