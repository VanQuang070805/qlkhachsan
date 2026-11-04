<?php

namespace App\Services;

use App\Models\IntegrationOutbox;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OutboxService
{
    public const TOPIC_CHATBOT_EVENTS = 'royal.chatbot.events.v1';
    public const TOPIC_HOTEL_CATALOG = 'royal.hotel.catalog.v1';
    public const TOPIC_SECURITY_AUDIT = 'royal.security.audit.v1';

    private const FORBIDDEN_PAYLOAD_KEYS = [
        'api_key',
        'google_api_key',
        'gemini_api_key',
        'langchain_api_key',
        'tool_api_key',
        'internal_api_key',
        'cookie',
        'session_id',
        'capability',
        'capability_token',
        'thread_secret',
        'password',
        'otp',
        'otp_code',
        'card_number',
        'cvv',
        'face_embedding',
        'embedding',
        'raw_question',
        'raw_answer',
        'message',
        'reply',
    ];

    public function record(
        string $topic,
        string $eventType,
        array $attributes = [],
        ?string $eventKey = null,
        ?string $correlationId = null,
    ): ?IntegrationOutbox {
        if (! Schema::hasTable('integration_outbox')) {
            return null;
        }

        $eventId = (string) ($attributes['event_id'] ?? Str::ulid());
        $corrId = $correlationId ?: (string) (request()?->attributes->get('correlation_id') ?? Str::ulid());
        $sanitized = $this->sanitizePayload($attributes);

        $payload = array_merge([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'schema_version' => 1,
            'occurred_at' => now()->toIso8601String(),
            'correlation_id' => $corrId,
            'actor_type' => $sanitized['actor_type'] ?? 'anonymous',
            'actor_ref_hash' => $sanitized['actor_ref_hash'] ?? null,
            'duration_ms' => isset($sanitized['duration_ms']) ? (int) $sanitized['duration_ms'] : null,
            'cache_status' => $sanitized['cache_status'] ?? null,
            'queue_wait_ms' => isset($sanitized['queue_wait_ms']) ? (int) $sanitized['queue_wait_ms'] : null,
            'error_code' => $sanitized['error_code'] ?? null,
        ], $sanitized);

        return IntegrationOutbox::query()->create([
            'event_id' => $eventId,
            'topic' => $topic,
            'event_key' => $eventKey ?: ($payload['actor_ref_hash'] ?? $eventType),
            'event_type' => $eventType,
            'schema_version' => 1,
            'correlation_id' => $corrId,
            'payload' => $payload,
            'available_at' => now(),
            'attempts' => 0,
        ]);
    }

    public function recordSecurityAudit(string $eventType, array $context = [], ?string $correlationId = null): ?IntegrationOutbox
    {
        return $this->record(self::TOPIC_SECURITY_AUDIT, $eventType, $context, null, $correlationId);
    }

    public function recordCatalogMutation(string $eventType, array $context = [], ?string $correlationId = null): ?IntegrationOutbox
    {
        return $this->record(self::TOPIC_HOTEL_CATALOG, $eventType, $context, null, $correlationId);
    }

    public function recordChatbotEvent(string $eventType, array $context = [], ?string $correlationId = null): ?IntegrationOutbox
    {
        return $this->record(self::TOPIC_CHATBOT_EVENTS, $eventType, $context, null, $correlationId);
    }

    private function sanitizePayload(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (in_array($normalizedKey, self::FORBIDDEN_PAYLOAD_KEYS, true)) {
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = $this->sanitizePayload($value);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
