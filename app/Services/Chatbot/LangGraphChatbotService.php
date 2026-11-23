<?php

namespace App\Services\Chatbot;

use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\CacheVersionService;
use App\Services\OutboxService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LangGraphChatbotService
{
    public function __construct(
        private readonly ChatbotCapabilityService $capabilities = new ChatbotCapabilityService(),
        private readonly HotelChatScopeService $scope = new HotelChatScopeService(),
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

    /**
     * Fast-path check so SSE controller can skip upstream thread creation on cache hits.
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @param  array<string, mixed>  $pageContext
     */
    public function hasCachedPublicReply(string $message, array $history = [], ?Request $request = null, array $pageContext = []): bool
    {
        $cacheKey = $this->publicFaqCacheKey($message, $history, $request, $pageContext);
        if ($cacheKey === null) {
            return false;
        }

        $cached = Cache::get($cacheKey);

        return is_array($cached) && isset($cached['reply']) && is_string($cached['reply']) && $cached['reply'] !== '';
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @param  array<string, mixed>  $pageContext
     */
    private function publicFaqCacheKey(string $message, array $history = [], ?Request $request = null, array $pageContext = []): ?string
    {
        $trimmed = trim($message);
        if (
            $trimmed === '' ||
            $history !== [] ||
            ($request !== null && (int) $request->session()->get('customer_user_id', 0) > 0) ||
            ! empty($pageContext['room_name']) ||
            $this->isPersonalBookingQuery($trimmed) ||
            $this->isContextDependentQuery($trimmed) ||
            $this->isTimeSensitiveQuery($trimmed) ||
            preg_match('/\b\d{1,2}[\/\-]\d{1,2}\b|\b202\d[\-\/]\d{1,2}\b/u', $trimmed) ||
            preg_match('/^(còn|con|thế|the|vậy|vay|nó|no|loại đó|loai do|phòng đó|phong do|hạng đó|hang do|bao nhiêu|bao nhieu|giá thì sao|gia thi sao|nếu|neu|quá giờ|qua gio|sớm hơn|som hon|ở đó|o do|vừa rồi|vua roi)\b/iu', $trimmed)
        ) {
            return null;
        }

        return 'royal:chatbot:answer:'.$this->buildFingerprint($trimmed, [], 'public');
    }

    private function isContextDependentQuery(string $message): bool
    {
        return (bool) preg_match('/\b(tôi|mình|của tôi|cua toi|tên gì|ten gi|là ai|la ai|trang này|trang nay|phòng này|phong nay|hạng này|hang nay|đang xem|dang xem|đang ở|dang o|tài khoản|tai khoan)\b/iu', $message);
    }

    public function sessionParticipantId(Request $request): string
    {
        $customerId = (int) $request->session()->get('customer_user_id', 0);
        $staffContext = (int) $request->session()->get('staff_user_id', 0) > 0 && $customerId === 0;
        $visitNonce = (string) $request->session()->get('chatbot.visit_nonce', '');
        if ($visitNonce === '') {
            $visitNonce = bin2hex(random_bytes(12));
            $request->session()->put('chatbot.visit_nonce', $visitNonce);
        }

        $actorKey = $customerId > 0
            ? 'user:'.$customerId
            : ($staffContext ? 'staff-session' : 'guest');

        return 'royal_'.substr(hash_hmac(
            'sha256',
            $actorKey.'|'.$visitNonce,
            (string) config('app.key'),
        ), 0, 40);
    }

    /**
     * Build a verified reality snapshot of the current user, their bookings, and the page they are viewing.
     *
     * @param  array<string, mixed>  $pageContext
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function buildUserRealitySummary(Request $request, array $pageContext = [], string $message = '', array $history = []): string
    {
        $lines = [];
        $now = now('Asia/Ho_Chi_Minh');
        $after18 = (int) $now->format('H') >= 18;
        $lines[] = 'Thời điểm thực tế tại Hà Nội: '.$now->format('H:i d/m/Y').' ('.($after18
            ? 'Đã qua 18:00 nên khách đặt mới lúc này chỉ có thể chọn ngày nhận phòng từ ngày mai trở đi'
            : 'Khung nhận phòng hôm nay mở từ 14:00 đến trước 18:00').').';

        $customerId = (int) $request->session()->get('customer_user_id', 0);
        $user = $customerId > 0 ? User::where('role', 'customer')->find($customerId) : null;

        if ($user) {
            $phoneState = $user->phone ? 'Đã cập nhật số điện thoại ('.substr((string) $user->phone, 0, 3).'***'.substr((string) $user->phone, -3).')' : 'Chưa cập nhật số điện thoại';
            $lines[] = sprintf(
                'Khách hàng đang đăng nhập: %s | Email: %s | %s.',
                $user->fullname ?: 'Khách hàng',
                $user->email,
                $phoneState
            );

            $bookings = Booking::with('rooms.roomType')
                ->where('user_id', $user->id)
                ->orderByDesc((new Booking)->getCreatedAtColumn())
                ->limit(3)
                ->get();

            if ($bookings->isEmpty()) {
                $lines[] = 'Lịch sử đặt phòng hiện tại của khách: Chưa có đơn đặt phòng nào.';
            } else {
                $summaries = $bookings->map(function (Booking $b) {
                    $rooms = $b->rooms->map(fn ($r) => 'P.'.$r->room_number.' ('.($r->roomType?->type_name ?? 'Phòng').')')->implode(', ');
                    $in = $b->check_in ? Carbon::parse($b->check_in)->format('d/m/Y') : '';
                    $out = $b->check_out ? Carbon::parse($b->check_out)->format('d/m/Y') : '';
                    $statusLabel = match ($b->status) {
                        'pending' => 'Chờ thanh toán cọc 50%',
                        'confirmed' => 'Đã xác nhận giữ phòng',
                        'checked_in' => 'Đang lưu trú',
                        'completed' => 'Đã trả phòng',
                        'cancelled' => 'Đã hủy',
                        default => (string) $b->status,
                    };

                    return sprintf(
                        'Đơn #%d [%s | %s | Nhận %s - Trả %s | Tổng %sđ]',
                        $b->id,
                        $statusLabel,
                        $rooms !== '' ? $rooms : 'Chưa gán phòng',
                        $in,
                        $out,
                        number_format((float) $b->total_price, 0, ',', '.')
                    );
                })->implode('; ');
                $lines[] = 'Các đơn đặt phòng gần nhất của khách: '.$summaries.'.';
            }
        } else {
            $lines[] = 'Trạng thái tài khoản: Khách vãng lai (chưa đăng nhập). Nếu khách muốn xem đơn đặt phòng cá nhân hoặc đặt phòng, hãy hướng dẫn khách đăng nhập.';
        }

        $path = trim((string) ($pageContext['path'] ?? ''));
        $title = trim((string) ($pageContext['title'] ?? ''));
        $roomName = trim((string) ($pageContext['room_name'] ?? ''));
        $roomPrice = trim((string) ($pageContext['room_price'] ?? ''));
        $checkIn = trim((string) ($pageContext['check_in'] ?? ''));
        $checkOut = trim((string) ($pageContext['check_out'] ?? ''));
        $guests = trim((string) ($pageContext['guests'] ?? ''));

        if ($path !== '' || $title !== '') {
            $pageParts = array_filter([
                $path !== '' ? 'Đường dẫn '.$path : null,
                $title !== '' ? 'Tiêu đề "'.$title.'"' : null,
                $roomName !== '' ? 'Đang xem hạng phòng "'.$roomName.'"' : null,
                $roomPrice !== '' ? 'Giá hiển thị '.$roomPrice : null,
                ($checkIn !== '' || $checkOut !== '') ? 'Ngày đang chọn '.$checkIn.' -> '.$checkOut : null,
                $guests !== '' ? 'Số khách đang chọn '.$guests : null,
            ]);
            $lines[] = 'Ngữ cảnh trang web khách đang mở ngay lúc này: '.implode(' | ', $pageParts).'.';
        }

        $quoteCtx = $this->buildLiveQuoteContext($message, $history, $pageContext);
        if (! empty($quoteCtx['reality_block'])) {
            $lines[] = $quoteCtx['reality_block'];
        }

        return implode("\n", $lines);
    }

    /**
     * Parse natural Vietnamese relative/explicit dates, stay duration, and guest counts
     * from the current message + recent conversation history, and compute exact live prices & availability.
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @param  array<string, mixed>  $pageContext
     * @return array{is_quote_query: bool, reality_block: string, fallback_reply: string}
     */
    public function buildLiveQuoteContext(string $message, array $history = [], array $pageContext = []): array
    {
        $now = now('Asia/Ho_Chi_Minh');
        $today = $now->copy()->startOfDay();
        $userMessages = [];
        foreach (array_slice($history, -6) as $item) {
            if (($item['role'] ?? '') === 'user' && ! empty($item['content'])) {
                $userMessages[] = (string) $item['content'];
            }
        }
        if ($message !== '') {
            $userMessages[] = $message;
        }
        $combined = mb_strtolower(implode(' | ', $userMessages), 'UTF-8');
        $currentLower = mb_strtolower($message, 'UTF-8');

        $isQuoteOrRoomIntent = (bool) preg_match('/\b(giá|gia|bao nhiêu|bao nhieu|bao tiền|bao tien|hết bao|het bao|chi phí|chi phi|check|xem|báo giá|bao gia|tính|tinh|phòng|phong|hạng|hang|người|nguoi|khách|khach|đêm|dem|ngày|ngay|hôm|hom|còn trống|con trong|đặt|dat|nhận|nhan|trả|tra)\b/ui', $combined);
        if (! $isQuoteOrRoomIntent) {
            return ['is_quote_query' => false, 'reality_block' => '', 'fallback_reply' => ''];
        }

        try {
            $roomTypes = RoomType::with('rooms')->orderBy('price')->get();
        } catch (\Throwable) {
            return ['is_quote_query' => false, 'reality_block' => '', 'fallback_reply' => ''];
        }

        if ($roomTypes->isEmpty()) {
            return ['is_quote_query' => false, 'reality_block' => '', 'fallback_reply' => ''];
        }

        // 1. Parse check-in & check-out from current message first, then fallback to recent history or pageContext
        [$checkIn, $checkOut] = $this->parseNaturalDateRange($currentLower, $today);
        if (! $checkIn || ! $checkOut) {
            [$histIn, $histOut] = $this->parseNaturalDateRange($combined, $today);
            $checkIn = $checkIn ?: $histIn;
            $checkOut = $checkOut ?: $histOut;
        }
        if ((! $checkIn || ! $checkOut) && ! empty($pageContext['check_in']) && ! empty($pageContext['check_out'])) {
            try {
                $pIn = Carbon::parse((string) $pageContext['check_in'])->startOfDay();
                $pOut = Carbon::parse((string) $pageContext['check_out'])->startOfDay();
                if ($pOut->gt($pIn)) {
                    $checkIn = $checkIn ?: $pIn;
                    $checkOut = $checkOut ?: $pOut;
                }
            } catch (\Throwable) {
                // Ignore invalid pageContext dates.
            }
        }

        $hasExplicitDates = $checkIn instanceof Carbon && $checkOut instanceof Carbon && $checkOut->gt($checkIn);
        $effectiveIn = $hasExplicitDates ? $checkIn : ($now->hour >= 18 ? $today->copy()->addDay() : $today->copy());
        $effectiveOut = $hasExplicitDates ? $checkOut : $effectiveIn->copy()->addDay();
        $nights = max(1, min(30, (int) $effectiveIn->diffInDays($effectiveOut)));

        // 2. Parse guest counts (adults, children, total guests)
        [$adults, $children, $totalGuests] = $this->parseNaturalGuests($currentLower);
        if ($totalGuests === null) {
            [$adults, $children, $totalGuests] = $this->parseNaturalGuests($combined);
        }

        $reservedIds = Booking::reservedRoomIds($effectiveIn->toDateString(), $effectiveOut->toDateString());

        $matchingLines = [];
        $fallbackItems = [];
        foreach ($roomTypes as $rt) {
            $maxAdults = (int) $rt->max_adults;
            $maxChildren = (int) $rt->max_children;
            $maxGuests = (int) $rt->max_guests;

            $fitsGuests = true;
            if ($adults !== null && $maxAdults < $adults) {
                $fitsGuests = false;
            }
            if ($children !== null && $maxChildren < $children) {
                $fitsGuests = false;
            }
            if ($totalGuests !== null && $maxGuests < $totalGuests) {
                $fitsGuests = false;
            }

            $availCount = $rt->rooms->filter(fn (Room $r) => $r->status === Room::STATUS_AVAILABLE && ! $reservedIds->contains($r->id))->count();
            $basePrice = (float) $rt->price;
            $totalPrice = round((float) PriceSetting::calculateTotalPrice($basePrice, $effectiveIn->toDateString(), $effectiveOut->toDateString()));
            $deposit = round($totalPrice * 0.5);

            $line = sprintf(
                '- %s (Tối đa %d người lớn, %d trẻ em, tổng %d khách): Giá niêm yết %sđ/đêm | Tổng %d đêm (%s -> %s): %sđ (Đặt cọc 50%%: %sđ) | Còn trống: %d phòng%s',
                $rt->type_name,
                $maxAdults,
                $maxChildren,
                $maxGuests,
                number_format($basePrice, 0, ',', '.'),
                $nights,
                $effectiveIn->format('d/m/Y'),
                $effectiveOut->format('d/m/Y'),
                number_format($totalPrice, 0, ',', '.'),
                number_format($deposit, 0, ',', '.'),
                $availCount,
                $fitsGuests ? ' [PHÙ HỢP SỐ KHÁCH]' : ' [VƯỢT SỨC CHỨA 1 PHÒNG]'
            );
            $matchingLines[] = $line;

            if ($fitsGuests && $availCount > 0) {
                $fallbackItems[] = sprintf(
                    '%s: %sđ/đêm, tổng %d đêm (%s - %s) là %sđ (cọc 50%%: %sđ, còn %d phòng trống)',
                    $rt->type_name,
                    number_format($basePrice, 0, ',', '.'),
                    $nights,
                    $effectiveIn->format('d/m'),
                    $effectiveOut->format('d/m'),
                    number_format($totalPrice, 0, ',', '.'),
                    number_format($deposit, 0, ',', '.'),
                    $availCount
                );
            }
        }

        $guestDesc = $totalGuests !== null
            ? ($adults !== null ? "{$adults} người lớn".($children ? ", {$children} trẻ em" : '') : "{$totalGuests} khách")
            : 'chưa giới hạn số khách';

        $after18Note = ($effectiveIn->isSameDay($today) && $now->hour >= 18)
            ? ' (Lưu ý: hiện đã qua 18:00 nên khung nhận phòng hôm nay đã đóng; khách đặt trực tuyến lúc này vui lòng chọn nhận phòng từ ngày mai)'
            : '';

        $realityBlock = sprintf(
            "BẢNG BÁO GIÁ & PHÒNG TRỐNG TÍNH SẴN THEO NGỮ CẢNH HỘI THOẠI (Kỳ nghỉ: %s đến %s - %d đêm | Số khách: %s)%s:\n%s\nChỉ dẫn bắt buộc: Dùng ngay số liệu trên để báo giá chi tiết cho khách ngay trong lượt trả lời này, tuyệt đối không hẹn kiểm tra sau.",
            $effectiveIn->format('d/m/Y'),
            $effectiveOut->format('d/m/Y'),
            $nights,
            $guestDesc,
            $after18Note,
            implode("\n", $matchingLines)
        );

        $fallbackReply = '';
        if ($fallbackItems !== []) {
            $fallbackReply = sprintf(
                'Báo giá trực tiếp tại Rosaliza Hotel cho lịch trình %d đêm (%s - %s, %s)%s: %s. Bạn có thể chọn phòng và đặt cọc 50%% trực tuyến tại mục Tìm phòng trống trên website nhé.',
                $nights,
                $effectiveIn->format('d/m/Y'),
                $effectiveOut->format('d/m/Y'),
                $guestDesc,
                $after18Note,
                implode('; ', $fallbackItems)
            );
        }

        return [
            'is_quote_query' => true,
            'reality_block' => $realityBlock,
            'fallback_reply' => $fallbackReply,
        ];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function parseNaturalDateRange(string $text, Carbon $today): array
    {
        $found = [];

        if (preg_match_all('/\b(20\d{2})-(\d{2})-(\d{2})\b/u', $text, $isoMatches, PREG_SET_ORDER)) {
            foreach ($isoMatches as $m) {
                try {
                    $found[] = Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}", 'Asia/Ho_Chi_Minh')->startOfDay();
                } catch (\Throwable) {
                }
            }
        }

        if (preg_match_all('/\b(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](20\d{2}))?\b/u', $text, $dmMatches, PREG_SET_ORDER)) {
            foreach ($dmMatches as $m) {
                $d = (int) $m[1];
                $mo = (int) $m[2];
                if ($d < 1 || $d > 31 || $mo < 1 || $mo > 12) {
                    continue;
                }
                $yr = ! empty($m[3]) ? (int) $m[3] : (int) $today->year;
                try {
                    $candidate = Carbon::create($yr, $mo, $d, 0, 0, 0, 'Asia/Ho_Chi_Minh')->startOfDay();
                    if (empty($m[3]) && $candidate->lt($today)) {
                        $candidate->addYear();
                    }
                    $found[] = $candidate;
                } catch (\Throwable) {
                }
            }
        }

        $checkIn = $found[0] ?? null;
        $checkOut = $found[1] ?? null;

        if (! $checkIn) {
            if (preg_match('/\b(hôm nay|hom nay|tối nay|toi nay|bây giờ|bay gio)\b/ui', $text)) {
                $checkIn = $today->copy();
            } elseif (preg_match('/\b(ngày mai|ngay mai|tối mai|toi mai|\bmai\b)\b/ui', $text)) {
                $checkIn = $today->copy()->addDay();
            } elseif (preg_match('/\b(ngày kia|ngay kia|ngày mốt|ngay mot)\b/ui', $text)) {
                $checkIn = $today->copy()->addDays(2);
            } elseif (preg_match('/\b(cuối tuần|cuoi tuan)\b/ui', $text)) {
                $checkIn = $today->copy()->next(Carbon::SATURDAY);
            }
        }

        if (! $checkOut) {
            // Match "trả 2 hôm nữa", "2 hôm nữa", "2 ngày nữa", "sau 2 ngày", "ở 2 đêm", "2 đêm", "2 ngày"
            if (preg_match('/\b(?:trả|tra|đến|den|sau|ở|o)?\s*(\d{1,2})\s*(?:đêm|dem|hôm nữa|hom nua|ngày nữa|ngay nua|hôm|hom|ngày|ngay)\b/ui', $text, $durMatch)) {
                $days = max(1, min(30, (int) $durMatch[1]));
                $checkIn = $checkIn ?: $today->copy();
                $checkOut = $checkIn->copy()->addDays($days);
            } elseif ($checkIn && preg_match('/\b(?:trả|tra|đến|den)\s*(ngày kia|ngay kia|ngày mốt|ngay mot)\b/ui', $text)) {
                $checkOut = $today->copy()->addDays(2);
            } elseif ($checkIn && preg_match('/\b(?:trả|tra|đến|den)\s*(ngày mai|ngay mai|\bmai\b)\b/ui', $text)) {
                $checkOut = $today->copy()->addDay();
            } elseif ($checkIn) {
                $checkOut = $checkIn->copy()->addDay();
            }
        }

        if ($checkIn && $checkOut && $checkOut->lte($checkIn)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        return [$checkIn, $checkOut];
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?int}
     */
    private function parseNaturalGuests(string $text): array
    {
        $adults = null;
        $children = null;
        $total = null;

        if (preg_match_all('/(\d{1,2})\s*(?:người lớn|nguoi lon)/ui', $text, $am)) {
            $adults = (int) end($am[1]);
        }
        if (preg_match_all('/(\d{1,2})\s*(?:trẻ em|tre em|bé|be|trẻ nhỏ|tre nho)/ui', $text, $cm)) {
            $children = (int) end($cm[1]);
        }

        if ($adults !== null || $children !== null) {
            $adults = max(1, $adults ?? 1);
            $children = max(0, $children ?? 0);
            $total = $adults + $children;

            return [$adults, $children, $total];
        }

        if (preg_match_all('/(\d{1,2})\s*(?:người|nguoi|khách|khach)/ui', $text, $gm)) {
            $total = max(1, (int) end($gm[1]));
            $adults = $total;
            $children = 0;
        }

        return [$adults, $children, $total];
    }

    /**
     * Ensure the AI never stops after saying "Đợi mình chút để kiểm tra / Tôi sẽ kiểm tra giá..."
     * without delivering the actual quote information to the customer.
     */
    private function ensureQuoteFollowThrough(string $reply, array $quoteContext): string
    {
        $trimmed = trim($reply);
        if ($trimmed === '' || empty($quoteContext['is_quote_query']) || empty($quoteContext['fallback_reply'])) {
            return $trimmed;
        }

        $hasPriceDigits = (bool) preg_match('/\b\d{2,3}[\.,]\d{3}\b/u', $trimmed);
        $isHoldingPromise = (bool) preg_match('/\b(đợi|chờ|sẽ kiểm tra|đang kiểm tra|kiểm tra giúp|check giúp|tra cứu giúp|báo lại sau|phản hồi sau)\b/iu', $trimmed);

        if ($isHoldingPromise && ! $hasPriceDigits) {
            return $trimmed."\n\n".$quoteContext['fallback_reply'];
        }

        return $trimmed;
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

    private function isTimeSensitiveQuery(string $message): bool
    {
        return (bool) preg_match('/hôm nay|hom nay|tối nay|toi nay|ngày mai|ngay mai|ngày kia|ngay kia|ngày mốt|ngay mot|cuối tuần|cuoi tuan|hôm nữa|hom nua|ngày nữa|ngay nua|\d+\s*(đêm|dem|ngày|ngay|hôm|hom)|bây giờ|hiện tại|mấy giờ|thứ mấy|thời tiết|thoi tiet|nhiệt độ|nhiet do|mưa|weather|forecast|temperature/ui', $message);
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
        $baseUrl = rtrim((string) config('services.langgraph.base_url'), '/');
        $participant = $this->sessionParticipantId($request);
        $threadKey = 'royal_langgraph_thread_'.substr(hash('sha256', $participant), 0, 32);
        $existingThreadId = (string) ($request->session()->get($threadKey) ?: $request->session()->get('chatbot.langgraph_thread_id', ''));

        if ($baseUrl !== '' && $existingThreadId !== '' && preg_match('/^[A-Za-z0-9_-]{1,120}$/', $existingThreadId)) {
            try {
                $this->httpClient(3)->delete("{$baseUrl}/threads/{$existingThreadId}");
            } catch (\Throwable) {
                // Ignore upstream cleanup errors during reset.
            }
        }

        $request->session()->forget([
            $threadKey,
            $threadKey.'_expires_at',
            'chatbot.langgraph_thread_id',
            'chatbot.participant_token',
        ]);
        $request->session()->put('chatbot.visit_nonce', bin2hex(random_bytes(12)));
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @param  array<string, mixed>  $pageContext
     */
    public function ask(Request $request, string $message, array $history = [], array $pageContext = []): ?array
    {
        $baseUrl = rtrim((string) config('services.langgraph.base_url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        $startedAt = microtime(true);
        $correlationId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $participant = $this->sessionParticipantId($request);
        $capability = $this->capabilities->issueForRequest($request, $participant, $correlationId);
        $quoteContext = $this->buildLiveQuoteContext($message, $history, $pageContext);
        $userReality = $this->buildUserRealitySummary($request, $pageContext, $message, $history);
        $fingerprint = hash('sha256', $this->buildFingerprint($message, $history, $capability['actor_scope']).'|'.($pageContext['path'] ?? '').'|'.($pageContext['room_name'] ?? ''));

        $this->outbox->recordChatbotEvent('requested', [
            'actor_type' => $capability['actor_type'],
            'actor_ref_hash' => $capability['actor_ref_hash'],
            'cache_status' => 'pending',
        ], $correlationId);

        $cacheKey = $this->publicFaqCacheKey($message, $history, $request, $pageContext);
        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);
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
                        'history' => $history,
                    ],
                    'config' => [
                        'configurable' => [
                            'retrieverProvider' => config('services.langgraph.vector_provider', 'mysql'),
                            'queryModel' => $this->resolvedQueryModel(),
                            'k' => 5,
                            'capabilityToken' => $capability['token'],
                            'actorScope' => $capability['actor_scope'],
                            'correlationId' => $correlationId,
                            'dedupeFingerprint' => $fingerprint,
                            'userReality' => $userReality,
                            'knowledgeVersion' => $this->versions->knowledgeVersion(),
                            'catalogVersion' => $this->versions->catalogVersion(),
                            'scopePolicy' => $this->scope->upstreamPolicy(),
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
            $reply = $this->ensureQuoteFollowThrough($reply, $quoteContext);

            $result = [
                'reply' => $reply,
                'sources' => is_array($data['sources'] ?? null) ? $data['sources'] : [],
            ];

            if ($cacheKey !== null) {
                Cache::put($cacheKey, $result, now()->addMinutes(10));
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
     * @param  array<string, mixed>  $pageContext
     */
    public function stream(
        Request $request,
        string $message,
        callable $onDelta,
        array &$sources = [],
        ?string $preResolvedThreadId = null,
        array $history = [],
        array $pageContext = [],
    ): bool {
        $baseUrl = rtrim((string) config('services.langgraph.base_url'), '/');
        if ($baseUrl === '') {
            return false;
        }

        $startedAt = microtime(true);
        $correlationId = (string) ($request->attributes->get('correlation_id') ?? Str::ulid());
        $participant = $this->sessionParticipantId($request);
        $capability = $this->capabilities->issueForRequest($request, $participant, $correlationId);
        $quoteContext = $this->buildLiveQuoteContext($message, $history, $pageContext);
        $userReality = $this->buildUserRealitySummary($request, $pageContext, $message, $history);
        $fingerprint = hash('sha256', $this->buildFingerprint($message, $history, $capability['actor_scope']).'|'.($pageContext['path'] ?? '').'|'.($pageContext['room_name'] ?? ''));

        $this->outbox->recordChatbotEvent('requested', [
            'actor_type' => $capability['actor_type'],
            'actor_ref_hash' => $capability['actor_ref_hash'],
            'cache_status' => 'pending',
        ], $correlationId);

        $cacheKey = $this->publicFaqCacheKey($message, $history, $request, $pageContext);
        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['reply']) && is_string($cached['reply']) && $cached['reply'] !== '') {
                $sources = is_array($cached['sources'] ?? null) ? $cached['sources'] : [];
                $words = preg_split('/(?<=\s)/u', $cached['reply'], -1, PREG_SPLIT_NO_EMPTY) ?: [$cached['reply']];
                foreach (array_chunk($words, 6) as $index => $phrase) {
                    if ($index > 0 && ! app()->runningUnitTests()) {
                        usleep(15000);
                    }
                    $onDelta(implode('', $phrase));
                }
                $this->outbox->recordChatbotEvent('cache_hit', [
                    'actor_type' => $capability['actor_type'],
                    'actor_ref_hash' => $capability['actor_ref_hash'],
                    'cache_status' => 'hit',
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], $correlationId);

                return true;
            }
        }

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
                        'history' => $history,
                    ],
                    'streamMode' => ['messages', 'updates'],
                    'config' => [
                        'configurable' => [
                            'retrieverProvider' => config('services.langgraph.vector_provider', 'mysql'),
                            'queryModel' => $this->resolvedQueryModel(),
                            'k' => 5,
                            'capabilityToken' => $capability['token'],
                            'actorScope' => $capability['actor_scope'],
                            'correlationId' => $correlationId,
                            'dedupeFingerprint' => $fingerprint,
                            'userReality' => $userReality,
                            'knowledgeVersion' => $this->versions->knowledgeVersion(),
                            'catalogVersion' => $this->versions->catalogVersion(),
                            'scopePolicy' => $this->scope->upstreamPolicy(),
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
                $reply = $this->ensureQuoteFollowThrough($reply, $quoteContext);
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

                $line = \GuzzleHttp\Psr7\Utils::readLine($body);
                if ($line === '' && $body->eof()) {
                    break;
                }

                $buffer .= str_replace("\r\n", "\n", $line);
                if (! str_contains($buffer, "\n\n")) {
                    continue;
                }

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
                $followedUp = $this->ensureQuoteFollowThrough($accumulated, $quoteContext);
                if ($followedUp !== trim($accumulated) && ! empty($quoteContext['fallback_reply'])) {
                    $appendix = "\n\n".$quoteContext['fallback_reply'];
                    $accumulated .= $appendix;
                    $onDelta($appendix);
                }

                if ($cacheKey !== null && $accumulated !== '') {
                    Cache::put($cacheKey, [
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

    private function resolvedQueryModel(): string
    {
        $explicit = trim((string) config('services.langgraph.query_model', ''));
        if ($explicit !== '') {
            return str_contains($explicit, '/') ? $explicit : 'google-genai/'.$explicit;
        }

        $chatModel = trim((string) config('services.gemini.chat_model', 'gemini-3.1-flash-lite'));

        return str_contains($chatModel, '/') ? $chatModel : 'google-genai/'.($chatModel ?: 'gemini-3.1-flash-lite');
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
