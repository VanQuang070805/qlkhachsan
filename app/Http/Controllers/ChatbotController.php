<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotCapabilityService;
use App\Services\Chatbot\HotelChatScopeService;
use App\Services\Chatbot\HotelRoomToolService;
use App\Services\Chatbot\LangGraphChatbotService;
use App\Services\OutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly LangGraphChatbotService $langGraph,
        private readonly HotelRoomToolService $roomTools,
        private readonly HotelChatScopeService $scope = new HotelChatScopeService(),
        private readonly ChatbotCapabilityService $capabilities = new ChatbotCapabilityService(),
        private readonly OutboxService $outbox = new OutboxService(),
    ) {}

    public function api(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
            'page_context' => ['sometimes', 'nullable', 'array'],
            'page_context.path' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page_context.title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page_context.room_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'page_context.room_price' => ['sometimes', 'nullable', 'string', 'max:80'],
            'page_context.check_in' => ['sometimes', 'nullable', 'string', 'max:40'],
            'page_context.check_out' => ['sometimes', 'nullable', 'string', 'max:40'],
            'page_context.guests' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);
        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];
        $pageContext = is_array($validated['page_context'] ?? null) ? $validated['page_context'] : [];

        if ($spamError = $this->checkAntiSpamGuard($request, $message)) {
            return response()->json([
                'source' => 'anti-spam',
                'message' => $spamError,
                'reply' => $spamError,
            ], 429);
        }

        if (! $this->scope->allows($message, $history)) {
            return response()->json([
                'source' => 'scope-guard',
                'reply' => $this->scope->refusal(),
                'sources' => [],
            ]);
        }

        if (! $this->langGraph->configured()) {
            return response()->json([
                'source' => 'error',
                'message' => 'Pico đang tạm thời bảo trì. Bạn vui lòng thử lại sau ít phút.',
            ], 503);
        }

        $answer = $this->langGraph->ask($request, $message, $history, $pageContext);
        if (! $answer) {
            return response()->json([
                'source' => 'error',
                'message' => 'Pico đang tạm thời mất kết nối. Bạn vui lòng thử lại sau ít phút.',
            ], 503);
        }

        return response()->json([
            'source' => 'gemini-langgraph',
            'reply' => $answer['reply'],
            'sources' => $answer['sources'] ?? [],
        ]);
    }

    public function reset(Request $request)
    {
        $this->langGraph->resetThread($request);
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->json(['reset' => true]);
    }

    public function stream(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
            'page_context' => ['sometimes', 'nullable', 'array'],
            'page_context.path' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page_context.title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page_context.room_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'page_context.room_price' => ['sometimes', 'nullable', 'string', 'max:80'],
            'page_context.check_in' => ['sometimes', 'nullable', 'string', 'max:40'],
            'page_context.check_out' => ['sometimes', 'nullable', 'string', 'max:40'],
            'page_context.guests' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);
        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];
        $pageContext = is_array($validated['page_context'] ?? null) ? $validated['page_context'] : [];

        if ($spamError = $this->checkAntiSpamGuard($request, $message)) {
            return response()->json([
                'source' => 'anti-spam',
                'message' => $spamError,
                'reply' => $spamError,
            ], 429);
        }

        if (! $this->scope->allows($message, $history)) {
            return $this->scopedStreamResponse($this->scope->refusal());
        }

        if (! $this->langGraph->configured()) {
            return response()->json([
                'source' => 'error',
                'message' => 'Pico đang tạm thời bảo trì. Bạn vui lòng thử lại sau ít phút.',
            ], 503);
        }

        $threadId = $this->langGraph->hasCachedPublicReply($message, $history, $request, $pageContext)
            ? null
            : $this->langGraph->ensureThreadId($request);
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->stream(function () use ($request, $message, $threadId, $history, $pageContext) {
            if (! app()->runningUnitTests()) {
                @ini_set('zlib.output_compression', '0');
                @ini_set('implicit_flush', '1');
                while (ob_get_level() > 0) {
                    @ob_end_flush();
                }
                ob_implicit_flush(true);
            }
            $flushSse = static function (): void {
                if (! app()->runningUnitTests() && PHP_SAPI === 'cli-server') {
                    echo ': '.str_repeat(' ', 4096)."\n\n";
                }
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            echo ": connected\n\n";
            $flushSse();

            $emitted = false;
            $sources = [];
            $ok = $this->langGraph->stream($request, $message, function (string $delta) use (&$emitted, $flushSse) {
                $emitted = true;
                echo 'data: '.json_encode(['delta' => $delta], JSON_UNESCAPED_UNICODE)."\n\n";
                $flushSse();
            }, $sources, $threadId, $history, $pageContext);

            if (! $ok || ! $emitted) {
                echo 'data: '.json_encode([
                    'error' => 'Pico đang tạm thời mất kết nối. Bạn vui lòng thử lại sau ít phút.',
                ], JSON_UNESCAPED_UNICODE)."\n\n";
                $flushSse();

                return;
            }

            if ($sources !== []) {
                echo 'data: '.json_encode(['sources' => $sources], JSON_UNESCAPED_UNICODE)."\n\n";
            }

            echo 'data: '.json_encode([
                'done' => true,
                'source' => 'gemini-langgraph',
                'sources' => $sources,
            ], JSON_UNESCAPED_UNICODE)."\n\n";
            $flushSse();
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function createThread(Request $request)
    {
        $corrId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        if (! $this->langGraph->configured()) {
            return response()->json([
                'error' => [
                    'code' => 'CHATBOT_UNAVAILABLE',
                    'message' => 'Pico đang tạm thời bảo trì.',
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 503);
        }

        $threadId = $this->langGraph->ensureThreadId($request);
        if ($request->hasSession()) {
            $request->session()->save();
        }
        if (! $threadId) {
            return response()->json([
                'error' => [
                    'code' => 'CHATBOT_THREAD_FAILED',
                    'message' => 'Không thể khởi tạo phiên trò chuyện.',
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 503);
        }

        return response()->json([
            'data' => [
                'thread_id' => $threadId,
            ],
            'meta' => [
                'correlation_id' => $corrId,
            ],
        ], 201);
    }

    public function deleteThread(Request $request, string $thread)
    {
        abort_unless((bool) preg_match('/^[A-Za-z0-9_-]{1,120}$/', $thread), 422);
        $this->langGraph->resetThread($request);

        return response()->noContent();
    }

    public function postThreadMessage(Request $request, string $thread)
    {
        $corrId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        if (! preg_match('/^[A-Za-z0-9_-]{1,120}$/', $thread)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_THREAD_ID',
                    'message' => 'Mã phiên hội thoại không hợp lệ.',
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 422);
        }
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
        ]);

        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];
        if (! $this->scope->allows($message, $history)) {
            return response()->json([
                'error' => [
                    'code' => 'CHATBOT_OUT_OF_SCOPE',
                    'message' => $this->scope->refusal(),
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 422);
        }

        $participant = $this->langGraph->sessionParticipantId($request);
        $capability = $this->capabilities->issueForRequest($request, $participant, $corrId);
        $fingerprint = $this->langGraph->buildFingerprint(
            $message,
            $history,
            $capability['actor_scope'],
        );

        // Deduplicate active run within the same scope
        $dedupeRunKey = "royal:chatbot:active-run:{$fingerprint}";
        $runId = (string) Cache::get($dedupeRunKey, '');
        if ($runId === '') {
            $runId = 'run_'.Str::lower((string) Str::ulid());
            Cache::put($dedupeRunKey, $runId, now()->addSeconds(30));
        }

        Cache::put("royal:chatbot:run:{$runId}", [
            'run_id' => $runId,
            'thread_id' => $thread,
            'participant' => $participant,
            'message' => $message,
            'history' => $history,
            'fingerprint' => $fingerprint,
            'status' => 'queued',
            'created_at' => now()->toIso8601String(),
        ], now()->addMinutes(15));

        return response()->json([
            'data' => [
                'run_id' => $runId,
                'thread_id' => $thread,
                'status' => 'queued',
                'fingerprint' => $fingerprint,
                'status_url' => url("/api/v1/customer/chat/runs/{$runId}"),
                'events_url' => url("/api/v1/customer/chat/runs/{$runId}/events"),
            ],
            'meta' => [
                'correlation_id' => $corrId,
            ],
        ], 202);
    }

    public function showRun(Request $request, string $run)
    {
        $corrId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $record = Cache::get("royal:chatbot:run:{$run}");
        $participant = $this->langGraph->sessionParticipantId($request);

        if (! is_array($record) || ($record['participant'] ?? '') !== $participant) {
            return response()->json([
                'error' => [
                    'code' => 'CHAT_RUN_NOT_FOUND',
                    'message' => 'Không tìm thấy lượt hội thoại.',
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 404);
        }

        return response()->json([
            'data' => [
                'run_id' => $record['run_id'],
                'thread_id' => $record['thread_id'],
                'status' => $record['status'],
                'reply' => $record['reply'] ?? null,
                'sources' => $record['sources'] ?? [],
            ],
            'meta' => [
                'correlation_id' => $corrId,
            ],
        ]);
    }

    public function streamRunEvents(Request $request, string $run)
    {
        $corrId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $record = Cache::get("royal:chatbot:run:{$run}");
        $participant = $this->langGraph->sessionParticipantId($request);

        if (! is_array($record) || ($record['participant'] ?? '') !== $participant) {
            return response()->json([
                'error' => [
                    'code' => 'CHAT_RUN_NOT_FOUND',
                    'message' => 'Không tìm thấy lượt hội thoại.',
                    'fields' => (object) [],
                    'correlation_id' => $corrId,
                ],
            ], 404);
        }

        if (! $this->scope->allows((string) $record['message'], is_array($record['history'] ?? null) ? $record['history'] : [])) {
            return $this->scopedStreamResponse($this->scope->refusal());
        }

        $threadId = $this->langGraph->ensureThreadId($request) ?: (string) $record['thread_id'];
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->stream(function () use ($request, $run, $record, $threadId) {
            $emitted = false;
            $sources = [];
            $accumulated = '';

            $ok = $this->langGraph->stream(
                $request,
                (string) $record['message'],
                function (string $delta) use (&$emitted, &$accumulated) {
                    $emitted = true;
                    $accumulated .= $delta;
                    echo 'data: '.json_encode(['delta' => $delta], JSON_UNESCAPED_UNICODE)."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                },
                $sources,
                $threadId,
                is_array($record['history'] ?? null) ? $record['history'] : [],
            );

            if (! $ok || ! $emitted) {
                Cache::put("royal:chatbot:run:{$run}", array_merge($record, [
                    'status' => 'failed',
                ]), now()->addMinutes(15));

                echo 'data: '.json_encode([
                    'error' => 'Pico đang tạm thời mất kết nối. Bạn vui lòng thử lại sau ít phút.',
                ], JSON_UNESCAPED_UNICODE)."\n\n";
                return;
            }

            Cache::put("royal:chatbot:run:{$run}", array_merge($record, [
                'status' => 'completed',
                'reply' => $accumulated,
                'sources' => $sources,
            ]), now()->addMinutes(15));

            echo 'data: '.json_encode([
                'done' => true,
                'source' => 'gemini-langgraph',
                'sources' => $sources,
            ], JSON_UNESCAPED_UNICODE)."\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function roomTypes(Request $request)
    {
        if ($response = $this->authorizeInternalTool($request, 'room_types')) {
            return $response;
        }

        $data = $this->roomTools->getPublicRoomTypes();

        return response()->json([
            'room_types' => $data['room_types'],
            'catalog_version' => $data['catalog_version'] ?? 1,
            'queried_at' => $data['queried_at'],
        ]);
    }

    public function searchRooms(Request $request)
    {
        if ($response = $this->authorizeInternalTool($request, 'search_rooms')) {
            return $response;
        }

        $result = $this->roomTools->searchPublicRooms(
            $request->only(['check_in', 'check_out', 'guests', 'rooms', 'adults', 'children', 'room_count'])
        );
        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function myBookings(Request $request)
    {
        if ($response = $this->authorizeInternalTool($request, 'get_my_bookings', true)) {
            return $response;
        }

        $capability = $request->attributes->get('verified_chatbot_capability', []);
        $customerId = (int) ($capability['actor_id'] ?? 0);

        if (($capability['actor_type'] ?? '') !== 'customer' || $customerId <= 0) {
            $this->outbox->recordSecurityAudit('tool_denied', [
                'actor_type' => (string) ($capability['actor_type'] ?? 'guest'),
                'error_code' => 'CUSTOMER_CAPABILITY_REQUIRED',
            ]);

            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $this->roomTools->getCustomerBookings($customerId);

        return response()->json($data)->withHeaders([
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeInternalTool(Request $request, string $toolName, bool $requireCapability = false)
    {
        if (! config('services.langgraph.live_tools_enabled', true)) {
            return response()->json(['message' => 'Chatbot tools are not enabled.'], 503);
        }

        $apiKey = (string) config('services.langgraph.tool_api_key');
        if ($apiKey === '') {
            return response()->json(['message' => 'Chatbot tools are not configured.'], 503);
        }

        $provided = (string) ($request->bearerToken() ?: $request->header('X-Chatbot-Tool-Secret', ''));
        if ($provided === '' || ! hash_equals($apiKey, $provided)) {
            $this->outbox->recordSecurityAudit('tool_denied', [
                'actor_type' => 'unauthorized_caller',
                'error_code' => 'INVALID_TOOL_SECRET',
            ]);

            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $capabilityToken = (string) ($request->header('X-Chatbot-Capability', '') ?: $request->input('capability_token', ''));
        if ($requireCapability && trim($capabilityToken) === '') {
            $this->outbox->recordSecurityAudit('capability_rejected', [
                'actor_type' => 'guest',
                'error_code' => 'CAPABILITY_MISSING',
            ]);

            return response()->json(['message' => 'Capability token is required for this tool.'], 403);
        }

        $verification = $this->capabilities->verifyToolCapability($capabilityToken !== '' ? $capabilityToken : null, $toolName);
        if (! ($verification['valid'] ?? false)) {
            $this->outbox->recordSecurityAudit('capability_rejected', [
                'actor_type' => (string) ($verification['actor_type'] ?? 'guest'),
                'actor_ref_hash' => $verification['actor_ref_hash'] ?? null,
                'error_code' => (string) ($verification['reason'] ?? 'CAPABILITY_REJECTED'),
            ]);

            return response()->json(['message' => 'Tool capability denied.'], 403);
        }

        $request->attributes->set('verified_chatbot_capability', $verification);

        return null;
    }

    private function checkAntiSpamGuard(Request $request, string $message): ?string
    {
        if (app()->runningUnitTests() && ! config('services.langgraph.enforce_anti_spam_in_tests', false)) {
            return null;
        }

        $actorKey = sha1($this->langGraph->sessionParticipantId($request).'|'.$request->ip());
        $lockKey = "royal:chatbot:cooldown_lock:{$actorKey}";
        $lockedUntil = (int) Cache::get($lockKey, 0);
        $nowMs = (int) round(microtime(true) * 1000);

        if ($lockedUntil > $nowMs) {
            $waitSec = max(1, (int) ceil(($lockedUntil - $nowMs) / 1000));

            return "Bạn đang gửi tin nhắn quá nhanh. Vui lòng đợi {$waitSec} giây trước khi gửi tiếp nhé.";
        }

        // 1. Minimum 2-second interval between consecutive messages
        $lastSentKey = "royal:chatbot:last_sent_ms:{$actorKey}";
        $lastSentMs = (int) Cache::get($lastSentKey, 0);
        if ($lastSentMs > 0 && ($nowMs - $lastSentMs) < 2000) {
            Cache::put($lockKey, $nowMs + 5000, now()->addSeconds(10));

            return 'Bạn đang gửi tin nhắn quá nhanh. Vui lòng đợi vài giây giữa mỗi lần gửi nhé.';
        }
        Cache::put($lastSentKey, $nowMs, now()->addSeconds(30));

        // 2. Duplicate message flood guard (>= 3 identical messages within 30 seconds)
        $normalizedMsg = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $message) ?? ''), 'UTF-8');
        $dupKey = "royal:chatbot:dup:{$actorKey}:".sha1($normalizedMsg);
        $dupCount = (int) Cache::get($dupKey, 0) + 1;
        Cache::put($dupKey, $dupCount, now()->addSeconds(30));
        if ($dupCount >= 3) {
            Cache::put($lockKey, $nowMs + 15000, now()->addSeconds(20));

            return 'Bạn vừa gửi lặp lại cùng một câu hỏi nhiều lần. Vui lòng đợi 15 giây hoặc đặt câu hỏi khác nhé.';
        }

        // 3. Sliding window burst guard (> 5 messages within 15 seconds)
        $burstKey = "royal:chatbot:burst:{$actorKey}";
        $timestamps = Cache::get($burstKey, []);
        $timestamps = is_array($timestamps)
            ? array_values(array_filter($timestamps, fn ($ts) => ($nowMs - (int) $ts) < 15000))
            : [];
        $timestamps[] = $nowMs;
        Cache::put($burstKey, $timestamps, now()->addSeconds(20));
        if (count($timestamps) > 5) {
            Cache::put($lockKey, $nowMs + 15000, now()->addSeconds(20));

            return 'Bạn đã gửi quá nhiều tin nhắn liên tiếp. Hộp thoại tạm khóa 15 giây để chống spam.';
        }

        return null;
    }

    private function scopedStreamResponse(string $reply)
    {
        return response()->stream(function () use ($reply) {
            echo ": connected\n\n";
            echo 'data: '.json_encode(['delta' => $reply], JSON_UNESCAPED_UNICODE)."\n\n";
            echo 'data: '.json_encode(['done' => true, 'source' => 'scope-guard', 'sources' => []], JSON_UNESCAPED_UNICODE)."\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
