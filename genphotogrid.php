<?php

require_once('db.php');
require_once('config.inc.php');

$row_count = intval($_POST['row_count'], 10);
$col_count = intval($_POST['col_count'], 10);

if ($row_count <= 0 || $col_count <= 0 || count($_POST['image']) != $row_count * $col_count) {
	die(json_encode(array('success' => false, 'error' => 'Invalid input!')));
}
if ($row_count * $col_count > 20) {
	die(json_encode(array('success' => false, 'error' => 'image count reached the limit!')));
}

$filename_iphoto = $config['folders']['images'] . DIRECTORY_SEPARATOR . $_POST['image'][0];
list($iwidth0, $iheight0) = getimagesize($filename_iphoto);
if ($iwidth0 == 0 || $iheight0 == 0) {
	die(json_encode(array('success' => false, 'error' => 'could not get iimage size')));
}
$xpadding = 30;
$ypadding = 30;
$width = ($xpadding + $iwidth0) * $col_count;
$height = ($ypadding + $iheight0) * $row_count;
$gimg = imagecreatetruecolor($width, $height);

if (isset($_POST['bkg'])) {
	$bkg_fn = 'resources/img/bg.jpg';
	$bkg = imagecreatefromjpeg($bkg_fn);
	list($bkg_width, $bkg_height) = getimagesize($bkg_fn);
	imageCopyFill($gimg, $bkg, $bkg_width, $bkg_height, 0, 0, $width, $height);
	imagedestroy($bkg);
} else {
	// sets background to white
	$white = imagecolorallocate($gimg, 255, 255, 255);
	imagefill($gimg, 0, 0, $white);
}


foreach ($_POST['image'] as $index => $ifn) {
	$iimg_path = $config['folders']['images'] . DIRECTORY_SEPARATOR . $ifn;
	$iimg = imagecreatefromjpeg($iimg_path);
	if (empty($iimg)) {
		die(json_encode(array('success' => false, 'error' => 'image not found, '.$ifn)));
	}
	list($iwidth, $iheight) = getimagesize($iimg_path);
	$ci = $index % $col_count;
	$ri = floor($index / $col_count);
	$x = round(($ci + 0.5) * $xpadding + $ci * $iwidth0);
	$y = round(($ri + 0.5) * $ypadding + $ri * $iheight0);
	imageCopyFill($gimg, $iimg, $iwidth, $iheight, $x, $y, $iwidth0, $iheight0);
	imagedestroy($iimg);
}

switch(isset($config['file_format']) ? $config['file_format'] : ''){
	case 'date':
		$file = 'photogrid-'.date('Ymd_His').'.jpg';
		break;
	default:
		$file = 'photogrid-'.md5(time()).'.jpg';
}

$filename_photo = $config['folders']['images'] . DIRECTORY_SEPARATOR . $file;
$filename_thumb = $config['folders']['thumbs'] . DIRECTORY_SEPARATOR . $file;

imagejpeg($gimg, $filename_photo);

mkThumbnail($filename_photo, $filename_thumb);

// insert into database
$images[] = $file;
file_put_contents('data.txt', json_encode($images));

// send imagename to frontend
echo json_encode(array('success' => true, 'img' => $file));

// gen thumbnail image scale
function imageCopyFill($dest, $source, $sw, $sh, $dx, $dy, $dw, $dh) {
	if ($sw / $sh > $dw / $dh) {
		// stretch height
		$tdy = 0;
		$tdx = round(($sw - ($dw / $dh * $sh)) / 2);
		$tdw = $sw - $tdx * 2;
		$tdh = $sh;
	} else {
		$tdx = 0;
		$tdy = round(($sh - ($dh / $dw * $sw)) / 2);
		$tdh = $sh - $tdy * 2;
		$tdw = $sw;
	}
	imagecopyresized($dest, $source, $dx, $dy, $tdx, $tdy, $dw, $dh, $tdw, $tdh);
}
function mkThumbnail($filename_photo, $filename_thumb) {
	list($width, $height) = getimagesize($filename_photo);
	$newwidth = 500;
	$newheight = $height * (1 / $width * 500);
	$source = imagecreatefromjpeg($filename_photo);
	$thumb = imagecreatetruecolor($newwidth, $newheight);
	imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	imagejpeg($thumb, $filename_thumb);
}

