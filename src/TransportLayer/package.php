<?php

$myfile = fopen("./config.txt", "r");
$contents = fread($myfile,filesize("./config.txt"));
$src_ip = explode(":", $contents)[0];
$src_port = (int)explode(":", $contents)[1] + 1;
fclose($myfile);

class Package {	

	public function __construct($pkg_json) {
        $this->src_port = $pkg_json->src_port;
        $this->dst_port = $pkg_json->dst_port;
        $this->seq = $pkg_json->seq;
        $this->ack = $pkg_json->ack;
		$this->flags = $pkg_json->flags;
		$this->wdn_sz = $pkg_json->wdn_sz;
		$this->checksum = $pkg_json->checksum;
		$this->data = $pkg_json->data;
		$this->dst_ip = $pkg_json->dst_ip;
		$this->orig_ip = $pkg_json->orig_ip;
    }

	public static function mount($pkg_json){
		$obj = new Package(json_decode($pkg_json));
		$obj->flags = (array) $obj->flags;
		return $obj;
	}
	
	public static function create($data, $dst_port, $flags, $dst_ip, $seq, $ack) {
		$obj = new stdClass();
		global $src_port, $src_ip;
        $obj->src_port = $src_port;
        $obj->dst_port = $dst_port;
        $obj->seq = $seq;
        $obj->ack = $ack;
		$obj->flags = $flags;
		$obj->wdn_sz = 10;
		$obj->checksum = 0;
		$obj->data = $data;
		$obj->orig_ip = $src_ip;
		$obj->dst_ip = $dst_ip;
		return new Package($obj);
	}
}
?>