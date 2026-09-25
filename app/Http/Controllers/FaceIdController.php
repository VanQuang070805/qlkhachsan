<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use App\Services\FaceId\FaceIdService;
use App\Services\FaceId\FaceSyncService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class FaceIdController extends Controller
{
    public function index()
    {
        $cameraUrl = config('iot.camera_stream_url');
        $cameraLinkAllowed = filter_var($cameraUrl, FILTER_VALIDATE_URL)
            && in_array(parse_url($cameraUrl, PHP_URL_SCHEME), ['http', 'https'], true)
            && !parse_url($cameraUrl, PHP_URL_USER) && !parse_url($cameraUrl, PHP_URL_PASS);
        $bookings = Booking::query()
            ->with(['rooms:id,room_number', 'faceProfile:id,booking_id,active,updated_at'])
            ->where('status', 'checked_in')
            ->orderByDesc('actual_check_in')
            ->get();

        return response()->view('staff.iot', compact('cameraUrl', 'cameraLinkAllowed', 'bookings'))
            ->header('Cache-Control', 'no-store');
    }

    public function createSession(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'consent' => ['accepted'],
        ]);
        $booking = Booking::findOrFail($validated['booking_id']);
        if ($booking->status !== 'checked_in') {
            return response()->json(['message' => 'Booking phải đang check-in để đăng ký Face ID.'], 422);
        }

        try {
            $response = $this->pcClient()->post(config('face_id.pc_service_url').'/api/enrollment-sessions');
            if (!$response->successful()) {
                return response()->json(['message' => 'Dịch vụ nhận diện chưa sẵn sàng.'], 503);
            }
            $sessionId = (string) $response->json('session_id');
            session()->put('face_enrollment.'.$sessionId, [
                'booking_id' => $booking->id,
                'created_at' => now()->timestamp,
            ]);
            return response()->json(['session_id' => $sessionId, 'target' => (int) $response->json('target')]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Không kết nối được dịch vụ Face ID trên máy lễ tân.'], 503);
        }
    }

    public function addSample(Request $request, string $sessionId, FaceIdService $faceIds)
    {
        $request->validate(['frame' => ['required', 'image', 'max:3072']]);
        $session = session('face_enrollment.'.$sessionId);
        if (!$session || now()->timestamp - (int) $session['created_at'] > 600) {
            session()->forget('face_enrollment.'.$sessionId);
            return response()->json(['message' => 'Phiên đăng ký đã hết hạn.'], 404);
        }

        try {
            $file = $request->file('frame');
            $response = $this->pcClient()
                ->attach('frame', file_get_contents($file->getRealPath()), 'frame.jpg')
                ->post(config('face_id.pc_service_url').'/api/enrollment-sessions/'.rawurlencode($sessionId).'/samples');
            if (!$response->successful()) {
                return response()->json(['message' => 'Không xử lý được khung hình.'], $response->status() >= 500 ? 503 : 422);
            }

            $result = $response->json();
            if (!empty($result['complete'])) {
                $booking = Booking::findOrFail((int) $session['booking_id']);
                $profile = $faceIds->enroll($booking, $result['embedding'], (int) $result['samples']);
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
            return response()->json($result);
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
            ->with('booking.rooms:id,room_number')
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
            if (!$response->successful()) {
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
                ]);
            }

            $profile = $profiles->firstWhere('id', $result['customer_id'] ?? '');
            if (!$profile || !$profile->booking) {
                return response()->json(['message' => 'Kết quả Face ID không còn hiệu lực.'], 422);
            }

            return response()->json([
                'matched' => true,
                'score' => $result['score'],
                'threshold' => $result['threshold'],
                'booking_id' => $profile->booking->id,
                'customer_name' => $profile->booking->customer_name,
                'room' => $profile->booking->rooms->pluck('room_number')->join(', '),
            ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Mất kết nối dịch vụ Face ID trên máy lễ tân.'], 503);
        } catch (Throwable $error) {
            report($error);
            return response()->json(['message' => 'Không thể test Face ID lúc này.'], 500);
        }
    }

    public function health(FaceSyncService $sync)
    {
        try {
            $pc = Http::connectTimeout(1)->timeout(2)->get(config('face_id.pc_service_url').'/api/health');
            $pcOnline = $pc->successful();
        } catch (Throwable) {
            $pcOnline = false;
        }
        return response()->json([
            'pc_online' => $pcOnline,
            'pi' => $sync->health(),
            'pending' => FaceSyncJob::where('status', 'PENDING')->count(),
            'active_profiles' => FaceProfile::where('active', true)->count(),
        ]);
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
}
