import socket
import signal
import subprocess
import sys
import re
import json
import random
import time

s = socket.socket()
mac_table = {}
host = '127.0.0.1'
host_mac = 'F82819A1E957'
random.seed()

	
def run_server():
	port = 8000
	filename = "server_file.pdu"
	package_size = 1024*1024
	
	sys.stdout.write('### Server start ###\n')
	sys.stdout.write('\n' + show_timestamp() + 'Server listening...\n')
	
	destination_ip = None
	frame = None
	
	s.bind((host, port))
	s.listen(5)

	while True:
		try:
			conn, addr = s.accept()
			sys.stdout.write (show_timestamp() + 'Got connection from ' + addr[0] + '\n')
			
			data = conn.recv(package_size)
			
			if data.decode("utf-8") == "Hello":
				sys.stdout.write(show_timestamp() + 'Server poke\n')
				conn.send(bytes("Hi", encoding='utf-8'))
				conn.close()
			elif addr[0] == host:

				with open(filename, 'wb') as f:
					try:
						frame, destination_ip = mount_frame(data)
						f.write(bytes(frame, encoding="utf-8"))
						sys.stdout.write(show_timestamp() + 'Successfully encoded the file, sending to {}...\n'.format(destination_ip))
					except Exception as e:
						print("Error: {}".format(e))
					f.close()
				conn.close()
				if destination_ip and frame:
					send_data(frame, destination_ip)
			else:
				
				with open(filename, 'wb') as f:
					frame = unmount_frame(data)
					message = frame.get('payload')
					f.write(bytes(message, encoding="utf-8"))
					f.close()
					sys.stdout.write(show_timestamp() + 'Successfully got the file\nSaved as file_server.pdu')
				conn.close()
				
			sys.stdout.write('\n' + show_timestamp() + 'Server listening...\n')
		except KeyboardInterrupt as e:
			s.close()
			raise e
			
def send_data(data, destination_ip):
	collision = random.randint(1, 100) <= 5
	while collision:
		print(show_timestamp() + "Collision! Waiting...")
		time.sleep(random.randint(1,100)/100)
		collision = random.randint(1, 100) <= 5
	
	port = 8000
	sock = socket.socket()
	
	try:
		sock.settimeout(0.5)
		sock.connect((destination_ip, port))
		sock.settimeout(None)
	except socket.timeout as e:
		sys.stdout.write(show_timestamp() + "Timeout: Destination unavailable!\n")
		return
	except Exception as e:
		sys.stdout.write(show_timestamp() + "Erro: " + str(e))
		return

	sock.send(bytes(data, encoding="utf-8"))

	sock.close()
	print(show_timestamp() + 'Sent file to destination ('+ destination_ip +')')
	return
	
def mount_frame(data):
	data = data.decode('latin')
	begin = '1010101010101010101010101010101010101010101010101010101010101011'
	origin = hex2bin(host_mac)
	destination_ip = data.split('::')[0]
	payload = str2bin(data)
	bin_size = int2bin(len(data))
	crc = crc_remainder(payload)
	
	destination = get_destination_mac(destination_ip)
	destination_bin = hex2bin(destination)
	
	result = begin + destination_bin + origin + bin_size + payload + crc
	
	print (show_timestamp() + "\nProcessed PDU\nMessage:\n{}\n\nResult:\n{}\n".format(data,result))
	return result, destination_ip
	
def unmount_frame(data):
	data = data.decode('latin')
	size = int(data[160:176], 2) * 8
	frame = {
		'begin': data[0:64],
		'destination': bin2hex(data[64:112]),
		'origin': bin2hex(data[112:160]),
		'size': size/8,
		'payload': bin2str(data[176:(176+size)]),
		'crc': data[(176+size):]
	}
	
	print (show_timestamp() + "\nRead PDU\n{}\n\nResult:".format(data))
	print (json.dumps(frame, indent=2))
	print ("\nCRC check: ", end='')
	
	if crc_check(data[176:(176+size)], frame['crc']):
		print ('Success!\n')
	else:
		print ('Fail :/\n')
		
	return frame

def bin2str(data):
	result = ''.join(chr(int(data[i*8:i*8+8],2)) for i in range(len(data)//8))
	return result

def str2bin(data):
	result = ''.join(format(ord(c), '08b') for c in data)
	return result
	
def int2bin(data):
	digits = 16
	return bin(data)[2:].zfill(digits)
	
def hex2bin(data):
	scale = 16
	num_of_bits = 48
	return bin(int(data, scale))[2:].zfill(num_of_bits)
	
def bin2hex(data):
	scale = 2
	digits = 12
	return hex(int(data, scale))[2:].zfill(digits)
	
def crc_remainder(input_bitstring):
	polynomial_bitstring = '101010101010101010101010101010101'
	len_input = len(input_bitstring)
	initial_padding = '0' * (len(polynomial_bitstring) - 1)
	input_padded_array = list(input_bitstring + initial_padding)
	while '1' in input_padded_array[:len_input]:
		cur_shift = input_padded_array.index('1')
		for i in range(len(polynomial_bitstring)):
			input_padded_array[cur_shift + i] = str(int(polynomial_bitstring[i] != input_padded_array[cur_shift + i]))
	return ''.join(input_padded_array)[len_input:]
	
def crc_check(input_bitstring, check_value):
	polynomial_bitstring = '101010101010101010101010101010101'
	len_input = len(input_bitstring)
	initial_padding = check_value
	input_padded_array = list(input_bitstring + initial_padding)
	while '1' in input_padded_array[:len_input]:
		cur_shift = input_padded_array.index('1')
		for i in range(len(polynomial_bitstring)):
			input_padded_array[cur_shift + i] = str(int(polynomial_bitstring[i] != input_padded_array[cur_shift + i]))
	return ('1' not in ''.join(input_padded_array)[len_input:])
	
def get_destination_mac(ip):
	mac_destination = mac_table.get(ip)
	
	if not mac_destination:
		mac_destination = arp(ip)
		mac_table[ip] = mac_destination
	else:
		print(show_timestamp() + "Got MAC address from table: ", end='')
	print (mac_destination)
	
	return mac_destination
	
def arp(ip):
	print(show_timestamp() + "Arp: Searhing MAC for {}... ".format(ip), end='')
	result = subprocess.run(['arp', '-a', ip], stdout=subprocess.PIPE).stdout.decode('latin')
	pattern = re.compile(r'(?:[0-9a-fA-F]-?){12}')
	mac_list = re.findall(pattern, result)
	if len(mac_list) < 1:
		pattern = re.compile(r'(?:[0-9a-fA-F]:?){12}')
		mac_list = re.findall(pattern, result)
		if len(mac_list) < 1:
			raise Exception('MAC not found :/')
	mac = mac_list[0].replace('-', '').replace(':', '')
	return mac
		
def show_timestamp():
	return time.strftime("%Y-%m-%d %H:%M:%S", time.gmtime()) + " >> "

run_server()
