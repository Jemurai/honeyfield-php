<?php

include 'config.php';
include 'honey.php';

$config = new Config();
$config->hf_debug_mode = true;

$hf = new Honey($config);
$app = $argv[1];
$uri = $argv[2];
$post = $argv[3];
$e1 = new Event($app, $uri, $post, '192.168.1.1', $config->hf_key);
$hf->fire_event($e1);

?>