<?php

$image = static fn (string $id): string => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=1920&q=88";

return [
    // 1: Phòng Đơn Tiêu Chuẩn (Single Standard)
    1 => [
        $image('photo-1631049307264-da0ec9d70304'),
        $image('photo-1566665797739-1674de7a421a'),
        $image('photo-1590490359683-658d3d23f972'),
        $image('photo-1505693416388-ac5ce068fe85'),
    ],
    // 2: Phòng Đôi Tiêu Chuẩn (Double Standard)
    2 => [
        $image('photo-1631049552057-403cdb8f0658'),
        $image('photo-1591088398332-8a7791972843'),
        $image('photo-1618773928121-c32242e63f39'),
        $image('photo-1595526114035-0d45ed16cfbf'),
    ],
    // 3: Phòng Triple (Triple Room)
    3 => [
        $image('photo-1598928506311-c55ded91a20c'),
        $image('photo-1600210492486-724fe5c67fb0'),
        $image('photo-1560448204-e02f11c3d0e2'),
        $image('photo-1586023492125-27b2c045efd7'),
    ],
    // 4: Phòng Gia Đình (Family Suite)
    4 => [
        $image('photo-1600607687920-4e2a09cf159d'),
        $image('photo-1600566753190-17f0baa2a6c3'),
        $image('photo-1590490360182-c33d57733427'),
        $image('photo-1596394516093-501ba68a0ba6'),
    ],
    // 5: Phòng VIP (Presidential VIP Suite)
    5 => [
        $image('photo-1611892440504-42a792e24d32'),
        $image('photo-1578683010236-d716f9a3f461'),
        $image('photo-1616594039964-ae9021a400a0'),
        $image('photo-1582719478250-c89cae4dc85b'),
    ],
];
