#!/usr/bin/env python
import sys, plistlib

try:
	RootPath = sys.argv[2]
except:
	RootPath = 'NetBoot.dmg'
try:
	Name = sys.argv[3]
except:
	Name = 'Faux NetBoot'
try:
	Description = sys.argv[4]
except:
	Description = ''
try:
	Type = sys.argv[5]
except:
	Type = 'HTTP'
try:
	Index = int(sys.argv[6])
except:
	Index = 526
try:
	SupportsDiskless = sys.argv[7] == 'True'
except:
	SupportsDiskless = False

try:
	p = plistlib.readPlist(sys.argv[1])
except:
	p = {}
try:
	p['Architectures']
except:
	p['Architectures'] = ['i386']
try:
	p['BackwardCompatible']
except:
	p['BackwardCompatible'] = False
try:
	p['BootFile']
except:
	p['BootFile'] = 'booter'
try:
	p['Description']
except:
	p['Description'] = ''
try:
	p['DisabledSystemIdentifiers']
except:
	p['DisabledSystemIdentifiers'] = ['MacBook1,1', 'MacBook2,1', 'MacBook3,1', 'MacBook4,1', 'MacBook5,1', 'MacBook5,2', 'MacBook6,1', 'MacBook7,1', 'MacBook8,1', 'MacBook9,1', 'MacBookAir1,1', 'MacBookAir2,1', 'MacBookAir3,1', 'MacBookAir3,2', 'MacBookAir4,1', 'MacBookAir4,2', 'MacBookAir5,1', 'MacBookAir5,2', 'MacBookAir6,1', 'MacBookAir6,2', 'MacBookAir7,1', 'MacBookAir7,2', 'MacBookPro1,1', 'MacBookPro1,2', 'MacBookPro2,1', 'MacBookPro2,2', 'MacBookPro3,1', 'MacBookPro4,1', 'MacBookPro5,1', 'MacBookPro5,2', 'MacBookPro5,3', 'MacBookPro5,4', 'MacBookPro5,5', 'MacBookPro6,1', 'MacBookPro6,2', 'MacBookPro7,1', 'MacBookPro8,1', 'MacBookPro8,2', 'MacBookPro8,3', 'MacBookPro9,1', 'MacBookPro9,2', 'MacBookPro10,1', 'MacBookPro10,2', 'MacBookPro11,1', 'MacBookPro11,2', 'MacBookPro11,3', 'MacBookPro11,4', 'MacBookPro11,5', 'MacBookPro12,1', 'MacBookPro13,1', 'MacBookPro13,2', 'MacBookPro13,3', 'MacPro1,1', 'MacPro1,1,Quad', 'MacPro2,1', 'MacPro3,1', 'MacPro4,1', 'MacPro5,1', 'MacPro6,1', 'Macmini1,1', 'Macmini2,1', 'Macmini3,1', 'Macmini4,1', 'Macmini5,1', 'Macmini5,2', 'Macmini5,3', 'Macmini6,1', 'Macmini6,2', 'Macmini7,1', 'PowerBook1,1', 'PowerBook2,1', 'PowerBook2,2', 'PowerBook2,3', 'PowerBook3,1', 'PowerBook3,2', 'PowerBook3,3', 'PowerBook3,4', 'PowerBook3,5', 'PowerBook4,1', 'PowerBook4,2', 'PowerBook4,3', 'PowerBook4,4', 'PowerBook5,1', 'PowerBook5,2', 'PowerBook5,3', 'PowerBook5,4', 'PowerBook5,5', 'PowerBook5,6', 'PowerBook5,7', 'PowerBook5,8', 'PowerBook5,9', 'PowerBook6,1', 'PowerBook6,2', 'PowerBook6,3', 'PowerBook6,4', 'PowerBook6,5', 'PowerBook6,7', 'PowerBook6,8', 'PowerMac1,1', 'PowerMac1,2', 'PowerMac2,1', 'PowerMac2,2', 'PowerMac3,1', 'PowerMac3,2', 'PowerMac3,3', 'PowerMac3,4', 'PowerMac3,5', 'PowerMac3,6', 'PowerMac4,1', 'PowerMac4,2', 'PowerMac4,4', 'PowerMac4,5', 'PowerMac5,1', 'PowerMac5,2', 'PowerMac6,1', 'PowerMac6,3', 'PowerMac6,4', 'PowerMac7,2', 'PowerMac7,3', 'PowerMac8,1', 'PowerMac8,2', 'PowerMac9,1', 'PowerMac10,1', 'PowerMac10,2', 'PowerMac11,2', 'PowerMac11,2,Quad', 'PowerMac12,1', 'RackMac1,1', 'RackMac1,2', 'RackMac3,1', 'Xserve1,1', 'Xserve2,1', 'Xserve3,1', 'iMac4,1', 'iMac4,2', 'iMac5,1', 'iMac5,2', 'iMac6,1', 'iMac7,1', 'iMac8,1', 'iMac9,1', 'iMac10,1', 'iMac11,1', 'iMac11,2', 'iMac11,3', 'iMac12,1', 'iMac12,2', 'iMac13,1', 'iMac13,2', 'iMac13,3', 'iMac14,1', 'iMac14,2', 'iMac14,3', 'iMac14,4', 'iMac15,1', 'iMac16,1', 'iMac16,2', 'iMac17,1']
try:
	p['EnabledSystemIdentifiers']
except:
	p['EnabledSystemIdentifiers'] = []
try:
	p['Index']
except:
	p['Index'] = 526
try:
	p['IsDefault']
except:
	p['IsDefault'] = False
try:
	p['IsEnabled']
except:
	p['IsEnabled'] = True
try:
	p['IsInstall']
except:
	p['IsInstall'] = False
try:
	p['Kind']
except:
	p['Kind'] = 1
try:
	p['Language']
except:
	p['Language'] = 'Default'
try:
	p['Name']
except:
	p['Name'] = 'Faux NetBoot'
try:
	p['RootPath']
except:
	p['RootPath'] = 'NetBoot.dmg'
try:
	p['SupportsDiskless']
except:
	p['SupportsDiskless'] = False
try:
	p['Type']
except:
	p['Type'] = 'HTTP'
try:
	p['imageType']
except:
	p['imageType'] = 'netboot'
try:
	p['osVersion']
except:
	p['osVersion'] = '10.12'

p['Name'] = Name
p['Description'] = Description
p['Type'] = Type
p['Index'] = Index
p['SupportsDiskless'] = SupportsDiskless
p['RootPath'] = RootPath

plistlib.writePlist(p, sys.argv[1])
