# Royal Hotel — Kế hoạch thiết kế trang quản trị

## 1. Mục tiêu

Thiết kế hệ quản trị rõ ràng, nhanh và nhất quán với thương hiệu Royal Hotel. Giao diện ưu tiên khả năng quét dữ liệu, thao tác ít bước, trạng thái dễ nhận biết và hoạt động tốt trên laptop lẫn máy tính bảng.

## 2. Nguyên tắc giao diện

- Dùng Inter, nền trắng ấm `#F9F9F9`, xanh Royal `#779BC1`, xanh nhạt `#CBDCEC`, chữ `#151515`.
- Sidebar tối giản, icon có nhãn; header gọn với tìm kiếm, thông báo và tài khoản.
- Card bán kính 16–20px, viền xám xanh nhẹ, hạn chế bóng đổ.
- Bảng dữ liệu là thành phần chính; không biến mọi dữ liệu thành card.
- Một màu trạng thái cho một ý nghĩa: xanh lá hoàn tất, xanh dương đang xử lý, hổ phách chờ, đỏ lỗi hoặc hủy.
- Motion 150–320ms; GSAP chỉ dùng cho chuyển trang, mở panel, số liệu dashboard và trạng thái thay đổi.
- Giữ keyboard focus, nhãn form, tương phản và `prefers-reduced-motion`.

## 3. Kiến trúc thông tin

1. Tổng quan
2. Đặt phòng
3. Phòng và hạng phòng
4. Khách hàng
5. Thanh toán
6. Hủy phòng và hoàn tiền
7. Đánh giá
8. Nhân sự và phân quyền
9. Báo cáo
10. Cấu hình hệ thống

## 4. Shell quản trị

- **Sidebar:** logo mới, điều hướng chính, trạng thái thu gọn, mục đang mở.
- **Topbar:** breadcrumb, tìm kiếm toàn hệ thống, thông báo, hồ sơ và đăng xuất.
- **Workspace:** chiều rộng linh hoạt, khoảng cách 24px, vùng thao tác chính luôn nằm trước thống kê phụ.
- **Command panel:** `Ctrl/Cmd + K` để tìm đặt phòng, khách hoặc phòng nếu nhu cầu thực tế xác nhận cần thiết.

## 5. Kế hoạch từng màn hình

### Dashboard

- KPI: công suất phòng, lượt nhận/trả hôm nay, doanh thu và khoản chờ xử lý.
- Biểu đồ công suất theo thời gian.
- Danh sách hành động cần xử lý, nhận phòng sắp tới và phòng cần dọn.

### Quản lý đặt phòng

- Bảng lọc theo ngày, trạng thái, nguồn thanh toán và khách hàng.
- Drawer chi tiết thay cho chuyển trang khi chỉ xem nhanh.
- Luồng thay đổi trạng thái có xác nhận và lịch sử thao tác.

### Phòng và hạng phòng

- Sơ đồ phòng theo tầng và trạng thái thời gian thực.
- Form hạng phòng gồm giá, sức chứa, tiện nghi và album ảnh.
- Kiểm tra ảnh tối thiểu 1920×1080, chỉ nhận ảnh nội thất phù hợp hạng phòng.

### Khách hàng

- Hồ sơ, lịch sử lưu trú, tổng chi tiêu và ghi chú dịch vụ.
- Che dữ liệu nhạy cảm theo quyền; ghi log khi thay đổi thông tin.

### Thanh toán

- Bảng giao dịch, trạng thái đối soát và phương thức thanh toán.
- Chi tiết hiển thị mã giao dịch, số tiền, booking liên quan và lịch sử callback.
- Không hiển thị secret hoặc raw credential trên giao diện.

### Báo cáo

- Bộ lọc thời gian dùng control native.
- Biểu đồ doanh thu, công suất, hủy phòng và hạng phòng bán tốt.
- Xuất CSV/PDF chỉ khi dữ liệu đã lọc và người dùng có quyền.

### Nhân sự và phân quyền

- Danh sách tài khoản, vai trò, trạng thái và lần đăng nhập gần nhất.
- Ma trận quyền theo nghiệp vụ; thao tác nhạy cảm yêu cầu xác nhận rõ ràng.

## 6. Bộ component

- Button, input, select, date field, badge trạng thái.
- Data table, pagination, filter bar, empty state, skeleton.
- Modal xác nhận, drawer chi tiết, toast duy nhất cho mỗi sự kiện.
- KPI tile, chart container, activity timeline và audit log.

## 7. Lộ trình triển khai

### Giai đoạn 1 — Nền tảng

- Token màu, typography, spacing, logo, sidebar, topbar và responsive shell.
- Chuẩn hóa button, form, bảng, badge và thông báo.

### Giai đoạn 2 — Nghiệp vụ chính

- Dashboard, đặt phòng, sơ đồ phòng, khách hàng và thanh toán.
- Kết nối dữ liệu hiện có, giữ nguyên route và quyền truy cập.

### Giai đoạn 3 — Quản trị nâng cao

- Báo cáo, nhân sự, cấu hình giá, audit log và trạng thái hệ thống.

### Giai đoạn 4 — Kiểm tra

- Responsive, keyboard, quyền truy cập, trạng thái rỗng/lỗi/loading.
- Kiểm tra query, pagination, filter, back/forward và thao tác lặp.
- Chạy build, test nghiệp vụ và kiểm tra trực quan trước khi nghiệm thu.

## 8. Tiêu chí hoàn thành

- Không thay đổi sai logic nghiệp vụ hoặc quyền truy cập.
- Mọi trạng thái tải, rỗng, lỗi và thành công đều có phản hồi rõ.
- Không có lỗi console, tràn ngang hoặc nội dung bị che ở breakpoint hỗ trợ.
- Giao diện đồng nhất với `DESIGN.md` và `DESIGN_REFERENCE.md`.
- Các luồng đặt phòng, thanh toán, phân quyền và báo cáo vượt qua kiểm thử tương ứng.
