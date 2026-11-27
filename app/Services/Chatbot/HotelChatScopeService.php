<?php

namespace App\Services\Chatbot;

class HotelChatScopeService
{
    public const MAX_OUT_OF_SCOPE_QUESTIONS = 2;

    private const REFUSAL_VI = 'Pico chỉ hỗ trợ các câu hỏi về Rosaliza Hotel và nghiệp vụ lưu trú, như phòng, đặt phòng, giá, thanh toán, nhận/trả phòng, chính sách và tài khoản.';

    private const REFUSAL_EN = 'Pico only assists with Rosaliza Hotel bookings, room rates, check-in/check-out, payment, cancellation/refund policies, and guest account support.';

    private const LIMIT_REFUSAL_VI = 'Pico đã hỗ trợ tối đa 2 câu hỏi ngoài phạm vi khách sạn. Mình xin phép chỉ tiếp tục giải đáp các thông tin về phòng nghỉ, giá và chính sách lưu trú tại Rosaliza Hotel nhé.';

    private const LIMIT_REFUSAL_EN = 'Pico can only answer up to 2 off-topic questions per conversation. Please feel free to ask me about room availability, rates, bookings, or stay policies at Rosaliza Hotel.';

    private const PROHIBITED_REFUSAL_VI = 'Xin lỗi, Pico không hỗ trợ các nội dung liên quan đến chính trị, chiến tranh, lịch sử, quân sự, vũ khí, y khoa, nội dung người lớn hoặc thông tin chưa được cơ quan Nhà nước Việt Nam kiểm duyệt. Mình sẵn sàng hỗ trợ bạn về đặt phòng và chính sách tại Rosaliza Hotel.';

    private const PROHIBITED_REFUSAL_EN = 'Sorry, Pico does not answer questions regarding politics, war, history, military/weapons, medical advice, adult content, or unverified state information. I am happy to assist you with room bookings and stay policies at Rosaliza Hotel.';

    private const SQLI_REFUSAL_VI = 'Yêu cầu chứa cú pháp truy vấn không hợp lệ nên đã bị từ chối vì lý do an toàn bảo mật. Vui lòng nhập câu hỏi thông thường về dịch vụ lưu trú tại Rosaliza Hotel.';

    private const SQLI_REFUSAL_EN = 'Your message contains restricted query syntax and has been blocked for security reasons. Please ask a normal question about room bookings or policies at Rosaliza Hotel.';

