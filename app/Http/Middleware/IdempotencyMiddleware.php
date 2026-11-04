<?php

namespace App\Http\Middleware;

use App\Models\ApiIdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $rawKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($rawKey === '' || ! Schema::hasTable('api_idempotency_keys')) {
            return $next($request);
        }

        $correlationId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $actorScope = $this->resolveActorScope($request);
        $routeSignature = $request->method().':'.ltrim($request->path(), '/');
        $keyHash = hash('sha256', $rawKey);
        $payload = $request->except(['_token']);
        ksort($payload);
        $requestHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));

        $existing = ApiIdempotencyKey::query()
            ->where('actor_scope', $actorScope)
            ->where('route_signature', $routeSignature)
            ->where('idempotency_key_hash', $keyHash)
            ->first();

        if ($existing && $existing->expires_at && $existing->expires_at->isPast()) {
            $existing->delete();
            $existing = null;
        }

        if ($existing) {
            if (! hash_equals($existing->request_hash, $requestHash)) {
                return response()->json([
                    'error' => [
                        'code' => 'IDEMPOTENCY_KEY_CONFLICT',
                        'message' => 'Idempotency-Key đã được sử dụng với dữ liệu đầu vào khác.',
                        'fields' => (object) [],
                        'correlation_id' => $correlationId,
                    ],
                ], 409)->withHeaders([
                    'X-Correlation-ID' => $correlationId,
                ]);
            }

            $body = $existing->response_body ?? [];
            if (is_array($body) && isset($body['meta']) && is_array($body['meta'])) {
                $body['meta']['correlation_id'] = $correlationId;
            }

            return response()->json($body, (int) $existing->status_code)->withHeaders([
                'X-Correlation-ID' => $correlationId,
                'X-Idempotent-Replay' => 'true',
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $decoded = json_decode((string) $response->getContent(), true);
            if (is_array($decoded)) {
                ApiIdempotencyKey::query()->updateOrCreate([
                    'actor_scope' => $actorScope,
                    'route_signature' => $routeSignature,
                    'idempotency_key_hash' => $keyHash,
                ], [
                    'request_hash' => $requestHash,
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $decoded,
                    'expires_at' => now()->addHours(24),
                ]);
            }
        }

        return $response;
    }

    private function resolveActorScope(Request $request): string
    {
        $staffId = (int) ($request->session()->get('staff_user_id') ?: auth('staff')->id() ?: auth('admin')->id() ?: 0);
        if ($staffId > 0) {
            return 'staff:'.$staffId;
        }

        $customerId = (int) ($request->session()->get('customer_user_id') ?: auth('customer')->id() ?: 0);
        if ($customerId > 0) {
            return 'customer:'.$customerId;
        }

        return 'ip:'.$request->ip();
    }
}
