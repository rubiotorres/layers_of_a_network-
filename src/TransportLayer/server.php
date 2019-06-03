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

$porta = 1053;
$host = '127.0.0.1';
$socket_servidor = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket_servidor < 0) {
	out("Couldn't create socket on $host");
	exit;
}
$bind = socket_bind($socket_servidor, $host, $porta);
if ($bind < 0) {
	out("Bind error on $host:$porta");
	exit;
}
$listen = socket_listen($socket_servidor, 5);
if ($listen < 0) {
	out("Error listening on $host:$porta");
	exit;
}

$connections = array();

$flags = array("URG" => 0, "ACK" => 0, "PSH" => 0, "RST" => 0, "SYN" => 0, "FIN" => 0);

function udpSendMsg($msg, $ip)
{
	$host = '127.0.0.1';
	$length = 4;
	$cont = 1;
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket_send < 0) {
		out("Couldn't create socket on $host");
		return;
	}

	socket_connect($socket_send, $host, 1051);

	$pkg = UDPPackage::create($msg, 1051, $ip);
	$sent = socket_write($socket_send, json_encode($pkg));

	out("Sent message:\n" . json_encode($pkg) . "\n");

	socket_close($socket_send);
}

function sendMsg($msg, $send_flags, $ip)
{
	$host = '127.0.0.1';
	$length = 4;
	$cont = 1;
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket_send < 0) {
		out("Couldn't create socket on $host");
		return;
	}

	socket_connect($socket_send, $host, 1051);

	$pkg = Package::create($msg, 1051, $send_flags, $ip);
	$sent = socket_write($socket_send, json_encode($pkg));

	out("Sent message:\n" . json_encode($pkg) . "\n");

	socket_close($socket_send);
}

function send_apl($msg)
{
	$host = '127.0.0.1';
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	$socket = socket_connect($socket_send, $host, 1054);
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
			array_splice($connections, i, i);
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
		$ip = explode(":", explode("\n", $pkg)[2])[0];
		$port = explode(":", explode("\n", $pkg)[2])[1];
		out("Sending message via UDP...");
		udpSendMsg($pkg, $ip);
	} else if (substr($pkg, 0, 3) == "TCP") {
		out("Received message from Application Layer:\n" . $pkg . "\n");
		$ip = explode(":", explode("\n", $pkg)[2])[0];
		$port = explode(":", explode("\n", $pkg)[2])[1];
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
			$ip = $pkg->dst_ip;
			$connection = get_connection($ip, $connections);
			if ($connection == NULL) {
				out("Refused Connection: non-SYN without connection");
			} else {
				$ip = $connection->dst_ip;
				$connection->append_msg($pkg->data);
				$num_send = 2;
				$mss_send = chunk_split_unicode($mssg, $num_send);
				sendMsg($mssg, $send_flags, $ip);
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
