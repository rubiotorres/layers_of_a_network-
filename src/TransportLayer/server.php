<?php

include 'package.php';
include 'connection.php';

error_reporting(E_ALL);

function out($txt){
	print $txt . "\n";
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
$waiting = array();

$flags = array("URG"=>0,"ACK"=>0,"PSH"=>0,"RST"=>0,"SYN"=>0,"FIN"=>0);

function sendMsg($msg, $send_flags) {
	$host = '127.0.0.1';
	$length = 4;
	$cont = 1;
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket_send < 0) {
		out("Couldn't create socket on $host");
		return;
	}
	
	while (true) {
		socket_connect($socket_send, $host, 1053);
		
		out($socket_send);
		
		$pkg = new Package($msg, 1053, $send_flags);
		$sent = socket_write($socket_send, json_encode($pkg));
		//verifica término do envio
		if ($sent === false) {
			break;
		} elseif ($cont > 1) { //Verifica quantidade de acertos 
			$cont = 0;
			$length = $length + 1; // Caso tenha +2 acertos manda mais 1 
		} elseif ($sent == $length) { //Vrifica tamanho da mssg
			$cont += 1;
		}

		out("Quantidade de dados enviados: " . $sent);
		out("Menssagem enviada: " . $msg);
		out("Acertos: " . $cont);
		
		socket_close($socket_send);

		if ($sent > 0) {
			out("Quantidade de msg à ser eviada: ".$length);
			$msg = substr($msg, $sent);
		} else {
			break;
		}
	}
}

function send_apl($msg){
	$socket_send = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	$socket = socket_connect($socket_send, $host, 1054);
	$sent = socket_write($socket, $msg);
	socket_close($socket);
}

function get_connection($ip, $connections){
	for($i = 0; $i < count($connections); $i++) {
		if ($connections[$i]["ip"] == $ip)
			return $connections[$i];
	}
	return NULL;
}

function remove_connection($ip, $connections){
	for($i = 0; $i < count($connections); $i++) {
		if ($connections[$i]["ip"] == $ip)
			array_splice($connections, i, i);  
	}
	return $connections;
}

while (true) {
	print "Server listening...";
    $client = socket_accept($socket_servidor);
    if ($client < 0) {
        out("Error accepting connection");
        break;
    }
	
    $pkg = socket_read($client, 10240);
		
	if (substr($pkg, 0, 3) == "DNS"){
		$ip = explode(":", explode("\n", $pkg)[2])[0];
		$port = explode(":", explode("\n", $pkg)[2])[1];
		$connection = get_connection($ip, $connections);
		if ($connection == NULL){
			$connection = new Connection($port, $ip);
			$send_flags = $flags;
			$send_flags["SYN"] = 1;
			sendMsg("", $send_flags);
			$waiting[$ip] = $pkg;
			$connection->status = "SYN_SENT";
		}
		else{
			sendMsg($pkg, $flags);
		}
	}
	else{
		$pkg = Package::mount($pkg);
		socket_getpeername($client, $ip);
		$connection = get_connection($ip, $connections);
		if ($connection == NULL){
			if ($pkg->flags["SYN"] == 1 and $pkg->flags["ACK"] == 0){
				$connection = new Connection($pkg->src_port, $ip);
				$send_flags = $flags;
				$send_flags["SYN"] = 1;
				$send_flags["ACK"] = 1;
				sendMsg("", $send_flags);
				$connection->status = "SYN_RCVD";
			}
			else{
				out("Refused Connection: non-SYN without connection");
			}
		}
		else{
			switch ($connection->status){
				case "SYN_SENT":
					if ($pkg->flags["SYN"] == 1 and $pkg->flags["ACK"] == 1){
						$send_flags = $flags;
						$send_flags["ACK"] = 1;
						sendMsg("", $send_flags);
						$connection->status = "ESTABLISHED";
						if (waiting[$ip]){
							sendMsg(waiting[$ip], $flags);
							$connection->status = "FIN_WAIT_1";
							unset($waiting[$ip]);
						}
					}
					break;
				case "SYN_RCVD":
					if ($pkg->flags["SYN"] == 0 and $pkg->flags["ACK"] == 1){
						$connection->status = "ESTABLISHED";
						if (waiting[$ip]){
							sendMsg(waiting[$ip], $flags);
							unset($waiting[$ip]);
						}
					}
					break;
				case "ESTABLISHED":
					$connection->append_msg($pkg->data);
					if ($pkg->flags["FIN"] == 1){
						$send_flags = $flags;
						$send_flags["ACK"] = 1;
						sendMsg("", $send_flags);
						$send_flags["ACK"] = 0;
						$send_flags["FIN"] = 1;
						sendMsg("", $send_flags);
						$connection->status = "CLOSE_WAIT";
					}
					break;
				case "FIN_WAIT_1":
					if ($pkg->flags["FIN"] == 1){
						$send_flags = $flags;
						$send_flags["ACK"] = 1;
						sendMsg("", $send_flags);
						$connection->status = "CLOSING";
					}
					if ($pkg->flags["ACK"] == 1){
						$connection->status = "FIN_WAIT_2";
					}
					break;
				case "FIN_WAIT_2":
					if ($pkg->flags["FIN"] == 1){
						$send_flags = $flags;
						$send_flags["ACK"] = 1;
						sendMsg("", $send_flags);
						$connections = remove_connection($ip, $connections);
					}
					break;
				case "CLOSING":
					if ($pkg->flags["ACK"] == 1){
						$connections = remove_connection($ip, $connections);
					}
					break;
				case "CLOSE_WAIT":
					if ($pkg->flags["ACK"] == 1){
						$connections = remove_connection($ip, $connections);
					}
					break;
			}
		}	
	}
}
socket_close($socket_servidor);
out("Server shutdown");
?>