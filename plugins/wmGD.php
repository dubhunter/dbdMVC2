<?php
require_once("GDColor.php");

function imageCreateFrom($filename)
{
	if (!file_exists($filename) || !is_readable($filename))
		return false;
	$tmp = getimagesize($filename);
	switch ($tmp[2])
	{
		case IMAGETYPE_JPEG:
			return imagecreatefromjpeg($filename);
		case IMAGETYPE_GIF:
			return imagecreatefromgif($filename);
		case IMAGETYPE_PNG:
			return imagecreatefrompng($filename);
	}
}

function outputAndDestroy(&$im, $type = "png", $cache_file = null, $no_output = false)
{
	switch ($type)
	{
		case 'jpeg':
		case 'jpg':
			if ($cache_file)
				imagejpeg($im, $cache_file, 90);
			if (!$no_output)
			{
				header("Content-type: image/jpeg");
				ob_start();
				imagejpeg($im);
			}
			break;
		case 'gif':
			if ($cache_file)
				imagegif($im, $cache_file);
			if (!$no_output)
			{
				header("Content-type: image/gif");
				ob_start();
				imagegif($im);
			}
			break;
		case 'png':
			if ($cache_file)
				imagepng($im, $cache_file);
			if (!$no_output)
			{
				header("Content-type: image/png");
				ob_start();
				imagepng($im);
			}
			break;
		default:
			throw new Exception("Invalid extension!");
	}
	if (!$no_output)
	{
		if ($cache_file)
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($cache_file))." GMT");
//		$len = ob_get_length();
		$img = ob_get_contents();
		ob_end_clean();
//		header("Content-length: ".$len);
		echo $img;
	}
	imagedestroy($im);
}

function imageType($filename)
{
	$tmp = getimagesize($filename);
	return image_type_to_extension($tmp[2]);
}

function imageTypesArray()
{
    $a = array();
    $possibleBits = array(
        IMG_GIF => 'gif',
        IMG_JPG => 'jpg',
        IMG_PNG => 'png',
        IMG_WBMP => 'wbmp'
    );
    $intTypes = imagetypes();
    foreach ($possibleBits as $bit => $str)
    {
        if ($intTypes & $bit)
            $a[] = $str;
    }
    return $a;
}

function colorizeImage(&$im, GDColor $color)
{
	$n = imageColorsTotal($im);
	if ($n > 0)
	{
		for ($i = 0; $i < $n; $i++)
		{
			$old = imagecolorsforindex($im, $i);
			$new = array(
				min($old[0] + $color->getRed(), 255),
				min($old[1] + $color->getGreen(), 255),
				min($old[2] + $color->getBlue(), 255)
			);
			imagecolorset($im, $i, $new[0], $new[1], $new[2]);
		}
	}
	else
	{
		$w = imagesx($im);
		$h = imagesy($im);
		$im2 = imagecreatetruecolor($w, $h);
		imagealphablending($im2, false);
		imagesavealpha($im2, true);
		for ($x = 0; $x < $w; $x++)
		{
			for ($y = 0; $y < $h; $y++)
			{
				$old = GDColor::int2arr(imagecolorat($im, $x, $y));
				$new = array(
					min($old[0] + $color->getRed(), 255),
					min($old[1] + $color->getGreen(), 255),
					min($old[2] + $color->getBlue(), 255),
					$old[3]
				);
				imagesetpixel($im2, $x, $y, imagecolorallocatealpha($im2, $new[0], $new[1], $new[2], $new[3]));
			}
		}
		imagedestroy($im);
		$im = $im2;
	}
}

function resizeImage(&$src, $w, $h, $force_upscale = false)
{
	$sw = imagesx($src);
	$sh = imagesy($src);
	if ($sw > $sh)
	{
		$ow = $w;
		$oh = $w / $sw * $sh;
	}
	else
	{
		$oh = $h;
		$ow = $h / $sh * $sw;
	}
	if (!$force_upscale)
	{
		$ow = min($ow, $sw);
		$oh = min($oh, $sh);
	}
	$out = imagecreatetruecolor($ow, $oh);
	imagealphablending($out, false);
	imagesavealpha($out, true);
	imagecopyresampled($out, $src, 0, 0, 0, 0, $ow, $oh, $sw, $sh);
	return $out;
}

