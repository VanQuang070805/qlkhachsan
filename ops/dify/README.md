# Royal Concierge — triển khai Dify

## Kiến trúc

```text
Widget RoyalChat hiện có
  → POST /chatbot/stream (CSRF + session)
  → Laravel ChatbotController
  → DifyChatbotService (API key + conversation ID chỉ ở server)
  → Dify Chatflow: Gemini + Knowledge Retrieval + public room tools
  → stream câu trả lời về widget
```

Đây là luồng chatbot duy nhất trên website. Laravel không gọi OpenAI/canned replies khi Dify lỗi; thay vào đó trả lỗi để widget báo kết nối gián đoạn. Widget chỉ gửi câu hỏi hiện tại, không gửi lịch sử trình duyệt. Không có thay đổi schema hay dữ liệu database. Frontend giữ nguyên thiết kế; câu trả lời không hiển thị tên/tài liệu nguồn.

Gemini/Dify nhận một mã user HMAC giả danh và ID hội thoại trong session server. Không gửi cookie, ID khách/nhân viên thật, lịch sử booking hoặc dữ liệu nội bộ. Chatflow Memory giữ ngữ cảnh nhiều lượt; session PHP giữ conversation ID ở server.

## Biên quyền truy cập

Dify không có kết nối database Royal Hotel. Khi cần dữ liệu thay đổi theo thời gian, Chatflow chỉ gọi hai Laravel API read-only đã định nghĩa trong [`royal-hotel-tools.openapi.yaml`](royal-hotel-tools.openapi.yaml):

- `POST /api/chatbot/tools/room-types`: tên hạng, giá công khai hiện hành, sức chứa.
- `POST /api/chatbot/tools/rooms/search`: ngày ở, tổng khách, số phòng cần; trả số lượng còn theo hạng và cờ đáp ứng số phòng.

Availability tái sử dụng `Booking::reservedRoomIds()` để tính các đơn đang giữ phòng theo khoảng ngày. Tool không trả room number/ID, customer/booking data, employee data, admin data hoặc thông tin xác thực. Chatbot không tra cứu booking cá nhân, không tạo/sửa/hủy booking và không thanh toán.

## Cấu hình Laravel cục bộ

Giữ các giá trị sau trong `.env` (không commit):

```env
DIFY_BASE_URL=http://127.0.0.1:8089/v1
DIFY_API_KEY=app-<key-chatflow>
DIFY_TIMEOUT=35
DIFY_TOOL_API_KEY=<bearer-secret-rieng>
DIFY_LIVE_TOOLS_ENABLED=true
```

`DIFY_TOOL_API_KEY` phải là secret riêng, khác Gemini API key và Dify app key. Dùng cùng giá trị trong cấu hình Authentication của Dify Custom Tool. Không ghi key trong prompt, docs, screenshots hoặc Git. Tắt tools bằng `DIFY_LIVE_TOOLS_ENABLED=false` khi cần rollback. Sau khi sửa `.env`, chạy `php artisan config:clear`.

Với Docker Dify và website Laravel, OpenAPI đi qua bridge hẹp `http://host.docker.internal:8001/api`. Bridge (`tool-router.php`) chỉ forward đúng hai route read-only tới Apache nội bộ ở `127.0.0.1:80`, kiểm tra bearer header và giới hạn peer vào loopback/Docker Desktop host network. Dùng Apache thay vì PHP `artisan serve` một luồng để request chat đang chờ Dify không chặn lượt Dify gọi ngược lại room tool. Để mở cổng có giới hạn, chạy `ops/dify/allow-tool-router-firewall.ps1` trong PowerShell Administrator; rule chỉ cho TCP 8001 từ `192.168.65.0/24` tới `php.exe`. Sau đó khởi động bridge thường (không chạy dưới quyền Administrator):

```powershell
php -S 0.0.0.0:8001 ops/dify/tool-router.php
```

Port Dify hiện chỉ bind loopback `127.0.0.1:8089`, không expose Internet. Trong `D:\Docker\royal-dify\docker\.env`, chỉ allowlist `host.docker.internal` cho `SSRF_PROXY_ALLOW_PRIVATE_DOMAINS`; sau khi sửa, recreate riêng cả `ssrf_proxy` và `agent_ssrf_proxy` của project `royal-dify` để Agent tool dùng đúng allowlist. Khi chuyển môi trường, thay URL bằng host nội bộ/HTTPS hợp lệ; không giữ `host.docker.internal` trong production.

