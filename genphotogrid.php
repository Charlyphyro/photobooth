<?php

require_once('config.inc.php');
require_once('db.php');

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

$pgcfg = $config['photogrid'];
$lmargin = $pgcfg['grid_leftmargin'];
$rmargin = $pgcfg['grid_rightmargin'];
$tmargin = $pgcfg['grid_topmargin'];
$bmargin = $pgcfg['grid_bottommargin'];
$xpadding = $pgcfg['grid_xpadding'];
$ypadding = $pgcfg['grid_ypadding'];
$width = ($xpadding + $iwidth0) * $col_count + $lmargin + $rmargin;
$height = ($ypadding + $iheight0) * $row_count + $tmargin + $bmargin;
$gimg = imagecreatetruecolor($width, $height);

if (isset($pgcfg['background_image'])) {
	$bkg_fn = $pgcfg['background_image'];
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
	$x = $lmargin + round(($ci + 0.5) * $xpadding + $ci * $iwidth0);
	$y = $tmargin + round(($ri + 0.5) * $ypadding + $ri * $iheight0);
	imageCopyFill($gimg, $iimg, $iwidth, $iheight, $x, $y, $iwidth0, $iheight0);
	imagedestroy($iimg);
}

if (isset($pgcfg['overlay_image'])) {
	drawImageWithLoc($gimg, 0, 0, $width, $height, $pgcfg['overlay_image']);
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
file_put_contents($config['db'], json_encode($images));

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
function drawImageWithLoc($dest, $dx, $dy, $dw, $dh, $data) {
	list($sw, $sh) = getimagesize($data['src']);
	$source = imagecreatefromjpeg($data['src']);
    $sx = 0;
    $sy = 0;
    $tdx = $dx;
    $tdy = $dy;
    $tdw = $sw;
    $tdh = $sh;
	if (isset($data['left'])) {
		$tdx = $dx + $data['left'];
    }
	if (isset($data['top'])) {
		$tdy = $dy + $data['top'];
    }
	if (isset($data['right'])) {
		if (isset($data['left'])) {
			$tdw = $dw - $data['right'] - $dy;
		} else {
			$tdx = $dw - $data['right'] - $tdw + $dy;
		}
    }
	if (isset($data['bottom'])) {
		if (isset($data['top'])) {
			$tdh = $dh - $data['bottom'] - $dy;
		} else {
			$tdy = $dy + $dh - $data['bottom'] - $tdh;
		}
    }
	if (isset($data['vcenter'])) {
		$tdy = $dx + ($dh - $tdh) / 2;
    }
	if (isset($data['hcenter'])) {
		$tdx = $dx + ($dw - $tdw) / 2;
    }
	if ($tdw > 0 && $tdh > 0) {
		imagecopyresized($dest, $source, $tdx, $tdy, $sx, $sy, $tdw, $tdh, $sw, $sh);
	}
}

