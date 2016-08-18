# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import time
import datetime
import re
import signal
from optparse import OptionParser
from os.path import join
import json
import struct
import random
import bluepy.btle as btle

try:
	from jeedom.jeedom import *
except ImportError:
	print "Error: importing module jeedom.jeedom"
	sys.exit(1)

# ----------------------------------------------------------------------------

DOTTIS = {}

def connect(mac=None):
	maxRetry = 3
	global DOTTIS
	logging.debug("Get bluettoth connection for : " + str(mac))
	if mac in DOTTIS and not DOTTIS[mac]['connection'] is None : 
		logging.debug("Found it in cache")
		return

	if mac not in DOTTIS:
		DOTTIS[mac] = {}
		DOTTIS[mac]['connection'] = None
	DOTTIS[mac]['display'] = {}
	i=0
	while True:
		i = i + 1
		try:
			logging.debug("("+str(i)+") Try connect to " + str(mac))
			DOTTIS[mac]['connection'] = btle.Peripheral(mac,iface=_device)
			break
		except Exception as err:
			if i >= maxRetry :
				logging.error('Connection error on '+ str(mac) +' => '+str(err))
				return
			logging.debug("Wait " + str(i)+" second before retry")	
			time.sleep(i)

	logging.debug("Connection successfull on " + str(mac))		
	DOTTIS[mac]['characteristic'] = btle.Characteristic(DOTTIS[mac]['connection'], btle.UUID('fff3'), 0x29, 8, 0x2A)
	return

def disconnect(mac=None):
	logging.debug("Disconnect from : " + str(mac))
	if mac in DOTTIS and not DOTTIS[mac]['connection'] is None : 
		try:
			DOTTIS[mac]['connection'].disconnect()
			DOTTIS[mac]['connection'] = None
			logging.debug('Disconnection successfull')
		except Exception as err:
			logging.error('Disconnection error on '+ str(mac)+' => '+str(err))

def write(mac=None,message=None):
	global DOTTIS
	if mac is None or message is None:
		logging.error('[write] mac and message arg can not be null')
		return
	logging.debug('Write message into '+str(mac))
	if not mac in DOTTIS or DOTTIS[mac]['connection'] is None:
		connect(mac)

	if not mac in DOTTIS or DOTTIS[mac]['connection'] is None:
		raise Exception("Can not found or connect to "+str(mac))
		return

	try:
		DOTTIS[mac]['characteristic'].write(message)
	except Exception as err:
		time.sleep(0.05)
		try:
			disconnect(mac)
			connect(mac)
			DOTTIS[mac]['characteristic'].write(message)
		except Exception as err:
			logging.error('Write error on '+ str(mac)+' => '+str(err))

# ----------------------------------------------------------------------------

def display(mac=None,data=None):
	global DOTTIS
	if mac is None or data is None:
		logging.error('[display] mac and data arg can not be null')
		return
	logging.debug('Write display into '+str(mac))
	save_pixel=0
	total_pixel=0
	if 'display' not in DOTTIS[mac]:
		DOTTIS[mac]['display'] = {}

	temp={}
	maxint=0
	maxhex='#FFFFFF'
	if len(data) == 64:
		for pixel, value in data.iteritems():
			shex = rgb_to_hex((int(value['0']), int(value['1']), int(value['2'])));
			if shex not in temp:
				temp[shex] = 1
			else:
				temp[shex] = temp[shex] + 1	
			if temp[shex] > maxint:
				maxint = temp[shex]
				maxhex = shex
		for pixel, value in data.iteritems():
			total_pixel = total_pixel + 1
			if int(pixel) in DOTTIS[mac]['display'] and DOTTIS[mac]['display'][int(pixel)] == rgb_to_hex((int(value['0']), int(value['1']), int(value['2']))):
				save_pixel = save_pixel + 1

		if  (maxint+1) > save_pixel and maxint > 2:
			logging.debug('I use color all screen method to improve display speed')
			color(mac,hex_to_rgb(maxhex))

	save_pixel=0
	total_pixel=0
	for pixel, value in data.iteritems():
		total_pixel = total_pixel + 1
		if int(pixel) in DOTTIS[mac]['display'] and DOTTIS[mac]['display'][int(pixel)] == rgb_to_hex((int(value['0']), int(value['1']), int(value['2']))):
			save_pixel = save_pixel + 1
			continue
		write(mac,struct.pack('<BBBBBB', 0x07, 0x02,int(pixel), int(value['0']), int(value['1']), int(value['2'])))
		DOTTIS[mac]['display'][int(pixel)] = rgb_to_hex((int(value['0']), int(value['1']), int(value['2'])))
		time.sleep(0.05)
	logging.debug('I save '+str(save_pixel)+'/'+str(total_pixel)+' pixel to write so '+str((save_pixel*100)/total_pixel)+'%')

