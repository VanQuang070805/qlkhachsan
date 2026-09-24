<?php

return [
    // Display only. Raspberry Pi connects to its own CAMERA_URL directly.
    'camera_stream_url' => env('IOT_CAMERA_STREAM_URL', 'http://172.20.10.3/stream'),
];
