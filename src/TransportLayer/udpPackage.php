<?php
class UDPPackage {	
	public function __construct($pkg_json) {
        $this->src_port = $pkg_json->src_port;
        $this->dst_port = $pkg_json->dst_port;
		$this->checksum = $pkg_json->checksum;
		$this->dst_ip = $pkg_json->dst_ip;
		$this->data = $pkg_json->data;
    }

	public static function mount($pkg_json){
		$obj = new UDPPackage(json_decode($pkg_json));
		return $obj;
	}
	
	public static function create($data, $dst_port, $dst_ip) {
		$obj = new stdClass();
        $obj->src_port = 1051;
        $obj->dst_port = $dst_port;
   		$obj->checksum = 0;
		$obj->data = $data;
		$obj->dst_ip = $dst_ip;
		return new UDPPackage($obj);
	}
}
?>