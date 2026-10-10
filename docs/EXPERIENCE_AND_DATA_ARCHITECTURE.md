# Royal Hotel — hướng triển khai trải nghiệm, AI và dữ liệu

## 1. Kết luận nhanh

Royal Hotel có thể triển khai đồng thời **GSAP ScrollTrigger**, **Three.js**, **Vue 3** và một **chatbot RAG có hội thoại tự nhiên**. Nên triển khai theo từng phần nhỏ trên kiến trúc Laravel hiện tại, không chuyển toàn bộ website thành SPA và chưa cần xây kho dữ liệu ngay.

## Trạng thái triển khai 25/09/2026

- Đã triển khai Vue 3 island cho Royal Concierge; phần website còn lại tiếp tục dùng Laravel Blade.
- Đã triển khai trả lời dạng stream, lịch sử hội thoại ngắn theo session và fallback khi AI không khả dụng.
- Đã triển khai truy hồi tài liệu local có nguồn; có thể chuyển sang OpenAI Vector Store bằng `ROYAL_AI_VECTOR_STORE_ID` mà không đổi giao diện.
- Đã triển khai tool chỉ đọc cho hạng phòng, tìm phòng trống và kỳ nghỉ của khách đang đăng nhập. Tool kỳ nghỉ luôn lấy `customer_user_id` từ session, không nhận ID người dùng từ mô hình.
- Đã triển khai GSAP/ScrollTrigger cho giao diện khách và motion dùng chung cho admin, staff, gồm reveal, chuyển trang, popup, nút thao tác và FLIP cho ma trận phòng.
- Đã triển khai snapshot KPI hằng ngày bằng lệnh `php artisan reports:snapshot`; scheduler chạy lúc 00:10.
- Không triển khai Three.js theo quyết định hiện tại. Không xây kho dữ liệu vật lý ở giai đoạn này.

## 2. Chatbot hội thoại và RAG

### Trạng thái hiện tại

- Khi có `ROYAL_AI_API_KEY`, chatbot đã gửi lịch sử hội thoại và dữ liệu hạng phòng hiện tại tới mô hình AI. Prompt đã cho phép trò chuyện tự nhiên về chủ đề thông thường nhưng chỉ được dùng dữ liệu thật khi trả lời về khách sạn.
- Khi chưa có API key hoặc dịch vụ AI lỗi, hệ thống chuyển sang câu trả lời theo luật. Chế độ này chỉ trả lời tốt nhóm câu hỏi khách sạn và không thể trò chuyện ngẫu nhiên như một người.
- Đây chưa phải RAG hoàn chỉnh vì chưa có bước phân đoạn tài liệu, embedding, vector search, trích nguồn và đánh giá độ liên quan.

### Kiến trúc đề xuất

```text
Khách hàng
   ↓
Chat API Laravel → kiểm tra phiên + rate limit + moderation
   ↓
Conversation orchestrator
   ├─ Hội thoại thông thường → LLM
   ├─ Hỏi chính sách/tiện nghi → vector retrieval từ tài liệu Royal
   └─ Hỏi giá/phòng trống/đơn đặt → tool gọi service và database thật
   ↓
LLM tổng hợp câu trả lời + đường dẫn thao tác + nguồn tham chiếu
```

