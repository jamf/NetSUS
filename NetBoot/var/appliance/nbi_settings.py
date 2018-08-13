#!/usr/bin/env python

# sys.argv[1]: nbi
# sys.argv[2]: action
# sys.argv[3]: key
# sys.argv[4:]: value(s)

import sys, os
from plistlib import readPlist, writePlist
from random import randint
from json import dumps

try:
	nbi = sys.argv[1]
except:
	exit(1)

actions = [
	"read",
	"write",
	"json"
]
try:
	action = sys.argv[2]
	if (action not in actions):
		exit(1)
except:
	exit(1)

plist =  nbi + "/NBImageInfo.plist"
try:
	plistObj = readPlist(plist)
except:
	plistObj = {
		"Name": os.path.basename(nbi).replace('.nbi', ''),
		"Description": os.path.basename(nbi).replace('.nbi', ''),
		"Index": randint(1, 4095),
		"BootFile": "booter",
		"RootPath": ''.join([i for i in os.listdir(nbi) if i.endswith('.dmg')]),
		"IsEnabled": False,
		"IsInstall": False,
		"IsDefault": False,
		"Type": "HTTP",
		"Kind": 1,
		"Architectures": [ "i386" ]
	}

if (action == "json"):
	print dumps(plistObj)
else:
	keys = [
		"Architectures",
		"BackwardCompatible",
		"BootFile",
		"Description",
		"DisabledMACAddresses",
		"DisabledSystemIdentifiers",
		"EnabledMACAddresses",
		"EnabledSystemIdentifiers",
		"Index",
		"IsDefault",
		"IsEnabled",
		"IsInstall",
		"Kind",
		"Language",
		"Name",
		"RootPath",
		"SupportsDiskless",
		"Type",
		"imageType",
		"osVersion"
	]
	try:
		key = sys.argv[3]
		if (key not in keys):
			exit(1)
	except:
		exit(1)


	if (action == "read"):
		try:
			value = plistObj[key]
		except KeyError:
			value = ""	
		if (type(value) is list):
			for string in value: print string
		else:
			print value


	if (action == "write"):
		if (key in [ "BackwardCompatible", "IsDefault", "IsEnabled", "IsInstall", "SupportsDiskless" ]):
			try:
				if (sys.argv[4].lower() in [ "true", "yes", "1" ]):
					value = True
				elif (sys.argv[4].lower() in [ "false", "no", "0" ]):
					value = False
				else:
					value = None
			except:
				value = None
		elif (key in [ "Architectures", "DisabledMACAddresses", "DisabledSystemIdentifiers", "EnabledMACAddresses", "EnabledSystemIdentifiers" ]):
			try:
				value =	sys.argv[4:]
			except:
				value = []
		else:
			try:
				value =	sys.argv[4]
			except:
				value = ""
		if (value is not None):
			plistObj[key] = value
			writePlist(plistObj, plist)
