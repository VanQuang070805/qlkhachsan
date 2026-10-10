<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomCapacitySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roomTypeIds = DB::table('room_types')->orderBy('id')->pluck('id')->all();
            if ($roomTypeIds === []) {
                throw new \RuntimeException('Tạo loại phòng trước khi thêm phòng.');
            }

            $existing = DB::table('rooms')->pluck('room_number')->mapWithKeys(
                fn ($number) => [(string) $number => true]
            );
            $roomCount = $existing->count();
            $rooms = [];

            for ($floor = 6; $floor <= 10 && $roomCount + count($rooms) < 50; $floor++) {
                for ($suffix = 1; $suffix <= 5 && $roomCount + count($rooms) < 50; $suffix++) {
                    $number = (string) ($floor * 100 + $suffix);
                    if ($existing->has($number)) {
                        continue;
                    }

                    $rooms[] = [
                        'room_number' => $number,
                        'room_type_id' => $roomTypeIds[($floor + $suffix - 2) % count($roomTypeIds)],
                        'floor' => $floor,
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($rooms !== []) {
                DB::table('rooms')->insert($rooms);
            }
        });
    }
}
