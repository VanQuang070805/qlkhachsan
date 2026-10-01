<?php

return [
    'pi_camera_model' => env('IOT_PI_CAMERA_MODEL', 'Raspberry Pi Camera Module Rev 1.3'),
    'device_api_key' => env('IOT_DEVICE_API_KEY'),
    'cleaning_room_number' => (string) env('IOT_CLEANING_ROOM_NUMBER', '501'),
];
