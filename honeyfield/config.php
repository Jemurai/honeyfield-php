<?php

// Configuration.
class Config {
	public $hf_key = "register-and-change-me";
  	public $hf_host = "http://the.honeyfield.io/events.json";
	public $hf_debug_mode = true;
	public $hf_blocked_params = "pwd";
	public $hf_sample_rate = "10";
	
	public function __toString(){
		return "Key: $this->hf_key | Host: $this->hf_host | Debug: $this->hf_debug_mode";
	}
}

?>