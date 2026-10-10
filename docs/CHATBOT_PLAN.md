# Kế hoạch chatbot tư vấn Royal Hotel

## Mục tiêu

Tư vấn tự nhiên theo câu hỏi của khách, dùng thông tin khách sạn đang có và hỏi lại đúng phần còn thiếu. Chatbot không buộc khách chọn câu trả lời có sẵn, không bịa tình trạng phòng, giá cuối hoặc điều kiện đặt phòng.

## Tích hợp AI trả lời

Có. Phương án là dùng mô hình ngôn ngữ ở phía server để hiểu câu hỏi tự do và diễn đạt câu trả lời tự nhiên. Mô hình nhận lịch sử hội thoại ngắn cùng kết quả từ các công cụ nghiệp vụ của khách sạn; giao diện vẫn cho phép khách gõ bất kỳ câu hỏi nào.

- AI chọn khi nào cần tra cứu chính sách, kiểm tra phòng trống, tính giá hoặc hỏi thêm một dữ kiện. Những kết quả liên quan đến giá, ngày trống và đơn đặt phòng phải lấy từ công cụ server đang dùng trong website.
- Dữ liệu khách sạn đưa vào AI theo từng truy vấn, chỉ gồm phần cần thiết. Không đưa khóa API, cookie, OTP hay dữ liệu cá nhân của khách khác vào ngữ cảnh.
- Lưu khóa nhà cung cấp trong biến môi trường ở server. Bộ điều phối dùng adapter để có thể đổi mô hình mà không sửa giao diện hay logic đặt phòng.
- Nếu AI không chắc hoặc công cụ lỗi, trả lời ngắn gọn rằng chưa xác nhận được thông tin và đưa lựa chọn liên hệ lễ tân. Không tự tạo giá hoặc hứa còn phòng.
- Bắt đầu bằng bản thử nghiệm chỉ đọc: hỏi đáp, kiểm tra phòng trống và gợi ý link tìm phòng. Chỉ cho AI hỗ trợ thay đổi đơn khi đã có xác thực, xác nhận rõ của khách và kiểm tra quyền ở server.

## Trải nghiệm hội thoại

- Nhận câu tự nhiên, câu ngắn, lỗi chính tả nhẹ và nhiều ý trong cùng một tin nhắn.
- Trả lời ngắn trước; mở rộng khi khách hỏi thêm. Không lặp lời chào hay đọc danh sách dài nếu chưa cần.
- Khi thiếu thông tin, hỏi một câu làm rõ phù hợp nhất, chẳng hạn ngày ở hoặc số khách; vẫn trả lời phần đã biết trong cùng lượt.
- Giữ ngữ cảnh của vài lượt gần nhất để khách sửa ngày, số người hoặc loại phòng mà không phải nhập lại toàn bộ.
- Đưa nút hành động đúng thời điểm như xem phòng, kiểm tra ngày trống, gọi lễ tân; không biến hội thoại thành menu bắt buộc.
- Cho khách sửa yêu cầu, quay lại bước trước hoặc chuyển sang nhân viên mà không mất thông tin đã cung cấp.

## Nguồn dữ liệu và quyền trả lời

1. **Thông tin cố định**: giờ nhận/trả phòng, địa chỉ, tiện ích, chính sách đã được khách sạn xác nhận; quản trị nội dung từ cấu hình hoặc bảng phù hợp thay vì hard-code trong controller.
2. **Thông tin phòng**: loại phòng, sức chứa, tiện ích và giá hiện hành lấy từ `room_types`, `price_settings` cùng quy tắc tính giá hiện tại.
3. **Tình trạng còn phòng**: chỉ kết luận sau khi gọi cùng dịch vụ kiểm tra availability mà luồng đặt phòng sử dụng. Gửi ngày đến/đi và số khách qua validator hiện hành; không đọc trạng thái phòng chung để suy đoán còn chỗ trong một khoảng ngày.
4. **Đặt phòng cá nhân**: yêu cầu đăng nhập và kiểm tra chủ sở hữu ở server trước khi đọc hoặc thay đổi đơn. Không đưa thông tin khách khác vào prompt hoặc log.
5. **Thông tin chưa xác minh**: nói rõ cần kiểm tra với lễ tân và cung cấp đường chuyển tiếp; không lấy dữ liệu cũ làm chính sách hiện hành.

## Cấu trúc triển khai đề xuất

- Giữ `ChatbotController` làm cổng vào mỏng: xác thực đầu vào, giới hạn tần suất, gọi bộ điều phối và trả JSON đã escape.
- Tách bộ điều phối khỏi controller. Mỗi công cụ nghiệp vụ nhỏ, chỉ đọc dữ liệu cần thiết, có schema đầu vào rõ ràng, quyền truy cập và timeout.
- Tạo lớp truy xuất nội dung cho FAQ/chính sách. Ưu tiên tìm kiếm theo cụm từ và dữ liệu có cấu trúc trước; chỉ dùng vector retrieval khi kho tài liệu đủ lớn.
- Có thể nối mô hình ngôn ngữ qua server adapter để hiểu ý định và tạo câu trả lời. Chỉ truyền đoạn kiến thức tối thiểu và kết quả công cụ; không truyền secrets, session cookie hay toàn bộ lịch sử booking.
- Để mô hình chọn giữa trả lời từ nguồn, gọi công cụ, hỏi làm rõ hoặc chuyển nhân viên. Kết quả của công cụ là nguồn sự thật; mô hình không tự tạo giá và availability.
- Nếu không cấu hình mô hình hoặc nhà cung cấp lỗi, chạy bộ trả lời tra cứu nhỏ và trả lời có giới hạn, không làm hỏng trang đặt phòng.

