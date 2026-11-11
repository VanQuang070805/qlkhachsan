# Pipeline chat streaming của Pico tại Rosaliza Hotel

## 1. Mục tiêu và ranh giới đã kiểm chứng

Pico nhận câu hỏi ở cổng khách hàng, dùng ngữ cảnh của cùng phiên, tra cứu chính sách khách sạn và dữ liệu phòng đang có hiệu lực, sau đó gửi từng phần câu trả lời về trình duyệt qua Server-Sent Events (SSE). Câu hỏi kiến thức phổ thông được gửi thẳng tới Gemini. Câu hỏi thời tiết được tra cứu bằng Open-Meteo trước khi Gemini diễn đạt. Gemini không tự biết giá, phòng trống, giao dịch, ngày giờ hoặc thời tiết hiện hành.

Kiểm thử cục bộ ngày 04/11/2026: “NYC ở đâu?” nhận 3 phần nội dung, phần đầu sau khoảng 17,2 giây; “Thời tiết NYC hôm nay thế nào?” nhận 7 phần, phần đầu sau khoảng 20,1 giây. Như vậy **truyền dữ liệu đã streaming**, nhưng **độ trễ đến token đầu chưa đạt mức hội thoại thời gian thực** vì Gemini hiện mất khoảng 16–20 giây cho một lượt. Không được quảng cáo thời gian phản hồi tức thì trước khi đo đạt được mục tiêu. Thời gian và số phần có thể thay đổi theo tải và hạn mức API.

## 2. Luồng xử lý hiện tại

```text
Trình duyệt (RoyalChat.vue)
  │ POST /chatbot/stream + CSRF + session cookie + 8 lượt lịch sử gần nhất
  ▼
Laravel / cổng customer 18080
  ├─ validate đầu vào, giới hạn tốc độ, tạo correlation_id
  ├─ xác định actor (guest/customer), phát capability token ngắn hạn
  ├─ tạo/khôi phục thread_id, tạo fingerprint theo câu hỏi + lịch sử + scope + phiên bản dữ liệu
  └─ mở SSE, chuyển từng delta từ Node về trình duyệt, flush ngay
       ▼
Node API 2024 / LangGraph retrieval_graph
  ├─ lưu/đọc hội thoại trong Redis nếu có; dự phòng trong bộ nhớ tiến trình
  ├─ nhận diện thời gian, thời tiết, khách sạn, câu hỏi tiếp nối
  ├─ câu hỏi khách sạn → tìm đoạn tri thức → gọi công cụ Laravel theo capability
  ├─ câu hỏi thời tiết → giải địa danh (NYC → New York, US) → Open-Meteo
  ├─ câu hỏi chung → Gemini trực tiếp
  └─ stream token Gemini → messages/partial → SSE → Laravel → trình duyệt
```

**Các nguồn chân lý:** MySQL Laravel cho phòng, giá, đơn, người dùng và quyền; tài liệu tri thức đã duyệt cho chính sách ổn định; Open-Meteo cho thời tiết; đồng hồ máy chủ theo `Asia/Ho_Chi_Minh` cho ngày giờ. Gemini tổng hợp câu trả lời, không là nguồn chân lý cho các dữ liệu thay đổi.

