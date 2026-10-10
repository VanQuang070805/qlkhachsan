<?php
$swanSource = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/rosa_mascot_swan_1791539989284.jpg';
$flamingoSource = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/rosa_mascot_flamingo_1791539959753.jpg';
$artifactsDir = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5';
$publicDir = __DIR__ . '/../public/images/branding';

function makeTransparentVariant($sourcePath, $prefix, $artifactsDir, $publicDir) {
    if (!file_exists($sourcePath)) return;
    $im = imagecreatefromjpeg($sourcePath);
    $width = imagesx($im);
    $height = imagesy($im);

    // Save with background PNG
    imagepng($im, "$artifactsDir/{$prefix}_with_bg.png", 9);
    imagepng($im, "$publicDir/{$prefix}_with_bg.png", 9);

    // Transparent Black Version
    $trans = imagecreatetruecolor($width, $height);
    imagealphablending($trans, false);
    imagesavealpha($trans, true);
    $clearColor = imagecolorallocatealpha($trans, 0, 0, 0, 127);
    imagefill($trans, 0, 0, $clearColor);

    // Sample background luminance from corner
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
                imagesetpixel($trans, $x, $y, $clearColor);
            } elseif ($lum <= $thresholdLow) {
                $colBlack = imagecolorallocatealpha($trans, 0, 0, 0, 0);
                imagesetpixel($trans, $x, $y, $colBlack);
            } else {
                $opacity = ($thresholdHigh - $lum) / ($thresholdHigh - $thresholdLow);
                $opacity = $opacity * $opacity * (3 - 2 * $opacity);
                $gdAlpha = (int)round((1.0 - $opacity) * 127);
                $gdAlpha = max(0, min(127, $gdAlpha));
                $colBlack = imagecolorallocatealpha($trans, 0, 0, 0, $gdAlpha);
                imagesetpixel($trans, $x, $y, $colBlack);
            }
        }
    }

    imagepng($trans, "$artifactsDir/{$prefix}_transparent.png", 9);
    imagepng($trans, "$publicDir/{$prefix}_transparent.png", 9);

    // Create side by side preview with checkerboard
    $previewCard = imagecreatetruecolor($width * 2 + 60, $height + 60);
    $bgPreview = imagecolorallocate($previewCard, 245, 245, 247);
    imagefill($previewCard, 0, 0, $bgPreview);

    // Left side: with original background
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
    imagecopy($previewCard, $trans, $startX, 30, 0, 0, $width, $height);
    imagepng($previewCard, "$artifactsDir/{$prefix}_preview.png", 9);

    echo "Saved $prefix variants!\n";
}

makeTransparentVariant($swanSource, 'rosa_mascot_swan', $artifactsDir, $publicDir);
makeTransparentVariant($flamingoSource, 'rosa_mascot_flamingo', $artifactsDir, $publicDir);