function cropImage(&$src, $w, $h)
{

	$sw = imagesx($src);
	$sh = imagesy($src);
	if ($sw > $sh)
	{
		$cw = $ch = $sh;
		$x = ($sw - $cw) / 2;
		$y = 0;
	}
	else
	{
		$cw = $ch = $sw;
		$x = 0;
		$y = ($sh - $ch) / 2;
	}
	$out = imagecreatetruecolor($w, $h);
	imagealphablending($out, false);
	imagesavealpha($out, true);
	imagecopyresampled($out, $src, 0, 0, $x, $y, $w, $h, $cw, $ch);
	return $out;
}

function cropResizeImage(&$src, $w, $h, $align = 1)
{
	$r = $w / $h;
	$sw = imagesx($src);
	$sh = imagesy($src);
	$sr = $sw / $sh;
	if ($sr > $r)
	{
		$cw = round($sh * $r);
		$ch = $sh;
		switch ($align)
		{
			case 0:
				$x = 0;
				break;
			case 1:
				$x = round(($sw - $cw) / 2);
				break;
			case 2:
				$x = $sw - $cw;
		}
		$y = 0;
	}
	else
	{
		$cw = $sw;
		$ch = round($sw / $r);
		$x = 0;
		switch ($align)
		{
			case 0:
				$y = 0;
				break;
			case 1:
				$y = round(($sh - $ch) / 2);
				break;
			case 2:
				$y = $sh - $ch;
		}
	}
	$out = imagecreatetruecolor($w, $h);
	imagealphablending($out, false);
	imagesavealpha($out, true);
	imagecopyresampled($out, $src, 0, 0, $x, $y, $w, $h, $cw, $ch);
	return $out;
}

function cropImageReal(&$src, $x, $y, $w, $h)
{
	$out = imagecreatetruecolor($w, $h);
	imagealphablending($out, false);
	imagesavealpha($out, true);
	imagecopyresampled($out, $src, 0, 0, $x, $y, $w, $h, $w, $h);
	return $out;
}

function stretchCopyButton($dst_im, $src_im, $dst_wd, $src_wd, $src_ht, $y = 0, $cap_wd = null)
{
	if ($cap_wd > 0)
	{
		//stretch center pixels
		imagecopyresampled($dst_im, $src_im, $cap_wd, $y, $cap_wd, 0, $dst_wd - ($cap_wd * 2), $src_ht, $src_wd - ($cap_wd * 2), $src_ht);
		//caps
		imagecopy($dst_im, $src_im, 0, $y, 0, 0, $cap_wd, $src_ht);
		imagecopy($dst_im, $src_im, $dst_wd - $cap_wd, $y, $src_wd - $cap_wd, 0, $cap_wd, $src_ht);
	}
	else
	{
		$diff_wd = $dst_wd - $src_wd;
		$half_wd = $src_wd / 2;
		//stretch center pixel
		imageCopyResampled($dst_im, $src_im, $half_wd, $y, $half_wd, 0, $diff_wd, $src_ht, 1, $src_ht);
		//caps
		imageCopy($dst_im, $src_im, 0, $y, 0, 0, $half_wd, $src_ht);
		imageCopy($dst_im, $src_im, $half_wd + $diff_wd, $y, $half_wd, 0, $half_wd, $src_ht);
	}
	imageDestroy($src_im);
}

function imageLineThick(&$im, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
	//make sure antialiasing is on
	imageantialias($im, true);
    if ($thick == 1)
        return imageline($im, $x1, $y1, $x2, $y2, $color);
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2)
        return imagefilledrectangle($im, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1 + $k) * $a), round($y1 + (1 - $k) * $a),
        round($x1 - (1 - $k) * $a), round($y1 - (1 + $k) * $a),
        round($x2 + (1 + $k) * $a), round($y2 - (1 - $k) * $a),
        round($x2 + (1 - $k) * $a), round($y2 + (1 + $k) * $a),
    );
    imagefilledpolygon($im, $points, 4, $color);
    return imagepolygon($im, $points, 4, $color);
}

