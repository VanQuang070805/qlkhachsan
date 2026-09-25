<?php

namespace App\Services\FaceId;

use App\Models\Booking;
use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FaceIdService
{
    public function enroll(Booking $booking, array $embedding, int $sampleCount = 15): FaceProfile
    {
        if ($booking->status !== 'checked_in') {
            throw new InvalidArgumentException('Chỉ đăng ký Face ID cho booking đang check-in.');
        }

        $normalized = $this->normalizeEmbedding($embedding);
        $duplicate = FaceProfile::query()
            ->with('booking:id,customer_name')
            ->where('active', true)
            ->where('booking_id', '!=', $booking->id)
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
            throw new InvalidArgumentException(
                'Khuôn mặt này đã được đăng ký cho khách '.($duplicate->booking?->customer_name ?? '#'.$duplicate->booking_id).'.'
            );
        }

        return DB::transaction(function () use ($booking, $normalized, $sampleCount) {
            $profile = FaceProfile::query()->lockForUpdate()->where('booking_id', $booking->id)->first();
            $action = $profile ? 'UPDATE' : 'ADD';

            if (!$profile) {
                $profile = new FaceProfile(['booking_id' => $booking->id]);
            }

            $profile->fill([
                'user_id' => $booking->user_id,
                'embedding' => $normalized,
                'embedding_model' => 'sface_2021dec',
                'embedding_dimension' => count($normalized),
                'sample_count' => $sampleCount,
                'version' => $profile->exists ? $profile->version + 1 : 1,
                'active' => true,
                'consent_at' => now(),
                'revoked_at' => null,
            ])->save();

            $this->replacePendingJob($profile, $action);

            return $profile->fresh();
        });
    }

    public function deactivateForBooking(int $bookingId): bool
    {
        return DB::transaction(function () use ($bookingId) {
            $profile = FaceProfile::query()->lockForUpdate()->where('booking_id', $bookingId)->first();
            if (!$profile || !$profile->active) {
                return false;
            }

            $profile->update([
                'active' => false,
                'revoked_at' => now(),
                'version' => $profile->version + 1,
            ]);
            $this->replacePendingJob($profile, 'DELETE');

            return true;
        });
    }

    public function reconcileCheckedOutBookings(): int
    {
        $count = 0;
        FaceProfile::query()
            ->where('active', true)
            ->whereHas('booking', fn ($query) => $query->whereIn('status', ['completed', 'cancelled']))
            ->select('booking_id')
            ->chunkById(100, function ($profiles) use (&$count) {
                foreach ($profiles as $profile) {
                    $count += $this->deactivateForBooking((int) $profile->booking_id) ? 1 : 0;
                }
            }, 'booking_id');
        return $count;
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

    private function normalizeEmbedding(array $embedding): array
    {
        if (count($embedding) < 16 || count($embedding) > 4096) {
            throw new InvalidArgumentException('Embedding có kích thước không hợp lệ.');
        }

        $sumSquares = 0.0;
        $values = [];
        foreach ($embedding as $value) {
            if (!is_numeric($value) || !is_finite((float) $value)) {
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
}
