<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionRevocationService
{
    public function revoke(User $user, ?string $exceptSessionId = null): void
    {
        if (! app()->environment('testing') && config('session.driver') !== 'database') {
            throw new \LogicException('Session revocation requires the database session driver.');
        }

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (!Schema::hasTable('sessions')) {
            return;
        }

        $sessions = DB::table('sessions')->where('user_id', $user->id);
        if ($exceptSessionId !== null) {
            $sessions->where('id', '!=', $exceptSessionId);
        }
        $sessions->delete();
    }
}
