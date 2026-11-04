<?php

$publicPath = realpath(__DIR__ . '/../../public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($uri !== '/' && $publicPath) {
    $target = $publicPath . $uri;
    if (file_exists($target) && !is_dir($target)) {
        $realTarget = realpath($target);
        if ($realTarget && is_file($realTarget)) {
            $ext = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
            $mimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'woff2' => 'font/woff2',
                'woff' => 'font/woff',
                'ttf' => 'font/ttf',
            ];
            if (isset($mimes[$ext])) {
                header('Content-Type: ' . $mimes[$ext]);
                header('Content-Length: ' . filesize($realTarget));
                readfile($realTarget);
                exit;
            }
        }
        return false;
    }
}

require_once $publicPath . '/index.php';
