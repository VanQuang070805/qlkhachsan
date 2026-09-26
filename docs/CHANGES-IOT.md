# Thay đổi cho mốc camera

## Máy tính

- `resources/views/staff/iot.blade.php`: trang kiểm tra webcam lễ tân và trạng thái Raspberry Pi.
- `public/js/iot-camera-test.js`: webcam trình duyệt, xử lý từ chối quyền/không có camera, tắt khi rời trang; không lưu/upload ảnh.
- `routes/web.php`: `/staff/iot` bảo vệ bởi auth + vai trò receptionist/admin, không cache.
- `resources/views/layouts/dashboard.blade.php`, `layouts/admin.blade.php`: thêm lối vào Camera & IoT.
- `config/iot.php`, `.env.example`: hiển thị Camera Module Rev 1.3; dùng MySQL phù hợp migration hiện có.
- `artisan82.bat`, `composer82.bat`, `serve82.bat`: bỏ đường dẫn `C:\Users\admin\...` của máy cũ, hỗ trợ PHP XAMPP và đường dẫn dự án có dấu/khoảng trắng.
- `database/seeders/DatabaseSeeder.php`: chuyển dữ liệu giá sang bảng `price_settings`, mật khẩu mẫu từ môi trường, transaction và chặn seed đè dữ liệu hiện có.
- Chuẩn hóa tên `AdminController.php`, `AdminUser.php` để Composer PSR-4 không bỏ qua class.
- `tests/Feature/IotPageTest.php`: quyền truy cập và URL camera an toàn.

## Pi

- `raspberry-pi/config.py`: cấu hình camera index, độ phân giải, FPS và thời gian warm-up.
- `camera/pi_camera.py`: đọc Camera Module Rev 1.3 qua Picamera2, kiểm tra frame, tự khởi tạo lại và dọn camera.
- `main.py`, `camera_test.py`: kiểm tra môi trường, headless/GUI, thời gian test, log, báo cáo JSON, mã thoát.
- `tests/`: kiểm thử cấu hình, frame và khởi tạo lại camera bằng backend giả lập.
- `.env.example`, `requirements.txt`: môi trường riêng cho Pi.

Không tạo file rỗng giả cho face/database/door/buttons: sẽ thêm khi triển khai từng mốc. Không thêm endpoint OPEN_DOOR.

## Kiểm tra tại máy tính ngày 23/09/2026

- PHP 8.2.12, Python 3.10.11, OpenCV 4.10.0, requests 2.32.5.
- Migration và dữ liệu mẫu chạy được trên database mới `hotel_iot_local`.
- Bộ kiểm thử camera cũ dùng ESP32-CAM đã được thay bằng kiểm thử Picamera2 giả lập.
- 5 kiểm thử trang IoT đạt (11 assertions).
- Đã đăng nhập lễ tân bằng trình duyệt, thấy 25 phòng và mở trang Camera & IoT; webcam để người dùng tự cấp quyền/test.
- HTTP smoke test: trang chủ, phòng, đăng nhập, dashboard admin, báo cáo, bảng giá, sơ đồ phòng và trang IoT đều trả 200; JavaScript mới và các file PHP sửa đổi qua kiểm tra cú pháp.
- Camera Module Rev 1.3 cần được xác nhận bằng `rpicam-hello` và test 60 giây trên Pi thật.

Kiểm thử trên Windows chỉ xác nhận logic và không thay thế test Camera Module Rev 1.3 trên Pi thật.