function imagefilledellipsealpha(&$im, $cx, $cy, $w, $h, $color)
{
	//make sure antialiasing is off
	imageantialias($im, false);
	$a = floor($w / 2);
	$b = floor($h / 2);
	$rgba = GDColor::int2arr($color);
	$baseFilled = ($rgba[3] / 127);
	$lastX = 0;
	for ($y = 0; $y <= $b; $y++)
	{
		$x = sqrt(pow($a, 2) * (1 - pow($y, 2) / pow($b, 2)));
		if ($lastX - $x >= 1)
			break;
		$x1 = round($x + $cx);
		$x2 = round(-$x + $cx);
		$y1 = $cy - $y;
		$y2 = $y + $cy;
		if ($y < $b)
		{
			imageline($im, $x1 - 1, $y1, $x2 + 1, $y1, $color);
			if ($y > 0)
				imageline($im, $x1 - 1, $y2, $x2 + 1, $y2, $color);
		}
		$filled = ($x - round($x)) * (1.0 - $baseFilled);
		$alpha = min(127 - (abs(90 * $filled + 37)), 127);
		$aColor = imagecolorexactalpha($im, $rgba[0], $rgba[1], $rgba[2], $alpha);
		imagesetpixel($im, $x1, $y1, $aColor);
		imagesetpixel($im, $x2, $y1, $aColor);
		if ($y > 0)
		{
			imagesetpixel($im, $x1, $y2, $aColor);
			imagesetpixel($im, $x2, $y2, $aColor);
		}
		imagecolordeallocate($im, $aColor);
		$lastX = $x;
		$lastX1 = $x1;
		$lastX2 = $x2;
		$lastY1 = $y1 - 1;
		$lastY2 = $y2 + 1;
	}
	$lastY = 0;
	for ($x = 0; $x < round($lastX); $x++)
	{
		$y = sqrt(pow($b, 2) * (1 - pow($x, 2) / pow($a, 2)));
		$x1 = $cx - $x;
		$x2 = $x + $cx;
		$y1 = round($y + $cy);
		$y2 = round(-$y + $cy);
		if ($x < round($lastX) - 1)
		{
			imageline($im, $x1, $y1 - 1, $x1, $lastY2, $color);
			imageline($im, $x1, $y2 + 1, $x1, $lastY1, $color);
			if ($x > 0)
			{
				imageline($im, $x2, $y1 - 1, $x2, $lastY2, $color);
				imageline($im, $x2, $y2 + 1, $x2, $lastY1, $color);
			}
		}
		$filled = ($y - round($y)) * (1.0 - $baseFilled);
		$alpha = min(127 - (abs(90 * $filled + 37)), 127);
		$aColor = imagecolorexactalpha($im, $rgba[0], $rgba[1], $rgba[2], $alpha);
		imagesetpixel($im, $x1, $y1, $aColor);
		imagesetpixel($im, $x1, $y2, $aColor);
		if ($x > 0)
		{
			imagesetpixel($im, $x2, $y1, $aColor);
			imagesetpixel($im, $x2, $y2, $aColor);
		}
		imagecolordeallocate($im, $aColor);
		$lastY = $y;
	}
}

