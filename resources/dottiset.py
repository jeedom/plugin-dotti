#!/usr/bin/env python2.7

# Copyright (c) 2016 Martin F. Falatic

from __future__ import print_function

"""Bluetooth LE Python interface to the Witti Dotti device"""

import sys
import os
import time
import struct
import random
import bluepy.btle as btle
import json

if len(sys.argv) < 2:
	sys.exit("Usage:\n  %s <mac-address> [<address-type>]" % sys.argv[0])

if not os.path.isfile(btle.helperExe):
	raise ImportError("Cannot find required executable '%s'" % btle.helperExe)

device_mac = sys.argv[1]
if len(sys.argv) == 3:
	address_type = sys.argv[2]
else:
	address_type = btle.ADDR_TYPE_PUBLIC

json_path = '/tmp/dotti'+device_mac.replace(':','')+'.json'

if not os.path.exists(json_path):
	sys.exit("Config file not found : "+json_path)

with open(json_path) as data_file:    
	display = json.load(data_file)


conn = btle.Peripheral(device_mac, address_type)
try:
	newch = btle.Characteristic(conn, btle.UUID('fff3'), 0x29, 8, 0x2A)
	for pixel in display['data']:
		newch.write(struct.pack('<BBBBBB', 0x07, 0x02,int(pixel), int(display['data'][pixel]['0']), int(display['data'][pixel]['1']), int(display['data'][pixel]['2'])))
		time.sleep(0.20)
except Exception as err:
	print(err)

conn.disconnect()
print('OK');