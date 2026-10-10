<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureInternalPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $userId = session('staff_user_id') ?: session('auth_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $user->hasInternalPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tài khoản chưa được cấp quyền thực hiện thao tác này.'], 403);
            }

            return redirect()->route('receptionist.profile')
                ->with('error', 'Tài khoản chưa được cấp quyền thực hiện thao tác này.');
        }

        return $next($request);
    }
}
