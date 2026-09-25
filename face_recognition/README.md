# Hotel Face ID

Module Face ID chạy hoàn toàn trong mạng LAN, dùng YuNet ONNX để phát hiện mặt
và SFace ONNX để tạo embedding. Laravel/MySQL trên máy lễ tân là source of
truth; Raspberry Pi chỉ giữ cache SQLite phục vụ nhận diện tại chỗ.

## Thành phần

- `pc/`: FastAPI nội bộ trên `127.0.0.1:8001`, đăng ký 15 mẫu từ webcam và tạo
  embedding đại diện. Ảnh chỉ tồn tại trong bộ nhớ.
- Laravel: lưu embedding đã mã hóa trong `face_profiles`, lưu yêu cầu bền vững
  trong `face_sync_queue`, retry và full-sync sang Pi.
- `pi/`: FastAPI trên `0.0.0.0:8002`, SQLite, RAM cache, API key và worker nhận
  diện stream ESP32-CAM.

## Chạy trên máy lễ tân Windows

Từ thư mục project:

```powershell
cd face_recognition\pc
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
.\.venv\Scripts\python.exe run_api.py
```

Ở terminal khác:

```powershell
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
php artisan schedule:work
```

Đăng nhập nhân viên tại `http://127.0.0.1:8000/internalauth/login`, sau đó mở
`http://127.0.0.1:8000/staff/iot`.

## Chạy trên Raspberry Pi

Chép nội dung `face_recognition/pi` sang Pi, tạo `.env` từ `.env.example`, dùng
cùng `FACE_API_KEY` với Laravel và sửa `ESP32_CAM_URL`:

```bash
python3 -m venv .venv
.venv/bin/python -m pip install -r requirements.txt
.venv/bin/python run_recognition.py
```

`run_api.py` chỉ chạy API/cache. `run_recognition.py` chạy cả API và worker đọc
ESP32-CAM. Trên PC, sửa `FACE_PI_BASE_URL` trong `.env` thành IP LAN của Pi rồi
chạy `php artisan config:clear`.

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

