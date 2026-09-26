# Test Raspberry Pi 4 với Camera Module Rev 1.3

## 1. Lắp camera

Tắt nguồn Raspberry Pi trước khi cắm hoặc tháo cáp CSI. Cắm Camera Module Rev
1.3 vào cổng camera, mặt tiếp điểm của cáp đúng hướng theo bo mạch Pi, rồi bật
nguồn lại. Mã cảm biến của module này là `ov5647`.

Kiểm tra Raspberry Pi OS đã nhận camera:

```bash
rpicam-hello --list-cameras
rpicam-hello -t 5000
```

Nếu dùng Raspberry Pi OS cũ, lệnh có thể mang tên `libcamera-hello`. Dự án dùng
camera stack hiện hành và Picamera2, không dùng chế độ legacy camera.

## 2. Chép chương trình test camera

Laptop giữ toàn bộ Laravel và MySQL. Pi chỉ cần nội dung trong `raspberry-pi/`,
đặt tại `/home/ph10650/hotel-iot/`.

Trên PowerShell Windows, thay `IP_CUA_PI` bằng IP thật của Pi:

```powershell
cd 'C:\Tài liệu học\QLDA\qlkhachsan-main'
$piHost = 'IP_CUA_PI'
ssh "ph10650@$piHost" "mkdir -p /home/ph10650/hotel-iot"
scp .\raspberry-pi\main.py .\raspberry-pi\camera_test.py .\raspberry-pi\config.py .\raspberry-pi\requirements.txt .\raspberry-pi\.env.example "ph10650@${piHost}:/home/ph10650/hotel-iot/"
scp -r .\raspberry-pi\camera .\raspberry-pi\tests "ph10650@${piHost}:/home/ph10650/hotel-iot/"
ssh "ph10650@$piHost"
```

## 3. Cài Picamera2 và OpenCV

```bash
sudo apt update
sudo apt install -y python3-picamera2 python3-opencv python3-numpy python3-venv
cd ~/hotel-iot
python3 -m venv --system-site-packages venv
source venv/bin/activate
python -m pip install -r requirements.txt
python main.py --check-env
python -m unittest discover -s tests -v
```

Trong kết quả `--check-env`, `picamera2` phải là `true`. Venv bắt buộc có
`--system-site-packages` để dùng Picamera2/libcamera do Raspberry Pi OS cung cấp.

## 4. Test camera trong 60 giây

```bash
cd ~/hotel-iot
source venv/bin/activate
test -f .env || cp .env.example .env
set -a
. ./.env
set +a
python main.py --headless --seconds 60 --min-frames 300 --report camera-report.json
echo $?
cat camera-report.json
```

Mặc định chương trình mở camera index `0` ở 640x480, 15 FPS. Báo cáo không lưu
ảnh khuôn mặt. `received_minimum_frames: true`, kích thước 640x480 và số lỗi thấp
là điều kiện kiểm tra ban đầu. Khi gắn màn hình vào Pi, bỏ `--headless` để xem
preview và bấm `q` để thoát.

Các biến có thể chỉnh trong `.env`:

```dotenv
PI_CAMERA_INDEX=0
PI_CAMERA_WIDTH=640
PI_CAMERA_HEIGHT=480
PI_CAMERA_FRAMERATE=15
PI_CAMERA_WARMUP_SECONDS=1
```

## 5. Chạy dịch vụ nhận diện trên Pi

Chép nội dung `face_recognition/pi/` sang Pi, đặt model YuNet/SFace vào thư mục
`models/`, tạo `.env` từ `.env.example`, rồi chạy:

```bash
cd ~/hotel-face-id
python3 -m venv --system-site-packages .venv
.venv/bin/python -m pip install -r requirements.txt
.venv/bin/python run_recognition.py
```

`run_recognition.py` khởi động API cổng 8002 và worker đọc Camera Module Rev 1.3.
`run_api.py` chỉ khởi động API/cache, không mở camera.

## 6. Xử lý lỗi

| Hiện tượng | Cách kiểm tra |
|---|---|
| `No cameras available` | Tắt nguồn, cắm lại cáp CSI đúng chiều, rồi chạy `rpicam-hello --list-cameras` |
| `No module named picamera2` | Cài `python3-picamera2`; tạo lại venv với `--system-site-packages` |
| Camera đang được sử dụng | Tắt `rpicam-hello`, preview hoặc tiến trình Python khác đang giữ camera |
| Hình tối/mờ | Chỉnh vòng focus thủ công của Camera Module Rev 1.3 và tăng ánh sáng phía trước |
| Không hiện cửa sổ | Dùng `--headless` khi chạy qua SSH |
| FPS thấp | Giữ 640x480 @ 15 FPS; tăng `PROCESS_EVERY_N_FRAMES` cho nhận diện |

Tài liệu camera chính thức: [Raspberry Pi camera software](https://www.raspberrypi.com/documentation/computers/camera_software.html)
và [Picamera2 manual](https://datasheets.raspberrypi.com/camera/picamera2-manual.pdf).
