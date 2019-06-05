<?php

$myfile = fopen("./config.txt", "r");
$contents = fread($myfile,filesize("./config.txt"));
$src_ip = explode(":", $contents)[0];
$src_port = (int)explode(":", $contents)[1] + 1;
fclose($myfile);

class Connection
{
	public function __construct($dst_port, $dst_ip)
	{
		global $src_port, $src_ip;
		$this->src_port = $src_port;
		$this->dst_port = $dst_port;
		$this->src_ip = $src_ip;
		$this->dst_ip = $dst_ip;
		$this->status = "CLOSED";
		$this->seq = 0;
		$this->ack = 0;
		$this->received = "";
		$this->sending = "";
		$this->last_ack = 0;
		$this->dup_acks = 0;
	}

	public function append_msg($msg)
	{
		$this->received = ($this->received) . $msg;
	}
}