## An toàn và riêng tư

- Giới hạn message length, loại nội dung không hợp lệ, rate limit theo session/IP có ngưỡng phù hợp và chống lạm dụng.
- Bảo vệ endpoint bằng CSRF nếu dùng session cookie; kiểm tra origin, validate server-side và giữ query có tham số.
- Escape nội dung khi render; không trả HTML do mô hình tự tạo. UI chỉ dùng text và liên kết/nút được ứng dụng tạo.
- Không lưu dữ liệu thẻ, mật khẩu, OTP hoặc giấy tờ. Rút gọn/xóa PII khỏi telemetry; đặt thời hạn lưu lịch sử và chỉ bật lưu dài hạn khi có mục đích, thông báo rõ.
- Tách dữ liệu người dùng bằng quyền server-side. Không coi prompt, ID gửi từ trình duyệt hay lời khẳng định “tôi là chủ đơn” là quyền truy cập.
- Ghi log mã công cụ, độ trễ, kết quả và lỗi kỹ thuật đã loại PII; không ghi raw prompt mặc định.

## Trạng thái UI

- Có trạng thái đang trả lời nhẹ, giữ nội dung hội thoại không nhảy bố cục và khóa gửi trùng trong lúc request đang chạy.
- Retry khi mất mạng; giữ draft; hiển thị lỗi ngay trong vùng hội thoại và giữ nguyên câu đã nhập.
- Hỗ trợ bàn phím, screen reader, mobile, reduced motion và nút chuyển nhân viên rõ ràng.
- Phân biệt câu trả lời đã xác nhận từ dữ liệu với ước tính/gợi ý; hiển thị mốc cập nhật khi thông tin dễ đổi.

## Lộ trình

### Giai đoạn 1 — Sửa độ tin cậy hiện tại

- Loại nội dung giờ check-in, chính sách hủy, hotline và loại phòng hard-code đang không khớp cấu hình thực tế.
- Dùng một nguồn DB/config để trả lời giá, sức chứa và tiện ích; thêm câu hỏi làm rõ ngày/số khách thay vì chỉ đưa hướng dẫn chung.
- Sửa xử lý lỗi DB để không giả làm dữ liệu mặc định là dữ liệu thật.

### Giai đoạn 2 — Công cụ nghiệp vụ

- Tái sử dụng dịch vụ kiểm tra phòng trống và tính giá của booking flow.
- Trả về danh sách lựa chọn có link chứa tiêu chí tìm kiếm đã validate; không tạo booking trước khi khách xác nhận trên luồng hiện tại.
- Chỉ bật truy vấn đơn đặt phòng sau khi có xác thực và kiểm tra quyền sở hữu.

### Giai đoạn 3 — Hội thoại có ngữ cảnh

- Lưu ngữ cảnh ngắn hạn theo session với giới hạn thời gian và số lượt.
- Thêm bộ phân loại ý định/slots nhẹ; cho phép nhiều yêu cầu trong một câu và sửa từng slot.
- Nếu dùng LLM, bổ sung adapter, timeout, budget token, fallback, kiểm soát dữ liệu và nguồn trích dẫn nội bộ.

### Giai đoạn 4 — Đo lường và cải thiện

- Xây bộ câu hỏi kiểm tra từ tình huống thật: hỏi giá, sức chứa, ngày trống, đặt nhiều phòng, sửa ngày, chính sách, câu mơ hồ và prompt injection.
- Đo tỷ lệ trả lời đúng theo DB, tỷ lệ hỏi lại hữu ích, tỷ lệ chuyển nhân viên, thời gian phản hồi và tỷ lệ lỗi; không tối ưu chỉ theo độ dài hội thoại.
- Chạy kiểm thử hồi quy cho thay đổi giá/chính sách và xác minh không có câu trả lời bịa khi tool rỗng hoặc lỗi.

## Tiêu chí nghiệm thu

- Giá, sức chứa, tiện ích, trạng thái phòng và trạng thái đơn khớp nguồn dữ liệu tương ứng.
- Câu hỏi thiếu dữ kiện nhận được câu hỏi bổ sung tự nhiên, không yêu cầu chọn một mẫu cố định.
- Ngày không hợp lệ hoặc phòng hết chỗ không dẫn tới kết luận sai hay tạo booking.
- Người dùng không thể tra cứu đơn của người khác qua chatbot.
- Lỗi mô hình, DB hoặc mạng có phản hồi dễ hiểu, có lối tiếp tục tự phục vụ hoặc liên hệ lễ tân.
- Nội dung hiển thị an toàn, không render HTML tùy ý, không làm lộ PII hoặc secrets.
