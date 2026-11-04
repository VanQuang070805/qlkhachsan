<?php

namespace App\Services\Chatbot;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatbotCapabilityService
{
    public const ALLOWED_PUBLIC_TOOLS = [
        'room_types',
        'search_rooms',
    ];

    public const ALLOWED_CUSTOMER_TOOLS = [
        'room_types',
        'search_rooms',
        'get_my_bookings',
    ];

    /**
     * Issue a short-lived capability token stored server-side in Cache/Redis.
     *
     * @return array{token: string, actor_scope: string, actor_type: string, actor_ref_hash: string, allowed_tools: array<int, string>, expires_at: string}
     */
    public function issueForRequest(Request $request, string $participantHash, ?string $correlationId = null): array
    {
        $customerId = (int) ($request->session()->get('customer_user_id') ?: auth('customer')->id() ?: 0);
        $staffId = (int) ($request->session()->get('staff_user_id') ?: auth('staff')->id() ?: auth('admin')->id() ?: 0);

        if ($customerId > 0) {
            $actorType = 'customer';
            $actorId = $customerId;
            $actorScope = 'customer:'.substr(hash_hmac('sha256', 'customer-'.$customerId, (string) config('app.key')), 0, 24);
            $allowedTools = self::ALLOWED_CUSTOMER_TOOLS;
        } elseif ($staffId > 0) {
            $actorType = 'staff';
            $actorId = $staffId;
            $actorScope = 'public';
            $allowedTools = self::ALLOWED_PUBLIC_TOOLS;
        } else {
            $actorType = 'guest';
            $actorId = 0;
            $actorScope = 'public';
            $allowedTools = self::ALLOWED_PUBLIC_TOOLS;
        }

        $actorRefHash = substr(hash_hmac('sha256', $actorType.'|'.$actorId.'|'.$participantHash, (string) config('app.key')), 0, 32);
        $ttlSeconds = (int) config('services.langgraph.capability_ttl', 90);
        $expiresAt = now()->addSeconds(max(30, $ttlSeconds));
        $token = 'cap_'.Str::random(48);

        $record = [
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_scope' => $actorScope,
            'actor_ref_hash' => $actorRefHash,
            'allowed_tools' => $allowedTools,
            'participant_hash' => $participantHash,
            'correlation_id' => $correlationId ?: (string) ($request->attributes->get('correlation_id') ?? Str::ulid()),
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_ts' => $expiresAt->timestamp,
        ];

        Cache::put($this->cacheKey($token), $record, $expiresAt);

        return [
            'token' => $token,
            'actor_scope' => $actorScope,
            'actor_type' => $actorType,
            'actor_ref_hash' => $actorRefHash,
            'allowed_tools' => $allowedTools,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Resolve and verify a capability token for a specific tool name.
     *
     * @return array{valid: bool, reason?: string, actor_type?: string, actor_id?: int, actor_scope?: string, actor_ref_hash?: string, allowed_tools?: array<int, string>, correlation_id?: string}
     */
    public function verifyToolCapability(?string $token, string $toolName): array
    {
        if (! is_string($token) || trim($token) === '') {
            // Backward-compatible public tool fallback when capability is not required for strictly public tools
            if (in_array($toolName, self::ALLOWED_PUBLIC_TOOLS, true) && ! config('services.langgraph.require_capability_for_public_tools', false)) {
                return [
                    'valid' => true,
                    'actor_type' => 'guest',
                    'actor_id' => 0,
                    'actor_scope' => 'public',
                    'allowed_tools' => self::ALLOWED_PUBLIC_TOOLS,
                ];
            }

            return [
                'valid' => false,
                'reason' => 'CAPABILITY_MISSING',
            ];
        }

        $record = Cache::get($this->cacheKey(trim($token)));
        if (! is_array($record)) {
            return [
                'valid' => false,
                'reason' => 'CAPABILITY_EXPIRED_OR_INVALID',
            ];
        }

        if ((int) ($record['expires_ts'] ?? 0) < now()->timestamp) {
            Cache::forget($this->cacheKey(trim($token)));

            return [
                'valid' => false,
                'reason' => 'CAPABILITY_EXPIRED',
            ];
        }

        $allowedTools = is_array($record['allowed_tools'] ?? null) ? $record['allowed_tools'] : [];
        if (! in_array($toolName, $allowedTools, true)) {
            return [
                'valid' => false,
                'reason' => 'TOOL_NOT_ALLOWED',
                'actor_type' => (string) ($record['actor_type'] ?? 'guest'),
                'actor_ref_hash' => (string) ($record['actor_ref_hash'] ?? ''),
            ];
        }

        return [
            'valid' => true,
            'actor_type' => (string) ($record['actor_type'] ?? 'guest'),
            'actor_id' => (int) ($record['actor_id'] ?? 0),
            'actor_scope' => (string) ($record['actor_scope'] ?? 'public'),
            'actor_ref_hash' => (string) ($record['actor_ref_hash'] ?? ''),
            'allowed_tools' => $allowedTools,
            'correlation_id' => (string) ($record['correlation_id'] ?? ''),
        ];
    }

    private function cacheKey(string $token): string
    {
        return 'royal:chatbot:cap:'.hash('sha256', $token);
    }
}