function reflectImage(&$src, $ref_height = 0.50, $fade_start = 80, $fade_end = 0, $tint = "7f7f7f", $bg = "ffffff", $type = "png")
{
	//calculate reflection height
	if (substr($ref_height, -1) == '%')
	{
		$ref_height = substr($ref_height, 0, -1);
		if ($ref_height == 100)
            $ref_height = "0.99";
		elseif ($ref_height < 10)
            $ref_height = "0.0".$ref_height;
        else
			$ref_height = "0.".$ref_height;
	}
	//calc fade start
	if (strpos($fade_start, '%') !== false)
	{
		$alpha_start = str_replace('%', '', $fade_start);
		$alpha_start = (int) (127 * $alpha_start / 100);
	}
	else
	{
		$alpha_start = (int) $fade_start;
		if ($alpha_start < 1 || $alpha_start > 127)
			$alpha_start = 80;
	}
	//calc fade end
	if (strpos($fade_end, '%') !== false)
	{
		$alpha_end = str_replace('%', '', $fade_end);
		$alpha_end = (int) (127 * $alpha_end / 100);
	}
	else
	{
		$alpha_end = (int) $fade_end;
		if ($alpha_end < 1 || $alpha_end > 0)
			$alpha_end = 0;
	}
	$width = imagesx($src);
	$height = imagesy($src);
	//calc height of reflection and output image
	if ($ref_height < 1.0)
		$ref_height = $height * $ref_height;
	else
		$ref_height = $ref_height;
	$final_height = $height + $ref_height;

	//allocate some images
	$final = imagecreatetruecolor($width, $final_height);
	$reflect = imagecreatetruecolor($width, $ref_height);
	$buffer = imagecreatetruecolor($width, $ref_height);
	//calculate tinting
	$tint = new GDColor($final, $tint);
	//save any alpha data that might have existed in the source image and disable blending
	imagesavealpha($src, true);
	imagesavealpha($final, true);
	imagealphablending($final, false);
	imagesavealpha($reflect, true);
	imagealphablending($reflect, false);
	imagesavealpha($buffer, true);
	imagealphablending($buffer, false);
	//copy bottom most section as reflection
	imagecopy($reflect, $src, 0, 0, 0, $height - $ref_height, $width, $ref_height);
	//flip image (strip flip)
	for ($y = 0; $y < $ref_height; $y++)
	   imagecopy($buffer, $reflect, 0, $y, 0, $ref_height - $y - 1, $width, 1);
	$reflect = $buffer;
	$alpha_length = abs($alpha_start - $alpha_end);
	imagelayereffect($reflect, IMG_EFFECT_OVERLAY);
	for ($y = 0; $y <= $ref_height; $y++)
	{
	    $pct = $y / $ref_height;
	    if ($alpha_start > $alpha_end)
	        $alpha = (int) ($alpha_start - ($pct * $alpha_length));
	    else
	        $alpha = (int) ($alpha_start + ($pct * $alpha_length));
	    $final_alpha = 127 - $alpha;
	    imagefilledrectangle($reflect, 0, $y, $width, $y, imagecolorallocatealpha($reflect, $tint->getRed(), $tint->getGreen(), $tint->getBlue(), $final_alpha));
	}
	if ($type != "png")
	{
		//calculate tinting
		$bg = new GDColor($final, $bg);
		imageFill($final, 0, 0, $bg->getColor());
		imagealphablending($final, true);
	}
	else
	{
		imagealphablending($final, false);
		imagesavealpha($final, true);
	}
	//copy source to final
	imagecopy($final, $src, 0, 0, 0, 0, $width, $height);
	//copy reflection to bottom of final
	imagecopy($final, $reflect, 0, $height, 0, 0, $width, $ref_height);
	return $final;
}

