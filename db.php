<?php
// get data from $config['db']
if(!file_exists($config['db'])){
	file_put_contents($config['db'], json_encode(array()));
}
$images = json_decode(file_get_contents($config['db']));
