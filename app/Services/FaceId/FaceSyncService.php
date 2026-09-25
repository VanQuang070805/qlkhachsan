<?php

namespace App\Services\FaceId;

use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FaceSyncService
{
    public function __construct(private readonly FaceIdService $faceIds)
    {
    }

    public function syncPending(int $limit = 50): array
    {
        $deactivated = $this->faceIds->reconcileCheckedOutBookings();
        $jobs = FaceSyncJob::query()
            ->with('profile.booking.rooms:id,room_number')
            ->where('status', 'PENDING')
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id')
            ->limit(max(1, min($limit, 500)))
            ->get();

        $result = ['processed' => 0, 'synced' => 0, 'failed' => 0, 'deactivated' => $deactivated];
        foreach ($jobs as $job) {
            $result['processed']++;
            $this->syncOne($job) ? $result['synced']++ : $result['failed']++;
        }
        return $result;
    }

    public function fullSync(): array
    {
        $this->guardConfiguration();
        $this->faceIds->reconcileCheckedOutBookings();
        $profiles = FaceProfile::query()
            ->with('booking.rooms:id,room_number')
            ->where('active', true)
            ->get()
            ->map(fn (FaceProfile $profile) => $this->profilePayload($profile))
            ->values()
            ->all();

        $response = $this->client()->post(config('face_id.pi_base_url').'/api/faces/full-sync', [
            'complete' => true,
            'faces' => $profiles,
        ]);
        if (!$response->successful()) {
            throw new RuntimeException('Pi full sync failed with HTTP '.$response->status());
        }
        FaceSyncJob::query()->where('status', 'PENDING')->update([
            'status' => 'SYNCED',
            'synced_at' => now(),
            'last_attempt_at' => now(),
            'next_attempt_at' => null,
            'last_error' => null,
        ]);
        return $response->json();
    }

    public function health(): array
    {
        try {
            $response = Http::connectTimeout(1)->timeout(2)->get(config('face_id.pi_base_url').'/api/health');
            return ['online' => $response->successful(), 'status' => $response->status(), 'data' => $response->json()];
        } catch (Throwable) {
            return ['online' => false, 'status' => null, 'data' => null];
        }
    }

    private function syncOne(FaceSyncJob $job): bool
    {
        try {
            $this->guardConfiguration();
            $profile = $job->profile;
            if (!$profile) {
                throw new RuntimeException('Face profile no longer exists');
            }

            $baseUrl = config('face_id.pi_base_url').'/api/faces/'.rawurlencode($profile->id);
            $response = match ($job->action) {
                'ADD' => $this->client()->post(config('face_id.pi_base_url').'/api/faces', $this->profilePayload($profile)),
                'UPDATE' => $this->client()->put($baseUrl, $this->profilePayload($profile)),
                'DELETE' => $this->client()->delete($baseUrl),
                default => throw new RuntimeException('Unsupported sync action'),
            };

            if (!$response->successful()) {
                throw new RuntimeException('Pi returned HTTP '.$response->status());
            }

            $job->update([
                'status' => 'SYNCED',
                'synced_at' => now(),
                'last_attempt_at' => now(),
                'next_attempt_at' => null,
                'last_error' => null,
            ]);
            Log::info('[FACE SYNC] Success', ['profile_id' => $profile->id, 'action' => $job->action]);
            return true;
        } catch (Throwable $error) {
            $retries = $job->retry_count + 1;
            $baseDelay = max(5, (int) config('face_id.sync_retry_interval', 15));
            $delay = min(900, $baseDelay * (2 ** min($retries - 1, 6)));
            $message = $error instanceof ConnectionException
                ? 'Pi unavailable; retry later'
                : substr($error->getMessage(), 0, 500);
            $job->update([
                'status' => 'PENDING',
                'retry_count' => $retries,
                'last_attempt_at' => now(),
                'next_attempt_at' => now()->addSeconds($delay),
                'last_error' => $message,
            ]);
            Log::warning('[FACE SYNC] Failed; retry later', [
                'profile_id' => $job->face_profile_id,
                'action' => $job->action,
                'retry_count' => $retries,
            ]);
            return false;
        }
    }

    private function profilePayload(FaceProfile $profile): array
    {
        $booking = $profile->booking;
        if (!$booking) {
            throw new RuntimeException('Booking for face profile no longer exists');
        }
        return [
            'customer_id' => $profile->id,
            'booking_id' => $booking->id,
            'user_id' => $profile->user_id,
            'name' => $booking->customer_name,
            'room' => $booking->rooms->pluck('room_number')->join(', '),
            'embedding' => $profile->embedding,
            'embedding_model' => $profile->embedding_model,
            'version' => $profile->version,
            'valid_until' => optional($booking->check_out)->toDateString(),
        ];
    }

    private function client()
    {
        return Http::acceptJson()
            ->withHeaders(['X-API-Key' => (string) config('face_id.api_key')])
            ->connectTimeout((float) config('face_id.connect_timeout', 2))
            ->timeout((float) config('face_id.request_timeout', 5));
    }

    private function guardConfiguration(): void
    {
        if (!config('face_id.api_key')) {
            throw new RuntimeException('FACE_API_KEY is not configured');
        }
    }
}
