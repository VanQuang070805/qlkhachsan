<?php
$sourcePath = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/rosa_logo_cream_1791539615313.jpg';
$im = imagecreatefromjpeg($sourcePath);
$width = imagesx($im);
$height = imagesy($im);

// Sample background color from top-left, top-right, bottom-left, bottom-right corners
$corners = [
    imagecolorat($im, 10, 10),
    imagecolorat($im, $width - 10, 10),
    imagecolorat($im, 10, $height - 10),
    imagecolorat($im, $width - 10, $height - 10)
];

$rAvg = 0; $gAvg = 0; $bAvg = 0;
foreach ($corners as $c) {
    $rAvg += ($c >> 16) & 0xFF;
    $gAvg += ($c >> 8) & 0xFF;
    $bAvg += $c & 0xFF;
}
$rBg = $rAvg / 4.0;
$gBg = $gAvg / 4.0;
$bBg = $bAvg / 4.0;

echo "Background average RGB: $rBg, $gBg, $bBg\n";
