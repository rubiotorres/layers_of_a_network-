import socket
import os
import subprocess
import sys
import time
import re

host = '127.0.0.1'
port = 8000
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
		'test_encode <IP>: Encode a test file and send it to IP\n'
		'exit: Exit the program\n'
	)
	
	while(True):
		print ("Command >> ", end='')
		command = input()
		
		if command.split(' ')[0] == "test_encode":
			if len(command.split(' '))>1:
				test_encode_file(command.split(' ')[1])
			else:
				print("\nPlease type in an IP address!\n")
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
			
def test_send_file():
	s = socket.socket()
	filename='file_bin.txt'
	
	try:
		s.settimeout(1)
		s.connect((host, port))
		s.settimeout(None)
	except socket.timeout:
		print ("\nTimeout: Server unavailable!\n")
		return

	f = open(filename,'rb')
	l = f.read(package_size)
	s.send(l)

	f.close()
	s.close()
	print('Sent file.txt')
	
def test_encode_file(ip):
	s = socket.socket()
	filename='file.txt'
	
	with open(filename, 'wb') as f:
		f.write(bytes("{}::Arquivo enviado pela rede!".format(ip), encoding="utf-8"))
		f.close()

	try:
		s.settimeout(1)
		s.connect((host, port))
		s.settimeout(None)
	except socket.timeout as e:
		print(e.message)
		print ("\nTimeout: Server unavailable!\n")
		return

	f = open(filename,'rb')
	l = f.read(package_size)
	s.send(l)

	f.close()
	s.close()
	print('Sent file.txt')

try:
	init()
except Exception as e:
	raise e
	
