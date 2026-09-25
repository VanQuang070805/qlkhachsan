<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\RoyalKnowledgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function __construct(private readonly RoyalKnowledgeService $knowledge) {}

    public function api(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['sometimes', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1000'],
        ]);
        $message = trim($validated['message']);
        $history = $validated['history'] ?? $request->session()->get('royal_chat_history', []);

        if ($this->isFlagged($message)) {
            $reply = 'Tôi không thể hỗ trợ nội dung này. Tôi vẫn sẵn sàng trò chuyện hoặc giúp bạn chuẩn bị một kỳ nghỉ an toàn tại Royal Hotel.';
            $this->rememberConversation($request, $history, $message, $reply);
            return response()->json(['reply' => $reply, 'source' => 'safety', 'sources' => []]);
        }

        if ($answer = $this->askAi($request, $message, $history)) {
            $this->rememberConversation($request, $history, $message, $answer['reply']);
            return response()->json($answer + ['source' => 'ai']);
        }

        $fallback = $this->getRuleBasedReply(mb_strtolower($message, 'UTF-8'));
        $fallback = strip_tags(preg_replace('/<br\s*\/?\s*>/i', "\n", $fallback));

        $fallback = trim($fallback);
        $this->rememberConversation($request, $history, $message, $fallback);

        return response()->json(['reply' => $fallback, 'source' => 'hotel', 'sources' => []]);
    }

    public function stream(Request $request)
    {
        $response = $this->api($request);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $payload = $response->getData(true);

        return response()->stream(function () use ($payload) {
            foreach (preg_split('/(?<=\s)/u', (string) $payload['reply'], -1, PREG_SPLIT_NO_EMPTY) as $chunk) {
                echo 'data: '.json_encode(['delta' => $chunk], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                usleep(12000);
            }
            echo 'data: '.json_encode(['done' => true, 'source' => $payload['source'], 'sources' => $payload['sources'] ?? []], JSON_UNESCAPED_UNICODE)."\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function rememberConversation(Request $request, array $history, string $message, string $reply): void
    {
        $history[] = ['role' => 'user', 'content' => mb_substr($message, 0, 1000)];
        $history[] = ['role' => 'assistant', 'content' => mb_substr($reply, 0, 1000)];
        $request->session()->put('royal_chat_history', array_slice($history, -10));
    }

    private function isFlagged(string $message): bool
    {
        $key = config('services.royal_ai.key');
        if (! $key || ! config('services.royal_ai.moderation_enabled') || ! str_contains((string) config('services.royal_ai.endpoint'), 'api.openai.com')) {
            return false;
        }

        try {
            return (bool) Http::withToken($key)->acceptJson()->timeout(8)
                ->post('https://api.openai.com/v1/moderations', [
                    'model' => 'omni-moderation-latest',
                    'input' => $message,
                ])->json('results.0.flagged', false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function askAi(Request $request, string $message, array $history): ?array
    {
        $key = config('services.royal_ai.key');
        if (! $key) {
            return null;
        }

        $documents = $this->knowledge->search($message);
        $context = collect($documents)->map(fn (array $document) => "[{$document['source']}]\n{$document['content']}")->implode("\n\n");
        $system = "Bạn là Royal Concierge, một người đồng hành lịch thiệp và tự nhiên. Hãy trả lời ngôn ngữ của người dùng, ưu tiên tiếng Việt, với giọng điệu ngắn gọn, ấm áp và có mạch hội thoại. "
            . "Bạn có thể trò chuyện và trả lời kiến thức phổ thông ngoài chủ đề khách sạn bằng kiến thức của mô hình. Với tin tức hoặc dữ liệu thời gian thực mà không có tool, hãy nói rõ giới hạn thay vì đoán. "
            . "Riêng thông tin Royal Hotel, chỉ dùng tài liệu truy hồi hoặc tool. Giá, phòng trống và kỳ nghỉ phải lấy bằng tool; không tự đoán. "
            . "Không yêu cầu hay lặp lại mật khẩu, OTP, dữ liệu thẻ hoặc khóa bí mật. Khách chỉ được xem kỳ nghỉ của chính phiên đăng nhập. "
            . "Nếu chưa đủ ngày hoặc số khách để tìm phòng, hãy hỏi lại. Không dùng HTML. Khi dùng tài liệu, có thể nhắc tên nguồn tự nhiên.\n\n"
            . "TÀI LIỆU TRUY HỒI:\n" . ($context ?: 'Không có đoạn tài liệu phù hợp; hãy dùng tool hoặc nói rõ giới hạn.');

        $messages = [['role' => 'system', 'content' => $system]];
        foreach (array_slice($history, -8) as $item) {
            $messages[] = ['role' => $item['role'], 'content' => trim($item['content'])];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $client = Http::withToken($key)->acceptJson()->timeout(18)->retry(1, 200);
            $payload = [
                'model' => config('services.royal_ai.model'),
                'messages' => $messages,
                'tools' => $this->toolDefinitions(),
                'tool_choice' => 'auto',
                'temperature' => 0.3,
                'max_tokens' => 550,
            ];
            $response = $client->post(config('services.royal_ai.endpoint'), $payload);

            if (! $response->successful()) {
                Log::warning('Royal AI request failed', ['status' => $response->status()]);
                return null;
            }

            $assistant = $response->json('choices.0.message', []);
            $toolCalls = $assistant['tool_calls'] ?? [];
            if ($toolCalls) {
                $messages[] = $assistant;
                foreach ($toolCalls as $call) {
                    $arguments = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $call['id'],
                        'content' => json_encode($this->runTool($request, $call['function']['name'] ?? '', $arguments), JSON_UNESCAPED_UNICODE),
                    ];
                }
                $response = $client->post(config('services.royal_ai.endpoint'), [
                    'model' => config('services.royal_ai.model'),
                    'messages' => $messages,
                    'temperature' => 0.25,
                    'max_tokens' => 650,
                ]);
                if (! $response->successful()) {
                    return null;
                }
                $assistant = $response->json('choices.0.message', []);
            }

            $reply = trim((string) ($assistant['content'] ?? ''));
            return $reply !== '' ? [
                'reply' => mb_substr($reply, 0, 3000),
                'sources' => collect($documents)->pluck('source')->unique()->values()->all(),
            ] : null;
        } catch (\Throwable $exception) {
            Log::warning('Royal AI unavailable', ['type' => $exception::class]);
            return null;
        }
    }

    private function toolDefinitions(): array
    {
        return [
            ['type' => 'function', 'function' => [
                'name' => 'get_room_types',
                'description' => 'Lấy hạng phòng và giá hiện tại.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'search_rooms',
                'description' => 'Tìm phòng còn trống theo ngày và tổng số khách.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'check_in' => ['type' => 'string', 'description' => 'Ngày YYYY-MM-DD'],
                        'check_out' => ['type' => 'string', 'description' => 'Ngày YYYY-MM-DD'],
                        'guests' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                    ],
                    'required' => ['check_in', 'check_out', 'guests'],
                ],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'get_booking_for_user',
                'description' => 'Lấy các kỳ nghỉ gần đây của khách đang đăng nhập.',
                'parameters' => ['type' => 'object', 'properties' => new \stdClass],
            ]],
        ];
    }

    private function runTool(Request $request, string $name, array $arguments): array
    {
        return match ($name) {
            'get_room_types' => ['room_types' => RoomType::query()->orderBy('price')->get(['id', 'type_name', 'price', 'max_guests'])->toArray()],
            'search_rooms' => $this->searchRooms($arguments),
            'get_booking_for_user' => $this->bookingsForCurrentCustomer($request),
            default => ['error' => 'Tool không được phép.'],
        };
    }

    private function searchRooms(array $arguments): array
    {
        $validator = Validator::make($arguments, [
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        if ($validator->fails()) {
            return ['error' => 'Ngày hoặc số khách chưa hợp lệ.', 'details' => $validator->errors()->toArray()];
        }

        $data = $validator->validated();
        if ($data['check_in'] === now()->toDateString() && now()->hour >= 17) {
            return ['error' => 'Sau 17:00 không thể nhận phòng trong ngày hôm nay.'];
        }

        $reserved = Booking::reservedRoomIds($data['check_in'], $data['check_out']);
        $rooms = Room::query()->with('roomType:id,type_name,price,max_guests')
            ->where('status', 'available')->whereNotIn('id', $reserved)
            ->whereHas('roomType', fn ($query) => $query->where('max_guests', '>=', $data['guests']))
            ->orderBy('room_type_id')->limit(12)->get(['id', 'room_number', 'room_type_id'])
            ->map(fn (Room $room) => [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->roomType?->type_name,
                'price_per_night' => (float) ($room->roomType?->price ?? 0),
                'max_guests' => $room->roomType?->max_guests,
            ])->values()->all();

        return ['check_in' => $data['check_in'], 'check_out' => $data['check_out'], 'rooms' => $rooms];
    }

    private function bookingsForCurrentCustomer(Request $request): array
    {
        $userId = (int) ($request->session()->get('customer_user_id') ?: 0);
        if (! $userId) {
            return ['error' => 'Khách cần đăng nhập để xem kỳ nghỉ của mình.'];
        }

        $bookings = Booking::query()->with('rooms:id,room_number')->where('user_id', $userId)
            ->latest((new Booking)->getCreatedAtColumn())->limit(5)->get()
            ->map(fn (Booking $booking) => [
                'booking_id' => $booking->id,
                'check_in' => $booking->check_in?->toDateString(),
                'check_out' => $booking->check_out?->toDateString(),
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'rooms' => $booking->rooms->pluck('room_number')->all(),
            ])->values()->all();

        return ['bookings' => $bookings];
    }

    private function getRuleBasedReply(string $msg): string
    {
        // 1. Nhóm kịch bản: Hỏi số người (Ưu tiên kiểm tra trước)
        $hasGuestKeyword = $this->containsAny($msg, [
            '1 người', '2 người', '3 người', '4 người', 
            'một người', 'hai người', 'ba người', 'bốn người', 
            'gia đình', 'cặp đôi', 'người', 'khách', 'khach', 'nguoi',
            '1 nguoi', '2 nguoi', '3 nguoi', '4 nguoi',
            'mot nguoi', 'hai nguoi', 'ba nguoi', 'bon nguoi',
            'gia dinh', 'cap doi', 'đại gia đình', 'dai gia dinh',
            'đông người', 'dong nguoi'
        ]);

        if ($hasGuestKeyword) {
            $guests = null;
            // Dùng regex để bắt số lượng khách
            if (preg_match('/(\d+)\s*(người|nguoi|khách|khach)/u', $msg, $matches)) {
                $guests = (int)$matches[1];
            } else {
                // Ánh xạ các từ chữ sang số
                if ($this->containsAny($msg, ['một người', 'mot nguoi', '1 người', '1 nguoi'])) {
                    $guests = 1;
                } elseif ($this->containsAny($msg, ['hai người', 'hai nguoi', '2 người', '2 nguoi'])) {
                    $guests = 2;
                } elseif ($this->containsAny($msg, ['ba người', 'ba nguoi', '3 người', '3 nguoi'])) {
                    $guests = 3;
                } elseif ($this->containsAny($msg, ['bốn người', 'bon nguoi', '4 người', '4 nguoi'])) {
                    $guests = 4;
                }
            }

            if ($guests !== null) {
                if ($guests <= 2) {
                    return "Dựa trên số lượng khách là {$guests} người (<= 2 người), chúng tôi gợi ý bạn lựa chọn loại phòng <b>Standard</b> hoặc <b>Deluxe</b>.";
                } elseif ($guests <= 4) {
                    return "Dựa trên số lượng khách là {$guests} người (3-4 người), chúng tôi gợi ý bạn lựa chọn loại phòng <b>Deluxe</b> hoặc <b>phòng gia đình</b>.";
                } else {
                    return "Dựa trên số lượng khách là {$guests} người (> 4 người), bạn nên cân nhắc <b>đặt nhiều phòng</b> hoặc gửi yêu cầu tại trang <b>Liên hệ</b> để lễ tân hỗ trợ sắp xếp.";
                }
            }

            if ($this->containsAny($msg, ['đại gia đình', 'dai gia dinh', 'đông người', 'dong nguoi'])) {
                return "Đối với đoàn khách đông hoặc đại gia đình (> 4 người), bạn nên <b>đặt nhiều phòng</b> hoặc gửi yêu cầu tại trang <b>Liên hệ</b> để lễ tân hỗ trợ.";
            }

            if ($this->containsAny($msg, ['cặp đôi', 'cap doi'])) {
                return "Đối với cặp đôi đi nghỉ dưỡng (<= 2 người), chúng tôi gợi ý bạn lựa chọn loại phòng <b>Standard</b> hoặc <b>Deluxe</b> để có không gian lãng mạn nhất.";
            }

            if ($this->containsAny($msg, ['gia đình', 'gia dinh'])) {
                return "Đối với chuyến đi gia đình (3-4 người), chúng tôi gợi ý bạn lựa chọn loại phòng <b>Deluxe</b> hoặc <b>phòng gia đình</b> để có không gian thoải mái nhất.";
            }
        }

        // 2. Nhóm kịch bản: Hỏi giá phòng (Lấy dữ liệu thật từ DB)
        $hasPriceKeyword = $this->containsAny($msg, [
            'giá', 'bao nhiêu', 'tiền phòng', 'phòng rẻ nhất', 'phòng đắt nhất',
            'gia', 'bao nhieu', 'tien phong', 're nhat', 'dat nhat'
        ]);

        if ($hasPriceKeyword) {
            try {
                $roomTypes = DB::select('SELECT type_name, price, max_guests, description FROM room_types ORDER BY price ASC');
            } catch (\Exception $e) {
                $roomTypes = [];
            }

            if (!empty($roomTypes)) {
                // Hỏi phòng rẻ nhất
                if ($this->containsAny($msg, ['rẻ nhất', 're nhat', 'thấp nhất', 'thap nhat'])) {
                    $cheapest = $roomTypes[0];
                    $name = is_array($cheapest) ? $cheapest['type_name'] : $cheapest->type_name;
                    $price = is_array($cheapest) ? $cheapest['price'] : $cheapest->price;
                    $formattedPrice = number_format((float)$price, 0, ',', '.');
                    $desc = is_array($cheapest) ? $cheapest['description'] : $cheapest->description;
                    return "Loại phòng có giá rẻ nhất tại Royal Hotel là <b>{$name}</b> với giá chỉ từ <b>{$formattedPrice} VNĐ/đêm</b> ({$desc}).";
                }

                // Hỏi phòng đắt nhất
                if ($this->containsAny($msg, ['đắt nhất', 'dat nhat', 'cao nhất', 'cao nhat'])) {
                    $expensive = $roomTypes[count($roomTypes) - 1];
                    $name = is_array($expensive) ? $expensive['type_name'] : $expensive->type_name;
                    $price = is_array($expensive) ? $expensive['price'] : $expensive->price;
                    $formattedPrice = number_format((float)$price, 0, ',', '.');
                    $desc = is_array($expensive) ? $expensive['description'] : $expensive->description;
                    return "Loại phòng cao cấp nhất tại Royal Hotel là <b>{$name}</b> với giá từ <b>{$formattedPrice} VNĐ/đêm</b> ({$desc}).";
                }

                // Giá phòng nói chung
                $response = "Bảng giá phòng hiện tại của Royal Hotel:<br>";
                foreach ($roomTypes as $rt) {
                    $rtName = is_array($rt) ? $rt['type_name'] : $rt->type_name;
                    $rtPrice = is_array($rt) ? $rt['price'] : $rt->price;
                    $rtGuests = is_array($rt) ? $rt['max_guests'] : $rt->max_guests;
                    $formattedPrice = number_format((float)$rtPrice, 0, ',', '.');
                    $response .= "- <b>{$rtName}</b>: {$formattedPrice} VNĐ/đêm (Tối đa {$rtGuests} người)<br>";
                }
                $response .= "<br>Bạn có thể nhấn vào mục 'Tìm phòng trống' trên thanh menu để chọn ngày và đặt phòng nhé.";
                return $response;
            } else {
                // Tĩnh phòng hờ khi DB lỗi hoặc trống
                return "Hiện chưa thể đọc bảng giá. Bạn vui lòng mở mục 'Phòng nghỉ' hoặc thử lại sau để xem dữ liệu cập nhật.";
            }
        }

        // 3. Nhóm kịch bản: Hỏi loại phòng (Lấy dữ liệu thật từ DB)
        $hasRoomTypeKeyword = $this->containsAny($msg, [
            'loại phòng', 'phòng nào', 'standard', 'deluxe', 'suite', 'phòng gia đình',
            'loai phong', 'phong nao', 'phong gia dinh'
        ]);

        if ($hasRoomTypeKeyword) {
            try {
                $roomTypes = DB::select('SELECT type_name, price, max_guests, description FROM room_types ORDER BY price ASC');
            } catch (\Exception $e) {
                $roomTypes = [];
            }

            if (!empty($roomTypes)) {
                // Kiểm tra xem người dùng có hỏi loại phòng cụ thể nào không
                foreach ($roomTypes as $rt) {
                    $rtName = is_array($rt) ? $rt['type_name'] : $rt->type_name;
                    $rtPrice = is_array($rt) ? $rt['price'] : $rt->price;
                    $rtGuests = is_array($rt) ? $rt['max_guests'] : $rt->max_guests;
                    $desc = is_array($rt) ? $rt['description'] : $rt->description;
                    $formattedPrice = number_format((float)$rtPrice, 0, ',', '.');

                    $lowerRtName = mb_strtolower($rtName, 'UTF-8');
                    // Ví dụ: tìm từ khóa "phòng đôi", "triple", "gia đình"
                    if (mb_strpos($msg, $lowerRtName) !== false) {
                        return "Thông tin chi tiết về <b>{$rtName}</b>:<br>- Giá phòng: Từ {$formattedPrice} VNĐ/đêm<br>- Sức chứa: Tối đa {$rtGuests} người<br>- Mô tả: {$desc}";
                    }
                }

                // Nếu hỏi loại phòng chung chung
                $response = "Khách sạn Royal Hotel hiện cung cấp các loại phòng sau:<br>";
                foreach ($roomTypes as $rt) {
                    $rtName = is_array($rt) ? $rt['type_name'] : $rt->type_name;
                    $rtGuests = is_array($rt) ? $rt['max_guests'] : $rt->max_guests;
                    $desc = is_array($rt) ? $rt['description'] : $rt->description;
                    $response .= "- <b>{$rtName}</b>: {$desc} (Tối đa {$rtGuests} khách)<br>";
                }
                $response .= "<br>Hãy gõ tên phòng cụ thể để tôi tư vấn chi tiết hơn nhé!";
                return $response;
            } else {
                return "Chúng tôi cung cấp đa dạng loại phòng từ phòng đơn tiêu chuẩn, phòng đôi, phòng triple đến phòng gia đình lớn và phòng VIP. Vui lòng nhấn vào mục 'Tìm phòng trống' để xem chi tiết từng loại.";
            }
        }

        // 4. Nhóm kịch bản: Hỏi đặt phòng
        $hasBookingKeyword = $this->containsAny($msg, ['đặt phòng', 'đặt lịch', 'book phòng', 'booking', 'dat phong', 'dat lich', 'book phong']);
        if ($hasBookingKeyword) {
            return "Để đặt phòng tại Royal Hotel, bạn vui lòng làm theo các bước sau:<br>" .
                   "1. Nhấp vào mục <b>'Tìm phòng trống'</b> trên thanh menu chính.<br>" .
                   "2. Chọn ngày nhận phòng (Check-in), ngày trả phòng (Check-out) và số lượng khách.<br>" .
                   "3. Nhấn 'Tìm kiếm' để hiển thị các phòng còn trống.<br>" .
                   "4. Chọn phòng ưng ý, bấm 'Đặt phòng', điền đầy đủ thông tin cá nhân và chọn phương thức thanh toán.<br>" .
                   "Hệ thống sẽ gửi email xác nhận đặt phòng ngay sau khi hoàn tất.";
        }

        // 5. Nhóm kịch bản: Hỏi hủy phòng
        $hasCancelKeyword = $this->containsAny($msg, ['hủy', 'hủy phòng', 'cancel', 'huy', 'huy phong']);
        if ($hasCancelKeyword) {
            return "Quy định hủy phòng tại Royal Hotel:<br>" .
                   "- Bạn có thể tự hủy đặt phòng trực tuyến tại mục <b>Tài khoản -> Đặt phòng của tôi</b> đối với các đơn phòng chưa được xác nhận (trạng thái Chờ xác nhận).<br>" .
                   "- Đối với đơn đã xác nhận hoặc đã thanh toán, vui lòng gửi yêu cầu tại trang <b>Liên hệ</b> để được kiểm tra điều kiện hoàn hủy.";
        }

        // 6. Nhóm kịch bản: Hỏi thanh toán
        $hasPaymentKeyword = $this->containsAny($msg, ['thanh toán', 'đặt cọc', 'chuyển khoản', 'hoàn tiền', 'tiền mặt', 'thanh toan', 'dat coc', 'chuyen khoan', 'hoan tien', 'tien mat']);
        if ($hasPaymentKeyword) {
            return "Khách sạn hỗ trợ nhiều phương thức thanh toán linh hoạt:<br>" .
                   "- <b>Thanh toán online:</b> Chuyển khoản ngân hàng hoặc qua cổng thanh toán VNPAY khi đặt phòng.<br>" .
                   "- <b>Thanh toán tại quầy:</b> Tiền mặt hoặc quẹt thẻ (Visa, Mastercard, JCB...) khi nhận phòng tại quầy lễ tân.<br>" .
                   "- <i>Lưu ý:</i> Một số giai đoạn cao điểm hoặc chương trình ưu đãi đặc biệt có thể yêu cầu thanh toán đặt cọc trước để giữ phòng.";
        }

        // 7. Nhóm kịch bản: Hỏi check-in/check-out
        $hasCheckInOutKeyword = $this->containsAny($msg, ['check in', 'nhận phòng', 'check out', 'trả phòng', 'nhan phong', 'tra phong', 'checkin', 'checkout']);
        if ($hasCheckInOutKeyword) {
            return "Quy định thời gian nhận/trả phòng tại Royal Hotel:<br>" .
                   "- <b>Thời gian nhận phòng (Check-in):</b> Từ 12:00 đến 17:00.<br>" .
                   "- <b>Thời gian trả phòng (Check-out):</b> Trước 12:00 trưa.<br>" .
                   "- Nếu bạn có nhu cầu nhận phòng sớm hoặc trả phòng muộn, vui lòng liên hệ trước với bộ phận lễ tân để được kiểm tra tình trạng phòng trống và áp dụng mức phụ thu tương ứng.";
        }

        // 8. Nhóm kịch bản: Hỏi phòng trống
        $hasAvailableKeyword = $this->containsAny($msg, ['phòng trống', 'còn phòng', 'tìm phòng', 'available', 'phong trong', 'con phong', 'tim phong']);
        if ($hasAvailableKeyword) {
            return "Để biết chính xác các phòng còn trống trong khoảng thời gian lưu trú của bạn, vui lòng truy cập trang <b>'Tìm phòng trống'</b> trên thanh menu, nhập ngày đi và ngày về để hệ thống tự động kiểm tra trạng thái phòng trực tuyến.";
        }

        // 9. Nhóm kịch bản: Chào hỏi cơ bản
        if ($this->containsAny($msg, ['xin chào', 'chào', 'hello', 'hi', 'chao'])) {
            return "Xin chào! Tôi là trợ lý ảo của Royal Hotel.<br>Tôi có thể giúp bạn tìm hiểu thông tin về giá phòng, đặt phòng, loại phòng, thanh toán, hủy phòng, check-in hoặc check-out. Hãy nhập câu hỏi để tôi hỗ trợ nhé!";
        }

        // 10. Fallback mặc định khi không hiểu (Yêu cầu 4)
        return "Tôi đang ở chế độ hỗ trợ cơ bản nên chỉ xử lý được thông tin lưu trú. Khi cấu hình mô hình AI, Royal Concierge sẽ có thể trò chuyện tự nhiên hơn và vẫn tra cứu đúng dữ liệu khách sạn.";
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}
