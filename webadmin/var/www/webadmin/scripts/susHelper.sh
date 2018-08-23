#!/bin/bash

case ${1} in

repoSync)
/var/appliance/sus_sync.py > /dev/null 2>&1 &
;;

getSyncStatus)
if ps ax | grep -v grep | grep -q repo_sync ; then
	echo "true"
fi
;;

getUtilStatus)
if ps ax | grep -v grep | grep -q repoutil ; then
	echo "true"
fi
;;

getLastSync)
echo $(stat -c "%Y" $(ls -t /srv/SUS/html/content/catalogs/*.sucatalog* 2>/dev/null | head -1) 2>/dev/null)
;;

getBranchlist)
echo $(/var/lib/reposado/repoutil --branches)
;;

rootBranch)
# $2: Branch
rm -f /var/www/html/*.sucatalog
if [ "${2}" != '' ]; then
	catalogArray="/srv/SUS/html/content/catalogs/others/index*_${2}.sucatalog"
	for i in ${catalogArray}; do
		catalogName="$(basename "${i}" "_${2}.sucatalog")"
		ln -fs "/srv/SUS/html/content/catalogs/others/${catalogName}_${2}.sucatalog" "/var/www/html/${catalogName}.sucatalog"
	done
	if [ -f "/srv/SUS/html/content/catalogs/index_${2}.sucatalog" ]; then
		ln -fs "/srv/SUS/html/content/catalogs/index_${2}.sucatalog" "/var/www/html/index.sucatalog"
	fi
fi
;;

deleteBranch)
# $2: Branch
echo y | /var/lib/reposado/repoutil --delete-branch ${2}
;;

createBranch)
# $2: Branch
/var/lib/reposado/repoutil --new-branch ${2}
;;

copyBranch)
# $2: Source Branch
# $3: Dest Branch
/var/lib/reposado/repoutil --copy-branch ${2} ${3}
;;

numBranches)
echo $(/var/lib/reposado/repoutil --branches | wc | awk '{print $1}')
;;

repoPurge)
/var/lib/reposado/repoutil --purge-product=all-deprecated > /dev/null 2>&1 &
;;

setBaseUrl)
# $2: URL
if [ "${2}" != '' ]; then
	/var/appliance/sus_prefs.py write LocalCatalogURLBase ${2}
else
	/var/appliance/sus_prefs.py delete LocalCatalogURLBase
fi
;;

setSchedule)
# $2: Hour
tmpfile=$(mktemp /tmp/crontab.XXXXXX)
crontab -l 2>/dev/null | grep -v "sus_sync.py" > ${tmpfile}
echo "0 ${2} * * * /var/appliance/sus_sync.py > /dev/null 2>&1" >> ${tmpfile}
crontab ${tmpfile}
rm ${tmpfile}
;;

delSchedule)
tmpfile=$(mktemp /tmp/crontab.XXXXXX)
crontab -l 2>/dev/null | grep -v "sus_sync.py" > ${tmpfile}
crontab ${tmpfile}
rm ${tmpfile}
;;

#getPrefs)
#/var/appliance/sus_prefs.py json
#;;

getPref)
# $2: Key
echo $(/var/appliance/sus_prefs.py read ${2})
;;

getProxy)
proxy=$(/var/appliance/sus_prefs.py read AdditionalCurlOptions | grep '^proxy' | grep -v '^proxy-user' | cut -d \" -f 2)
proxyuser=$(/var/appliance/sus_prefs.py read AdditionalCurlOptions | grep '^proxy-user' | cut -d \" -f 2)
echo "${proxy}:${proxyuser}"
;;

setProxy)
# $2: host
# $3: port
# $4: user
# $5: pass
if [ "${5}" != '' ]; then
	/var/appliance/sus_prefs.py write AdditionalCurlOptions "proxy = \"${2}:${3}\"" "proxy-user = \"${4}:${5}\""
elif [ "${3}" != '' ]; then
	/var/appliance/sus_prefs.py write AdditionalCurlOptions "proxy = \"${2}:${3}\""
else
	/var/appliance/sus_prefs.py delete AdditionalCurlOptions
fi
;;

getCatalogURLs)
echo $(/var/appliance/sus_prefs.py read AppleCatalogURLs)
;;

setCatalogURLs)
# $2: Catalog URLs
/var/appliance/sus_prefs.py write AppleCatalogURLs ${2}
;;

setLogFile)
# $2: Log File
/var/appliance/sus_prefs.py write RepoSyncLogFile ${2}
;;

productList)
/var/appliance/sus_info.py --products
;;

productInfo)
# $2: Product ID
/var/appliance/sus_info.py --details=${2}
;;

addProducts)
# $2: Product ID(s)
# $3: Branch
/var/lib/reposado/repoutil --add-product=${2} ${3}
;;

esac