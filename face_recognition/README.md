# Hotel Face ID

Module Face ID chạy hoàn toàn trong mạng LAN, dùng YuNet ONNX để phát hiện mặt
và SFace ONNX để tạo embedding. Laravel/MySQL trên máy lễ tân là source of
truth; Raspberry Pi chỉ giữ cache SQLite phục vụ nhận diện tại chỗ.
Mặc định Laravel chỉ đồng bộ các Face ID của phòng `501`; có thể đổi bằng
`FACE_PI_ROOM_NUMBER` trong `.env`.

## Thành phần

- `pc/`: FastAPI nội bộ trên `127.0.0.1:8001`, đăng ký 15 mẫu từ webcam và tạo
  embedding đại diện. Ảnh chỉ tồn tại trong bộ nhớ.
- Laravel: lưu embedding đã mã hóa trong `face_profiles`, lưu yêu cầu bền vững
  trong `face_sync_queue`, retry và full-sync sang Pi.
- `pi/`: FastAPI trên `0.0.0.0:8002`, SQLite, RAM cache, API key và worker nhận
  diện trực tiếp từ Raspberry Pi Camera Module Rev 1.3 qua Picamera2.

## Chạy trên máy lễ tân Windows

Từ thư mục project:

```powershell
cd face_recognition\pc
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
.\.venv\Scripts\python.exe run_api.py
```

Sau khi virtualenv trên đã được cài một lần, chạy toàn bộ hệ thống từ thư mục
gốc bằng:

```powershell
.\serve82.bat
```

Script tự khởi động và kiểm tra dịch vụ Face ID cổng 8001, bật Laravel scheduler
để retry đồng bộ Pi, rồi mới bật web. Thay đổi Face ID của phòng 501 được gửi
sang Pi ngay sau khi lưu; scheduler sẽ gửi lại nếu Pi từng mất kết nối.

Đăng nhập nhân viên tại `http://127.0.0.1:8000/internalauth/login`, sau đó mở
`http://127.0.0.1:8000/staff/iot`.

## Chạy trên Raspberry Pi

Chép nội dung `face_recognition/pi` sang Pi, tạo `.env` từ `.env.example` và dùng
cùng `FACE_API_KEY` với Laravel. Cài camera/OpenCV từ Raspberry Pi OS trước:

```bash
sudo apt update
sudo apt install -y python3-picamera2 python3-opencv python3-numpy python3-gpiozero python3-venv
rpicam-hello --list-cameras
python3 -m venv --system-site-packages .venv
.venv/bin/python -m pip install -r requirements.txt
.venv/bin/python run_recognition.py
```

`run_api.py` chỉ chạy API/cache. `run_recognition.py` chạy cả API và worker đọc
camera CSI. `rpicam-hello --list-cameras` phải thấy cảm biến `ov5647` của Camera
Module Rev 1.3. Trên PC, sửa `FACE_PI_BASE_URL` trong `.env` thành IP LAN của Pi rồi
chạy `php artisan config:clear`.

Servo cửa mặc định dùng BCM GPIO18 (chân vật lý 12), góc đóng 0 và góc mở 90.
Dùng nguồn 5V rời cho servo, nối chung GND nguồn servo với GND Pi, sau đó đặt
`SERVO_ENABLED=true` trong `.env`. Chạy `.venv/bin/python test_servo.py`
trước khi chạy nhận diện. Sơ đồ nối dây đầy đủ nằm trong `docs/TEST-PI.md`.

## Lệnh test

```powershell
face_recognition\pc\.venv\Scripts\python.exe -m pytest face_recognition\pc\tests -q
face_recognition\pi\.venv\Scripts\python.exe -m pytest face_recognition\pi\tests -q
php artisan test --filter="FaceIdServiceTest|IotPageTest"
php artisan face:sync
php artisan face:sync --full
```

Test webcam YuNet/SFace độc lập:

```powershell
cd face_recognition\pc
.\.venv\Scripts\python.exe test_camera.py
.\.venv\Scripts\python.exe test_recognition.py
```

Nhấn `Q` để thoát. Threshold trong `.env` chỉ là giá trị khởi đầu và cần hiệu
chỉnh bằng dữ liệu thực tế của khách sạn.

## Dữ liệu riêng tư

- Model ONNX, `.env`, virtual environment và database SQLite không được commit.
- Không lưu ảnh gốc mặc định.
- Không log embedding hoặc API key.
- Checkout vô hiệu hóa profile trên PC và tạo DELETE PENDING; scheduler retry
  đến khi Pi xác nhận.
