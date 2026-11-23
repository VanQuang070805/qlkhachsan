<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_api_refuses_an_unrelated_question_without_calling_langgraph(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);
        Http::fake();

        $this->postJson(route('chatbot.api'), [
            'message' => 'Hôm nay thời tiết Hà Nội thế nào?',
        ])->assertOk()
            ->assertJsonPath('source', 'scope-guard')
            ->assertJsonPath('reply', 'Pico chỉ hỗ trợ các câu hỏi về Rosaliza Hotel và nghiệp vụ lưu trú, như phòng, đặt phòng, giá, thanh toán, nhận/trả phòng, chính sách và tài khoản.');

        Http::assertNothingSent();
    }

    public function test_chatbot_stream_refuses_an_unrelated_question_without_calling_langgraph(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);
        Http::fake();

        $response = $this->post(route('chatbot.stream'), [
            'message' => 'Viết cho tôi một bài thơ.',
        ]);

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('"source":"scope-guard"', $content);
        $this->assertStringContainsString('Pico chỉ hỗ trợ các câu hỏi về Rosaliza Hotel', $content);
        Http::assertNothingSent();
    }

    public function test_chatbot_allows_hotel_question_and_sends_scope_policy_upstream(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);
        Http::fake([
            '127.0.0.1:2024/threads' => Http::response(['thread_id' => 'scope-thread-1'], 200),
            '127.0.0.1:2024/threads/scope-thread-1/runs/wait' => Http::response([
                'messages' => [['type' => 'ai', 'content' => 'Giờ nhận phòng là từ 14:00.']],
            ], 200),
        ]);

        $this->postJson(route('chatbot.api'), [
            'message' => 'Giờ nhận phòng là khi nào?',
        ])->assertOk()->assertJsonPath('source', 'gemini-langgraph');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/runs/wait')
            && str_contains((string) data_get($request->data(), 'config.configurable.scopePolicy'), 'Chỉ trả lời về Rosaliza Hotel'));
    }

    public function test_chatbot_allows_a_stay_quote_with_dates_and_guest_count(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);
        Http::fake([
            '127.0.0.1:2024/threads' => Http::response(['thread_id' => 'stay-quote-thread'], 200),
            '127.0.0.1:2024/threads/stay-quote-thread/runs/wait' => Http::response([
                'messages' => [['type' => 'ai', 'content' => 'Tôi sẽ kiểm tra giá cho 2 khách.']],
            ], 200),
        ]);

        $this->postJson(route('chatbot.api'), [
            'message' => 'Tôi muốn ở từ ngày 22/11 đến 25/11 ở 2 người thì bao tiền?',
        ])->assertOk()->assertJsonPath('source', 'gemini-langgraph');
    }

    public function test_chatbot_understands_relative_dates_guest_counts_and_follows_up_when_ai_promises_to_check(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 23, 14, 0, 0, 'Asia/Ho_Chi_Minh'));
        $type = RoomType::create([
            'type_name' => 'Deluxe Suite',
            'price' => 1200000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
        Room::create([
            'room_number' => '301',
            'room_type_id' => $type->id,
            'floor' => 3,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);

        // Even if the upstream LLM replies with a holding phrase ("Đợi mình kiểm tra giá..."),
        // LangGraphChatbotService automatically appends the live 2-night quote and 50% deposit.
        Http::fake([
            '127.0.0.1:2024/threads' => Http::response(['thread_id' => 'relative-quote-thread'], 200),
            '127.0.0.1:2024/threads/relative-quote-thread/runs/wait' => Http::response([
                'messages' => [['type' => 'ai', 'content' => 'Đợi Pico kiểm tra giá giúp bạn nhé.']],
            ], 200),
        ]);

        $res1 = $this->postJson(route('chatbot.api'), [
            'message' => 'xem giá đặt phòng hôm nay và trả 2 hôm nữa thì bao nhiêu',
        ])->assertOk()->assertJsonPath('source', 'gemini-langgraph');

        $reply1 = (string) $res1->json('reply');
        $this->assertStringContainsString('Deluxe Suite', $reply1);
        $this->assertStringContainsString('2.400.000đ', $reply1);
        $this->assertStringContainsString('1.200.000đ', $reply1);

        // Follow-up question "2 người lớn thì bao nhiêu" without repeating the word "phòng"
        $res2 = $this->postJson(route('chatbot.api'), [
            'message' => '2 người lớn thì bao nhiêu',
            'history' => [
                ['role' => 'user', 'content' => 'xem giá đặt phòng hôm nay và trả 2 hôm nữa thì bao nhiêu'],
                ['role' => 'assistant', 'content' => $reply1],
            ],
        ])->assertOk()->assertJsonPath('source', 'gemini-langgraph');

        $this->assertStringContainsString('2 người lớn', (string) $res2->json('reply'));
        $this->assertStringContainsString('2.400.000đ', (string) $res2->json('reply'));

        Carbon::setTestNow();
    }

    public function test_chatbot_anti_spam_guard_blocks_rapid_fire_and_duplicate_flooding(): void
    {
        Cache::flush();
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
            'services.langgraph.enforce_anti_spam_in_tests' => true,
        ]);

        Http::fake([
            '127.0.0.1:2024/threads' => Http::response(['thread_id' => 'spam-thread'], 200),
            '127.0.0.1:2024/threads/spam-thread/runs/wait' => Http::response([
                'messages' => [['type' => 'ai', 'content' => 'Xin chào quý khách.']],
            ], 200),
        ]);

        // First message succeeds
        $this->postJson(route('chatbot.api'), [
            'message' => 'Cho tôi hỏi giá phòng hôm nay',
        ])->assertOk();

        // Immediate second message (<2s) is blocked by anti-spam cooldown with HTTP 429
        $this->postJson(route('chatbot.api'), [
            'message' => 'Cho tôi hỏi giá phòng ngày mai',
        ])->assertStatus(429)
            ->assertJsonPath('source', 'anti-spam');
    }
}
