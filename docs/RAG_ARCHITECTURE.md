# Royal Concierge RAG

## Luồng xử lý

1. Giao diện Vue gửi câu hỏi và tối đa tám lượt hội thoại gần nhất đến server Laravel.
2. Server kiểm tra độ dài, rate limit và moderation trước khi gọi mô hình.
3. `RoyalKnowledgeService` truy hồi tối đa ba đoạn liên quan theo thứ tự:
   - OpenAI Vector Store khi có `ROYAL_AI_VECTOR_STORE_ID`.
   - Chỉ mục nội bộ `knowledge_chunks` với cosine similarity từ embedding và điểm từ khóa.
   - Các file Markdown trong `resources/knowledge` khi chưa có chỉ mục database.
4. Mô hình nhận system prompt, lịch sử hội thoại và ngữ cảnh truy hồi. Các dữ liệu thay đổi theo thời gian được lấy bằng tool server side từ database.
5. Câu trả lời được stream về giao diện. API key và dữ liệu của người dùng khác không bao giờ được gửi xuống trình duyệt.

## Tool dữ liệu thật

- `get_room_types`: hạng phòng, giá và sức chứa hiện tại.
- `search_rooms`: phòng trống theo ngày và số khách, gồm quy tắc sau 17:00.
- `get_booking_for_user`: chỉ đọc các kỳ nghỉ thuộc phiên khách đang đăng nhập.

Mô hình được phép trò chuyện tự nhiên về kiến thức phổ thông. Với giá, phòng trống, đặt phòng và chính sách Royal, mô hình phải dùng tài liệu hoặc tool thay vì tự suy đoán.

## Cấu hình

```env
ROYAL_AI_API_KEY=
ROYAL_AI_ENDPOINT=https://api.openai.com/v1/chat/completions
ROYAL_AI_MODEL=gpt-4o-mini
ROYAL_AI_EMBEDDING_MODEL=text-embedding-3-small
ROYAL_AI_VECTOR_STORE_ID=
ROYAL_AI_MODERATION=true
```

Giữ các giá trị này ở server. Không đưa key vào Blade, JavaScript hoặc repository.

## Lập chỉ mục

Sau khi thay đổi tài liệu trong `resources/knowledge`, chạy:

```bash
php artisan knowledge:index
```

Trong môi trường chưa có API key, có thể kiểm tra pipeline bằng:

```bash
php artisan knowledge:index --no-embeddings
```

Chế độ này vẫn truy hồi theo từ khóa. Khi key được cấu hình, chạy lại lệnh đầu tiên để bổ sung embedding cho semantic retrieval.

## Mở rộng

- Thêm tài liệu đã kiểm duyệt dưới dạng Markdown vào `resources/knowledge`.
- Với kho lớn, tải tài liệu lên một Vector Store và cấu hình ID thay cho việc lưu vector JSON trong MySQL.
- Giữ các thao tác thay đổi dữ liệu ngoài chatbot cho đến khi có xác nhận rõ ràng và audit log.
