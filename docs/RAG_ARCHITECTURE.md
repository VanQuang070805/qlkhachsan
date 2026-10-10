# Royal Concierge — kiến trúc Gemini + Dify

## Luồng chạy

```text
RoyalChat.vue
  └─ POST /chatbot/stream (CSRF + session cookie; chỉ gửi câu hỏi hiện tại)
       └─ Laravel ChatbotController
            └─ DifyChatbotService (giữ API key + conversation_id phía server)
                 └─ Dify Chatflow → Knowledge Retrieval → Gemini Agent (FunctionCalling + public room tools) → Answer
```

Website chỉ có một nhà cung cấp hội thoại đang hoạt động: Dify Chatflow dùng Gemini. Không chạy đồng thời bộ trả lời rule-based hay OpenAI cũ; nếu Dify/Gemini lỗi, endpoint trả lỗi thân thiện thay vì giả vờ câu trả lời cũ là phản hồi của AI. Giao diện hiện tại giữ nguyên, và SSE chỉ gửi nội dung trả lời, không gửi/hiển thị dòng “Nguồn”.

Agent local hiện chọn `Gemini 3.5 Flash-Lite` trên Free Tier. Google áp dụng quota theo project và model; không tự chuyển sang model trả phí. Khi quota hết, website báo gián đoạn thay vì dùng câu trả lời cũ hoặc phát sinh phí.

Laravel chỉ gửi `query` hiện tại cùng mã `user` giả danh HMAC và `conversation_id` do Dify cấp, tất cả khóa và ID hội thoại được giữ ở server. Lịch sử do browser gửi không được chuyển tiếp; bộ nhớ nhiều lượt do Chatflow quản lý. Mã hội thoại tách theo phiên trình duyệt và loại phiên khách/nhân viên để tránh dùng chung ngữ cảnh.

## Kiến thức và dữ liệu sống

- Nội dung ổn định đã duyệt nằm trong `resources/knowledge/*.md` và phải được tải lên đúng Knowledge Base trong Dify sau khi sửa; thư mục này không tự đồng bộ.
- Gemini có thể trả lời tự nhiên câu hỏi đời thường bằng kiến thức phổ thông. Câu hỏi về Royal Hotel chỉ khẳng định dữ kiện có trong tri thức đã duyệt hoặc kết quả công cụ.
- Giá hiện hành và số phòng trống được Dify gọi qua hai endpoint Laravel chỉ đọc: `POST /api/chatbot/tools/room-types` và `POST /api/chatbot/tools/rooms/search`.
- Tìm phòng dùng `Booking::reservedRoomIds()` và điều kiện trạng thái phòng hiện có, nhận ngày đến/đi, tổng khách và số phòng yêu cầu. Sức chứa được tính trên cả số phòng yêu cầu, không ép tổng khách vào mỗi phòng. API chỉ trả tên hạng phòng, số lượng còn lại, giá công khai, sức chứa và cờ đủ số lượng; không trả room ID/số phòng, booking, PII, staff/admin data.
- Chatbot công khai không tra cứu booking cá nhân và không tạo/sửa/hủy booking hay thanh toán. Khách được hướng sang đăng nhập và luồng đặt phòng chính thức.

## Biên bảo mật

- Laravel vẫn là cổng duy nhất từ website tới Dify; Dify không kết nối database Royal Hotel.
- `DIFY_API_KEY` dùng riêng cho ứng dụng Chatflow. `DIFY_TOOL_API_KEY` là bearer secret riêng cho hai endpoint room, không dùng lại Gemini key hoặc Dify app key.
- Hai tool chỉ hoạt động khi `DIFY_LIVE_TOOLS_ENABLED=true` và có bearer secret hợp lệ; route bị throttle. Lỗi xác thực bị từ chối mặc định.
- Không đưa lịch sử browser, cookie, mã khách/nhân viên thật, lịch booking hay bảng nội bộ vào prompt, tool output hoặc log.
- Không sửa schema/database/dữ liệu hiện tại. Không đưa `.env`, token, hoặc khóa API lên Git.

## Cấu hình server

```env
DIFY_BASE_URL=http://127.0.0.1:8089/v1
DIFY_API_KEY=app-<key-chatflow>
DIFY_TIMEOUT=90
DIFY_TOOL_API_KEY=<bearer-secret-rieng>
DIFY_LIVE_TOOLS_ENABLED=true
```

`DIFY_BASE_URL` là địa chỉ Dify mà PHP/Laravel truy cập được. Trong môi trường này, OpenAPI gọi qua bridge hẹp `http://host.docker.internal:8001/api`; bridge chỉ forward hai route room tool về Laravel qua Apache ở `127.0.0.1:80`. Không forward vào `artisan serve` cổng 8000 một luồng, vì website đang chờ Dify sẽ chặn request tool gọi ngược. Dify SSRF allowlist chỉ cho phép `host.docker.internal`, và firewall cần giới hạn TCP 8001 vào Docker Desktop network. Thay bằng host nội bộ/HTTPS đúng môi trường khi deploy.

Sau khi đổi `.env`, chạy `php artisan config:clear` (hoặc dựng lại cache config theo môi trường). Bật live tools chỉ sau khi cả hai request OpenAPI trả đúng dữ liệu và yêu cầu bearer key.

## Kho tri thức và cập nhật

1. Sửa tài liệu có kiểm duyệt trong `resources/knowledge/`.
2. Tránh đưa giá/availability snapshot, dữ liệu cá nhân, booking hoặc tài liệu nội bộ nhân viên vào Knowledge Base.
3. Tải tài liệu công khai đã duyệt lên dataset Dify, chờ trạng thái **Available**, rồi kiểm tra retrieval trong preview.
4. Publish Chatflow sau khi chạy bộ câu hỏi kiểm tra; Laravel gọi phiên bản đã publish, không dùng bản nháp tự động lưu.

## Kiểm tra nghiệm thu

- Hỏi chuyện đời thường, câu hỏi nghiệp vụ/loại phòng, câu nhiều ý, câu tiếp nối tham chiếu tin nhắn trước.
- Hỏi giá hiện hành; đối chiếu kết quả với tool/database tại thời điểm kiểm tra.
- Hỏi một hoặc nhiều phòng: thiếu ngày/khách thì chatbot hỏi lại; đủ dữ kiện thì gọi tool; không khẳng định đủ nếu số còn ít hơn yêu cầu.
- Tool trả rỗng/lỗi, key sai hoặc Dify lỗi: không bịa dữ liệu và không rơi về canned answer.
- Response browser không có metadata/tên nguồn; UI không đổi layout.
- Prompt injection đòi hỏi dữ liệu staff, booking người khác hoặc secrets bị từ chối.
- Chạy `php artisan test --compact --filter=DifyChatbotTest`, `npm run build` khi Vue đổi và `php artisan view:cache`.
