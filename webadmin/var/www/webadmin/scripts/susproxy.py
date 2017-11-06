#!/usr/bin/env python
import sys, plistlib

p = plistlib.readPlist(sys.argv[2])
try:
	p["AdditionalCurlOptions"]
except:
	p["AdditionalCurlOptions"] = list()
p["AdditionalCurlOptions"].append(sys.argv[1])
plistlib.writePlist(p, sys.argv[2])


