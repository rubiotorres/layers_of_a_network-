<?php

include 'package.php';
include 'udpPackage.php';
include 'connection.php';

error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

function out($txt)
{
	print "\033[32m--TRA-- >> [" . date("m/d/y H:i:s") . "] " . $txt . "\n\033[0m";
}

$myfile = fopen("./config.txt", "r");
$contents = fread($myfile,filesize("./config.txt"));
$host_ip = explode(":", $contents)[0];
$host_port = (int)explode(":", $contents)[1] + 1;
fclose($myfile);

$socket_servidor = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket_servidor < 0) {
	out("Couldn't create socket on $host_ip");
	exit;
}
$bind = socket_bind($socket_servidor, $host_ip, $host_port + 2);
if ($bind < 0) {
	out("Bind error on $host_ip:$host_port");
	exit;
}
$listen = socket_listen($socket_servidor, 5);
if ($listen < 0) {
	out("Error listening on $host_ip:$host_port");
	exit;
}

$connections = array();

$flags = array("URG" => 0, "ACK" => 0, "PSH" => 0, "RST" => 0, "SYN" => 0, "FIN" => 0);

function udpSendMsg($msg, $ip)
{
	global $host_ip, $host_port;
	$length = 4;
	$cont = 1;
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket_send < 0) {
		out("Couldn't create socket on $host_ip");
		return;
	}

	socket_connect($socket_send, $host_ip, $host_port);

	$pkg = UDPPackage::create($msg, $host_port, $ip);
	$sent = socket_write($socket_send, json_encode($pkg));

	out("Sent message:\n" . json_encode($pkg) . "\n");

	socket_close($socket_send);
}

function sendMsg($msg, $send_flags, $ip, $seq = 0, $ack = 0)
{
	global $host_ip, $host_port;
	$length = 4;
	$cont = 1;
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket_send < 0) {
		out("Couldn't create socket on $host_ip");
		return;
	}
	
	if ($msg == ""){
		socket_connect($socket_send, $host_ip, $host_port);

		$pkg = Package::create($msg, $host_port, $send_flags, $ip, $seq, $ack);
		socket_write($socket_send, json_encode($pkg));

		out("Sent message:\n" . json_encode($pkg) . "\n");

		socket_close($socket_send);
	} else {
		$sentTotal = $seq;
		$size = 10;
		while($sentTotal < strlen($msg)){
			if ($sentTotal + $size >= strlen($msg)){
				$send_flags["FIN"] = 1;
			}
			if($socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)){
					$size+=1;
			} else {
				$size = 10;
			}
			socket_connect($socket_send, $host_ip, $host_port);

			$pkg = Package::create(substr($msg, $sentTotal, $size), $host_port, $send_flags, $ip, $sentTotal, $ack);
			$sent = socket_write($socket_send, json_encode($pkg));
			$sentTotal = $sentTotal + $size;

			out("Sent message:\n" . json_encode($pkg) . "\n");

			socket_close($socket_send);
		}
	}
}

function send_apl($msg)
{
	$AplHost = $host_ip;
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	$socket = socket_connect($socket_send, $AplHost, $host_port + 3);
	$sent = socket_write($socket_send, $msg);
	socket_close($socket_send);

	out("Sent message to Application Layer:\n" . $msg . "\n");
}

function get_connection($ip, $connections)
{
	for ($i = 0; $i < count($connections); $i++) {
		if ($connections[$i]->dst_ip == $ip)
			return $connections[$i];
	}
	return NULL;
}

function remove_connection($ip, $connections)
{
	for ($i = 0; $i < count($connections); $i++) {
		if ($connections[$i]->dst_ip == $ip)
			array_splice($connections, $i, $i);
	}
	return $connections;
}