    /**
     * Evaluate a user message against SQL injection rules, zero-tolerance sensitive topics,
     * bilingual hotel scope (VI/EN), and the maximum 2 benign out-of-scope question allowance.
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @return array{
     *   allowed: bool,
     *   category: string,
     *   language: string,
     *   refusal: string,
     *   increment_out_of_scope: bool,
     *   out_of_scope_used: int
     * }
     */
    public function evaluate(string $message, array $history = [], int $sessionOutOfScopeCount = 0): array
    {
        $raw = trim($message);
        $lang = $this->detectLanguage($raw);
        $normalized = $this->normalize($raw);

        if ($normalized === '') {
            return [
                'allowed' => false,
                'category' => 'empty',
                'language' => $lang,
                'refusal' => $this->refusal($lang),
                'increment_out_of_scope' => false,
                'out_of_scope_used' => $sessionOutOfScopeCount,
            ];
        }

        // 1. Strict SQL Injection & Command/Prompt Injection Guard (0 tolerance)
        if ($this->hasSqlInjectionSignal($raw)) {
            return [
                'allowed' => false,
                'category' => 'sql_injection',
                'language' => $lang,
                'refusal' => $lang === 'en' ? self::SQLI_REFUSAL_EN : self::SQLI_REFUSAL_VI,
                'increment_out_of_scope' => false,
                'out_of_scope_used' => $sessionOutOfScopeCount,
            ];
        }

        // 2. Strict Sensitive / Prohibited Topics Guard (0 tolerance - never answered)
        if ($this->hasProhibitedTopicSignal($normalized, $raw)) {
            return [
                'allowed' => false,
                'category' => 'prohibited_topic',
                'language' => $lang,
                'refusal' => $lang === 'en' ? self::PROHIBITED_REFUSAL_EN : self::PROHIBITED_REFUSAL_VI,
                'increment_out_of_scope' => false,
                'out_of_scope_used' => $sessionOutOfScopeCount,
            ];
        }

        // 3. In-Scope Hotel Policy, Booking, Pricing, Greeting, or Hotel Contextual Follow-Up (Unlimited)
        if ($this->isHotelInScope($normalized, $history)) {
            return [
                'allowed' => true,
                'category' => 'in_scope',
                'language' => $lang,
                'refusal' => '',
                'increment_out_of_scope' => false,
                'out_of_scope_used' => $sessionOutOfScopeCount,
            ];
        }

        // 4. Benign Out-of-Scope Question: allow at most 2 questions per conversation/session
        $historyOosCount = $this->countOutOfScopeInHistory($history);
        $effectiveOosUsed = max($sessionOutOfScopeCount, $historyOosCount);

        if ($effectiveOosUsed >= self::MAX_OUT_OF_SCOPE_QUESTIONS) {
            return [
                'allowed' => false,
                'category' => 'out_of_scope_limit',
                'language' => $lang,
                'refusal' => $lang === 'en' ? self::LIMIT_REFUSAL_EN : self::LIMIT_REFUSAL_VI,
                'increment_out_of_scope' => false,
                'out_of_scope_used' => $effectiveOosUsed,
            ];
        }

        return [
            'allowed' => true,
            'category' => 'benign_out_of_scope',
            'language' => $lang,
            'refusal' => '',
            'increment_out_of_scope' => true,
            'out_of_scope_used' => $effectiveOosUsed + 1,
        ];
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function allows(string $message, array $history = [], int $sessionOutOfScopeCount = 0): bool
    {
        return $this->evaluate($message, $history, $sessionOutOfScopeCount)['allowed'];
    }

    public function refusal(string $language = 'vi', string $category = 'default'): string
    {
        if ($category === 'sql_injection') {
            return $language === 'en' ? self::SQLI_REFUSAL_EN : self::SQLI_REFUSAL_VI;
        }
        if ($category === 'prohibited_topic') {
            return $language === 'en' ? self::PROHIBITED_REFUSAL_EN : self::PROHIBITED_REFUSAL_VI;
        }
        if ($category === 'out_of_scope_limit') {
            return $language === 'en' ? self::LIMIT_REFUSAL_EN : self::LIMIT_REFUSAL_VI;
        }

        return $language === 'en' ? self::REFUSAL_EN : self::REFUSAL_VI;
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function isHotelInScope(string $messageOrNormalized, array $history = []): bool
    {
        $normalized = $this->normalize($messageOrNormalized);
        if ($normalized === '' || $this->hasSqlInjectionSignal($messageOrNormalized) || $this->hasProhibitedTopicSignal($normalized, $messageOrNormalized)) {
            return false;
        }

        if ($this->hasHotelSignal($normalized) || $this->hasStayQuoteSignal($normalized) || $this->isGreeting($normalized)) {
            return true;
        }

        return $this->isLikelyFollowUp($normalized) && $this->historyHasHotelContext($history);
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function isBenignOutOfScope(string $message, array $history = []): bool
    {
        $normalized = $this->normalize($message);
        if ($normalized === '' || $this->hasSqlInjectionSignal($message) || $this->hasProhibitedTopicSignal($normalized, $message)) {
            return false;
        }

        return ! $this->isHotelInScope($normalized, $history);
    }

    /**
     * Count how many prior user turns in $history were benign out-of-scope questions.
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function countOutOfScopeInHistory(array $history): int
    {
        $count = 0;
        $priorContext = [];

        foreach ($history as $item) {
            $role = (string) ($item['role'] ?? '');
            $content = trim((string) ($item['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            if ($role === 'user') {
                $norm = $this->normalize($content);
                if (
                    $norm !== ''
                    && ! $this->hasSqlInjectionSignal($content)
                    && ! $this->hasProhibitedTopicSignal($norm, $content)
                    && ! $this->isHotelInScope($norm, $priorContext)
                ) {
                    $count++;
                }
            }

            $priorContext[] = $item;
        }

        return $count;
    }

    public function detectLanguage(string $message): string
    {
        $trimmed = trim($message);
        if ($trimmed === '') {
            return 'vi';
        }

        // Any Vietnamese diacritics -> Vietnamese
        if (preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $trimmed)) {
            return 'vi';
        }

        // Common unaccented Vietnamese words -> Vietnamese
        if (preg_match('/\b(khach san|dat phong|gia phong|nhan phong|tra phong|hoan tien|huy phong|bao nhieu|hom nay|ngay mai|nguoi lon|tre em|xin chao|cam on|cho toi|giup toi|the nao)\b/iu', $trimmed)) {
            return 'vi';
        }

        // Common English words -> English
        if (preg_match('/\b(what|when|where|how|which|who|why|can|could|would|should|do|does|is|are|please|room|rooms|book|booking|price|rate|rates|cost|available|availability|check[ -]?in|check[ -]?out|cancel|refund|policy|policies|adult|adults|children|guest|guests|night|nights|tomorrow|today|tonight|hello|hi|thank|thanks|help|tell|about|politics|war|history|medical|weapon|military)\b/iu', $trimmed)) {
            return 'en';
        }

        return 'vi';
    }

    public function upstreamPolicy(): string
    {
        return 'Chỉ trả lời chuyên sâu về Rosaliza Hotel và nghiệp vụ lưu trú (hạng phòng, phòng trống, đặt phòng, giá, thanh toán, nhận/trả phòng, gia hạn, hủy, hoàn tiền, tiện nghi, chính sách, tài khoản) bằng đúng ngôn ngữ khách đang sử dụng (hỗ trợ đầy đủ Tiếng Việt và Tiếng Anh). '
            .'TUYỆT ĐỐI KHÔNG TRẢ LỜI bất kỳ câu hỏi nào liên quan đến: chính trị, đảng phái, bầu cử, biểu tình, chiến tranh, lịch sử, quân sự, vũ khí vũ trang, y khoa/chẩn đoán/thuốc men, nội dung người lớn (18+/NSFW), tấn công bảo mật/SQL injection, và TUYỆT ĐỐI KHÔNG BỊA ĐẶT hay phát ngôn các thông tin chưa được kiểm duyệt bởi cơ quan Nhà nước Việt Nam. '
            .'Nếu câu hỏi thuộc diện trò chuyện ngoài lề không nhạy cảm (trong giới hạn tối đa 2 câu ngoài lề được phép), chỉ trả lời ngắn gọn tối đa 2 câu rồi khéo léo hướng khách về dịch vụ lưu trú của Rosaliza Hotel. '
            .'TUYỆT ĐỐI KHÔNG BAO GIỜ trả lời kiểu hứa hẹn chờ đợi như "Đợi mình chút để kiểm tra" / "Let me check and get back to you" mà chưa đưa ra số liệu; mọi bảng giá, tổng chi phí theo số đêm, tiền cọc 50% và số phòng trống đã có sẵn trong <user_reality> và <live_hotel_tools>, bắt buộc phải báo giá đầy đủ ngay trong cùng một lượt phản hồi.';
    }

    public function hasSqlInjectionSignal(string $rawMessage): bool
    {
        $decoded = html_entity_decode(rawurldecode($rawMessage), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $candidates = [$rawMessage, $decoded];

        $patterns = [
            '/\bunion\s+(?:all\s+)?select\b/iu',
            '/\b(?:select|delete)\b[\s\S]{1,120}\bfrom\b[\s\S]{1,60}\b(?:users|bookings|rooms|room_types|payment_logs|refund_transactions|sessions|cache|knowledge_chunks|information_schema|sqlite_master|mysql|pg_catalog)\b/iu',
            '/\b(?:drop|truncate|alter)\s+(?:table|database|schema|view|index)\b/iu',
            '/\binsert\s+into\s+[a-z0-9_`"]+/iu',
            '/\bupdate\s+[a-z0-9_`"]+\s+set\b/iu',
            '/(?:[\'"`]\s*|\b)(?:or|and)\s+[\'"`]?(?:\d+|true|[a-z0-9_]+)[\'"`]?\s*=\s*[\'"`]?(?:\d+|true|[a-z0-9_]+)/iu',
            '/;\s*(?:select|insert|update|delete|drop|alter|truncate|create|exec|execute|declare|grant|revoke)\b/iu',
            '/\b(?:information_schema|sqlite_master|xp_cmdshell|load_file\s*\(|into\s+(?:outfile|dumpfile)|benchmark\s*\(|sleep\s*\(\s*\d+|pg_sleep\s*\(|waitfor\s+delay)\b/iu',
            '/[\'"`]\s*(?:--|#|\/\*)/u',
            '/\b0x[0-9a-f]{8,}\b/iu',
        ];

        foreach ($candidates as $text) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasProhibitedTopicSignal(string $normalized, string $rawMessage = ''): bool
    {
        // Strip legitimate hotel booking/order/payment history phrases BEFORE checking historical topics
        $sanitized = preg_replace(
            '/\b(?:lich su|lịch sử)\s+(?:dat phong|đặt phòng|dat|đặt|booking|don|đơn|giao dich|giao dịch|thanh toan|thanh toán|luu tru|lưu trú|hoan tien|hoàn tiền|huy|hủy)\b/iu',
            ' hotel_booking_records ',
            $normalized
        ) ?? $normalized;

        $sanitized = preg_replace(
            '/\b(?:booking|reservation|order|payment|transaction|stay|account|refund)\s+history\b/iu',
            ' hotel_booking_records ',
            $sanitized
        ) ?? $sanitized;

        // 1. Politics & Unverified State / Sovereignty / Regime topics (VI + EN)
        if (preg_match('/\b(chinh tri|chính trị|dang cong san|đảng cộng sản|da dang|đa đảng|chinh phu|chính phủ|quoc hoi|quốc hội|bau cu|bầu cử|bieu tinh|biểu tình|bao dong|bạo động|che do|chế độ|phan dong|phản động|chong pha|chống phá|lat do|lật đổ|tuyen truyen|tuyên truyền|kiem duyet|kiểm duyệt|nhan quyen|nhân quyền|tu nhan luong tam|tù nhân lương tâm|chu quyen|chủ quyền|tranh chap bien dong|tranh chấp biển đông|hoang sa|hoàng sa|truong sa|trường sa|lanh tu|lãnh tụ|tong thong|tổng thống|thu tuong|thủ tướng|chu tich nuoc|chủ tịch nước|bí thư|bi thu|quoc gia nhay cam|bí mật nhà nước|bi mat nha nuoc|chưa kiểm duyệt|chua kiem duyet|politics|political|politician|government|parliament|election|elections|protest|protests|riot|regime|communism|communist|socialism|capitalism|democracy|dictatorship|censorship|propaganda|human rights|sovereignty|disputed island|south china sea|state secret|unverified state)\b/ui', $sanitized)) {
            return true;
        }

        // 2. War, Military & Armed Weapons (VI + EN)
        if (preg_match('/\b(chien tranh|chiến tranh|quan su|quân sự|quan doi|quân đội|vu khi|vũ khí|vu trang|vũ trang|sung dan|súng đạn|khau sung|khẩu súng|sung truong|súng trường|sung ngan|súng ngắn|bom dan|bom đạn|qua bom|quả bom|thuoc no|thuốc nổ|luu dan|lựu đạn|ten lua|tên lửa|hat nhan|hạt nhân|xe tang|xe tăng|tiem kich|tiêm kích|tau chien|tàu chiến|khung bo|khủng bố|am sat|ám sát|giet nguoi|giết người|noi chien|nội chiến|xam luoc|xâm lược|war|wars|warfare|military|army|troops|soldier|soldiers|weapon|weapons|firearm|firearms|armed|gun|guns|rifle|pistol|ammunition|ammo|bullet|bullets|bomb|bombs|explosive|explosives|grenade|missile|missiles|nuclear|artillery|warship|fighter jet|terrorism|terrorist|assassination|battlefield|invasion)\b/ui', $sanitized)) {
            return true;
        }

        // 3. History (VI + EN - after stripping legitimate booking/payment history)
        if (preg_match('/\b(lich su|lịch sử|trieu dai|triều đại|phong kien|phong kiến|the chien|thế chiến|chien dich|chiến dịch|tran danh|trận đánh|khoi nghia|khởi nghĩa|cach mang|cách mạng|co dai|cổ đại|trung dai|trung đại|thuc dan|thực dân|de quoc|đế quốc|history|historical|historian|dynasty|dynasties|world war|ancient|medieval|colonial|colonialism|empire|feudal|revolution)\b/ui', $sanitized)) {
            return true;
        }

        // 4. Medical / Healthcare / Drugs (VI + EN)
        if (preg_match('/\b(y khoa|y te|y tế|chan doan|chẩn đoán|ke don|kê đơn|don thuoc|đơn thuốc|uong thuoc|uống thuốc|lieu luong|liều lượng|trieu chung|triệu chứng|dieu tri|điều trị|chua benh|chữa bệnh|benh tat|bệnh tật|phau thuat|phẫu thuật|ung thu|ung thư|tieu duong|tiểu đường|huyet ap|huyết áp|khang sinh|kháng sinh|vac xin|vắc xin|vaccine|di ung thuoc|dị ứng thuốc|dau bung|đau bụng|dau dau|đau đầu|sot cao|sốt cao|viem phoi|viêm phổi|nhiem trung|nhiễm trùng|bac si kham|bác sĩ khám|phòng khám|phong kham|bệnh viện chữa|medical|medicine|medication|clinical|diagnosis|diagnose|symptom|symptoms|prescription|dosage|treatment|cure|disease|illness|surgery|cancer|diabetes|hypertension|antibiotic|antibiotics|infection|healthcare|doctor advice|medical advice|pills)\b/ui', $sanitized)) {
            return true;
        }

        // 5. Adult / 18+ / NSFW Content (VI + EN - carefully preserving "2 người lớn" / "2 adults" for hotel guests!)
        if (preg_match('/\b(noi dung nguoi lon|nội dung người lớn|phim nguoi lon|phim người lớn|web nguoi lon|web người lớn|truyen nguoi lon|truyện người lớn|anh nong|ảnh nóng|phim nong|phim nóng|khieu dam|khiêu dâm|kich duc|kích dục|dam o|dâm ô|mai dam|mại dâm|gai goi|gái gọi|sugar baby|sugar daddy|onlyfans|quan he tinh duc|quan hệ tình dục|tinh duc|tình dục|lam tinh|làm tình|18\+|sex|sexual|porn|porno|pornography|nsfw|xxx|hentai|erotic|nude|nudes|nudity|prostitute|prostitution|escort service|adult content|adult video|adult film)\b/ui', $sanitized)) {
            return true;
        }

        return false;
    }

    private function hasHotelSignal(string $message): bool
    {
        if (preg_match('/\b(phòng khám|phong kham|văn phòng|van phong|phòng ban|phong ban|phòng thí nghiệm|giá vàng|gia vang|giá xăng|gia xang|giá cổ phiếu|gia co phieu|giá bitcoin|gold price|stock price|bitcoin price|oil price)\b/ui', $message)) {
            return false;
        }

        return (bool) preg_match('/\b(rosaliza|hotel|hotels|khach san|khách sạn|phong|phòng|hang phong|hạng phòng|loai phong|loại phòng|room|rooms|suite|suites|deluxe|standard|family|twin|double|single|booking|bookings|book|reserve|reservation|reservations|dat phong|đặt phòng|thue phong|thuê phòng|giu phong|giữ phòng|nhan phong|nhận phòng|check[ -]?in|tra phong|trả phòng|check[ -]?out|gia han|gia hạn|extend|extension|luu tru|lưu trú|stay|overnight|ky nghi|kỳ nghỉ|qua dem|qua đêm|gia phong|giá phòng|xem gia|xem giá|check gia|check giá|bao gia|báo giá|tinh gia|tính giá|tinh tien|tính tiền|gia|giá|price|prices|rate|rates|cost|quote|tariff|thanh toan|thanh toán|payment|pay|dat coc|đặt cọc|tien coc|tiền cọc|deposit|vietqr|momo|vnpay|zalopay|hoa don|hóa đơn|invoice|receipt|hoan tien|hoàn tiền|refund|refunds|huy phong|hủy phòng|huy don|hủy đơn|cancel|cancellation|phu thu|phụ thu|late fee|surcharge|chinh sach|chính sách|policy|policies|rule|rules|tien nghi|tiện nghi|amenity|amenities|dich vu|dịch vụ|service|services|don phong|dọn phòng|housekeeping|cleaning|bua sang|bữa sáng|an sang|ăn sáng|breakfast|wifi|bai xe|bãi xe|parking|ho boi|hồ bơi|pool|le tan|lễ tân|reception|front desk|concierge|face[ -]?id|voucher|khuyen mai|khuyến mãi|promotion|discount|ma giam|mã giảm|tai khoan|tài khoản|account|dang nhap|đăng nhập|login|sign in|dang ky|đăng ký|register|sign up|mat khau|mật khẩu|password|otp|qr|khach|khách|guest|guests|nguoi lon|người lớn|adult|adults|tre em|trẻ em|child|children|kids|giuong|giường|bed|beds|tang|tầng|floor|lien he|liên hệ|contact|dia chi|địa chỉ|address|location|hotline|so dien thoai|số điện thoại|phone|email|review|reviews|rating|danh gia|đánh giá|available|availability|vacancy|vacant)\b/ui', $message);
    }

    private function hasStayQuoteSignal(string $message): bool
    {
        $hasExplicitDates = (bool) preg_match('/\b\d{1,2}[\/-]\d{1,2}(?:[\/-]\d{2,4})?\b|\b20\d{2}-\d{2}-\d{2}\b/u', $message);
        $hasRelativeDatesOrDuration = (bool) preg_match('/\b(hom nay|hôm nay|toi nay|tối nay|ngay mai|ngày mai|toi mai|tối mai|ngay kia|ngày kia|ngay mot|ngày mốt|cuoi tuan|cuối tuần|thu\s*[2-7]|thứ\s*[2-7]|chu nhat|chủ nhật|today|tonight|tomorrow|weekend|this weekend|monday|tuesday|wednesday|thursday|friday|saturday|sunday|\d+\s*(dem|đêm|ngay|ngày|hom|hôm|night|nights|day|days)(?:\s*(nua|nữa|later))?)\b/ui', $message);
        $hasGuestCount = (bool) preg_match('/\b\d+\s*(nguoi|người|nguoi lon|người lớn|tre em|trẻ em|khach|khách|be|bé|adult|adults|child|children|kid|kids|guest|guests|people|person|pax)\b/ui', $message);
        $hasStayOrPriceIntent = (bool) preg_match('/\b(o|ở|nhan|nhận|tra|trả|thue|thuê|dat|đặt|check|xem|tinh|tính|bao tien|bao tiền|bao nhieu|bao nhiêu|het bao|hết bao|gia|giá|chi phi|chi phí|con trong|còn trống|con phong|còn phòng|loai nao|loại nào|hang nao|hạng nào|how much|price|rate|cost|stay|book|available|availability)\b/ui', $message);

        if (($hasExplicitDates || $hasRelativeDatesOrDuration) && ($hasStayOrPriceIntent || $hasGuestCount)) {
            return true;
        }

        if ($hasGuestCount && $hasStayOrPriceIntent) {
            return true;
        }

        return false;
    }

    private function isGreeting(string $message): bool
    {
        return (bool) preg_match('/^(xin chao|xin chào|chao|chào|chao ban|chào bạn|chao pico|chào pico|hello|hi|hey|good morning|good afternoon|good evening|alo|cam on|cảm ơn|cám ơn|thanks|thank you|ho tro|hỗ trợ|help|ok|oke|okay|vang|vâng|da|dạ|yes|sure)[!?.\s]*$/ui', $message);
    }

    private function isLikelyFollowUp(string $message): bool
    {
        if (mb_strlen($message, 'UTF-8') > 140) {
            return false;
        }

        return (bool) preg_match('/^(con|còn|the|thế|vay|vậy|neu|nếu|bao nhieu|bao nhiêu|bao tien|bao tiền|het bao|hết bao|co|có|khong|không|duoc|được|sao|roi|rồi|va|và|luc nao|lúc nào|ngay nao|ngày nào|hom nay|hôm nay|ngay mai|ngày mai|mai|toi nay|tối nay|cuoi tuan|cuối tuần|tu|từ|den|đến|o|ở|nhan|nhận|tra|trả|loai|loại|hang|hạng|re nhat|rẻ nhất|dat nhat|đắt nhất|tinh|tính|tong|tổng|ok|oke|chot|chốt|dung|đúng|them|thêm|how about|what about|and|if|how much|for|from|to|today|tonight|tomorrow|weekend|cheapest|best|total|\d+\b)/ui', $message);
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    private function historyHasHotelContext(array $history): bool
    {
        foreach (array_reverse($history) as $item) {
            $norm = $this->normalize((string) ($item['content'] ?? ''));
            if ($norm === '') {
                continue;
            }
            if ($this->hasHotelSignal($norm) || $this->hasStayQuoteSignal($norm)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $message): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $message) ?? ''), 'UTF-8');
    }
}
