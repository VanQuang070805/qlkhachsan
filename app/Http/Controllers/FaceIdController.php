<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use App\Models\Room;
use App\Services\FaceId\FaceIdService;
use App\Services\FaceId\FaceSyncService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class FaceIdController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::query()
            ->select(['id', 'room_number', 'status'])
            ->where('status', Room::STATUS_OCCUPIED)
            ->whereHas('bookings', fn ($query) => $query->where('status', 'checked_in'))
            ->with(['bookings' => fn ($query) => $query
                ->where('status', 'checked_in')
                ->select(['bookings.id', 'customer_name', 'customer_phone', 'status', 'user_id'])])
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'booking' => $room->bookings->first(),
            ])
            ->filter(fn (array $room) => $room['booking'] !== null)
            ->values();
        $profiles = FaceProfile::query()
            ->with(['booking:id,customer_name,status', 'room:id,room_number'])
            ->where('active', true)
            ->whereHas('booking', fn ($query) => $query->where('status', 'checked_in'))
            ->latest('updated_at')
            ->get();

        return response()->view('staff.iot', compact('profiles', 'rooms'))
            ->header('Cache-Control', 'no-store');
    }

    public function createSession(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_cccd' => ['nullable', 'regex:/^[0-9]{9,12}$/'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'consent' => ['accepted'],
        ]);
        $booking = Booking::findOrFail($validated['booking_id']);
        if ($booking->status !== 'checked_in') {
            return response()->json(['message' => 'Booking phải đang check-in để đăng ký Face ID.'], 422);
        }
        $room = Room::findOrFail($validated['room_id']);
        if (! $booking->rooms()->whereKey($room->id)->exists() || $room->status !== Room::STATUS_OCCUPIED) {
            return response()->json(['message' => 'Phòng phải thuộc booking và đang có khách để đăng ký Face ID.'], 422);
        }

        try {
            $response = $this->pcClient()->post(config('face_id.pc_service_url').'/api/enrollment-sessions');
            if (! $response->successful()) {
                return response()->json(['message' => 'Dịch vụ nhận diện chưa sẵn sàng.'], 503);
            }
            $sessionId = (string) $response->json('session_id');
            session()->put('face_enrollment.'.$sessionId, [
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'guest_name' => $validated['guest_name'],
                'guest_cccd' => $validated['guest_cccd'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'created_at' => now()->timestamp,
            ]);

            return response()->json(['session_id' => $sessionId, 'target' => (int) $response->json('target')])
                ->header('Cache-Control', 'private, no-store');
        } catch (ConnectionException) {
            return response()->json(['message' => 'Không kết nối được dịch vụ Face ID trên máy lễ tân.'], 503);
        }
    }

    public function addSample(Request $request, string $sessionId, FaceIdService $faceIds)
    {
        $request->validate(['frame' => ['required', 'image', 'max:3072']]);
        $session = session('face_enrollment.'.$sessionId);
        if (! $session || now()->timestamp - (int) $session['created_at'] > 600) {
            session()->forget('face_enrollment.'.$sessionId);

            return response()->json(['message' => 'Phiên đăng ký đã hết hạn.'], 404);
        }

        try {
            $file = $request->file('frame');
            $response = $this->pcClient()
                ->attach('frame', file_get_contents($file->getRealPath()), 'frame.jpg')
                ->post(config('face_id.pc_service_url').'/api/enrollment-sessions/'.rawurlencode($sessionId).'/samples');
            if (! $response->successful()) {
                return response()->json(['message' => 'Không xử lý được khung hình.'], $response->status() >= 500 ? 503 : 422);
            }

            $result = $response->json();
            if (! empty($result['complete'])) {
                $booking = Booking::findOrFail((int) $session['booking_id']);
                $room = Room::findOrFail((int) $session['room_id']);
                $profile = $faceIds->enroll(
                    $booking,
                    $room,
                    $result['embedding'],
                    (int) $result['samples'],
                    [
                        'guest_name' => $session['guest_name'],
                        'guest_cccd' => $session['guest_cccd'],
                        'guest_phone' => $session['guest_phone'],
                    ],
                );
                session()->forget('face_enrollment.'.$sessionId);
                try {
                    $this->pcClient()->delete(config('face_id.pc_service_url').'/api/enrollment-sessions/'.rawurlencode($sessionId));
                } catch (Throwable) {
                    // The in-memory PC session also expires automatically.
                }
                $result = [
                    'accepted' => true,
                    'samples' => (int) $result['samples'],
                    'target' => (int) $result['target'],
                    'complete' => true,
                    'profile_id' => $profile->id,
                    'sync_status' => 'PENDING',
                ];
            } else {
                unset($result['embedding']);
            }

            return response()->json($result)->header('Cache-Control', 'private, no-store');
        } catch (ConnectionException) {
            return response()->json(['message' => 'Mất kết nối dịch vụ Face ID trên máy lễ tân.'], 503);
        } catch (InvalidArgumentException $error) {
            session()->forget('face_enrollment.'.$sessionId);
            try {
                $this->pcClient()->delete(config('face_id.pc_service_url').'/api/enrollment-sessions/'.rawurlencode($sessionId));
            } catch (Throwable) {
                // The PC session expires automatically.
            }

            return response()->json(['message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            report($error);

            return response()->json(['message' => 'Không thể hoàn tất đăng ký Face ID.'], 500);
        }
    }

    public function cancelSession(string $sessionId)
    {
        session()->forget('face_enrollment.'.$sessionId);
        try {
            $this->pcClient()->delete(config('face_id.pc_service_url').'/api/enrollment-sessions/'.rawurlencode($sessionId));
        } catch (Throwable) {
            // Local session is already invalidated; PC sessions expire automatically.
        }

        return response()->json(['status' => 'cancelled']);
    }

    public function recognize(Request $request)
    {
        $request->validate(['frame' => ['required', 'image', 'max:3072']]);
        $profiles = FaceProfile::query()
            ->with(['booking', 'room:id,room_number'])
            ->where('active', true)
            ->whereHas('booking', fn ($query) => $query->where('status', 'checked_in'))
            ->get();

        if ($profiles->isEmpty()) {
            return response()->json(['message' => 'Chưa có Face ID đang hoạt động để so sánh.'], 422);
        }

        $candidates = $profiles->map(fn (FaceProfile $profile) => [
            'customer_id' => $profile->id,
            'embedding' => $profile->embedding,
        ])->values()->all();

        try {
            $file = $request->file('frame');
            $response = $this->pcClient()
                ->attach('frame', file_get_contents($file->getRealPath()), 'frame.jpg')
                ->post(config('face_id.pc_service_url').'/api/recognize', [
                    'candidates' => json_encode($candidates, JSON_THROW_ON_ERROR),
                ]);
            if (! $response->successful()) {
                return response()->json(['message' => 'Không xử lý được ảnh test Face ID.'], $response->status() >= 500 ? 503 : 422);
            }

            $result = $response->json();
            if (empty($result['matched'])) {
                return response()->json([
                    'matched' => false,
                    'reason' => $result['reason'] ?? 'unknown_face',
                    'score' => $result['score'] ?? null,
                    'second_score' => $result['second_score'] ?? null,
                    'threshold' => $result['threshold'] ?? null,
                ])->header('Cache-Control', 'private, no-store');
            }

            $profile = $profiles->firstWhere('id', $result['customer_id'] ?? '');
            if (! $profile || ! $profile->booking) {
                return response()->json(['message' => 'Kết quả Face ID không còn hiệu lực.'], 422);
            }

            return response()->json([
                'matched' => true,
                'score' => $result['score'],
                'threshold' => $result['threshold'],
                'booking_id' => $profile->booking->id,
                'customer_name' => $profile->guest_name ?: $profile->booking->customer_name,
                'guest_cccd' => $profile->guest_cccd,
                'guest_phone' => $profile->guest_phone,
                'room' => $profile->room?->room_number,
            ])->header('Cache-Control', 'private, no-store');
        } catch (ConnectionException) {
            return response()->json(['message' => 'Mất kết nối dịch vụ Face ID trên máy lễ tân.'], 503);
        } catch (Throwable $error) {
            report($error);

            return response()->json(['message' => 'Không thể test Face ID lúc này.'], 500);
        }
    }

    public function health(FaceSyncService $sync)
    {
        $targetRoom = (string) config('face_id.pi_room_number', '501');
        try {
            $pc = Http::connectTimeout(1)->timeout(2)->get(config('face_id.pc_service_url').'/api/health');
            $pcOnline = $pc->successful();
        } catch (Throwable) {
            $pcOnline = false;
        }

        return response()->json([
            'pc_online' => $pcOnline,
            'pi' => $sync->health(),
            'pending' => FaceSyncJob::query()
                ->where('status', 'PENDING')
                ->whereHas('profile.room', fn ($query) => $query->where('room_number', $targetRoom))
                ->count(),
            'active_profiles' => FaceProfile::query()
                ->where('active', true)
                ->whereHas('room', fn ($query) => $query->where('room_number', $targetRoom))
                ->count(),
            'pi_room' => $targetRoom,
        ]);
    }

    public function profiles(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
        ]);
        [$booking, $room] = $this->checkedInStay((int) $validated['booking_id'], (int) $validated['room_id']);

        $profiles = FaceProfile::query()
            ->where('booking_id', $booking->id)
            ->where('room_id', $room->id)
            ->where('active', true)
            ->latest('updated_at')
            ->get()
            ->map(fn (FaceProfile $profile) => $this->profileData($profile));

        return response()->json([
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'booking_customer_name' => $booking->customer_name,
            'booking_customer_phone' => $booking->customer_phone,
            'profiles' => $profiles,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function updateProfile(Request $request, FaceProfile $profile, FaceIdService $faceIds)
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_cccd' => ['nullable', 'regex:/^[0-9]{9,12}$/'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
        ]);
        $this->assertEditableProfile($profile);
        $updated = $faceIds->updateGuest($profile, $validated);

        return response()->json([
            'message' => 'Đã cập nhật thông tin khách.',
            'profile' => $this->profileData($updated),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function deleteProfile(FaceProfile $profile, FaceIdService $faceIds)
    {
        $this->assertEditableProfile($profile);
        $faceIds->deactivateProfile($profile);

        return response()->json(['message' => 'Đã xóa khách và vô hiệu hóa Face ID.']);
    }

    public function sync(FaceSyncService $sync)
    {
        return response()->json($sync->syncPending());
    }

    public function fullSync(FaceSyncService $sync)
    {
        try {
            return response()->json($sync->fullSync());
        } catch (Throwable $error) {
            report($error);

            return response()->json(['message' => 'Full sync thất bại; dữ liệu trên Pi được giữ nguyên.'], 503);
        }
    }

    private function pcClient()
    {
        return Http::acceptJson()
            ->connectTimeout((float) config('face_id.connect_timeout', 2))
            ->timeout(15);
    }

    private function checkedInStay(int $bookingId, int $roomId): array
    {
        $booking = Booking::findOrFail($bookingId);
        $room = Room::findOrFail($roomId);
        if ($booking->status !== 'checked_in'
            || $room->status !== Room::STATUS_OCCUPIED
            || ! $booking->rooms()->whereKey($room->id)->exists()) {
            abort(422, 'Phòng chưa có khách đang check-in.');
        }

        return [$booking, $room];
    }

    private function assertEditableProfile(FaceProfile $profile): void
    {
        $profile->loadMissing(['booking:id,status', 'room:id,status']);
        if (! $profile->active
            || $profile->booking?->status !== 'checked_in'
            || $profile->room?->status !== Room::STATUS_OCCUPIED) {
            abort(422, 'Face ID này không còn thuộc phòng đang có khách.');
        }
    }

    private function profileData(FaceProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'guest_name' => $profile->guest_name ?: $profile->booking?->customer_name,
            'guest_cccd' => $profile->guest_cccd,
            'guest_phone' => $profile->guest_phone,
            'updated_at' => optional($profile->updated_at)->format('d/m/Y H:i'),
        ];
    }
}
