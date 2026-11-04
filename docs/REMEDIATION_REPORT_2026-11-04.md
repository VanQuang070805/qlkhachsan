# Báo cáo khắc phục lỗi trước khi triển khai thông báo

**Dự án:** Rosaliza Hotel
**Ngày hoàn tất:** 04/11/2026
**Phạm vi:** Customer, Staff, Admin, dữ liệu dashboard, xác thực, đánh giá, hoàn tiền, người dùng, giá theo dịp và responsive.
**Tính năng thông báo:** Chưa triển khai trong vòng này.

## 1. Kết quả

Các lỗi có thể làm sai số liệu, sai lịch sử nghiệp vụ hoặc gây hỏng thao tác mobile đã được sửa. ReactBits, GSAP, Lenis, Pico và các hiệu ứng hiện có được giữ nguyên.

### Dữ liệu và nghiệp vụ

1. Dashboard chỉ tính số đêm bán từ booking `confirmed`, `checked_in`, `completed` và nhân đúng số phòng trong booking.
2. Mẫu số công suất được lọc theo đúng hạng phòng. ADR và RevPAR không được dựng số khi dữ liệu hiện tại không đủ phân bổ doanh thu của booking nhiều phòng theo từng hạng.
3. Báo cáo booking và bảng giá theo dịp đã có phân trang phía server.
4. Đăng ký đồng thời cùng email được bắt lỗi unique và trả về lỗi validation, không còn lỗi 500.
5. Đánh giá gắn với `booking_id`, khóa booking trong transaction và chặn đánh giá trùng cho cùng một lần lưu trú. Khách vẫn có thể đánh giá cùng hạng phòng ở lần lưu trú khác.
6. Hoàn tiền có bảng `refund_transactions` để lưu booking, người xử lý, số tiền, nhà cung cấp, mã tham chiếu, thời điểm và metadata.
7. Xóa tài khoản chuyển sang soft-delete, thu hồi session và giữ lịch sử booking, review cùng dấu vết quản trị.
8. Giá theo dịp dùng cache lock kết hợp database transaction và row lock. REST API đã dùng đúng trường `status`, kiểm tra ngày và chặn khoảng giá đang bật bị chồng lấn.
9. Nhãn booking, thanh toán, hoàn tiền và trạng thái phòng được gom về `config/statuses.php` cho các màn hình chính.

### UI, responsive và accessibility

1. FAQ mobile cho phép xuống dòng, không còn cắt chữ hoặc tạo scroll ngang.
2. Focus ring bàn phím được khôi phục cho Customer và các portal nội bộ.
3. Touch target chính trên mobile đạt tối thiểu 44 px.
4. Button không submit đã khai báo `type="button"` để tránh submit ngoài ý muốn.
5. Canvas dashboard có role và mô tả đọc màn hình; icon trang trí dùng alt rỗng hoặc `aria-hidden`.
6. Các liên kết footer trỏ đến route hoặc section có thật.
7. Điểm đánh giá 4,9x và diện tích phòng sinh từ ID đã bị xóa. Card chỉ hiển thị điểm đánh giá thật; khi database chưa có review, card ghi rõ “Chưa có đánh giá”. Dữ liệu phụ thay bằng số phòng trống thật.
8. Nội dung minh họa trong mockup không còn dùng tên, số điện thoại và email giả giống dữ liệu thật.
9. Nội dung có dấu gạch ngang kiểu máy đã được viết lại thành câu tự nhiên. Các ký tự còn lại chỉ nằm trong comment kỹ thuật.
10. Các hiệu ứng ReactBits, GSAP, flying button, HoloCard và Pico vẫn hoạt động sau sửa lỗi.

## 2. Thay đổi database

Đã chạy thành công ba migration:

- `2026_11_04_000001_scope_reviews_to_booking.php`
- `2026_11_04_000002_create_refund_transactions_table.php`
- `2026_11_04_000003_add_soft_deletes_to_users.php`

Kiểm tra database thật sau migration:

