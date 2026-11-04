# Royal Concierge — Kiến trúc Gemini + LangGraph & 3 Cổng Truy Cập

## 1. Luồng chạy Chatbot (Gemini + LangGraph)

```text
RoyalChat.vue
  └─ POST /chatbot/stream (CSRF + session cookie; chỉ gửi câu hỏi hiện tại)
       └─ Laravel ChatbotController
            └─ LangGraphChatbotService (giữ thread_id + internal key ở server)
                 └─ services/royal-ai-chatbot/backend (LangGraph Node server: ingestion_graph & retrieval_graph)
                      ├─ Router & Answer: Google Gemini (`gemini-2.5-flash`)
                      ├─ Vector Retrieval: `text-embedding-004` + MySQL `knowledge_chunks` (hybrid semantic + lexical)
                      └─ Live Hotel Tools: gọi ngược Laravel `/api/chatbot/tools/*` với `LANGGRAPH_TOOL_API_KEY`
```

Website chỉ vận hành một kiến trúc hội thoại duy nhất: **LangGraph + Google Gemini** (tự host từ bản mẫu `mayooear/ai-pdf-chatbot-langchain` tại `services/royal-ai-chatbot/`, gồm cả `backend` và `frontend`). Các nhánh Dify, OpenAI và rule-based cũ đã được gỡ bỏ hoàn toàn. Nếu dịch vụ LangGraph/Gemini gặp sự cố, Laravel trả mã `503` hoặc sự kiện SSE `error` rõ ràng thay vì giả lập phản hồi AI.

## 2. Tri thức khách sạn đã duyệt & Dữ liệu phòng trực tiếp

- **Nguồn tri thức chuẩn**: nằm tại `resources/knowledge/*.md` (`royal-hotel.md`, `royal-hotel-concierge.md`). Giữ phòng chờ thanh toán trực tuyến tối đa **10 phút** (`Booking::PAYMENT_HOLD_MINUTES = 10`).
- **Đồng bộ tri thức**:
  - Qua Laravel CLI: `php artisan knowledge:index` (hoặc `--no-embeddings` khi chạy offline/kiểm thử).
  - Qua LangGraph Ingestion Graph: `cd services/royal-ai-chatbot/backend && npm run sync:knowledge`.
- **Công cụ dữ liệu phòng trực tiếp (`HotelRoomToolService`)**:
  - `GET|POST /api/chatbot/tools/room-types`: trả danh sách hạng phòng, sức chứa, tiện nghi và giá cơ bản.
  - `POST /api/chatbot/tools/rooms/search`: kiểm tra số phòng trống theo `Booking::reservedRoomIds()` và tính giá theo `PriceSetting::calculateTotalPrice()`. Không bao giờ trả số phòng vật lý (`room_number`), ID phòng nội bộ hay dữ liệu đặt phòng của khách hàng.

## 3. Nơi đặt khóa cấu hình (`.env` ở Server)

Tuyệt đối không đặt khóa trong trình duyệt, Vue hay commit vào Git:

1. **File `.env` gốc của Laravel**:
   ```dotenv
   GOOGLE_API_KEY=<khoa-google-gemini-api>
   GEMINI_CHAT_MODEL=gemini-2.5-flash
   GEMINI_EMBEDDING_MODEL=text-embedding-004

   LANGGRAPH_ENABLED=true
   LANGGRAPH_BASE_URL=http://127.0.0.1:2024
   LANGGRAPH_INTERNAL_API_KEY=<secret-laravel-sang-node>
   LANGGRAPH_TOOL_API_KEY=<secret-node-goi-tool-laravel>
   LANGGRAPH_TIMEOUT=90
   ```
2. **File `services/royal-ai-chatbot/backend/.env`**:
   ```dotenv
   GOOGLE_API_KEY=<khoa-google-gemini-api>
   GEMINI_CHAT_MODEL=gemini-2.5-flash
   GEMINI_EMBEDDING_MODEL=text-embedding-004
   VECTOR_PROVIDER=mysql
   MYSQL_HOST=127.0.0.1
   MYSQL_PORT=3306
   MYSQL_USER=root
   MYSQL_PASSWORD=
   MYSQL_DATABASE=qlkhachsan
   LARAVEL_INTERNAL_API_URL=http://127.0.0.1/api
   LARAVEL_TOOL_API_KEY=<cung-gia-tri-voi-LANGGRAPH_TOOL_API_KEY>
   LANGGRAPH_HOST=127.0.0.1
   LANGGRAPH_PORT=2024
   LANGGRAPH_INTERNAL_API_KEY=<cung-gia-tri-voi-LANGGRAPH_INTERNAL_API_KEY>
   LANGCHAIN_TRACING_V2=false
   LANGCHAIN_API_KEY=<khoa-langsmith-tuy-chon>
   LANGCHAIN_PROJECT=royal-hotel-chatbot-dev
   ```

## 4. Ba cổng đăng nhập & Cách ly phiên (Session/Guard Isolation)

| Đối tượng | Đường dẫn đăng nhập | Khu vực sau đăng nhập | Guard & Session |
|---|---|---|---|
| **Khách hàng** | `/login`, `/register` | `/`, `/account`, `/my-bookings`, `/booking/*`, `/payment/*` | Guard `customer` (`customer_user_id`) |
| **Nhân viên** | `/staff/login` | `/staff/*`, `/staff/profile`, `/staff/iot`, `/staff/face-id/*` | Guard `staff` (`staff_user_id`, chỉ nhận role `receptionist`) |
| **Quản trị viên** | `/admin/login` | `/admin/*` và `/staff/*` | Guard `admin` + `staff` (`admin_user_id`, chỉ nhận role `admin`) |

- Đường dẫn cũ `GET /internalauth/login` tự động chuyển hướng sang `/staff/login`; `/receptionist/profile` chuyển hướng sang `/staff/profile`.
- Phiên khách hàng và phiên nội bộ (nhân viên/quản trị) tồn tại độc lập trong cùng trình duyệt; đăng xuất một cổng không làm rớt phiên của cổng còn lại.
- Toàn bộ thành phần **Face ID** (`FaceIdController`, `app/Services/FaceId`, `face_recognition`, `/staff/iot`, `/staff/face-id/*`) được giữ nguyên 100%.
