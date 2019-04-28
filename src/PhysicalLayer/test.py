import socket
import os
import subprocess
import sys
import time
import re

host = '127.0.0.1'
port = 1051
package_size = 1024*1024

def init():
	command = ""
	
	try:
		startup_check()
	except socket.timeout:
		print ("\nTimeout: Server unavailable!\n")
		return
		
	print (
		'Physical Layer Test- Python\nServer running\n\nCommands:\n'+
		'test_encode <IP> <Msg>: Encode a test file and send Msg it to IP\n'
		'exit: Exit the program\n'
	)
	
	while(True):
		print ("Command >> ", end='')
		command = input()
		
		if command.split(' ')[0] == "test_encode":
			if len(command.split(' '))>2:
				test_encode_file(command.split(' ')[1], command.split(' ')[2])
				# test_decode_file(command.split(' ')[1], command.split(' ')[2])
			else:
				print("\nPlease type in an IP address and a message!\n")
		elif command == "exit":
			print("\nGoodbye!")
			return
		else:
			print("\nUnknown Command!\n")	


def startup_check():
	s = socket.socket()
	s.settimeout(1)
	s.connect((host, port))
	s.settimeout(None)
	
	s.send(bytes("Hello", encoding='utf-8'))
	data = s.recv(1024)
	if data.decode('utf-8') != "Hi":
		raise socket.timeout
	s.close()
	
def test_encode_file(ip, msg):
	s = socket.socket()
	filename='file.txt'
	
	with open(filename, 'w') as f:
		f.write("127.0.0.1\n{}\n{}\n".format(ip, msg))
		f.close()

	try:
		s.settimeout(1)
		s.connect((host, port))
		s.settimeout(None)
	except socket.timeout as e:
		print(e.message)
		print ("\nTimeout: Server unavailable!\n")
		return

	f = open(filename,'r')
	l = f.read()
	s.send(bytes(l, encoding="utf-8"))

	f.close()
	s.close()
	print('Sent file.txt')
	
def test_decode_file(ip, msg):
	s = socket.socket()
	filename='test_file_bin.txt'

	try:
		s.settimeout(1)
		s.connect((host, port))
		s.settimeout(None)
	except socket.timeout as e:
		print(e.message)
		print ("\nTimeout: Server unavailable!\n")
		return

	f = open(filename,'r')
	l = f.read()
	s.send(bytes(l, encoding="utf-8"))

	f.close()
	s.close()
	print('Sent file.txt')

try:
	init()
except Exception as e:
	raise e
	
