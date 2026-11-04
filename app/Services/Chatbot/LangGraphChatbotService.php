<?php

namespace App\Services\Chatbot;

use App\Services\CacheVersionService;
use App\Services\OutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LangGraphChatbotService
{
    public function __construct(
        private readonly ChatbotCapabilityService $capabilities = new ChatbotCapabilityService(),
        private readonly CacheVersionService $versions = new CacheVersionService(),
        private readonly OutboxService $outbox = new OutboxService(),
    ) {}

    public function configured(): bool
    {
        if (! config('services.langgraph.enabled', true)) {
            return false;
        }

        return trim((string) config('services.langgraph.base_url')) !== '';
    }

    public function sessionParticipantId(Request $request): string
    {
        $customerId = (int) $request->session()->get('customer_user_id', 0);
        $staffContext = (int) $request->session()->get('staff_user_id', 0) > 0 && $customerId === 0;
        $sessionToken = (string) $request->session()->get('chatbot.participant_token', '');
        if ($sessionToken === '') {
            $sessionToken = $request->session()->getId() ?: bin2hex(random_bytes(16));
            $request->session()->put('chatbot.participant_token', $sessionToken);
        }

        return 'royal_'.substr(hash_hmac(
            'sha256',
            $sessionToken.'|'.($staffContext ? 'staff-session' : 'customer-'.$customerId),
            (string) config('app.key'),
        ), 0, 40);
    }

    public function isPersonalBookingQuery(string $message): bool
    {
        $lower = mb_strtolower($message, 'UTF-8');

        foreach ([
            'đơn của tôi',
            'don cua toi',
            'đặt phòng của tôi',
            'dat phong cua toi',
            'booking của tôi',
            'booking cua toi',
            'lịch sử đặt',
            'lich su dat',
            'tôi đã đặt',
            'toi da dat',
            'mã đặt phòng của tôi',
            'my booking',
        ] as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a deterministic, scope-aware deduplication and cache fingerprint (Section 8.2).
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function buildFingerprint(
        string $message,
        array $history = [],
        string $actorScope = 'public',
        string $locale = 'vi',
    ): string {
        $normalizedQuestion = Str::of($message)->lower()->squish()->toString();
        $historySlice = array_slice($history, -4);
        $historyDigest = $historySlice === []
            ? 'no-history'
            : hash('sha256', json_encode($historySlice, JSON_UNESCAPED_UNICODE));

        $effectiveScope = $this->isPersonalBookingQuery($message) ? $actorScope : 'public';
        $knowledgeVersion = $this->versions->knowledgeVersion();
        $catalogVersion = $this->versions->catalogVersion();
        $availabilityVersion = $this->versions->availabilityVersion();

        return hash('sha256', implode('|', [
            'v1',
            $normalizedQuestion,
            $historyDigest,
            $locale,
            $effectiveScope,
            'kv:'.$knowledgeVersion,
            'cv:'.$catalogVersion,
            'av:'.$availabilityVersion,
        ]));
    }

    public function ensureThreadId(Request $request): ?string
    {
        $baseUrl = rtrim((string) config('services.langgraph.base_url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        $participant = $this->sessionParticipantId($request);
        $threadKey = 'royal_langgraph_thread_'.substr(hash('sha256', $participant), 0, 32);
        $ttlKey = $threadKey.'_expires_at';

        $existingThreadId = $request->session()->get($threadKey) ?: $request->session()->get('chatbot.langgraph_thread_id');
        $expiresAt = (int) $request->session()->get($ttlKey, 0);

        if (is_string($existingThreadId) && $existingThreadId !== '' && $expiresAt > now()->timestamp) {
            return $existingThreadId;
        }

        try {
            $response = $this->httpClient(8)->post($baseUrl.'/threads', [
                'metadata' => [
                    'participant' => $participant,
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Royal LangGraph thread creation failed', ['status' => $response->status()]);
                return null;
            }

            $threadId = (string) data_get($response->json(), 'thread_id', '');
            if ($threadId === '') {
                return null;
            }

            $request->session()->put($threadKey, $threadId);
            $request->session()->put('chatbot.langgraph_thread_id', $threadId);
            $request->session()->put($ttlKey, now()->addHours(2)->timestamp);

            return $threadId;
        } catch (\Throwable $e) {
            Log::warning('Royal LangGraph thread creation unavailable', ['type' => $e::class]);
            return null;
        }
    }

    public function resetThread(Request $request): void
    {
        $participant = $this->sessionParticipantId($request);
        $threadKey = 'royal_langgraph_thread_'.substr(hash('sha256', $participant), 0, 32);
        $request->session()->forget([$threadKey, $threadKey.'_expires_at', 'chatbot.langgraph_thread_id']);
    }

    public function ask(Request $request, string $message, array $history = []): ?array
    {
        $baseUrl = rtrim((string) config('services.langgraph.base_url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        $startedAt = microtime(true);
        $correlationId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $participant = $this->sessionParticipantId($request);
        $capability = $this->capabilities->issueForRequest($request, $participant, $correlationId);
        $isPersonal = $this->isPersonalBookingQuery($message);
        $fingerprint = $this->buildFingerprint($message, $history, $capability['actor_scope']);

        $this->outbox->recordChatbotEvent('requested', [
            'actor_type' => $capability['actor_type'],
            'actor_ref_hash' => $capability['actor_ref_hash'],
            'cache_status' => 'pending',
        ], $correlationId);

        if (! $isPersonal && $history === []) {
            $cached = Cache::get("royal:chatbot:answer:{$fingerprint}");
            if (is_array($cached) && isset($cached['reply']) && is_string($cached['reply']) && $cached['reply'] !== '') {
                $this->outbox->recordChatbotEvent('cache_hit', [
                    'actor_type' => $capability['actor_type'],
                    'actor_ref_hash' => $capability['actor_ref_hash'],
                    'cache_status' => 'hit',
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], $correlationId);

                return $cached;
            }
        }

        $threadId = $this->ensureThreadId($request);
        if (! $threadId) {
            $this->outbox->recordChatbotEvent('failed', [
                'actor_type' => $capability['actor_type'],
                'actor_ref_hash' => $capability['actor_ref_hash'],
                'error_code' => 'THREAD_UNAVAILABLE',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], $correlationId);

            return null;
        }

        try {
            $response = $this->httpClient((int) config('services.langgraph.timeout', 60))
                ->withHeaders([
                    'X-Correlation-ID' => $correlationId,
                ])
                ->post("{$baseUrl}/threads/{$threadId}/runs/wait", [
                    'assistant_id' => config('services.langgraph.retrieval_assistant_id', 'retrieval_graph'),
                    'input' => [
                        'query' => $message,
                    ],
                    'config' => [
                        'configurable' => [
                            'retrieverProvider' => config('services.langgraph.vector_provider', 'mysql'),
                            'queryModel' => config('services.langgraph.query_model', 'google-genai/gemini-flash-latest'),
                            'k' => 5,
                            'capabilityToken' => $capability['token'],
                            'actorScope' => $capability['actor_scope'],
                            'correlationId' => $correlationId,
                            'dedupeFingerprint' => $fingerprint,
                            'knowledgeVersion' => $this->versions->knowledgeVersion(),
                            'catalogVersion' => $this->versions->catalogVersion(),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Royal LangGraph wait run failed', [
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                ]);
                $this->outbox->recordChatbotEvent('failed', [
                    'actor_type' => $capability['actor_type'],
                    'actor_ref_hash' => $capability['actor_ref_hash'],
                    'error_code' => 'UPSTREAM_HTTP_'.$response->status(),
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], $correlationId);

                return null;
            }

            $data = $response->json();
            $reply = $this->extractReplyFromWaitPayload(is_array($data) ? $data : []);
            if ($reply === '') {
                return null;
            }

            $result = [
                'reply' => $reply,
                'sources' => is_array($data['sources'] ?? null) ? $data['sources'] : [],
            ];

            if (! $isPersonal && $history === []) {
                Cache::put("royal:chatbot:answer:{$fingerprint}", $result, now()->addMinutes(10));
            }

            $this->outbox->recordChatbotEvent('completed', [
                'actor_type' => $capability['actor_type'],
                'actor_ref_hash' => $capability['actor_ref_hash'],
                'cache_status' => 'miss',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], $correlationId);

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Royal LangGraph wait run unavailable', [
                'type' => $e::class,
                'correlation_id' => $correlationId,
            ]);
            $this->outbox->recordChatbotEvent('failed', [
                'actor_type' => $capability['actor_type'],
                'actor_ref_hash' => $capability['actor_ref_hash'],
                'error_code' => 'UPSTREAM_EXCEPTION',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], $correlationId);

            return null;
        }
    }

    /**
     * Stream SSE chunks from LangGraph retrieval_graph to callback `$onDelta(string $delta)`.
     *
     * @param  array<int, string>  $sources
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function stream(
        Request $request,
        string $message,
        callable $onDelta,
        array &$sources = [],
        ?string $preResolvedThreadId = null,
        array $history = [],
    ): bool {
        $baseUrl = rtrim((string) config('services.langgraph.base_url'), '/');
        if ($baseUrl === '') {
            return false;
        }

        $startedAt = microtime(true);
        $correlationId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $participant = $this->sessionParticipantId($request);
        $capability = $this->capabilities->issueForRequest($request, $participant, $correlationId);
        $isPersonal = $this->isPersonalBookingQuery($message);
        $fingerprint = $this->buildFingerprint($message, $history, $capability['actor_scope']);

        $this->outbox->recordChatbotEvent('requested', [
            'actor_type' => $capability['actor_type'],
            'actor_ref_hash' => $capability['actor_ref_hash'],
            'cache_status' => 'pending',
        ], $correlationId);

        $threadId = $preResolvedThreadId ?: $this->ensureThreadId($request);
        if (! $threadId) {
            $this->outbox->recordChatbotEvent('failed', [
                'actor_type' => $capability['actor_type'],
                'actor_ref_hash' => $capability['actor_ref_hash'],
                'error_code' => 'THREAD_UNAVAILABLE',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], $correlationId);

            return false;
        }

        try {
            $response = $this->httpClient((int) config('services.langgraph.timeout', 60))
                ->withHeaders([
                    'X-Correlation-ID' => $correlationId,
                ])
                ->withOptions(['stream' => true])
                ->post("{$baseUrl}/threads/{$threadId}/runs/stream", [
                    'assistant_id' => config('services.langgraph.retrieval_assistant_id', 'retrieval_graph'),
                    'input' => [
                        'query' => $message,
                    ],
                    'streamMode' => ['messages', 'updates'],
                    'config' => [
                        'configurable' => [
                            'retrieverProvider' => config('services.langgraph.vector_provider', 'mysql'),
                            'queryModel' => config('services.langgraph.query_model', 'google-genai/gemini-flash-latest'),
                            'k' => 5,
                            'capabilityToken' => $capability['token'],
                            'actorScope' => $capability['actor_scope'],
                            'correlationId' => $correlationId,
                            'dedupeFingerprint' => $fingerprint,
                            'knowledgeVersion' => $this->versions->knowledgeVersion(),
                            'catalogVersion' => $this->versions->catalogVersion(),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Royal LangGraph stream run failed', [
                    'status' => $response->status(),
                    'correlation_id' => $correlationId,
                ]);
                $this->outbox->recordChatbotEvent('failed', [
                    'actor_type' => $capability['actor_type'],
                    'actor_ref_hash' => $capability['actor_ref_hash'],
                    'error_code' => 'UPSTREAM_HTTP_'.$response->status(),
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], $correlationId);

                return false;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if (! str_contains($contentType, 'text/event-stream')) {
                $data = $response->json();
                $reply = $this->extractReplyFromWaitPayload(is_array($data) ? $data : []);
                if ($reply === '') {
                    return false;
                }
                foreach (preg_split('/(?<=\s)/u', $reply, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                    $onDelta($word);
                }

                $this->outbox->recordChatbotEvent('completed', [
                    'actor_type' => $capability['actor_type'],
                    'actor_ref_hash' => $capability['actor_ref_hash'],
                    'cache_status' => 'miss',
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], $correlationId);

                return true;
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';
            $emittedLength = 0;
            $emittedAny = false;
            $accumulated = '';

            while (! $body->eof()) {
                if (connection_aborted()) {
                    $body->close();
                    break;
                }

                $chunk = $body->read(1024);
                if ($chunk === '') {
                    break;
                }

                $buffer .= str_replace("\r\n", "\n", $chunk);
                $events = explode("\n\n", $buffer);
                $buffer = array_pop($events) ?? '';

                foreach ($events as $rawEvent) {
                    if (! $this->processSseEventBlock($rawEvent, function (string $delta) use ($onDelta, &$accumulated) {
                        $accumulated .= $delta;
                        $onDelta($delta);
                    }, $emittedLength, $emittedAny, $sources)) {
                        return false;
                    }
                }
            }

            if (trim($buffer) !== '') {
                if (! $this->processSseEventBlock($buffer, function (string $delta) use ($onDelta, &$accumulated) {
                    $accumulated .= $delta;
                    $onDelta($delta);
                }, $emittedLength, $emittedAny, $sources)) {
                    return false;
                }
            }

            if ($emittedAny) {
                if (! $isPersonal && $history === [] && $accumulated !== '') {
                    Cache::put("royal:chatbot:answer:{$fingerprint}", [
                        'reply' => $accumulated,
                        'sources' => $sources,
                    ], now()->addMinutes(10));
                }

                $this->outbox->recordChatbotEvent('completed', [
                    'actor_type' => $capability['actor_type'],
                    'actor_ref_hash' => $capability['actor_ref_hash'],
                    'cache_status' => 'miss',
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], $correlationId);
            }

            return $emittedAny;
        } catch (\Throwable $e) {
            Log::warning('Royal LangGraph stream unavailable', [
                'type' => $e::class,
                'correlation_id' => $correlationId,
            ]);
            $this->outbox->recordChatbotEvent('failed', [
                'actor_type' => $capability['actor_type'],
                'actor_ref_hash' => $capability['actor_ref_hash'],
                'error_code' => 'UPSTREAM_EXCEPTION',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ], $correlationId);

            return false;
        }
    }

    /**
     * @param  array<int, string>  $sources
     */
    private function processSseEventBlock(
        string $rawEvent,
        callable $onDelta,
        int &$emittedLength,
        bool &$emittedAny,
        array &$sources,
    ): bool {
        $headerEventType = null;
        $dataLines = [];

        foreach (explode("\n", $rawEvent) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, 'event:')) {
                $headerEventType = trim(substr($line, 6));
                continue;
            }
            if (str_starts_with($line, 'data:')) {
                $dataLines[] = trim(substr($line, 5));
            }
        }

        if ($dataLines === []) {
            return true;
        }

        $jsonStr = implode("\n", $dataLines);
        if ($jsonStr === '' || $jsonStr === '[DONE]') {
            return true;
        }

        $decoded = json_decode($jsonStr, true);
        if (! is_array($decoded)) {
            return true;
        }

        if ($headerEventType === 'error' || isset($decoded['error']) || ($decoded['event'] ?? null) === 'error') {
            return false;
        }

        if (isset($decoded['delta']) && is_string($decoded['delta']) && $decoded['delta'] !== '') {
            $clean = str_replace('**', '', $decoded['delta']);
            if ($clean !== '') {
                $onDelta($clean);
                $emittedAny = true;
                $emittedLength += mb_strlen($clean);
            }
            return true;
        }

        $eventType = $headerEventType ?? ($decoded['event'] ?? null);
        $eventData = array_key_exists('data', $decoded) && isset($decoded['event']) ? $decoded['data'] : $decoded;

        if ($eventType === 'messages/partial' && is_array($eventData)) {
            $lastItem = array_is_list($eventData) ? end($eventData) : $eventData;
            if (is_array($lastItem)) {
                $itemType = $lastItem['type'] ?? 'ai';
                if ($itemType === 'ai') {
                    if (isset($lastItem['delta']) && is_string($lastItem['delta']) && $lastItem['delta'] !== '') {
                        $clean = str_replace('**', '', $lastItem['delta']);
                        if ($clean !== '') {
                            $onDelta($clean);
                            $emittedAny = true;
                            $emittedLength += mb_strlen($clean);
                        }
                    } elseif (isset($lastItem['content']) && is_string($lastItem['content'])) {
                        $fullClean = str_replace('**', '', $lastItem['content']);
                        $newSlice = mb_substr($fullClean, $emittedLength);
                        if ($newSlice !== '') {
                            $onDelta($newSlice);
                            $emittedAny = true;
                            $emittedLength = mb_strlen($fullClean);
                        }
                    }
                }
            }
        } elseif ($eventType === 'updates' && is_array($eventData)) {
            $docs = data_get($eventData, 'retrieveDocuments.documents', []);
            if (is_array($docs)) {
                foreach ($docs as $doc) {
                    $src = trim((string) data_get($doc, 'metadata.source', ''));
                    if ($src !== '' && ! in_array($src, $sources, true)) {
                        $sources[] = $src;
                    }
                }
            }
        }

        return true;
    }

    private function extractReplyFromWaitPayload(array $data): string
    {
        $direct = trim((string) data_get($data, 'reply', data_get($data, 'answer', '')));
        if ($direct !== '') {
            return str_replace('**', '', $direct);
        }

        $messages = data_get($data, 'values.messages', data_get($data, 'messages', []));
        if (is_array($messages) && $messages !== []) {
            $last = end($messages);
            if (is_array($last) && isset($last['content'])) {
                return str_replace('**', '', trim((string) $last['content']));
            }
        }

        return '';
    }

    private function httpClient(int $timeout)
    {
        $client = Http::acceptJson()->timeout(max(5, $timeout));
        $apiKey = trim((string) (config('services.langgraph.internal_api_key') ?: config('services.langgraph.api_key')));
        if ($apiKey !== '') {
            $client = $client->withToken($apiKey)->withHeaders([
                'X-Internal-Api-Key' => $apiKey,
            ]);
        }

        return $client;
    }
}