## Cấu hình Chatflow

1. Dùng app Chatflow Royal Concierge, cấu hình provider Gemini API key ở Dify; kiểm tra thử model một lần. Bản local hiện dùng `Gemini 3.5 Flash-Lite` trên Free Tier. Không cấu hình fallback sang model/dịch vụ trả phí. Nếu quota miễn phí hết, chatbot báo gián đoạn cho đến khi quota được làm mới hoặc chủ dự án chọn phương án khác.
2. Tạo Knowledge Base chỉ chứa chính sách/FAQ/hạng phòng công khai đã xác minh. Upload các tài liệu trong `resources/knowledge/`; Dify không tự đồng bộ thư mục này.
3. Thêm node Knowledge Retrieval, chọn dataset Royal Hotel và nối `result` vào Context của node Agent. Luồng hiện dùng `User Input → Knowledge Retrieval → Agent → Answer`, không còn LLM node cũ.
4. Cài plugin Agent Strategies chính thức trong Dify, chọn `FunctionCalling` và Gemini đã cấu hình. Thêm hai API tools theo OpenAPI schema trên, cấu hình Bearer Authentication với `DIFY_TOOL_API_KEY`, bật cả hai trong Tool list của Agent. Nối `Agent.text` vào Answer.
5. System prompt phải cho phép trò chuyện đời thường tự nhiên; dùng KB cho facts ổn định; bắt buộc gọi tool cho giá/availability hiện tại; hỏi khách ngày/số lượng còn thiếu; chỉ hứa đủ số phòng nếu kết quả `enough_for_request=true`; không hiện citations/“Nguồn”; không bịa hoặc tuyên bố đã đặt phòng.
6. Memory bật để xử lý sửa/tiếp nối ngày, số người và số lượng phòng trong hội thoại.
7. Chạy Preview tests; sau khi đạt, Publish. Laravel gọi phiên bản đã xuất bản, không phải bản nháp autosave. Agent có thể cần nhiều lượt gọi model/tool; đặt `DIFY_TIMEOUT` đủ dài (hiện 90 giây).

## Tài liệu tri thức

- [`resources/knowledge/royal-hotel.md`](../../resources/knowledge/royal-hotel.md): giờ nhận/trả, quy trình đặt, giữ phòng, thanh toán, hủy/hoàn tiền.
- [`resources/knowledge/royal-hotel-room-types.md`](../../resources/knowledge/royal-hotel-room-types.md): hạng phòng và sức chứa được xác minh.
- [`resources/knowledge/royal-hotel-concierge.md`](../../resources/knowledge/royal-hotel-concierge.md): giọng điệu, hướng dẫn dùng tool và giới hạn privacy.

Sau mỗi lần sửa, upload bản mới vào dataset Dify, kiểm tra trạng thái index là Available và thử các câu hỏi truy hồi trước khi publish. Không đưa giá/availability snapshot, booking, hồ sơ khách, nhân viên hoặc bản dump database vào KB.

## Kiểm thử và khắc phục

- Hỏi câu đời thường, câu hỏi hạng phòng/sức chứa, câu nhiều ý và câu tiếp nối.
- Hỏi giá, so sánh với tool/database trực tiếp; giá và availability không được lấy từ KB tĩnh.
- Hỏi tìm 2 phòng cho ngày cụ thể; thiếu dữ kiện thì phải hỏi lại; không đủ số phòng thì nêu đúng số lượng, không cam kết sai.
- Sai tool key/disabled/mất Dify: endpoint tool phải trả 401/503; website không được quay về canned answer.
- Hỏi dữ liệu nhân viên/booking khách khác/secrets: không tiết lộ.
- Response không có `source`, `sources` hoặc nội dung “Nguồn: …”.

Chạy test Laravel:

```bash
php artisan test --compact --filter=DifyChatbotTest
```

Nếu frontend JavaScript đã thay đổi, chạy `npm run build`. Không cần migration, không sửa database/schema. Không push hoặc publish source code lên GitHub.
