<?php
$sourceBgPath = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/rosa_mascot_flamingo_1791539959753.jpg';
$artifactsDir = 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5';
$publicBrandingDir = __DIR__ . '/../public/images/branding';
$publicDir = __DIR__ . '/../public';

if (!file_exists($publicBrandingDir)) {
    mkdir($publicBrandingDir, 0777, true);
}

$im = imagecreatefromjpeg($sourceBgPath);
$w = imagesx($im);
$h = imagesy($im);

// 1. Calculate bounding box of the graphic
// Sample background lum
$bgLums = [];
for ($y = 0; $y < 40; $y++) {
    for ($x = 0; $x < 40; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        $bgLums[] = 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }
}
sort($bgLums);
$bgLum = $bgLums[(int)(count($bgLums) * 0.9)];
$thresholdHigh = $bgLum - 8.0;
$thresholdLow = 30.0;

$minX = $w; $maxX = 0; $minY = $h; $maxY = 0;
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        if ($lum < $thresholdHigh) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

$bw = $maxX - $minX + 1;
$bh = $maxY - $minY + 1;
$pad = 24; // clean border padding
$cropX = max(0, $minX - $pad);
$cropY = max(0, $minY - $pad);
$cropW = min($w - $cropX, $bw + $pad * 2);
$cropH = min($h - $cropY, $bh + $pad * 2);

echo "Tight Crop: X=$cropX, Y=$cropY, W=$cropW, H=$cropH\n";

// 2. Generate tight transparent black image
$cropBlackTrans = imagecreatetruecolor($cropW, $cropH);
imagealphablending($cropBlackTrans, false);
imagesavealpha($cropBlackTrans, true);
$clearColor = imagecolorallocatealpha($cropBlackTrans, 0, 0, 0, 127);
imagefill($cropBlackTrans, 0, 0, $clearColor);

// 3. Generate tight transparent white image (for black notch & dark footer)
$cropWhiteTrans = imagecreatetruecolor($cropW, $cropH);
imagealphablending($cropWhiteTrans, false);
imagesavealpha($cropWhiteTrans, true);
$clearWhite = imagecolorallocatealpha($cropWhiteTrans, 255, 255, 255, 127);
imagefill($cropWhiteTrans, 0, 0, $clearWhite);

for ($dstY = 0; $dstY < $cropH; $dstY++) {
    $srcY = $cropY + $dstY;
    for ($dstX = 0; $dstX < $cropW; $dstX++) {
        $srcX = $cropX + $dstX;
        $rgb = imagecolorat($im, $srcX, $srcY);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;

        if ($lum >= $thresholdHigh) {
            imagesetpixel($cropBlackTrans, $dstX, $dstY, $clearColor);
            imagesetpixel($cropWhiteTrans, $dstX, $dstY, $clearWhite);
        } elseif ($lum <= $thresholdLow) {
            $colB = imagecolorallocatealpha($cropBlackTrans, 0, 0, 0, 0);
            $colW = imagecolorallocatealpha($cropWhiteTrans, 255, 255, 255, 0);
            imagesetpixel($cropBlackTrans, $dstX, $dstY, $colB);
            imagesetpixel($cropWhiteTrans, $dstX, $dstY, $colW);
        } else {
            $opacity = ($thresholdHigh - $lum) / ($thresholdHigh - $thresholdLow);
            $opacity = $opacity * $opacity * (3 - 2 * $opacity);
            $gdAlpha = (int)round((1.0 - $opacity) * 127);
            $gdAlpha = max(0, min(127, $gdAlpha));

            $colB = imagecolorallocatealpha($cropBlackTrans, 0, 0, 0, $gdAlpha);
            $colW = imagecolorallocatealpha($cropWhiteTrans, 255, 255, 255, $gdAlpha);
            imagesetpixel($cropBlackTrans, $dstX, $dstY, $colB);
            imagesetpixel($cropWhiteTrans, $dstX, $dstY, $colW);
        }
    }
}

// Save tight transparent logos
imagepng($cropBlackTrans, "$publicBrandingDir/rosa_mascot_flamingo_transparent.png", 9);
imagepng($cropBlackTrans, "$artifactsDir/rosa_mascot_flamingo_transparent.png", 9);

imagepng($cropWhiteTrans, "$publicBrandingDir/rosa_mascot_flamingo_white_transparent.png", 9);
imagepng($cropWhiteTrans, "$artifactsDir/rosa_mascot_flamingo_white_transparent.png", 9);

// 4. Save tight version with background
$cropWithBg = imagecreatetruecolor($cropW, $cropH);
imagecopy($cropWithBg, $im, 0, 0, $cropX, $cropY, $cropW, $cropH);
imagepng($cropWithBg, "$publicBrandingDir/rosa_mascot_flamingo_with_bg.png", 9);
imagepng($cropWithBg, "$artifactsDir/rosa_mascot_flamingo_with_bg.png", 9);

// 5. Square Favicon Generator with background (Clean centered square with padding)
$sqSize = max($bw, $bh) + 80;
$sqCenterX = $minX + $bw / 2;
$sqCenterY = $minY + $bh / 2;
$sqX = max(0, (int)round($sqCenterX - $sqSize / 2));
$sqY = max(0, (int)round($sqCenterY - $sqSize / 2));
$sqW = min($w - $sqX, $sqSize);
$sqH = min($h - $sqY, $sqSize);

$squareFaviconBase = imagecreatetruecolor(512, 512);
imagecopyresampled($squareFaviconBase, $im, 0, 0, $sqX, $sqY, 512, 512, $sqW, $sqH);

// Save full 512x512 favicon
imagepng($squareFaviconBase, "$publicDir/favicon-512x512.png", 9);
imagepng($squareFaviconBase, "$publicDir/favicon.png", 9);
imagepng($squareFaviconBase, "$publicBrandingDir/favicon_master.png", 9);

// Generate 180x180 (Apple touch icon)
$fav180 = imagecreatetruecolor(180, 180);
imagecopyresampled($fav180, $squareFaviconBase, 0, 0, 0, 0, 180, 180, 512, 512);
imagepng($fav180, "$publicDir/apple-touch-icon.png", 9);

// Generate 192x192 (Android / PWA)
$fav192 = imagecreatetruecolor(192, 192);
imagecopyresampled($fav192, $squareFaviconBase, 0, 0, 0, 0, 192, 192, 512, 512);
imagepng($fav192, "$publicDir/favicon-192x192.png", 9);

// Generate 32x32
$fav32 = imagecreatetruecolor(32, 32);
imagecopyresampled($fav32, $squareFaviconBase, 0, 0, 0, 0, 32, 32, 512, 512);
imagepng($fav32, "$publicDir/favicon-32x32.png", 9);

// Generate 16x16
$fav16 = imagecreatetruecolor(16, 16);
imagecopyresampled($fav16, $squareFaviconBase, 0, 0, 0, 0, 16, 16, 512, 512);
imagepng($fav16, "$publicDir/favicon-16x16.png", 9);

// Copy 32x32 as favicon.ico
imagepng($fav32, "$publicDir/favicon.ico");

echo "All branding and favicon assets successfully generated!\n";
