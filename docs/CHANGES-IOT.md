# Thay đổi cho mốc camera

## Máy tính

- `resources/views/staff/iot.blade.php`: trang kiểm tra, phân biệt laptop/Pi/ESP32 và tiến độ thật.
- `public/js/iot-camera-test.js`: webcam trình duyệt, xử lý từ chối quyền/không có camera, tắt khi rời trang; không lưu/upload ảnh.
- `routes/web.php`: `/staff/iot` bảo vệ bởi auth + vai trò receptionist/admin, không cache.
- `resources/views/layouts/dashboard.blade.php`, `layouts/admin.blade.php`: thêm lối vào Camera & IoT.
- `config/iot.php`, `.env.example`: URL stream cấu hình; dùng MySQL phù hợp migration hiện có.
- `artisan82.bat`, `composer82.bat`, `serve82.bat`: bỏ đường dẫn `C:\Users\admin\...` của máy cũ, hỗ trợ PHP XAMPP và đường dẫn dự án có dấu/khoảng trắng.
- `database/seeders/DatabaseSeeder.php`: chuyển dữ liệu giá sang bảng `price_settings`, mật khẩu mẫu từ môi trường, transaction và chặn seed đè dữ liệu hiện có.
- Chuẩn hóa tên `AdminController.php`, `AdminUser.php` để Composer PSR-4 không bỏ qua class.
- `tests/Feature/IotPageTest.php`: quyền truy cập và URL camera an toàn.

## Pi

- `raspberry-pi/config.py`: cấu hình/kiểm tra URL và timeout.
- `camera/esp32_camera.py`: đọc MJPEG, giới hạn bộ đệm JPEG, kiểm tra kiểu dữ liệu, giải mã OpenCV, reconnect có backoff, dọn kết nối.
- `main.py`, `camera_test.py`: kiểm tra môi trường, headless/GUI, thời gian test, log, báo cáo JSON, mã thoát.
- `tests/`: kiểm thử parser, timeout, dữ liệu lỗi, ngắt/reconnect và HTTP stream thực với server mô phỏng.
- `.env.example`, `requirements.txt`: môi trường riêng cho Pi.

Không tạo file rỗng giả cho face/database/door/buttons: sẽ thêm khi triển khai từng mốc. Không thêm endpoint OPEN_DOOR.

## Kiểm tra tại máy tính ngày 23/09/2026

- PHP 8.2.12, Python 3.10.11, OpenCV 4.10.0, requests 2.32.5.
- Migration và dữ liệu mẫu chạy được trên database mới `hotel_iot_local`.
- 9 kiểm thử Python đạt, bao gồm HTTP MJPEG thật từ server mô phỏng bị ngắt kết nối.
- 5 kiểm thử trang IoT đạt (11 assertions).
- Đã đăng nhập lễ tân bằng trình duyệt, thấy 25 phòng và mở trang Camera & IoT; webcam để người dùng tự cấp quyền/test.
- HTTP smoke test: trang chủ, phòng, đăng nhập, dashboard admin, báo cáo, bảng giá, sơ đồ phòng và trang IoT đều trả 200; JavaScript mới và các file PHP sửa đổi qua kiểm tra cú pháp.
- ESP32 `172.20.10.3/stream`: thử 8 giây, 0 frame, 2 lần timeout; chưa test phần cứng Pi.

Chỉ kết luận phần mềm camera đã qua kiểm thử mô phỏng. Chưa xác nhận milestone camera trên phần cứng, chưa triển khai nhận diện/relay/sync/nút dọn phòng.