while (true) {
	out("Server listening...");
	$client = socket_accept($socket_servidor);
	if ($client < 0) {
		out("Error accepting connection");
		break;
	}

	$pkg = socket_read($client, 10240);

	if (substr($pkg, 0, 3) == "UDP") {
		out("Received message from Application Layer:\n" . $pkg . "\n");
		$ip = explode(":", explode("\n", $pkg)[3])[0];
		$port = explode(":", explode("\n", $pkg)[3])[1];
		out("Sending message via UDP...");
		udpSendMsg($pkg, $ip);
	} else if (substr($pkg, 0, 3) == "TCP") {
		out("Received message from Application Layer:\n" . $pkg . "\n");
		$ip = explode(":", explode("\n", $pkg)[3])[0];
		$port = explode(":", explode("\n", $pkg)[3])[1];
		$connection = get_connection($ip, $connections);
		if ($connection == NULL) {
			out("Creating new connection with $ip, sending SYN...");
			$connection = new Connection($port, $ip);
			$send_flags = $flags;
			$send_flags["SYN"] = 1;
			sendMsg("", $send_flags, $ip);
			$connection->sending = $pkg;
			$connection->status = "SYN_SENT";
			array_push($connections, $connection);
		} else {
			out("Connection found with $ip, sending message...");
			sendMsg($pkg, $flags, $ip);
		}
	} else {
		out("Received message from layer below:\n $pkg \n");
		if (isUdp($pkg)){
			$pkg = UDPPackage::mount($pkg);
			send_apl($pkg->data);
		} else {
			$pkg = Package::mount($pkg);
			$ip = $pkg->orig_ip;
			$connection = get_connection($ip, $connections);
			if ($connection == NULL) {
				if ($pkg->flags["SYN"] == 1 and $pkg->flags["ACK"] == 0) {
					out("Creating new connection with $ip, sending SYN/ACK...");
					$connection = new Connection($pkg->src_port, $ip);
					$send_flags = $flags;
					$send_flags["SYN"] = 1;
					$send_flags["ACK"] = 1;
					sendMsg("", $send_flags, $ip);
					$connection->status = "SYN_RCVD";
					array_push($connections, $connection);
				} else {
					out("Refused Connection: non-SYN without connection");
				}
			} else {
				$ip = $connection->dst_ip;
				switch ($connection->status) {
					case "SYN_SENT":
						if ($pkg->flags["SYN"] == 1 and $pkg->flags["ACK"] == 1) {
							out("Received SYN/ACK, connection stablished, sending ACK...");
							$send_flags = $flags;
							$send_flags["ACK"] = 1;
							sendMsg("", $send_flags, $ip);
							$connection->status = "ESTABLISHED";
							if ($connection->sending) {
								sendMsg($connection->sending, $flags, $ip);
								$connection->status = "FIN_WAIT_1";
							}
						}
						break;
					case "SYN_RCVD":
						if ($pkg->flags["SYN"] == 0 and $pkg->flags["ACK"] == 1) {
							out("Received ACK, connection stablished...");
							$connection->status = "ESTABLISHED";
							$connection->append_msg($pkg->data);
							if ($connection->sending) {
								sendMsg($connection->sending, $flags, $ip);
							}
						}
						break;
					case "ESTABLISHED":
						if ($pkg->flags["ACK"] == 1) {
							if ($pkg->ack == $connection->last_ack){
								$this->dup_acks = $this->dup_acks + 1;
								if ($this->dup_acks == 3) {
									sendMsg($connection->sending, $flags, $ip, $connection->last_ack, $connection->ack);
								}
							} else {
								$this->dup_acks = 0;
							}
							$connection->last_ack = $pkg->ack;
							break;
						}
						
						if ($connection->seq != $pkg->seq){
							$send_flags = $flags;
							$send_flags["ACK"] = 1;
							sendMsg("", $send_flags, $ip, 0, $connection->seq);
							break;
						}
						
						$connection->append_msg($pkg->data);
						$connection->seq = $connection->seq + strlen($pkg->data);
						$send_flags = $flags;
						$send_flags["ACK"] = 1;
						sendMsg("", $send_flags, $ip, $connection->ack, $connection->seq);
						
						if ($pkg->flags["FIN"] == 1) {
							$send_flags = $flags;
							$send_flags["ACK"] = 0;
							$send_flags["FIN"] = 1;
							sendMsg("", $send_flags, $ip, $connection->ack, $connection->seq);
							$connection->status = "CLOSE_WAIT";
							send_apl($connection->received);
						}
						break;
					case "FIN_WAIT_1":
						if ($pkg->flags["FIN"] == 1) {
							$send_flags = $flags;
							$send_flags["ACK"] = 1;
							sendMsg("", $send_flags, $ip);
							$connection->status = "CLOSING";
						}
						if ($pkg->flags["ACK"] == 1) {
							$connection->status = "FIN_WAIT_2";
						}
						break;
					case "FIN_WAIT_2":
						if ($pkg->flags["FIN"] == 1) {
							$send_flags = $flags;
							$send_flags["ACK"] = 1;
							sendMsg("", $send_flags, $ip);
							$connections = remove_connection($ip, $connections);
						}
						break;
					case "CLOSING":
						if ($pkg->flags["ACK"] == 1) {
							$connections = remove_connection($ip, $connections);
						}
						break;
					case "CLOSE_WAIT":
						if ($pkg->flags["ACK"] == 1) {
							$connections = remove_connection($ip, $connections);
						}
						break;
				}
			}
		}
		
	}
}

function isUdp($pkg){
	$pkg = UDPPackage::mount($pkg);
	return substr($pkg->data, 0, 3) == "UDP";
}
socket_close($socket_servidor);
out("Server shutdown");