RAG nên chứa nội dung ổn định như chính sách, quy trình, tiện nghi, hướng dẫn thanh toán, FAQ và nội dung trang. Giá, phòng trống, trạng thái thanh toán và booking phải lấy qua **tool truy vấn dữ liệu sống**, không đưa vào vector store vì chúng thay đổi thường xuyên. OpenAI Retrieval hỗ trợ semantic search trên vector stores và tự động chunk, embed, index tài liệu; đây là một lựa chọn phù hợp cho bản đầu tiên: [OpenAI Retrieval](https://developers.openai.com/api/docs/guides/retrieval).

### Những gì cần có

1. API key phía server và giới hạn ngân sách.
2. Bộ tài liệu Royal đã duyệt, có phiên bản và người chịu trách nhiệm nội dung.
3. Vector store hoặc PostgreSQL + pgvector.
4. Các tool chỉ đọc cho `search_rooms`, `get_room_types`, `get_booking_for_user`, `get_policy`.
5. Quy tắc phân quyền: khách chỉ đọc booking của chính mình; chatbot không nhận dữ liệu thẻ hoặc mật khẩu.
6. Bộ câu hỏi đánh giá gồm câu đúng, câu thiếu dữ kiện, câu ngoài phạm vi và prompt injection.
7. Log ẩn danh, phản hồi hữu ích/không hữu ích và cảnh báo khi chatbot không chắc chắn.

### Lộ trình

- **Pha 1:** bật AI hội thoại, streaming, lưu lịch sử ngắn theo session và giữ fallback hiện tại.
- **Pha 2:** thêm RAG cho tài liệu tĩnh và hiển thị nguồn.
- **Pha 3:** thêm tool dữ liệu sống cho tìm phòng và booking; mọi thao tác ghi vẫn yêu cầu người dùng xác nhận.
- **Pha 4:** đánh giá chất lượng, chi phí, độ trễ và bổ sung voice nếu thật sự cần.

## 3. GSAP ScrollTrigger, Three.js và Vue

### Có thể triển khai

- Dùng ScrollTrigger cho reveal theo section, tiến trình cuộn, gallery và các chuyển cảnh có chủ đích. Plugin hỗ trợ trigger, scrub, pin, responsive và tự tính lại khi resize: [GSAP ScrollTrigger](https://gsap.com/docs/v3/Plugins/ScrollTrigger/).
- Dùng Three.js cho **một hero trải nghiệm** hoặc mô hình phòng 3D nhẹ. Three.js cần scene, camera, renderer và vòng lặp render: [Three.js — Creating a scene](https://threejs.org/manual/pages/creating-a-scene.html).
- Dùng Vue 3 theo mô hình “islands” cho chatbot, bộ chọn phòng, gallery hoặc dashboard tương tác. Vue hỗ trợ tích hợp tăng dần vào backend đang render HTML; không cần viết lại toàn bộ Laravel: [Ways of Using Vue](https://vuejs.org/guide/extras/ways-of-using-vue).
- Có thể học nhịp trình bày từ Door Dennis: headline rõ, project chapter lớn, typography chuyển động và CTA lặp có chủ đích; không sao chép nội dung hoặc hiệu ứng nguyên bản: [Door Dennis](https://www.doordennis.nl/).

### Cách ghép vào dự án hiện tại

```text
Laravel Blade + routes + auth + booking logic
├─ app.js: GSAP/ScrollTrigger dùng chung
├─ Vue islands: chỉ mount tại phần có state phức tạp
└─ Three.js scene: lazy-load trên đúng trang, hủy renderer khi rời trang
```

### Giới hạn và điều kiện

- Không dùng Three.js cho form đặt phòng, thanh toán, admin hoặc staff vì tăng tải và làm chậm thao tác.
- Cần model 3D tối ưu, texture WebP/AVIF, fallback ảnh tĩnh, giới hạn device pixel ratio và dừng render khi tab ẩn.
- Phải hỗ trợ `prefers-reduced-motion`, bàn phím, màn hình nhỏ và thiết bị không có WebGL tốt.
- Vue chỉ có lợi khi component có nhiều state. Header, nội dung marketing và form đơn giản tiếp tục dùng Blade/JS hiện có.
- Trước khi phát hành cần đo LCP, INP, CLS, dung lượng JS, FPS và bộ nhớ trên điện thoại tầm trung. Vue cũng khuyến nghị chọn kiến trúc theo nhu cầu tương tác và đo trên build thật: [Vue Performance](https://vuejs.org/guide/best-practices/performance).

### Pha triển khai đề xuất

1. Chuẩn hóa motion tokens và dọn ScrollTrigger trùng lặp.
2. Chuyển chatbot thành Vue island đầu tiên.
3. Làm một prototype Three.js độc lập cho hero, có fallback ảnh.
4. Chỉ tích hợp nếu prototype đạt ngân sách hiệu năng; các trang nghiệp vụ vẫn nhẹ.

## 4. Có cần kho dữ liệu không?

### Chưa cần ngay khi

- Chỉ có một cơ sở MySQL, một khách sạn và lượng booking còn nhỏ.
- Dashboard chủ yếu xem doanh thu, ADR, RevPAR và trạng thái theo ngày.
- Truy vấn báo cáo chưa ảnh hưởng tốc độ đặt phòng.

Trong giai đoạn này, nên dùng index đúng, query tổng hợp, cache ngắn và bảng snapshot theo ngày. Đây là giải pháp ít phức tạp hơn và đủ cho nhu cầu hiện tại.

### Kho dữ liệu trở nên cần thiết khi

- Có nhiều chi nhánh hoặc nhiều nguồn: PMS, CRM, thanh toán, quảng cáo, email, đánh giá.
- Cần lịch sử bất biến, so sánh theo năm, dự báo nhu cầu, attribution marketing hoặc Power BI cho nhiều người dùng.
- Truy vấn phân tích nặng làm chậm database giao dịch.
- Cần quản trị chất lượng dữ liệu, lineage, phân quyền dữ liệu và một định nghĩa KPI thống nhất.

Kho dữ liệu tách workload phân tích khỏi database giao dịch, hợp nhất dữ liệu và tạo nền cho BI/ML. Kiến trúc kho dữ liệu hiện đại thường phục vụ cả BI truyền thống và workload nâng cao như machine learning: [Microsoft Modern Data Warehouse](https://learn.microsoft.com/en-us/data-engineering/playbook/solutions/modern-data-warehouse/).

### Mô hình dữ liệu đề xuất khi mở rộng

- Fact: `fact_bookings`, `fact_room_nights`, `fact_payments`, `fact_refunds`.
- Dimension: `dim_date`, `dim_room`, `dim_room_type`, `dim_customer`, `dim_channel`, `dim_property`.
- Pipeline tăng dần theo `updated_at`, kiểm tra đối soát tổng tiền và số booking sau mỗi lần nạp.
- Dashboard đọc từ semantic layer/kho dữ liệu; nghiệp vụ đặt phòng vẫn đọc ghi MySQL chính.

### Quyết định cho Royal Hotel hiện tại

**Chưa xây kho dữ liệu ở thời điểm này.** Trước mắt tạo snapshot KPI hằng ngày và theo dõi thời gian query. Chuyển sang kho dữ liệu khi có thêm chi nhánh/nguồn dữ liệu hoặc khi báo cáo bắt đầu ảnh hưởng database nghiệp vụ.
