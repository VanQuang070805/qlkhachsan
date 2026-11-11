# Hồ sơ nghiệp vụ Rosaliza Hotel cho Pico

## Mục đích và nguồn sự thật

Pico là trợ lý khách hàng trên website Rosaliza Hotel Hà Nội. Tài liệu công khai được phép đưa vào RAG nằm tại `resources/knowledge/rosaliza-operations.md`. Nội dung ở đó được đối chiếu với luồng đặt phòng, thanh toán, hủy, hoàn tiền, gia hạn, đánh giá và dữ liệu hạng phòng hiện có. Đây là bản mô tả hành vi của hệ thống tại thời điểm cập nhật, không thay thế dữ liệu vận hành theo thời gian thực.

| Câu hỏi của khách | Nguồn trả lời ưu tiên | Giới hạn |
| --- | --- | --- |
| Địa chỉ, quy trình, giờ nhận/trả, điều kiện hủy | Tài liệu nghiệp vụ đã duyệt | Không tự thêm dịch vụ hoặc cam kết ngoại lệ |
| Hạng phòng, sức chứa, giá niêm yết hiện tại | API danh mục phòng đọc từ cơ sở dữ liệu | Giá niêm yết không phải báo giá cố định cho mọi ngày |
| Phòng còn trống, giá kỳ nghỉ | API tra cứu phòng theo ngày, số khách và số phòng | Kết quả có thể thay đổi trước khi khách xác nhận đơn |
| Đơn đặt phòng của khách | API chỉ đọc, có xác thực và giới hạn theo chính khách đó | Không trả đơn của người khác |
| Trò chuyện thường ngày | Gemini trực tiếp | Không biến kiến thức chung thành cam kết của khách sạn |

## Luồng khách hàng

1. Khách tìm phòng bằng ngày nhận, ngày trả, số người lớn, trẻ em và số phòng. Hệ thống kiểm tra thời hạn tìm kiếm, sức chứa, phòng còn trống và giá theo ngày.
2. Khách chọn phòng, nhập thông tin và tạo đơn. Hệ thống kiểm tra lại khả dụng trong giao dịch; kết quả tìm kiếm trước đó chưa giữ phòng.
3. Đơn chờ thanh toán giữ phòng tối đa 10 phút. Khách chọn phương thức đang khả dụng và thanh toán số tiền được hiển thị trên đơn; số tiền cọc mặc định được tính bằng 50% tổng tiền.
4. Khách xem kỳ nghỉ trong tài khoản của mình, gửi yêu cầu hủy hoặc gia hạn nếu trạng thái cho phép. Nhân viên có thẩm quyền xử lý hoàn tiền theo trạng thái thanh toán và điều kiện thời gian.
5. Sau khi hoàn tất trả phòng, khách có thể đánh giá hạng phòng đã ở theo giới hạn của hệ thống.

## Phân quyền chatbot

Pico chỉ gọi công cụ đọc dữ liệu công khai hoặc dữ liệu kỳ nghỉ của chính khách đã xác thực. Công cụ nội bộ dùng khóa giữa các dịch vụ và capability token giới hạn phạm vi. Pico không tạo, sửa, hủy đơn; không xác nhận thanh toán, hoàn tiền, đổi giá hoặc xem dữ liệu nhân viên, Face ID, hồ sơ khách khác. Không yêu cầu khách gửi mật khẩu, OTP, số thẻ hoặc mã thanh toán trong hội thoại.

## Cách trả lời

- Trả lời đúng ý chính trước, dùng ngôn ngữ của khách và giọng tự nhiên. Có thể dí dỏm nhẹ khi chuyện trò; với tiền, lỗi đặt phòng hoặc khiếu nại, ưu tiên rõ ràng và đồng cảm.
- Khi thiếu ngày ở, số khách hoặc số phòng, hỏi đúng dữ kiện còn thiếu. Không lặp lại câu hỏi đã được khách trả lời.
- Nếu công cụ trực tiếp lỗi, nói rõ chưa tra cứu được giá/phòng trống hiện tại. Không suy từ tài liệu tĩnh hoặc ví dụ cũ.
- Không hiển thị tên tài liệu nội bộ, khóa, dữ liệu thô của API hoặc câu trả lời theo mẫu cứng.
- Pico đọc tối đa các lượt hội thoại gần đây từ phiên chat và dùng chúng để hiểu câu hỏi tiếp nối. Nếu tiến trình Node vừa khởi động lại, giao diện gửi lại lịch sử ngắn đã lưu trong tab. Giọng "bro/anh em" chỉ dùng khi khách nói thân mật và chủ đề phù hợp; khi khách bực bội, lo lắng hoặc hỏi về tiền, trả lời nghiêm túc và có hướng xử lý.
- Ngày, thứ và giờ hiện tại lấy từ đồng hồ máy chủ theo múi giờ `Asia/Ho_Chi_Minh`, không suy đoán từ kiến thức của Gemini. Câu hỏi chỉ hỏi ngày giờ được trả lời trực tiếp; các câu hỏi nghiệp vụ vẫn đi qua RAG và công cụ dữ liệu.

## Vận hành và cập nhật

- `npm run start:local` tại gốc dự án khởi chạy ba cổng web, cổng API nội bộ và chatbot Node khi chúng chưa chạy. Endpoint `/health` của chatbot xác nhận tiến trình và cấu hình, còn câu hỏi thử qua `/chatbot/api` xác nhận cả đường đi đến Gemini.
- Sau khi sửa tài liệu công khai, chạy `npm run sync:knowledge --workspace backend` trong `services/royal-ai-chatbot`; sau khi đồng bộ thành công, tăng `knowledgeVersion` trong Laravel để làm mới cache câu trả lời.
- Hạng phòng và giá trong cơ sở dữ liệu hiện có giá rất thấp (ví dụ Phòng Đơn Tiêu Chuẩn 10.000 đồng/đêm). Đây là dữ liệu thật mà API đang trả, nhưng cần người quản trị xác nhận trước khi coi là giá bán thực tế. Pico không tự thay giá.
- Thẻ hạng phòng trên trang chủ lấy giá hiệu lực cho một đêm và sức chứa từ bảng hạng phòng/quy tắc giá; số phòng trống trừ cả phòng đang giữ hoặc đã đặt trong khoảng ngày hiển thị. Dữ liệu được tính lại khi trang được tải, không dùng số liệu cứng.
- Khi chính sách hoặc tính năng thay đổi, cập nhật tài liệu công khai theo code và phê duyệt nghiệp vụ rồi mới đồng bộ lại RAG. Thông tin chưa được xác nhận phải giữ ở trạng thái chưa biết.

## Giới hạn đã biết

Chatbot cần kết nối Internet và hạn mức Gemini. Khi model trả 429/503, hệ thống thử model dự phòng; nếu cả hai không khả dụng, giao diện báo lỗi tạm thời. Các khung hình sprite của Pico có số lượng hữu hạn; hiệu ứng biến đổi có thể chạy mượt trên bộ dựng hình của trình duyệt, nhưng không tương đương 60 khung hình hình vẽ riêng biệt mỗi giây.
