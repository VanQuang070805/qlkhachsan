<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
