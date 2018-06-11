<?php

require_once('config.inc.php');
require_once('db.php');

$filename = trim(basename($_GET['filename']));
if($pos = strpos($filename, '?')) {
    $parts = explode('?', $filename);
    $filename = array_shift($parts);
}

$filename_source = $config['folders']['images'] . DIRECTORY_SEPARATOR . $filename;
$filename_thumb = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $filename;
$status = false;

// exit with error
if(!file_exists($filename_source)) {
    echo json_encode(array('status' => sprintf('file "%s" not found', $filename_source)));
}

// print
if(file_exists($filename_source)) {   
    // print image
    // fixme: move the command to the config.inc.php
    $printimage = shell_exec(
        sprintf(
            $config['print']['cmd'],
            $filename_source
        )
    );
    echo json_encode(array('status' => 'ok', 'msg' => $printimage || ''));
}