| Kiểm tra | Kết quả |
|---|---:|
| Users | 3 |
| Rooms | 50 |
| Room types | 5 |
| Bookings | 3 |
| Reviews | 0 |
| Payment logs | 3 |
| Bản ghi mồ côi | 0 |
| Booking sai ngày, số khách hoặc giá | 0 |
| Phòng sai hạng | 0 |
| Review trùng | 0 |
| Khoảng giá active bị chồng | 0 |

## 3. Bằng chứng kiểm thử

- Laravel: **199 test, 1.318 assertion, PASS**.
- Vite production build: **PASS**.
- Blade view cache: **PASS**.
- PHP syntax cho controller, model, migration và config đã sửa: **PASS**.
- `git diff --check`: **PASS**, không còn trailing whitespace hoặc blank line lỗi.
- Browser click-through: **21/21, PASS**.
- Browser console: **0 exception, 0 console error**.
- Customer 375 px: không overflow; FAQ có `scrollWidth = clientWidth`; ảnh phòng tải thật khi section vào viewport.
- Staff 375 px: sidebar, backdrop, filter trạng thái và reset hoạt động.
- Admin 1440 px: đăng nhập, filter ngày, command palette và dashboard hoạt động.
- Báo cáo click-through: `output/playwright/antislop-interactions/report.json`.
- Ảnh Customer mobile: `output/playwright/customer-home-375-fixed.png` và `output/playwright/customer-accommodation-375.png`.

## 4. Giới hạn được xử lý trung thực

Khi lọc dashboard theo hạng phòng, ADR và RevPAR vẫn để trống nếu một booking chứa nhiều hạng phòng. Schema hiện chỉ lưu tổng tiền ở cấp booking, không lưu doanh thu phân bổ cho từng phòng. Tự chia đều sẽ tạo số liệu giả. Muốn hiển thị hai chỉ số này theo hạng phòng, bước tiếp theo phải lưu giá chốt và doanh thu ở từng dòng `booking_rooms`.

Tính năng chuông thông báo chưa được code trong vòng sửa lỗi này. Hệ thống hiện đã đủ ổn định để bắt đầu migration, model, event sau commit, queue và UI notification theo kế hoạch riêng.

## 5. Anti-slop Delivery Gate

### Hard Gate

- R-02 PASS: copy mới không dùng em dash hoặc en dash; kết quả tìm kiếm chỉ còn ký tự trong comment kỹ thuật.
- R-03 PASS: Customer và Staff ở 375 px không có document-level horizontal overflow; FAQ không còn clipping.
- R-17 PASS: điểm đánh giá giả đã bị xóa; dashboard và số phòng lấy từ database.
- R-18 PASS: không thêm testimonial, avatar hoặc danh tính khách giả.
- R-23 PASS: giữ tài sản thương hiệu và Pico do chủ dự án cung cấp; không tự tạo logo hoặc navigation mới.
- R-24 PASS: footer chỉ liên kết đến route hoặc section tồn tại.
- R-25 PASS: focus, metadata chatbot và text chính trên các surface đã audit đạt tương phản đọc được.
- R-26 PASS: click-through xác nhận menu, chat, FAQ, filter, reset và command palette có hành vi thật.
- R-27 PASS: các luồng quan trọng đã audit có validation, empty state hoặc phản hồi trạng thái phù hợp.
- R-28 PASS: FAQ chỉ hỏi vị trí, di chuyển, tiện ích, thanh toán và chính sách khách sạn.
- R-32 PASS: Tab focus hiển thị; drawer và command palette đóng bằng Escape; FAQ dùng button thật.
- R-33 PASS: mọi sửa đổi nằm trong source, migration và test; không dùng script ngoài để vá giao diện runtime.
- R-34 PASS: dự án không có theme toggle bắt buộc nên không tồn tại mode bị vỡ.
- R-35 PASS: đã build production, cache view và chạy 21 thao tác browser thật trên ba portal.
- R-36 PASS: không thêm tuyên bố bảo mật, hiệu năng hoặc tuân thủ không có bằng chứng.
- R-37 PASS: Design Read đã chọn Customer ENERGY 2, RHYTHM 2, MOTION 2; portal nội bộ ENERGY 1, RHYTHM 1, MOTION 1.
- R-38 PASS: tên khách minh họa và rating giả đã bị loại bỏ; dữ liệu vận hành hiển thị từ database.

