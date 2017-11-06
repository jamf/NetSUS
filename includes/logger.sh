#!/bin/bash

export logFile="/var/appliance/logs/applianceinstaller.log"

mkLogDir() {
    if [ ! -f "$logFile" ]
    then
        mkdir -p "$(dirname $logFile)"
    fi
}

timestamp() {
    date "+[%Y-%m-%d %H:%M:%S]: "
}

logToFile() {
    echo "$(timestamp)" "$1" >> $logFile
}

log(){
    mkLogDir

    if [[ $INTERACTIVE = true ]]
    then
        echo "$@"
    fi

    logToFile "${@: -1}"
}

logCritical(){
    INTERACTIVE=true log "$1"
}

logNoNewLine(){
    log -n "$1"
}

export -f log logCritical logNoNewLine logToFile timestamp mkLogDir
