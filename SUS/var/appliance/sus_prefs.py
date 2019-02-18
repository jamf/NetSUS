#!/usr/bin/env python

# sys.argv[1]: action
# sys.argv[2]: key
# sys.argv[3:]: value(s)

import sys
from plistlib import readPlist, writePlist
from json import dumps

actions = [
	"read",
	"write",
	"delete",
	"json"
]
try:
	action = sys.argv[1]
	if (action not in actions):
		exit(1)
except:
	exit(1)

plist = "/var/lib/reposado/preferences.plist"
try:
	plistObj = readPlist(plist)
except:
	plistObj = {}

if (action == "json"):
	print(dumps(plistObj))
else:
	keys = [
		"UpdatesRootDir",
		"UpdatesMetadataDir",
		"LocalCatalogURLBase",
		"AdditionalCurlOptions",
		"AppleCatalogURLs",
		"PreferredLocalizations",
		"CurlPath",
		"RepoSyncLogFile",
		"HumanReadableSizes"
	]
	try:
		key = sys.argv[2]
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
			for string in value: print(string)
		else:
			print(value)


	if (action == "write"):
		if (key in [ "HumanReadableSizes" ]):
			try:
				if (sys.argv[3].lower() in [ "true", "yes", "1" ]):
					value = True
				elif (sys.argv[3].lower() in [ "false", "no", "0" ]):
					value = False
				else:
					value = None
			except:
				value = None
		elif (key in [ "AdditionalCurlOptions", "AppleCatalogURLs", "PreferredLocalizations" ]):
			try:
				value =	sys.argv[3:]
			except:
				value = []
		else:
			try:
				value =	sys.argv[3]
			except:
				value = ""
		if (value is not None):
			plistObj[key] = value
			writePlist(plistObj, plist)


	if (action == "delete"):
		try:
			del plistObj[key]
		except KeyError:
			exit(0)
		writePlist(plistObj, plist)
