<?php
class Connection {
    public function __construct($dst_port, $dst_ip) {
        $this->src_port = 1051;
        $this->dst_port = $dst_port;
		$this->src_ip = "127.0.0.1";
		$this->dst_ip = $dst_ip;
		$this->status = "CLOSED";
		$this->seq = 0;
        $this->ack = 0;
		$this->received = "";
		$this->sending = "";
    }
	
	public function append_msg($mgs){
		$this->received = ($this->received).$msg;
	}
}

?>