<?php

include 'config.php';
include 'honey.php';

$config = new Config();

if ($config->hf_debug_mode){
	echo "Running honeyfield\n";	
}

$hf = new Honey($config);

$e1 = new Event("Test App 2", "http://whateverweknow.com/abc?123", "a=b&b=c", '202.202.202.1', $config->hf_key);

if ($config->hf_debug_mode){
	echo "Firing event\n";	
}

$hf->fire_event($e1);

if ($config->hf_debug_mode){
	echo "\n\nDone";	
}

?>