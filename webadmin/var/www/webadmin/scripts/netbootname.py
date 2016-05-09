#!/usr/bin/env python
import sys, plistlib

p = plistlib.readPlist(sys.argv[2])
p["Name"] = sys.argv[1]
plistlib.writePlist(p, sys.argv[2])


