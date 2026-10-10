<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index()
    {
        app(\App\Services\ExpireNoShowBookings::class)->cancelDueBookings();
        $floorCounts = Room::query()
            ->selectRaw('floor, COUNT(*) AS room_count')
            ->groupBy('floor')
            ->orderBy('floor')
            ->pluck('room_count', 'floor');
        $availableFloors = $floorCounts->keys()->map(fn ($floor) => (int) $floor);
        $selectedFloor = (int) request()->query('floor', $availableFloors->first() ?? 1);

        if ($availableFloors->isNotEmpty() && ! $availableFloors->contains($selectedFloor)) {
            $selectedFloor = (int) $availableFloors->first();
        }

        $rooms = Room::query()->with('roomType.amenities')->where('floor', $selectedFloor)->orderBy('room_number')->get();
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        $reservedToday = Booking::reservedRoomIds($today, now('Asia/Ho_Chi_Minh')->addDay()->toDateString());
        $roomTypes = RoomType::query()->withCount('rooms')->with('amenities')->orderBy('type_name')->get();
        $amenities = Amenity::query()->orderBy('amenity_name')->get();

        return view('admin.rooms.index', compact('rooms', 'roomTypes', 'amenities', 'selectedFloor', 'floorCounts', 'availableFloors', 'reservedToday'));
    }

    public function storeRoom(Request $request)
    {
        $data = $request->validateWithBag('addRoom', [
            'room_number' => ['required', 'string', 'max:20', Rule::in($this->roomNumbersForFloor((int) $request->input('floor'))), 'unique:rooms,room_number'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'floor' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        Room::query()->create($data + ['status' => Room::STATUS_AVAILABLE]);

        return redirect()->route('admin.rooms.index', ['floor' => $data['floor']])->with('success', 'Đã thêm phòng '.$data['room_number'].'.');
    }

    public function updateRoom(Request $request, Room $room)
    {
        $errorBag = 'editRoom'.$room->id;
        $data = $request->validateWithBag($errorBag, [
            'room_number' => ['required', 'string', 'max:20', Rule::in($this->roomNumbersForFloor((int) $request->input('floor'))), Rule::unique('rooms', 'room_number')->ignore($room->id)],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'floor' => ['required', 'integer', 'min:1', 'max:10'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($room, $data, $errorBag, &$storedPaths): void {
                $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);
                $holdColumn = (new Booking())->getUpdatedAtColumn() ?: (new Booking())->getCreatedAtColumn();
                $hasActiveBooking = $lockedRoom->bookings()
                    ->where(function ($query) use ($holdColumn): void {
                        $query->whereIn('bookings.status', ['confirmed', 'checked_in'])
                            ->orWhere(function ($pending) use ($holdColumn): void {
                                $pending->where('bookings.status', 'pending')
                                    ->where(function ($hold) use ($holdColumn): void {
                                        $hold->where('bookings.payment_status', 'paid')
                                            ->orWhere('bookings.'.$holdColumn, '>', now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES));
                                    });
                            });
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($hasActiveBooking && (
                    $lockedRoom->room_number !== $data['room_number']
                    || (int) $lockedRoom->room_type_id !== (int) $data['room_type_id']
                    || (int) $lockedRoom->floor !== (int) $data['floor']
                )) {
                    throw ValidationException::withMessages([
                        'room_type_id' => 'Không thể đổi số phòng, tầng hoặc hạng phòng khi phòng đang có đặt chỗ hoặc khách lưu trú.',
                    ])->errorBag($errorBag);
                }

                $images = $data['images'] ?? [];
                if (count($lockedRoom->galleryImagePaths()) + count($images) > 3) {
                    throw ValidationException::withMessages([
                        'images' => 'Mỗi phòng lưu tối đa 3 ảnh. Hãy chọn số ảnh mới không vượt quá giới hạn còn lại.',
                    ])->errorBag($errorBag);
                }

                $nextSlot = count($lockedRoom->galleryImagePaths()) + 1;
                foreach ($images as $index => $image) {
                    $path = $image->storeAs(
                        'room-galleries/'.$lockedRoom->id,
                        sprintf('%02d-%s.%s', $nextSlot + $index, Str::uuid(), $image->extension()),
                        'public'
                    );
                    if (! $path) {
                        throw ValidationException::withMessages([
                            'images' => 'Không thể lưu ảnh phòng. Vui lòng thử lại.',
                        ])->errorBag($errorBag);
                    }
                    $storedPaths[] = $path;
                }

                $lockedRoom->update(collect($data)->except('images')->all());
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return redirect()->route('admin.rooms.index', ['floor' => $data['floor']])->with('success', 'Đã cập nhật phòng '.$data['room_number'].'.');
    }

    private function roomNumbersForFloor(int $floor): array
    {
        $first = $floor * 100 + 1;

        return array_map('strval', range($first, $first + 4));
    }

    public function storeRoomType(Request $request)
    {
        $data = $this->validatedRoomType($request, null, 'addRoomType');
        $imagePath = $this->storeRoomTypeImage($request, 'addRoomType');
        if ($imagePath) {
            $data['image'] = 'storage/'.$imagePath;
        }

        try {
            DB::transaction(function () use ($data): void {
                $amenityIds = $this->syncAmenities($data, 'addRoomType');
                $roomType = RoomType::query()->create(collect($data)->except(['amenities', 'new_amenities', 'image_file'])->all());
                $roomType->amenities()->sync($amenityIds);
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return back()->with('success', 'Đã thêm hạng phòng.');
    }

    public function updateRoomType(Request $request, RoomType $roomType)
    {
        $errorBag = 'editRoomType'.$roomType->id;
        $data = $this->validatedRoomType($request, $roomType, $errorBag);
        $imagePath = $this->storeRoomTypeImage($request, $errorBag);
        if ($imagePath) {
            $data['image'] = 'storage/'.$imagePath;
        }

        try {
            DB::transaction(function () use ($roomType, $data, $errorBag): void {
                $lockedType = RoomType::query()->lockForUpdate()->findOrFail($roomType->id);
                $amenityIds = $this->syncAmenities($data, $errorBag);
                $lockedType->update(collect($data)->except(['amenities', 'new_amenities', 'image_file'])->all());
                $lockedType->amenities()->sync($amenityIds);
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return back()->with('success', 'Đã cập nhật hạng phòng '.$roomType->type_name.'.');
    }

    private function validatedRoomType(Request $request, ?RoomType $roomType = null, string $errorBag = 'default'): array
    {
        $validated = $request->validateWithBag($errorBag, [
            'type_name' => ['required', 'string', 'max:100', Rule::unique('room_types', 'type_name')->ignore($roomType?->id)],
            'price' => ['required', 'numeric', 'regex:/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/', 'min:1', 'max:9999999999.99'],
            'max_adults' => ['required', 'integer', 'min:1', 'max:100'],
            'max_children' => ['required', 'integer', 'min:0', 'max:100'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],
            'new_amenities' => ['nullable', 'string', 'max:2000'],
        ], [
            'price.regex' => 'Giá phòng chỉ được nhập chữ số và tối đa 2 chữ số thập phân.',
        ]);

        if ($validated['max_guests'] < $validated['max_adults'] + $validated['max_children']) {
            throw ValidationException::withMessages([
                'max_guests' => 'Tổng sức chứa phải bằng hoặc lớn hơn sức chứa người lớn và trẻ em.',
            ])->errorBag($errorBag);
        }

        return $validated;
    }

    private function storeRoomTypeImage(Request $request, string $errorBag): ?string
    {
        if (! $request->hasFile('image_file')) {
            return null;
        }

        $path = $request->file('image_file')->store('room-types', 'public');

        if (! $path) {
            throw ValidationException::withMessages([
                'image_file' => 'Không thể lưu ảnh. Vui lòng thử lại.',
            ])->errorBag($errorBag);
        }

        return $path;
    }

    private function syncAmenities(array $data, string $errorBag = 'default'): array
    {
        $ids = array_map('intval', $data['amenities'] ?? []);
        $names = preg_split('/[\r\n,]+/', $data['new_amenities'] ?? '', -1, PREG_SPLIT_NO_EMPTY);

        foreach (array_unique(array_map('trim', $names ?: [])) as $name) {
            if ($name === '') {
                continue;
            }

            if (mb_strlen($name) > 150) {
                throw ValidationException::withMessages([
                    'new_amenities' => 'Tên mỗi tiện nghi không được vượt quá 150 ký tự.',
                ])->errorBag($errorBag);
            }

            $amenity = Amenity::query()->whereRaw('LOWER(amenity_name) = ?', [mb_strtolower($name)])->first();
            $amenity ??= Amenity::query()->create(['amenity_name' => $name]);
            $ids[] = (int) $amenity->id;
        }

        return array_values(array_unique($ids));
    }
}
