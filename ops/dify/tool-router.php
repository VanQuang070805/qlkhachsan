<?php

declare(strict_types=1);

// Narrow bridge for Dify: only authenticated, read-only public room tools are proxied.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$allowedPaths = [
    '/api/chatbot/tools/room-types',
    '/api/chatbot/tools/rooms/search',
];

$remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$isLoopback = in_array($remoteAddress, ['127.0.0.1', '::1'], true);
$isDockerDesktopGateway = preg_match('/^192\.168\.65\.\d+$/', $remoteAddress) === 1;

if (! $isLoopback && ! $isDockerDesktopGateway) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Forbidden.']);
    return;
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || ! in_array($path, $allowedPaths, true)
    || (string) ($_SERVER['QUERY_STRING'] ?? '') !== ''
) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Not found.']);
    return;
}

$authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if (! preg_match('/^Bearer [A-Za-z0-9_-]{32,160}$/D', $authorization)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Unauthorized.']);
    return;
}

$body = file_get_contents('php://input') ?: '';
if (strlen($body) > 8192) {
    http_response_code(413);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Request too large.']);
    return;
}

$handle = curl_init('http://127.0.0.1'.$path);
curl_setopt_array($handle, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: '.$authorization,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTP,
]);

$responseBody = curl_exec($handle);
$curlError = curl_error($handle);
$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
curl_close($handle);

if ($responseBody === false) {
    error_log('Royal Dify tool bridge upstream failed: '.$curlError);
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Room tools are temporarily unavailable.']);
    return;
}

http_response_code($status ?: 502);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo $responseBody;
