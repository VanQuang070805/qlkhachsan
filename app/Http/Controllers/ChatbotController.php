<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function api(Request $request)
    {
        $userMessage = mb_strtolower(trim($request->input('message') ?? ''), 'UTF-8');

        if (empty($userMessage)) {
            return response()->json(['reply' => 'Bạn vui lòng nhập nội dung cần tư vấn nhé.']);
        }

        $replyText = $this->getRuleBasedReply($userMessage);
        return response()->json(['reply' => $replyText]);
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
                    return "Dựa trên số lượng khách là {$guests} người (> 4 người), bạn nên cân nhắc <b>đặt nhiều phòng</b> hoặc <b>liên hệ lễ tân</b> qua Hotline 0123 456 789 để được hỗ trợ sắp xếp tốt nhất.";
                }
            }

            if ($this->containsAny($msg, ['đại gia đình', 'dai gia dinh', 'đông người', 'dong nguoi'])) {
                return "Đối với đoàn khách đông hoặc đại gia đình (> 4 người), chúng tôi gợi ý bạn nên <b>đặt nhiều phòng</b> hoặc <b>liên hệ trực tiếp lễ tân</b> qua Hotline 0123 456 789 để được tư vấn và hỗ trợ tốt nhất.";
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
                return "Hiện tại giá phòng của chúng tôi dao động từ 400.000 VNĐ đến 2.000.000 VNĐ/đêm tùy thuộc vào loại phòng (Standard, Deluxe, Triple, Gia Đình, VIP). Bạn vui lòng liên hệ lễ tân hoặc vào mục 'Tìm phòng trống' để xem bảng giá cập nhật nhất nhé.";
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
                   "- Đối với các đơn phòng đã xác nhận hoặc đã thanh toán đặt cọc, bạn vui lòng liên hệ Hotline <b>0123 456 789</b> tối thiểu 24 giờ trước giờ nhận phòng để được hỗ trợ thủ tục hoàn hủy.";
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
                   "- <b>Thời gian nhận phòng (Check-in):</b> Từ 14:00 chiều.<br>" .
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
        return "Xin lỗi, tôi chưa hiểu rõ ý bạn. Bạn có thể hỏi về giá phòng, đặt phòng, phòng trống, thanh toán, hủy phòng, check-in hoặc check-out.";
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