def color(mac=None,data=None):
	global DOTTIS
	if mac is None or data is None:
		logging.error('[color] mac and data arg can not be null')
		return
	logging.debug('Write color into '+str(mac))
	if '0' in data:
		write(mac,struct.pack('<BBBBBB', 0x06, 0x01, int(data['0']), int(data['1']), int(data['2']), 0x00))
	if 0 in data:
		write(mac,struct.pack('<BBBBBB', 0x06, 0x01, data[0], data[1], data[2], 0x00))
	
	if 'display' not in DOTTIS[mac]:
		DOTTIS[mac]['display'] = {}

	for i in range(64): 
		if '0' in data:
			DOTTIS[mac]['display'][int(i)] = rgb_to_hex((int(value['0']), int(value['1']), int(value['2'])))
		if 0 in data:
			DOTTIS[mac]['display'][int(i)] = rgb_to_hex((data[0], data[1], data[2]))

def loadid(mac=None,loadid=None):
	global DOTTIS
	if mac is None or loadid is None:
		logging.error('[loadid] mac and loadid arg can not be null')
		return
	logging.debug('Load id '+str(saveid)+' into '+str(mac))
	write(mac,struct.pack('<BBBBBB', 0x06, 0x08, 0x02,int(loadid),0x00,0x00))
	DOTTIS[mac]['display'] = {}

def saveid(mac=None,saveid=None):
	global DOTTIS
	if mac is None or loadid is None:
		logging.error('[saveid] mac and saveid arg can not be null')
		return
	logging.debug('Save id '+str(saveid)+' into '+str(mac))
	write(mac,struct.pack('<BBBBBB', 0x06, 0x07, 0x02,int(saveid),0x00,0x00))

# ----------------------------------------------------------------------------

def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	if not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
		message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
		if message['apikey'] != _apikey:
			logging.error("Invalid apikey from socket : " + str(message))
			return
		if not 'mac' in message:
			logging.error("No mac address : " + str(message))
			return
		if not 'type' in message:
			logging.error("No type : " + str(message))
			return
		if not 'data' in message:
			logging.error("No data : " + str(message))
			return
		try:
			if message['type'] == 'display':
				display(message['mac'],message['data'])
			if message['type'] == 'loadid':
				loadid(message['mac'],message['data'])
			if message['type'] == 'saveid':
				saveid(message['mac'],message['data'])
			if message['type'] == 'color':
				color(message['mac'],message['data'])
		except Exception, e:
			logging.error('Send command to dotti error : '+str(e))

def listen():
	jeedom_socket.open()
	for mac in _macs:
		connect(mac)
	try:
		while 1:
			time.sleep(0.2)
			read_socket()
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------

def hex_to_rgb(value):
    value = value.lstrip('#')
    lv = len(value)
    return tuple(int(value[i:i + lv // 3], 16) for i in range(0, lv, lv // 3))

def rgb_to_hex(rgb):
    return '#%02x%02x%02x' % rgb

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Disconnect from all dotti")
	for mac in DOTTIS:
		if DOTTIS[mac]['connection'] is None:
			continue
		disconnect(mac)

	logging.debug("Removing PID file " + str(_pidfile))
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 55009
_socket_host = 'localhost'
_device = 'auto'
_pidfile = '/tmp/dottid.pid'
_apikey = ''
_macs = ''
_device = 0

for arg in sys.argv:
	if arg.startswith("--loglevel="):
		temp, _log_level = arg.split("=")
	elif arg.startswith("--socketport="):
		temp, _socket_port = arg.split("=")
	elif arg.startswith("--macs="):
		temp, _macs = arg.split("=")
	elif arg.startswith("--pidfile="):
		temp, _pidfile = arg.split("=")
	elif arg.startswith("--apikey="):
		temp, _apikey = arg.split("=")
	elif arg.startswith("--device="):
		temp, _device = arg.split("=")
		
_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('Start dottid')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Apikey : '+str(_apikey))
logging.info('Device : hci'+str(_device))
logging.info('Macs : '+str(_macs))

if not os.path.isfile(btle.helperExe):
	logging.error("Cannot find required executable '%s'" % btle.helperExe)
	shutdown()

if not _macs == '':
	_macs = string.split(_macs, ',')
else:
	_macs = []

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)	

try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception,e:
	logging.error('Fatal error : '+str(e))
	shutdown()