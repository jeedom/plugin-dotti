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
	global DOTTIS
	logging.debug("Get bluettoth connection for : " + str(mac))
	if mac in DOTTIS and not DOTTIS[mac]['connection'] is None : 
		logging.debug("Found it in cache")
		return DOTTIS[mac]['connection']

	if mac in DOTTIS:
		DOTTIS[mac] = {}

	if mac in _macs : 
		try:
			logging.debug("(1) Try connect to " + str(mac))
			DOTTIS[mac]['connection'] = btle.Peripheral(mac, btle.ADDR_TYPE_PUBLIC)
		except Exception as err:
			time.sleep(1)
			try:
				logging.debug("(2) Try connect to " + str(mac))
				DOTTIS[mac]['connection'] = btle.Peripheral(mac, btle.ADDR_TYPE_PUBLIC)
			except Exception as err:
				logging.error('Connection error on '+ str(mac) +' => '+str(err))
				return None
		logging.debug("Connection successfull on " + str(mac))		
		DOTTIS[mac]['characteristic'] = btle.Characteristic(DOTTIS[mac], btle.UUID('fff3'), 0x29, 8, 0x2A)
		return DOTTIS[mac]['connection']
	logging.error('Device not allow : '+str(mac))
	return None

def disconnect(mac=None):
	logging.debug("Disconnect from : " + str(mac))
	if mac in DOTTIS and not DOTTIS[mac]['connection'] is None : 
		try:
			DOTTIS[mac]['connection'].disconnect()
			DOTTIS[mac]['connection'] = None
			logging.error('Disconnection successfull')
		except Exception as err:
			logging.error('Disconnection error on '+ str(mac)+' => '+str(err))

# ----------------------------------------------------------------------------

def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	if not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
		message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
		if message['apikey'] != _apikey:
			logging.error("Invalid apikey from socket : " + str(message))
			return
		try:
			print 'read'
		except Exception, e:
			logging.error('Send command to dotti error : '+str(e))

def listen():
	jeedom_socket.open()
	for mac in _macs:
		connect(mac)
	try:
		while 1:
			time.sleep(0.5)
			read_socket()
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Disconnect from all dotti")
	for mac in DOTTIS:
		if DOTTIS[mac] is None:
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
logging.info('Device : '+str(_device))
logging.info('Macs : '+str(_macs))

if not os.path.isfile(btle.helperExe):
	raise ImportError("Cannot find required executable '%s'" % btle.helperExe)

if _macs == '':
	logging.error('Macs can not be empty')
	shutdown()
else:
	_macs = string.split(_macs, ',')

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)	

try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception,e:
	logging.error('Fatal error : '+str(e))
	shutdown()