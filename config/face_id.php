<?php

return [
    'pc_service_url' => rtrim(env('FACE_PC_SERVICE_URL', 'http://127.0.0.1:8001'), '/'),
    'pi_base_url' => rtrim(env('FACE_PI_BASE_URL', 'http://127.0.0.1:8002'), '/'),
    'api_key' => env('FACE_API_KEY'),
    'request_timeout' => (float) env('FACE_REQUEST_TIMEOUT', 5),
    'connect_timeout' => (float) env('FACE_CONNECT_TIMEOUT', 2),
    'sync_retry_interval' => (int) env('FACE_SYNC_RETRY_INTERVAL', 15),
    'duplicate_threshold' => (float) env('FACE_DUPLICATE_THRESHOLD', 0.65),
    'pi_room_number' => (string) env('FACE_PI_ROOM_NUMBER', '501'),
];
