<?php
$im = imagecreatefrompng('public/images/branding/rosa_mascot_flamingo_transparent.png');
$w = imagesx($im); $h = imagesy($im);
$minX = $w; $maxX = 0; $minY = $h; $maxY = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $alpha = (imagecolorat($im, $x, $y) >> 24) & 0x7F;
        if ($alpha < 100) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}
$bw = $maxX - $minX;
$bh = $maxY - $minY;
echo "BBox: minX=$minX, maxX=$maxX, minY=$minY, maxY=$maxY (Width=$bw, Height=$bh)\n";
