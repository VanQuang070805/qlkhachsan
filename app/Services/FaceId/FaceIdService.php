<?php

namespace App\Services\FaceId;

use App\Models\Booking;
use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class FaceIdService
{
    public function enroll(
        Booking $booking,
        Room $room,
        array $embedding,
        int $sampleCount = 15,
        array $guest = [],
    ): FaceProfile
    {
        $normalized = $this->normalizeEmbedding($embedding);

        $needsSync = false;
        $profile = DB::transaction(function () use ($booking, $room, $normalized, $sampleCount, $guest, &$needsSync) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            if ($booking->status !== 'checked_in') {
                throw new InvalidArgumentException('Chỉ đăng ký Face ID cho booking đang check-in.');
            }
            if (! $booking->rooms()->whereKey($room->id)->exists() || $room->status !== Room::STATUS_OCCUPIED) {
                throw new InvalidArgumentException('Phòng phải thuộc booking và đang có khách để đăng ký Face ID.');
            }

            $duplicate = FaceProfile::query()
                ->with(['booking:id,customer_name', 'room:id,room_number'])
                ->where('active', true)
                ->lockForUpdate()
                ->get()
                ->first(function (FaceProfile $profile) use ($normalized) {
                    $stored = $profile->embedding;
                    if (count($stored) !== count($normalized)) {
                        return false;
                    }

                    $score = array_sum(array_map(
                        fn (float $first, float $second) => $first * $second,
                        $normalized,
                        $stored,
                    ));

                    return $score >= (float) config('face_id.duplicate_threshold', 0.65);
                });

            if ($duplicate) {
                $owner = $duplicate->booking?->customer_name ?? '#'.$duplicate->booking_id;
                $registeredRoom = $duplicate->room?->room_number;
                throw new InvalidArgumentException(
                    'Khuôn mặt này đã được đăng ký cho khách '.$owner.($registeredRoom ? ' tại phòng '.$registeredRoom : '').'.'
                );
            }

            $profile = FaceProfile::create([
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'user_id' => $booking->user_id,
                'guest_name' => trim((string) ($guest['guest_name'] ?? $booking->customer_name)),
                'guest_cccd' => $this->nullableText($guest['guest_cccd'] ?? null),
                'guest_phone' => $this->nullableText($guest['guest_phone'] ?? $booking->customer_phone),
                'embedding' => $normalized,
                'embedding_model' => 'sface_2021dec',
                'embedding_dimension' => count($normalized),
                'sample_count' => $sampleCount,
                'version' => 1,
                'active' => true,
                'consent_at' => now(),
                'revoked_at' => null,
            ]);

            if ($this->shouldSyncToPi($profile)) {
                $this->replacePendingJob($profile, 'ADD');
                $needsSync = true;
            }

            return $profile->fresh();
        });

        $this->syncAfterCommit($needsSync);

        return $profile;
    }

    public function deactivateForBooking(int $bookingId, bool $syncImmediately = true): int
    {
        $needsSync = false;
        $count = DB::transaction(function () use ($bookingId, &$needsSync) {
            $profiles = FaceProfile::query()
                ->with('room:id,room_number')
                ->lockForUpdate()
                ->where('booking_id', $bookingId)
                ->where('active', true)
                ->get();

            foreach ($profiles as $profile) {
                $profile->update([
                    'active' => false,
                    'revoked_at' => now(),
                    'version' => $profile->version + 1,
                ]);

                if ($this->shouldSyncToPi($profile)) {
                    $this->replacePendingJob($profile, 'DELETE');
                    $needsSync = true;
                }
            }

            return $profiles->count();
        });

        $this->syncAfterCommit($needsSync && $syncImmediately);

        return $count;
    }

    public function reconcileCheckedOutBookings(): int
    {
        $count = 0;
        $bookingIds = FaceProfile::query()
            ->where('active', true)
            ->whereHas('booking', fn ($query) => $query->whereIn('status', ['completed', 'cancelled']))
            ->distinct()
            ->pluck('booking_id');

        foreach ($bookingIds as $bookingId) {
            $count += $this->deactivateForBooking((int) $bookingId, false);
        }

        return $count;
    }

    public function updateGuest(FaceProfile $profile, array $guest): FaceProfile
    {
        $needsSync = false;
        $updated = DB::transaction(function () use ($profile, $guest, &$needsSync) {
            $locked = FaceProfile::query()->lockForUpdate()->findOrFail($profile->id);
            if (! $locked->active) {
                throw new InvalidArgumentException('Khách này không còn Face ID hoạt động.');
            }

            $locked->update([
                'guest_name' => trim((string) $guest['guest_name']),
                'guest_cccd' => $this->nullableText($guest['guest_cccd'] ?? null),
                'guest_phone' => $this->nullableText($guest['guest_phone'] ?? null),
                'version' => $locked->version + 1,
            ]);

            if ($this->shouldSyncToPi($locked)) {
                $this->replacePendingJob($locked, 'UPDATE');
                $needsSync = true;
            }

            return $locked->fresh(['room', 'booking']);
        });

        $this->syncAfterCommit($needsSync);

        return $updated;
    }

    public function deactivateProfile(FaceProfile $profile): bool
    {
        $needsSync = false;
        $deactivated = DB::transaction(function () use ($profile, &$needsSync) {
            $locked = FaceProfile::query()
                ->with('room:id,room_number')
                ->lockForUpdate()
                ->findOrFail($profile->id);

            if (! $locked->active) {
                return false;
            }

            $locked->update([
                'active' => false,
                'revoked_at' => now(),
                'version' => $locked->version + 1,
            ]);

            if ($this->shouldSyncToPi($locked)) {
                $this->replacePendingJob($locked, 'DELETE');
                $needsSync = true;
            }

            return true;
        });

        $this->syncAfterCommit($needsSync);

        return $deactivated;
    }

    private function shouldSyncToPi(FaceProfile $profile): bool
    {
        $profile->loadMissing('room:id,room_number');

        return $profile->room?->room_number === (string) config('face_id.pi_room_number', '501');
    }

    private function replacePendingJob(FaceProfile $profile, string $action): FaceSyncJob
    {
        FaceSyncJob::query()
            ->where('face_profile_id', $profile->id)
            ->where('status', 'PENDING')
            ->delete();

        return FaceSyncJob::create([
            'face_profile_id' => $profile->id,
            'action' => $action,
            'status' => 'PENDING',
            'retry_count' => 0,
            'next_attempt_at' => now(),
        ]);
    }

    private function syncAfterCommit(bool $needed): void
    {
        if (! $needed || ! config('face_id.sync_immediately', true)) {
            return;
        }

        DB::afterCommit(function () {
            try {
                app(FaceSyncService::class)->syncPending();
            } catch (Throwable $error) {
                Log::warning('[FACE SYNC] Immediate sync failed; scheduler will retry', [
                    'error' => $error->getMessage(),
                ]);
            }
        });
    }

    private function normalizeEmbedding(array $embedding): array
    {
        if (count($embedding) < 16 || count($embedding) > 4096) {
            throw new InvalidArgumentException('Embedding có kích thước không hợp lệ.');
        }

        $sumSquares = 0.0;
        $values = [];
        foreach ($embedding as $value) {
            if (! is_numeric($value) || ! is_finite((float) $value)) {
                throw new InvalidArgumentException('Embedding chứa giá trị không hợp lệ.');
            }
            $number = (float) $value;
            $values[] = $number;
            $sumSquares += $number * $number;
        }

        $norm = sqrt($sumSquares);
        if ($norm <= 1.0e-12) {
            throw new InvalidArgumentException('Embedding không thể là vector zero.');
        }

        return array_map(fn (float $value) => $value / $norm, $values);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
