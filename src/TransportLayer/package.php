<?php
class Package {
    // constructor
    public function __construct($data, $dst_port, $flags) {
        $this->src_port = 1051;
        $this->dst_port = $dst_port;
        $this->seq = 0;
        $this->ack = 0;
		$this->flags = $flags;
		$this->wdn_sz = 10;
		$this->checksum = 0;
		$this->data = $data;
    }

	public static function mount($pkg_json){
		return new Package("data", 1051, array("SYN"=>1,"ACK"=>0,"URG"=>0,"FIN"=>0,"RST"=>0,"PSH"=>0));
	}
}
?>