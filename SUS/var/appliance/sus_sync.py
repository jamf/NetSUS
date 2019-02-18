#!/usr/bin/env python
# Copyright (C) 2012, JAMF Software, LLC
# All rights reserved.
# 
# SUPPORT FOR THIS PROGRAM
# 
# This program is distributed "as is" by JAMF Software, LLC.  For more information or support for the appliance, please utilize the following resources:
# 
# 	https://jamfnation.jamfsoftware.com/
# 
# Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
# 
# * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
# 
# * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
# 
# * Neither the name of the JAMF Software, LLC nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY JAMF SOFTWARE, LLC "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMF SOFTWARE, LLC BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

import xml.dom.minidom
import os, sys, glob
import time

if os.access("/var/run/lockfile.sus_sync.lock", os.F_OK):
    #if the lockfile is already there then check the PID number
    #in the lock file
    pidfile = open("/var/run/lockfile.sus_sync.lock", "r")
    pidfile.seek(0)
    old_pid = pidfile.readline()
    if os.path.exists("/proc/%s" % old_pid):
        print("You already have an instance of the program running")
        print("It is running as process %s," % old_pid)
        sys.exit(1)
    else:
        print("File is there but the program is not running")
        print("Removing lock file for the: %s as it can be there because of the program last time it was run" % old_pid)
        os.remove("/var/run/lockfile.sus_sync.lock")


pidfile = open("/var/run/lockfile.sus_sync.lock", "w")
pidfile.write("%s" % os.getpid())
pidfile.close()

global instarr
instarr = []
strRootBranch = ""

def handleXML(XML):
    entries = XML.getElementsByTagName("autosyncbranches")
    handleEntries(entries)

def handleRootBranch(XML):
    handleRootBranchName(XML.getElementsByTagName("rootbranch"))

def handleRootBranchName(names):
    global strRootBranch
    for item in names:
        strRootBranch = getText(item.childNodes)

def handleEntries(entries):
    for entry in entries:
        handleEntry(entry)

def handleEntry(entry):
    handleEntryName(entry.getElementsByTagName("branch"))

def handleEntryName(names):
    for item in names:
        instarr.append(getText(item.childNodes)) 

def getText(nodelist):
    rc = []
    for node in nodelist:
        if node.nodeType == node.TEXT_NODE:
            rc.append(node.data)
    return ''.join(rc)

def sync_sus():
    print("Syncing SUS")
    os.system("/var/lib/reposado/repo_sync")

    catalogArray=glob.glob("/srv/SUS/html/content/catalogs/others/index*_" + strRootBranch + ".sucatalog")
    for i in catalogArray:
    	catalogname = str.replace(str(i), "_" + str(strRootBranch), "")
    	catalogname = str.replace(str(catalogname), "/srv/SUS/html/content/catalogs/others/", "")
    	os.system("ln -fs " + i + " /var/www/html/" + catalogname)
	
	if os.path.isfile("/srv/SUS/html/content/catalogs/index_" + strRootBranch + ".sucatalog"):
	    os.system("ln -fs /srv/SUS/html/content/catalogs/index_" + strRootBranch + ".sucatalog /var/www/html/index.sucatalog")

def enable_all_sus():
    print("Enabling new updates")
    for inst in instarr:
        print("Adding all updates to: " + inst)
        os.system("/var/lib/reposado/repoutil --add-product all " + inst)
    catalogArray=glob.glob("/srv/SUS/html/content/catalogs/others/index*_" + strRootBranch + ".sucatalog")
    for i in catalogArray:
    	catalogname = str.replace(str(i), "_" + str(strRootBranch), "")
    	catalogname = str.replace(str(catalogname), "/srv/SUS/html/content/catalogs/others/", "")
    	os.system("ln -fs " + i + " /var/www/html/" + catalogname)

	if os.path.isfile("/srv/SUS/html/content/catalogs/index_" + strRootBranch + ".sucatalog"):
	    os.system("ln -fs /srv/SUS/html/content/catalogs/index_" + strRootBranch + ".sucatalog /var/www/html/index.sucatalog")

def sync_time():
	XML = xml.dom.minidom.parse('/var/appliance/conf/appliance.conf.xml')
	for node in XML.getElementsByTagName("lastsussync"):
		node.parentNode.removeChild(node)
	lastsussync = XML.createElement("lastsussync")
	time_int = int(time.time())
	timestamp = XML.createTextNode(str(time_int))
	lastsussync.appendChild(timestamp)
	XML.childNodes[0].appendChild(lastsussync)
	XML.writexml(open('/var/appliance/conf/appliance.conf.xml', 'w'))

try:
    dom = xml.dom.minidom.parse('/var/appliance/conf/appliance.conf.xml')
    handleXML(dom)
    handleRootBranch(dom)
except Exception:
    print("Oops!  We bailed while processing the XML.  Try again...")
    os.unlink("/var/run/lockfile.sus_sync.lock")
    sys.exit(1603)

try:
    sync_sus()
    sync_time()
    print("Finished SUS Sync")
except Exception:
    print("Unable to sync, what did you do!")
    os.unlink("/var/run/lockfile.sus_sync.lock")
    sys.exit(1603)

try:
    enable_all_sus()
    print("Finished enabling all updates for marked branches")
    os.unlink("/var/run/lockfile.sus_sync.lock")
except Exception:
    print("Unable to enable updates, what did you do!")
    os.unlink("/var/run/lockfile.sus_sync.lock")
    sys.exit(1603)