### Purpose Gate

- R-01 PASS: gradient và glow chỉ phục vụ chất liệu Quiet Luxury, không dùng làm trang trí mặc định cho mọi khối.
- R-04 PASS: Bootstrap icon gắn đúng hành động như lịch, phòng, thanh toán, cảnh báo và đóng.
- R-06 PASS: typography sans kết hợp serif phục vụ nhận diện khách sạn; monospace chỉ dùng mã booking và dữ liệu kỹ thuật.
- R-07 PASS: không thêm background grid hoặc dot pattern chung.
- R-08 PASS: mũi tên chỉ dùng cho điều hướng hoặc thay đổi carousel.
- R-09 PASS: pill được dành cho trạng thái, filter và lựa chọn có chức năng.
- R-10 PASS: glass effect được giới hạn ở dock hoặc overlay cần phân lớp.
- R-12 PASS: shadow phân biệt card, modal và lớp nổi, không phủ lên mọi phần tử.
- R-13 PASS: glow không xuất hiện đồng thời trên card, button, badge và nền.
- R-14 PASS: card dashboard đồng cấp; card phòng dùng card trung tâm và card phụ để thể hiện thứ tự xem.
- R-19 PASS: ReactBits và GSAP dùng cho reveal, carousel, scroll stack và chuyển trạng thái; không thêm engine chuyển động trùng.
- R-22 PASS: ảnh khách sạn, phòng, Hà Nội và Pico đều gắn với nội dung sản phẩm.

### Liveliness

- PASS: dials đã khai báo cho Customer và portal nội bộ.
- PASS: mật độ, nhịp và motion khớp các dials đã chọn.
- PASS: mỗi màn hình có focal point rõ là hero, KPI, bảng vận hành hoặc sơ đồ phòng.
- PASS: whitespace tạo nhịp section và tách nhóm thao tác.
- PASS: màu vàng champagne là accent có chủ đích; màu xanh nội bộ dùng cho trạng thái và hành động.
- PASS: motif Quiet Luxury, góc bo có kiểm soát và chữ serif được lặp lại nhất quán.
- PASS: Design Read đã được khai báo trước vòng sửa.

### Craftsmanship and Quality Locks

- C-1 PASS: mọi thay đổi có lý do nghiệp vụ, dữ liệu hoặc khả năng truy cập.
- C-2 PASS: các control trong click-through đều hoạt động và có nhãn.
- C-3 PASS: không thêm section hoặc nội dung để lấp template.
- C-4 PASS: các breakpoint đã kiểm tra không vỡ layout, drawer và keyboard flow hoạt động.
- C-5 PASS: không giữ rating, testimonial hoặc chỉ số dựng giả.
- R-05 PASS: không thêm cấu trúc landing page mẫu; giữ bố cục riêng của Rosaliza.
- R-11 PASS: radius thay đổi theo button, card, modal và input; không biến toàn bộ UI thành pill.
- R-15 PASS: CTA gọi đúng hành động như “Tìm phòng trống”, “Đặt phòng ngay” và “Áp dụng”.
- R-16 PASS: không thêm buzzword marketing AI.
- R-20 PASS: hình ảnh Hà Nội, nội dung khách sạn, Pico và nghiệp vụ phòng tạo bản sắc riêng.
- R-21 PASS: Customer giữ nhận diện sáng; portal nội bộ không ép dark mode.
- R-29 PASS: palette giữ nhóm trung tính, champagne và màu trạng thái có ngữ nghĩa.
- R-30 PASS: dashboard tham chiếu DashStack nhưng dữ liệu, navigation và nghiệp vụ đã được chuyển thành hệ thống khách sạn.
- R-31 PASS: màu, layout, typography, spacing và motion đều có lý do thương hiệu hoặc vận hành.

**Kết luận Delivery Gate:** PASS trong phạm vi Customer, Staff, Admin và các luồng đã audit.