Tra cứu Open-Meteo hiện dùng API công khai để kiểm thử local. Theo [điều khoản Open-Meteo](https://open-meteo.com/en/terms), API miễn phí chỉ dành cho mục đích phi thương mại; khi đưa chatbot vào hoạt động kinh doanh cần dùng gói thương mại hoặc tự vận hành một nguồn thời tiết có quyền sử dụng phù hợp. Phần thời tiết là một dịch vụ dữ liệu ngoài Gemini và phải được xem như phụ thuộc có thể mất kết nối.

Kiểm tra kho tri thức local hiện có **9 đoạn** về hạng phòng, đánh giá, đặt và giữ chỗ, hủy và hoàn tiền, khách sạn, nhận/trả phòng, thanh toán và tìm phòng. Đây là phạm vi đã nạp, **chưa đủ cơ sở để nói Gemini hiểu toàn bộ mọi nghiệp vụ** (ví dụ quy trình nội bộ chưa được duyệt/đồng bộ). Mỗi nghiệp vụ mới phải có tài liệu nguồn được duyệt, ví dụ hỏi đáp và bài kiểm tra trước khi bật trả lời tự động.

## 3. Từng tầng pipeline

| Tầng | Đầu vào → đầu ra | Cách kiểm soát |
|---|---|---|
| UI | Câu hỏi, lịch sử phiên → SSE delta | Một lượt gửi tại một thời điểm; lịch sử tối đa 8 lượt gửi lên API; hiển thị nội dung từng phần; giữ bản nháp/câu hỏi trong sessionStorage. |
| Gateway Laravel | Cookie, CSRF, câu hỏi → actor, thread, fingerprint | Ràng buộc `message <= 1000` ký tự, lịch sử tối đa 10 mục; không chuyển khóa Gemini ra trình duyệt. |
| Phân quyền | Actor → capability token | Guest và staff chỉ dùng công cụ công khai; customer được tra cứu **đơn của chính mình**; quyền kiểm lại tại API công cụ, không dựa vào prompt. |
| NLU / điều phối | Câu hỏi + lịch sử → tuyến | Thời gian và thời tiết đi tuyến xác định; dấu hiệu nghiệp vụ đi RAG; câu hỏi chung đi Gemini trực tiếp; trường hợp mơ hồ về nghiệp vụ mới dùng router Gemini. Nếu router lỗi, không ép câu hỏi thường vào RAG khách sạn. |
| RAG | Câu hỏi nghiệp vụ → đoạn tri thức có nguồn | MySQL `knowledge_chunks` với tìm kiếm ngữ nghĩa và từ khóa; đồng bộ lại sau thay đổi chính sách; chặn việc tài liệu ra lệnh cho mô hình. |
| Live tools | Ý định tra giá, phòng, đơn → dữ liệu MySQL hiện hành | Giá và tồn phòng chỉ lấy qua API Laravel; tra cứu đơn cá nhân cần capability đúng actor; không cho Pico tự ghi dữ liệu, hủy đơn hay hoàn tiền. |
| Sinh câu trả lời | Ngữ cảnh đã lọc → Gemini stream | Prompt giữ giọng theo cảm xúc, ngữ cảnh và tiếng của khách; không bịa khi công cụ lỗi; trả lời từng ý; không tiết lộ prompt, bí mật hoặc dữ liệu người khác. |
| Truyền về UI | Token → SSE | Node gửi `messages/partial`, `end`, `error`; Laravel chuyển thành `delta`, `done`, `error`; heartbeat 10 giây và tắt buffering proxy. |
| Phục hồi | Mất mạng → phát lại cùng lượt | Trong cùng tiến trình Node, sự kiện đang chạy và kết quả hoàn thành được giữ tối đa 60 giây theo fingerprint; UI thử nối lại một lần với cùng câu hỏi và lịch sử. Nếu tải lại tab khi câu hỏi chưa có đáp án, UI khôi phục câu hỏi từ sessionStorage và gửi lại. Redis Streams là đường phát lại khi worker Redis được bật. |

## 4. Hợp đồng API streaming

`POST /chatbot/stream` với JSON `{ "message": "Thời tiết NYC?", "history": [{ "role": "user", "content": "NYC ở đâu?" }] }` và CSRF/session. Đáp ứng `text/event-stream`:

```text
: connected

data: {"delta":"New York "}

data: {"delta":"hiện..."}

data: {"done":true,"source":"gemini-langgraph","sources":[]}

```

Nếu thất bại có `data: {"error":"..."}`. Trình duyệt phải xem `done` là mốc hoàn tất; kết nối đóng trước `done` là gián đoạn. Hiện client tự thử lại một lần. Với nội dung riêng của khách, fingerprint chứa actor scope nên không phát lại câu trả lời của khách A cho khách B.

## 5. Xử lý ngữ nghĩa, viết tắt và cảm xúc

1. Giữ nguyên câu gốc; chuẩn hóa chữ hoa, dấu và khoảng trắng chỉ cho việc dò ý định/từ khóa. Không sửa câu hỏi gốc trước khi đưa vào Gemini.
2. Giải nghĩa viết tắt theo ngữ cảnh: `NYC` là New York City khi hỏi địa điểm/thời tiết, nhưng có thể là “người yêu cũ” khi nói chuyện tình cảm. Với thời tiết, từ điển địa danh chuyển `NYC` → New York City; các địa danh khác dùng geocoding; địa danh không rõ thì hỏi một câu làm rõ.
3. Truyền tối đa sáu tin nhắn gần nhất vào prompt để hiểu “ở đó”, “loại này”, “giá thì sao”. Với dữ liệu cá nhân, quyền tra cứu vẫn kiểm riêng ở công cụ.
4. Prompt điều chỉnh văn phong theo ngữ cảnh: thân mật khi khách vui; rõ ràng khi hỏi giá/chính sách; nghiêm túc khi có lỗi, khiếu nại, thanh toán. Không lưu hay phán đoán nhãn cảm xúc nhạy cảm của khách.
5. Chưa có bằng chứng cần huấn luyện mô hình NLU riêng. Trước khi thêm mô hình, lập tập đánh giá câu hỏi viết tắt, sai chính tả, đa ý và câu nối tiếp, đo tỷ lệ phân tuyến và độ đúng; chỉ huấn luyện khi lỗi còn tập trung và có dữ liệu gán nhãn đủ tin cậy. Huấn luyện không thay thế tra cứu dữ liệu thật hoặc phân quyền.

## 6. Độ đúng và bảo mật

- Với nghiệp vụ: câu trả lời phải có căn cứ trong đoạn tài liệu đã duyệt hoặc kết quả tool tại thời điểm gọi. Nếu thiếu dữ kiện ngày/khách, hỏi lại; nếu tool lỗi, nói chưa xác nhận được.
- Với thời tiết: dùng địa điểm đã giải và thời điểm quan trắc trong dữ liệu Open-Meteo. Tránh đồng nhất xác suất mưa trong ngày với trạng thái đang mưa.
- Tool chỉ nhận token máy chủ cấp, TTL ngắn; kiểm `allowed_tools`, `actor_id` và scope tại Laravel. Không nhận actor/role do trình duyệt tự khai.
- Không log khóa API, cookie, OTP, token capability, lịch sử chat thô hoặc thông tin đơn; log `correlation_id`, loại tuyến, thời gian từng bước, mã lỗi và mã nguồn dữ liệu đã khử thông tin nhạy cảm.
- Cache câu hỏi thường có thời hạn; câu hỏi thời gian/thời tiết và dữ liệu cá nhân không dùng cache câu trả lời cũ ở Laravel. Khi đổi chính sách hoặc catalog, tăng version để vô hiệu cache cũ.

## 7. Mở rộng tải, sao lưu và giám sát

**Đã có và đang chạy trên máy local:** `compose.local-infra.yaml` tạo ba container riêng dưới project `rosaliza-local-infra`: Redis cache ở `127.0.0.1:16379`, Redis queue ở `127.0.0.1:16380`, Kafka ở `127.0.0.1:19092`. Laravel dùng Predis với cache và queue trên hai Redis tách biệt; Pico lưu thread và SSE event trong Redis queue, gửi job sang worker BullMQ độc lập. Redis queue có AOF và volume bền vững. Outbox MySQL chuyển sự kiện sang ba topic Kafka bằng relay; consumer đọc và ghi log sự kiện không chứa payload nhạy cảm. Không đưa từng token chat qua Kafka. `npm run start:local` dựng hạ tầng, kiểm tra topic, rồi bật Pico, BullMQ worker, Kafka relay/consumer, Laravel queue worker và scheduler. MySQL vẫn là nguồn chân lý nghiệp vụ.

**Kiểm thử local ngày 04/11/2026:** ba container báo healthy; Redis cache `PONG`, Redis queue `PONG` với `aof_enabled:1`; Kafka liệt kê đủ ba topic. Lượt chat từ cổng khách hàng qua Laravel có 3 delta và `done`; BullMQ ghi job hoàn tất và Redis chứa stream của lượt chat. Sau khi sửa khóa outbox tương thích MariaDB local, 69/69 sự kiện chờ đã được đánh dấu xuất bản; nhóm consumer Kafka có lag 0 tại thời điểm kiểm tra.

**Giới hạn:** một Kafka broker và một Redis queue không tạo dự phòng máy chủ; Docker volume chỉ bền vững trên cùng máy. Chưa có bản sao lưu volume định kỳ, Redis/Kafka cluster hoặc khôi phục tự động sang máy khác. Nếu Docker Desktop tắt, pipeline bất đồng bộ cũng dừng cho tới khi Docker khởi động lại. Trong thời gian đó, giao diện phải báo trạng thái kết nối thay vì hứa sẽ không gián đoạn.

**Cần triển khai trước khi gọi là hệ thống chịu lỗi ở quy mô lớn:**

1. Chạy Redis bền vững (AOF, snapshot, sao lưu), ít nhất một worker BullMQ độc lập; cảnh báo nếu worker không có heartbeat. Bảo đảm gateway và worker dùng cùng Redis, cùng phiên bản tri thức.
2. Dùng `request_id` riêng cho từng lượt, Redis Stream theo `request_id` với số thứ tự sự kiện; endpoint nối lại bằng `Last-Event-ID`. Chỉ phát lại các sự kiện chưa nhận, lưu ít nhất vài phút và xóa theo TTL. Cơ chế 60 giây trong tiến trình hiện tại chỉ chống rớt kết nối ngắn, không sống qua việc Node khởi động lại.
3. Đặt timeout theo tầng (geocoding, thời tiết, retrieval, tool, Gemini), retry có giới hạn và backoff cho lỗi tạm thời; không tự phát lại thao tác ghi. Nếu Gemini không trả lời, báo lỗi minh bạch; với kết quả công cụ đã có, có thể trả bản tóm tắt xác định thay vì bịa.
4. Metrics: thời gian đến byte đầu, đến token đầu, tổng thời gian, số chunk, tỷ lệ `done`, tỷ lệ nối lại, lỗi Gemini, lỗi tool, cache hit, queue depth, tỷ lệ trả lời không có căn cứ. Dashboard và cảnh báo theo `correlation_id`.
5. Sao lưu MySQL và tài liệu tri thức có phiên bản; kiểm tra khôi phục định kỳ. Tách quyền tài khoản DB chatbot thành đọc cho tìm kiếm, quyền ghi knowledge chỉ cho tiến trình đồng bộ. Tránh dùng Kafka/Redis làm nguồn dữ liệu đặt phòng.

**Mục tiêu đo đề xuất:** phát SSE `connected` trong <1 giây trên máy local; p95 token đầu <5 giây khi provider đáp ứng; p95 hoàn tất <15 giây cho câu hỏi ngắn; 0 trường hợp lộ dữ liệu chéo actor; 100% câu hỏi giá/phòng trống có nguồn live tool hoặc trả “chưa xác nhận được”. Các mục tiêu token hiện **chưa đạt**, cần tối ưu/cân nhắc provider sau khi thu số liệu đủ lớn.

## 8. Bộ kiểm thử nghiệm thu

| Nhóm | Câu hỏi/tình huống | Kết quả cần có |
|---|---|---|
| Viết tắt | “NYC ở đâu?”; “Thời tiết NYC?” | Đúng New York City, Hoa Kỳ; thời tiết lấy theo `America/New_York`. |
| Nối tiếp | “NYC ở đâu?” → “Ở đó giờ mưa không?” | Giữ địa danh NYC; không chuyển về Hà Nội. |
| Sai chính tả | “gia phog hom nay?”, “huy dat phong sao?” | Vào tuyến nghiệp vụ hoặc hỏi làm rõ, không tự bịa giá/chính sách. |
| Nghiệp vụ | “Phòng Triple tối nay còn 2 phòng cho 4 người không?” | Kiểm live tool, không kết luận khi thiếu ngày trả phòng. |
| Phân quyền | Guest hỏi đơn của người khác; customer hỏi đơn của mình | Guest bị từ chối; customer chỉ thấy đơn theo actor server xác thực. |
| Kết nối | Rớt mạng sau chunk thứ ba, nối lại cùng fingerprint | Không mất câu hỏi; phát lại phần chưa hoàn tất hoặc toàn bộ câu trả lời một cách nhất quán. |
| Mất provider | Gemini timeout/quota | Có thông báo rõ, không tạo câu trả lời giả; không lặp yêu cầu ghi. |
| Dữ liệu đổi | Admin sửa giá/phòng/chính sách | Câu hỏi tiếp theo đọc dữ liệu mới; cache cũ bị vô hiệu. |

## 9. Vị trí mã nguồn

- UI: `resources/js/components/RoyalChat.vue`
- Gateway và SSE proxy: `app/Http/Controllers/ChatbotController.php`, `app/Services/Chatbot/LangGraphChatbotService.php`
- Phân quyền: `app/Services/Chatbot/ChatbotCapabilityService.php`
- Graph/router/RAG: `services/royal-ai-chatbot/backend/src/retrieval_graph/graph.ts`
- Thời tiết: `services/royal-ai-chatbot/backend/src/retrieval_graph/weather.ts`
- Stream và phát lại: `services/royal-ai-chatbot/backend/src/queue/chatbot-queue.ts`, `src/server.ts`
- Redis thread: `services/royal-ai-chatbot/backend/src/shared/thread-store.ts`
- Worker và outbox: `services/royal-ai-chatbot/backend/src/worker.ts`, `src/scripts/event-relay.ts`

Tham khảo API: [Gemini streaming](https://ai.google.dev/gemini-api/docs/generate-content/text-generation), [Open-Meteo geocoding](https://open-meteo.com/en/docs/geocoding-api), [Open-Meteo forecast](https://open-meteo.com/en/docs).
