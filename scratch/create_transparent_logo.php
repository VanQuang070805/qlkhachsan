<?php
$sourcePath = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/rosa_logo_cream_1791539615313.jpg';
$artifactsDir = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5';
$publicDir = __DIR__ . '/../public/images/branding';

if (!file_exists($publicDir)) {
    mkdir($publicDir, 0777, true);
}

$im = imagecreatefromjpeg($sourcePath);
$width = imagesx($im);
$height = imagesy($im);

// 1. Version with background in PNG
imagepng($im, "$artifactsDir/rosa_logo_cream.png", 9);
imagepng($im, "$publicDir/rosa_logo_cream.png", 9);

// 2. Transparent Black Version
$transparentImg = imagecreatetruecolor($width, $height);
imagealphablending($transparentImg, false);
imagesavealpha($transparentImg, true);
$clearColor = imagecolorallocatealpha($transparentImg, 0, 0, 0, 127);
imagefill($transparentImg, 0, 0, $clearColor);

// 3. Transparent White Version (for dark backgrounds)
$whiteTransparentImg = imagecreatetruecolor($width, $height);
imagealphablending($whiteTransparentImg, false);
imagesavealpha($whiteTransparentImg, true);
$clearWhite = imagecolorallocatealpha($whiteTransparentImg, 255, 255, 255, 127);
imagefill($whiteTransparentImg, 0, 0, $clearWhite);

// Background luminance calculation
$bgLums = [];
for ($y = 0; $y < 40; $y++) {
    for ($x = 0; $x < 40; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $bgLums[] = 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }
}
sort($bgLums);
$bgLum = $bgLums[(int)(count($bgLums) * 0.9)];

$thresholdHigh = $bgLum - 8.0;
$thresholdLow = 30.0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;

        if ($lum >= $thresholdHigh) {
            imagesetpixel($transparentImg, $x, $y, $clearColor);
            imagesetpixel($whiteTransparentImg, $x, $y, $clearWhite);
        } elseif ($lum <= $thresholdLow) {
            $colBlack = imagecolorallocatealpha($transparentImg, 0, 0, 0, 0);
            $colWhite = imagecolorallocatealpha($whiteTransparentImg, 255, 255, 255, 0);
            imagesetpixel($transparentImg, $x, $y, $colBlack);
            imagesetpixel($whiteTransparentImg, $x, $y, $colWhite);
        } else {
            $opacity = ($thresholdHigh - $lum) / ($thresholdHigh - $thresholdLow);
            $opacity = $opacity * $opacity * (3 - 2 * $opacity);
            $gdAlpha = (int)round((1.0 - $opacity) * 127);
            $gdAlpha = max(0, min(127, $gdAlpha));

            $colBlack = imagecolorallocatealpha($transparentImg, 0, 0, 0, $gdAlpha);
            $colWhite = imagecolorallocatealpha($whiteTransparentImg, 255, 255, 255, $gdAlpha);
            imagesetpixel($transparentImg, $x, $y, $colBlack);
            imagesetpixel($whiteTransparentImg, $x, $y, $colWhite);
        }
    }
}

// Save outputs to artifacts and public branding dir
imagepng($transparentImg, "$artifactsDir/rosa_logo_transparent.png", 9);
imagepng($transparentImg, "$publicDir/rosa_logo_transparent.png", 9);

imagepng($whiteTransparentImg, "$artifactsDir/rosa_logo_white_transparent.png", 9);
imagepng($whiteTransparentImg, "$publicDir/rosa_logo_white_transparent.png", 9);

// Create preview card with both versions
$previewCard = imagecreatetruecolor($width * 2 + 60, $height + 60);
$bgPreview = imagecolorallocate($previewCard, 245, 245, 247);
imagefill($previewCard, 0, 0, $bgPreview);

// Left side: with original cream background
imagecopy($previewCard, $im, 20, 30, 0, 0, $width, $height);

// Right side: checkerboard behind transparent PNG
$check1 = imagecolorallocate($previewCard, 240, 240, 245);
$check2 = imagecolorallocate($previewCard, 215, 220, 230);
$checkSize = 32;
$startX = $width + 40;
for ($y = 30; $y < $height + 30; $y += $checkSize) {
    for ($x = $startX; $x < $startX + $width; $x += $checkSize) {
        $col = ((($x / $checkSize) + ($y / $checkSize)) % 2 == 0) ? $check1 : $check2;
        imagefilledrectangle($previewCard, $x, $y, min($startX + $width - 1, $x + $checkSize), min($height + 29, $y + $checkSize), $col);
    }
}
imagealphablending($previewCard, true);
imagecopy($previewCard, $transparentImg, $startX, 30, 0, 0, $width, $height);

imagepng($previewCard, "$artifactsDir/rosa_logo_comparison_preview.png", 9);

echo "All logo variants successfully generated and saved!\n";
