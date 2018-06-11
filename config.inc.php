<?php

$config = array();
$config['os'] = (DIRECTORY_SEPARATOR == '\\') || (strtolower(substr(PHP_OS, 0, 3)) === 'win') ? 'windows' : 'linux';
$config['dev'] = false;
$config['use_print'] = true;
$config['use_qr'] = false;
$config['show_fork'] = false;
$config['file_format'] = 'date'; // comment in to get dateformat images

// FOLDERS
// change the folders to whatever you like
$config['folders']['images'] = 'images';
$config['folders']['thumbs'] = 'thumbs';
$config['folders']['qrcodes'] = 'qrcodes';
// $config['folders']['print'] = 'print'; // is not used anymore
$config['db'] = 'data.json';

// GALLERY
// should the gallery list the newest pictures first?
$config['gallery']['newest_first'] = true;

// LANGUAGE
// possible values: en, de
$config['language'] = 'de';

// COMMANDS and MESSAGES
switch($config['os']) {
	case 'windows':
	$config['take_picture']['cmd'] = 'digicamcontrol\CameraControlCmd.exe /capture /filename %s';
	$config['take_picture']['msg'] = 'Photo transfer done.';
	$config['print']['cmd'] = 'mspaint /pt "%s"';
	$config['print']['msg'] = '';
	break;
	case 'linux':
	default:
	$config['take_picture']['cmd'] = 'sudo gphoto2 --capture-image-and-download --filename=%s images';
        $config['take_picture']['msg'] = 'New file is in location';
	$config['print']['cmd'] = 'sudo lp -o landscape fit-to-page %s';
	$config['print']['msg'] = '';
	break;
}

// DON'T MODIFY
// preparation
foreach($config['folders'] as $directory) {
	if(!is_dir($directory)){
		mkdir($directory, 0777);
	}
}

$config['photogrid'] = array(
  'grid_xpadding' => 50,
  'grid_ypadding' => 50,
  'grid_leftmargin' => 20,
  'grid_topmargin' => 20,
  'grid_bottommargin' => 20,
  'grid_rightmargin' => 220,
  'overlay_image'  => array(
    'src' => 'resources/img/photogrid-label.jpg',
    'right' => 50,
    'vcenter' => true
  ),
  'background_image' => 'resources/img/bg.jpg'
);

$config['jsconfig'] = array(
  'takephoto_countdown_amount' => 5,
  'interphoto_timeout' => 1000,
  'gridphoto' => true,
  'grid_row_count' => 2,
  'grid_col_count' => 2,
);

