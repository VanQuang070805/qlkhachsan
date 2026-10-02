<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\Chatbot\DifyChatbotService;
use App\Models\Room;
use App\Models\RoomType;
use Tests\TestCase;

class DifyChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_dify_answers_through_the_existing_stream_contract_without_receiving_customer_ids_or_history(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
            'services.royal_ai.key' => null,
        ]);
        Http::fake(['dify.example.test/*' => Http::response([
            'answer' => 'Royal Hotel có phục vụ bữa sáng.',
            'conversation_id' => 'dify-conversation-1',
            'metadata' => ['retriever_resources' => [[
                'document_name' => 'Royal Hotel · Tiện ích',
            ]]],
        ])]);

        $response = $this->withSession(['customer_user_id' => 431])
            ->postJson(route('chatbot.stream'), [
                'message' => 'Khách sạn có phục vụ bữa sáng không?',
                'history' => [['role' => 'assistant', 'content' => 'Chào bạn']],
            ]);

        $response->assertOk()->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $stream = $response->streamedContent();
        $this->assertStringContainsString('"delta":"Royal "', $stream);
        $this->assertStringContainsString('"delta":"bữa "', $stream);
        $this->assertStringContainsString('"done":true', $stream);
        $this->assertStringNotContainsString('Nguồn:', $stream);
        $this->assertStringNotContainsString('dify-server-only-test-key', $stream);

        Http::assertSent(fn ($request) => $request->url() === 'https://dify.example.test/v1/chat-messages'
            && $request->hasHeader('Authorization', 'Bearer dify-server-only-test-key')
            && str_contains($request->body(), '"inputs":{}')
            && $request['query'] === 'Khách sạn có phục vụ bữa sáng không?'
            && $request['response_mode'] === 'blocking'
            && str_starts_with($request['user'], 'royal_')
            && ! str_contains($request['user'], '431')
            && ! array_key_exists('history', $request->data())
            && ! array_key_exists('customer_user_id', $request->data()));
    }

    public function test_dify_conversation_id_is_kept_server_side_for_follow_up_turns(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
            'services.royal_ai.key' => null,
        ]);
        Http::fake(['dify.example.test/*' => Http::sequence()
            ->push(['answer' => 'Chào bạn.', 'conversation_id' => 'dify-conversation-1'])
            ->push(['answer' => 'Có phục vụ bữa sáng.', 'conversation_id' => 'dify-conversation-1'])]);

        $session = app('session.store');
        $session->start();
        $request = Request::create('/chatbot/api', 'POST');
        $request->setLaravelSession($session);
        $service = app(DifyChatbotService::class);

        $service->ask($request, 'Xin chào');
        $service->ask($request, 'Có phục vụ bữa sáng không?');

        Http::assertSent(fn ($request) => ($request->data()['query'] ?? null) === 'Có phục vụ bữa sáng không?'
            && ($request->data()['conversation_id'] ?? null) === 'dify-conversation-1');
    }

    public function test_room_advice_and_capacity_questions_use_gemini_knowledge_chatflow(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
            'services.royal_ai.key' => null,
        ]);
        Http::fake(['dify.example.test/*' => Http::response([
            'answer' => 'Phòng Đôi Tiêu Chuẩn phù hợp cho 2 người lớn và tối đa 1 trẻ em. Để kiểm tra 2 phòng còn trống, bạn cho mình ngày nhận và trả phòng nhé.',
            'conversation_id' => 'dify-room-advice',
        ])]);

        foreach ([
            'Phòng đôi phù hợp tối đa bao nhiêu khách?',
            'Tôi cần 2 phòng cho gia đình, nên chọn loại nào?',
        ] as $message) {
            $this->postJson(route('chatbot.api'), ['message' => $message])
                ->assertOk()
                ->assertExactJson(['reply' => 'Phòng Đôi Tiêu Chuẩn phù hợp cho 2 người lớn và tối đa 1 trẻ em. Để kiểm tra 2 phòng còn trống, bạn cho mình ngày nhận và trả phòng nhé.'])
                ->assertJsonPath('reply', 'Phòng Đôi Tiêu Chuẩn phù hợp cho 2 người lớn và tối đa 1 trẻ em. Để kiểm tra 2 phòng còn trống, bạn cho mình ngày nhận và trả phòng nhé.');
        }

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://dify.example.test/v1/chat-messages'
            && in_array($request['query'], [
                'Phòng đôi phù hợp tối đa bao nhiêu khách?',
                'Tôi cần 2 phòng cho gia đình, nên chọn loại nào?',
            ], true));
    }

    public function test_dify_failure_does_not_fall_back_to_the_legacy_chatbot(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
            'services.royal_ai.key' => null,
        ]);
        Http::fake(['dify.example.test/*' => Http::response(['message' => 'unavailable'], 503)]);

        $this->postJson(route('chatbot.api'), ['message' => 'Xin chào'])
            ->assertStatus(503)
            ->assertJsonMissingPath('reply');

        Http::assertSentCount(1);
    }

    public function test_long_gemini_answer_is_not_cut_off_mid_sentence(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
        ]);
        $answer = str_repeat('Giải thích chi tiết. ', 180).'Kết thúc đầy đủ.';
        Http::fake(['dify.example.test/*' => Http::response([
            'answer' => $answer,
            'conversation_id' => 'dify-long-answer',
        ])]);

        $this->postJson(route('chatbot.api'), ['message' => 'Giải thích kỹ nhé'])
            ->assertOk()
            ->assertJsonPath('reply', $answer);
    }

    public function test_markdown_bold_markers_are_not_shown_in_plain_text_widget(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
        ]);
        Http::fake(['dify.example.test/*' => Http::response([
            'answer' => 'Phòng **Đôi Tiêu Chuẩn** còn 2 phòng.',
            'conversation_id' => 'dify-formatting',
        ])]);

        $this->postJson(route('chatbot.api'), ['message' => 'Còn phòng không?'])
            ->assertOk()
            ->assertJsonPath('reply', 'Phòng Đôi Tiêu Chuẩn còn 2 phòng.');
    }

    public function test_live_room_prices_are_answered_by_dify_not_a_legacy_static_reply(): void
    {
        config([
            'services.dify.base_url' => 'https://dify.example.test/v1',
            'services.dify.api_key' => 'dify-server-only-test-key',
            'services.dify.live_tools_enabled' => true,
            'services.dify.tool_api_key' => null,
            'services.royal_ai.key' => null,
        ]);
        RoomType::create([
            'type_name' => 'Phòng Kiểm Thử',
            'price' => 765000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Không gian thử nghiệm',
        ]);
        Http::fake(['dify.example.test/*' => Http::response([
            'answer' => 'Giá hiện tại của Phòng Kiểm Thử là 765.000 đồng mỗi đêm.',
            'conversation_id' => 'dify-price-conversation',
        ])]);

        $this->postJson(route('chatbot.api'), ['message' => 'Giá phòng hiện tại?'])
            ->assertOk()
            ->assertExactJson(['reply' => 'Giá hiện tại của Phòng Kiểm Thử là 765.000 đồng mỗi đêm.']);

        Http::assertSentCount(1);
    }

    public function test_dify_public_tools_require_their_separate_bearer_key(): void
    {
        config(['services.dify.live_tools_enabled' => true, 'services.dify.tool_api_key' => 'dify-tools-test-key']);

        $this->postJson(route('chatbot.tools.room-types'))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized.']);
    }

    public function test_dify_availability_tool_returns_only_public_aggregated_room_data(): void
    {
        config(['services.dify.live_tools_enabled' => true, 'services.dify.tool_api_key' => 'dify-tools-test-key']);
        $roomType = RoomType::create([
            'type_name' => 'Phòng Kiểm Thử',
            'price' => 765000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Không gian thử nghiệm',
        ]);
        Room::create(['room_number' => 'QA-DIFY-101', 'room_type_id' => $roomType->id, 'floor' => 1, 'status' => 'available']);

        $checkIn = now()->addDays(2)->toDateString();
        $response = $this->postJson(route('chatbot.tools.rooms.search'), [
            'check_in' => $checkIn,
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
            'rooms' => 2,
        ], ['Authorization' => 'Bearer dify-tools-test-key']);

        $response->assertOk()
            ->assertJsonPath('room_types.0.type_name', 'Phòng Kiểm Thử')
            ->assertJsonPath('room_types.0.available_rooms', 1)
            ->assertJsonPath('requested_rooms', 2)
            ->assertJsonPath('room_types.0.enough_for_request', false)
            ->assertJsonPath('room_types.0.price_per_night', 765000)
            ->assertJsonMissingPath('room_types.0.room_number')
            ->assertJsonMissingPath('room_types.0.room_id')
            ->assertDontSee('QA-DIFY-101');
    }

    public function test_two_rooms_can_accommodate_guests_across_both_rooms(): void
    {
        config(['services.dify.live_tools_enabled' => true, 'services.dify.tool_api_key' => 'dify-tools-test-key']);
        $roomType = RoomType::create([
            'type_name' => 'Phòng Đôi Kiểm Thử',
            'price' => 650000,
            'max_adults' => 2,
            'max_children' => 0,
            'max_guests' => 2,
            'description' => 'Hai phòng cho bốn khách',
        ]);
        foreach (['QA-DIFY-201', 'QA-DIFY-202'] as $number) {
            Room::create(['room_number' => $number, 'room_type_id' => $roomType->id, 'floor' => 2, 'status' => 'available']);
        }

        $this->postJson(route('chatbot.tools.rooms.search'), [
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 4,
            'rooms' => 2,
        ], ['Authorization' => 'Bearer dify-tools-test-key'])
            ->assertOk()
            ->assertJsonPath('room_types.0.available_rooms', 2)
            ->assertJsonPath('room_types.0.enough_for_request', true);
    }
}
