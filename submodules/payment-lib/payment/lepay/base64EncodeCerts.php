<?php

array_shift($_SERVER['argv']);

$file = 'certs' . DIRECTORY_SEPARATOR . array_shift($_SERVER['argv']);

if(!file_exists($file)){
	echo 'File Not Found!' . PHP_EOL;
	exit(1);
}

echo base64_encode(file_get_contents($file));