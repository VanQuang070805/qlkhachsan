<?php

$image = static fn (string $id): string => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=1920&q=88";

return [
    1 => [
        $image('photo-1631049307264-da0ec9d70304'),
        $image('photo-1618773928121-c32242e63f39'),
        $image('photo-1566665797739-1674de7a421a'),
        $image('photo-1590490360182-c33d57733427'),
    ],
    2 => [
        $image('photo-1631049552057-403cdb8f0658'),
        $image('photo-1591088398332-8a7791972843'),
        $image('photo-1618773928121-c32242e63f39'),
        $image('photo-1590490360182-c33d57733427'),
    ],
    3 => [
        $image('photo-1598928506311-c55ded91a20c'),
        $image('photo-1600210492486-724fe5c67fb0'),
        $image('photo-1560448204-e02f11c3d0e2'),
        $image('photo-1586023492125-27b2c045efd7'),
    ],
    4 => [
        $image('photo-1566665797739-1674de7a421a'),
        $image('photo-1600607687920-4e2a09cf159d'),
        $image('photo-1600566753190-17f0baa2a6c3'),
        $image('photo-1590490360182-c33d57733427'),
    ],
    5 => [
        $image('photo-1611892440504-42a792e24d32'),
        $image('photo-1600607687939-ce8a6c25118c'),
        $image('photo-1600210491892-03d54c0aaf87'),
        $image('photo-1600607688969-a5bfcd646154'),
    ],
];
