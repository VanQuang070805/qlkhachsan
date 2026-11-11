<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotCapabilityService;
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
        ]);
        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];

        if (! $this->langGraph->configured()) {
            return response()->json([
                'source' => 'error',
                'message' => 'Pico đang tạm thời bảo trì. Bạn vui lòng thử lại sau ít phút.',
            ], 503);
        }

        $answer = $this->langGraph->ask($request, $message, $history);
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

    public function stream(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
        ]);
        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];

        if (! $this->langGraph->configured()) {
            return response()->json([
                'source' => 'error',
                'message' => 'Pico đang tạm thời bảo trì. Bạn vui lòng thử lại sau ít phút.',
            ], 503);
        }

        $threadId = $this->langGraph->ensureThreadId($request);

        return response()->stream(function () use ($request, $message, $threadId, $history) {
            echo ": connected\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            if (! $threadId) {
                echo 'data: '.json_encode([
                    'error' => 'Pico đang tạm thời mất kết nối. Bạn vui lòng thử lại sau ít phút.',
                ], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                return;
            }

            $emitted = false;
            $sources = [];
            $ok = $this->langGraph->stream($request, $message, function (string $delta) use (&$emitted) {
                $emitted = true;
                echo 'data: '.json_encode(['delta' => $delta], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, $sources, $threadId, $history);

            if (! $ok || ! $emitted) {
                echo 'data: '.json_encode([
                    'error' => 'Pico đang tạm thời mất kết nối. Bạn vui lòng thử lại sau ít phút.',
                ], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

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
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
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
        $this->langGraph->resetThread($request);

        return response()->noContent();
    }

    public function postThreadMessage(Request $request, string $thread)
    {
        $corrId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
        ]);

        $participant = $this->langGraph->sessionParticipantId($request);
        $capability = $this->capabilities->issueForRequest($request, $participant, $corrId);
        $fingerprint = $this->langGraph->buildFingerprint(
            trim($validated['message']),
            $validated['history'] ?? [],
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
            'message' => trim($validated['message']),
            'history' => $validated['history'] ?? [],
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

        $threadId = $this->langGraph->ensureThreadId($request) ?: (string) $record['thread_id'];

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
}
