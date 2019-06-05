<?php

$myfile = fopen("./config.txt", "r");
$contents = fread($myfile,filesize("./config.txt"));
$src_ip = explode(":", $contents)[0];
$src_port = (int)explode(":", $contents)[1] + 1;
fclose($myfile);

class UDPPackage {	

	public function __construct($pkg_json) {
        $this->src_port = $pkg_json->src_port;
        $this->dst_port = $pkg_json->dst_port;
		$this->checksum = $pkg_json->checksum;
		$this->size = strlen($pkg_json->data);
		$this->dst_ip = $pkg_json->dst_ip;
		$this->orig_ip = $pkg_json->orig_ip;
		$this->data = $pkg_json->data;
    }

	public static function mount($pkg_json){
		$obj = new UDPPackage(json_decode($pkg_json));
		return $obj;
	}
	
	public static function create($data, $dst_port, $dst_ip) {
		$obj = new stdClass();
		global $src_port, $src_ip;
        $obj->src_port = $src_port;
        $obj->dst_port = $dst_port;
   		$obj->checksum = 0;
		$obj->data = $data;
		$obj->dst_ip = $dst_ip;
		$obj->orig_ip = $src_ip;
		return new UDPPackage($obj);
	}
}
?>