//function imagefilledellipseaaplotpoints(&$im, $CX, $CY, $X, $Y, $color, $t)
//{
//	imagesetpixel($im, $CX + $X, $CY + $Y, $color);
//	imagesetpixel($im, $CX - $X, $CY + $Y, $color);
//	imagesetpixel($im, $CX - $X, $CY - $Y, $color);
//	imagesetpixel($im, $CX + $X, $CY - $Y, $color);
//
//	$aColor = GDColor::int2arr($color);
//	$mColor = imagecolorallocate($im, $aColor[0], $aColor[1], $aColor[2]);
//	if ($t == 1)
//	{
//	  	imageline($im, $CX - $X, $CY - $Y + 1, $CX + $X, $CY - $Y + 1, $mColor);
//	  	imageline($im, $CX - $X, $CY + $Y - 1, $CX + $X, $CY + $Y - 1, $mColor);
//	}
//	else
//	{
//	  	imageline($im, $CX - $X + 1, $CY - $Y, $CX + $X - 1, $CY - $Y, $mColor);
//	  	imageline($im, $CX - $X + 1, $CY + $Y, $CX + $X - 1, $CY + $Y, $mColor);
//	}
//	imagecolordeallocate($im, $mColor);
//}
//
//function imagefilledellipseaa(&$im, $CX, $CY, $Width, $Height, $color)
//{
//	//make sure antialiasing is off
//	imageantialias($im, false);
//	$XRadius = floor($Width / 2);
//	$YRadius = floor($Height / 2);
//
//	$baseColor = GDColor::int2arr($color);
//
//	$TwoASquare = 2 * $XRadius * $XRadius;
//	$TwoBSquare = 2 * $YRadius * $YRadius;
//	$X = $XRadius;
//	$Y = 0;
//	$XChange = pow($YRadius, 2) * (1 - (2 * $XRadius));
//	$YChange = pow($XRadius, 2);
//	$EllipseError = 0;
//	$StoppingX = $TwoBSquare * $XRadius;
//	$StoppingY = 0;
//
//	$alpha = 77;
//	$color = imagecolorexactalpha($im, $baseColor[0], $baseColor[1], $baseColor[2], $alpha);
//	while ($StoppingX >= $StoppingY) //1st set of points, y' > -1
//	{
//		imagefilledellipseaaplotpoints($im, $CX, $CY, $X, $Y, $color, 0);
//		$Y++;
//		$StoppingY += $TwoASquare;
//		$EllipseError += $YChange;
//	 	$YChange += $TwoASquare;
//		if ((2 * $EllipseError + $XChange) > 0)
//		{
//			$X--;
//			$StoppingX -= $TwoBSquare;
//			$EllipseError += $XChange;
//			$XChange += $TwoBSquare;
//		}
//
//		// decide how much of pixel is filled.
//		$filled = $X - sqrt(pow($XRadius, 2) - (pow($XRadius, 2) / pow($YRadius, 2)) * pow($Y, 2));
//		$alpha = abs(90 * $filled + 37);
//		imagecolordeallocate($im, $color);
//		$color = imagecolorexactalpha($im, $baseColor[0], $baseColor[1], $baseColor[2], $alpha);
//	}
//	// 1st point set is done; start the 2nd set of points
//
//	$X = 0;
//	$Y = $YRadius;
//	$XChange = pow($YRadius, 2);
//	$YChange = pow($XRadius, 2) * (1 - (2 * $YRadius));
//	$EllipseError = 0;
//	$StoppingX = 0;
//	$StoppingY = $TwoASquare * $YRadius;
//	$alpha = 77;
//	$color = imagecolorexactalpha($im, $baseColor[0], $baseColor[1], $baseColor[2], $alpha);
//
//	while ($StoppingX <= $StoppingY) //2nd set of points, y' < -1
//	{
//		imagefilledellipseaaplotpoints($im, $CX, $CY, $X, $Y, $color, 1);
//		$X++;
//		$StoppingX += $TwoBSquare;
//		$EllipseError += $XChange;
//		$XChange += $TwoBSquare;
//		if ((2 * $EllipseError + $YChange) > 0)
//		{
//			$Y--;
//			$StoppingY -= $TwoASquare;
//			$EllipseError += $YChange;
//			$YChange += $TwoASquare;
//		}
//
//		// decide how much of pixel is filled.
//		$filled = $Y - sqrt(pow($YRadius, 2) - (pow($YRadius, 2) / pow($XRadius, 2)) * pow($X, 2));
//		$alpha = abs(90 * $filled + 37);
//		imagecolordeallocate($im, $color);
//		$color = imagecolorexactalpha($im, $baseColor[0], $baseColor[1], $baseColor[2], $alpha);
//	}
//}
?>