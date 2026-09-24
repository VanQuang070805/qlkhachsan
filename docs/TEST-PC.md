# Test trên máy tính Windows

## Môi trường đã chuẩn bị trong lần sửa này

- PHP 8.2.12 của XAMPP: `C:\xampp\php\php.exe`.
- Laravel dependencies đã cài bằng `composer install` theo lock file.
- MySQL/MariaDB XAMPP, database riêng **hotel_iot_local**; không import đè `qlkhachsan.sql` và không sửa database khác.
- Có 25 phòng mẫu, tài khoản `reception`, `admin`, `customer`.
- Mật khẩu mẫu được sinh ngẫu nhiên, lưu riêng tại `storage/app/private/local-demo-access.txt` (không commit).
- Web: http://localhost:8000 ; nhân viên: http://localhost:8000/internalauth/login .

## Mở lại sau khi tắt máy

1. Mở XAMPP Control Panel và Start **MySQL**. Apache không cần cho cách chạy PHP server này.
2. Mở PowerShell:

```powershell
cd 'C:\Tài liệu học\QLDA\qlkhachsan-main'
.\serve82.bat
```

3. Giữ terminal đó mở. Truy cập http://localhost:8000/internalauth/login .
4. Đăng nhập `reception`, dùng mật khẩu trong file riêng ở trên. Bấm **Camera & IoT** hoặc mở http://localhost:8000/staff/iot .
5. Bấm **Bật camera laptop**, cho phép camera trong trình duyệt. Bấm **Tắt camera** để dừng. Chuyển tab cũng sẽ tắt camera.
6. Đăng xuất, thử truy cập lại `/staff/iot`: phải yêu cầu đăng nhập. Tài khoản khách không được vào trang này.
7. Kiểm tra sơ đồ phòng, đặt phòng/check-in/check-out hiện có với dữ liệu mẫu. Các chức năng này **chưa** tạo hoặc thu hồi quyền mở cửa trên Pi.

Nếu trình duyệt tích hợp không hỗ trợ quyền camera, mở cùng URL bằng Chrome/Edge. `localhost` dùng được webcam; `http://IP-laptop:8000` trên máy khác thường không được phép dùng `getUserMedia` nếu chưa có HTTPS.

## Kiểm thử tự động phần mới

```powershell
cd 'C:\Tài liệu học\QLDA\qlkhachsan-main'
.\artisan82.bat test --filter=IotPageTest
node --check public/js/iot-camera-test.js
cd raspberry-pi
python main.py --check-env
python -m unittest discover -s tests -v
python main.py --headless --seconds 10 --report camera-report.json
```

Lệnh cuối dùng ESP32 thật, phải cùng mạng với camera. Mã thoát 1 / `frames: 0` nghĩa là chưa nhận được đủ frame, **không** phải kết quả thành công. Unit test dùng MJPEG giả lập chỉ chứng minh cơ chế đọc/reconnect, không thay thế kiểm tra Pi thật.

## Cài trên một máy Windows khác

Cần PHP >= 8.2 với `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, Composer và XAMPP MySQL. Các migration cũ dùng MySQL ENUM nên không đổi database chính sang SQLite.

```powershell
cd 'DUONG_DAN_DU_AN'
.\composer82.bat install
Copy-Item .env.example .env
```

Trong phpMyAdmin tạo **database rỗng riêng** `hotel_iot_local`, charset utf8mb4. Sửa `.env`: thông tin MySQL, `APP_URL=http://localhost:8000`, `LOCAL_DEMO_PASSWORD` là mật khẩu riêng tự chọn. Sau đó:

```powershell
.\artisan82.bat key:generate
.\artisan82.bat migrate
.\artisan82.bat db:seed
.\serve82.bat
```

Seeder chỉ cho phép local/testing và database chưa có người dùng/phòng. Không chạy `migrate:fresh`, không seed vào dữ liệu thật. File `qlkhachsan.sql` là bản cũ, không cần import cho bộ dữ liệu test này.

Nếu cài XAMPP ở chỗ khác, đặt `$env:PHP_BINARY='D:\xampp\php\php.exe'` trước khi chạy `artisan82.bat` / `serve82.bat`; Composer cần PHP trong PATH.

Nếu dùng Apache, DocumentRoot phải trỏ tới thư mục **public/** của Laravel, không trỏ tới gốc dự án. Hiện tại PHP server chỉ bind loopback cho test máy tính. Khi triển khai API LAN ở mốc sau mới cấu hình HTTPS, giới hạn truy cập và token thiết bị.
