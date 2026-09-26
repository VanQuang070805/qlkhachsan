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
sudo apt install -y python3-picamera2 python3-opencv python3-numpy python3-gpiozero python3-venv
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

## 5. Nối và test servo

Tắt nguồn Pi và nguồn servo trước khi đấu dây. Với servo ba dây thông dụng:

| Dây servo | Nối vào |
|---|---|
| Signal vàng/cam/trắng | GPIO18, chân vật lý 12 của Pi |
| VCC đỏ | Cực dương nguồn 5V rời |
| GND nâu/đen | Cực âm nguồn 5V rời |
| GND nguồn rời | Nối chung với GND Pi, ví dụ chân vật lý 6 |

Không cấp servo từ chân 3.3V. Nên dùng nguồn 5V rời tối thiểu 2A cho SG90 và
nguồn khỏe hơn theo thông số servo lớn; không nối cực dương nguồn rời vào chân
5V của Pi khi Pi đang dùng nguồn riêng. Có thể đặt tụ 470-1000 uF giữa VCC và
GND gần servo để hạn chế Pi reset do sụt áp.

Cài GPIO Zero và test servo riêng trước:

```bash
sudo apt install -y python3-gpiozero
cd ~/hotel-face-id
.venv/bin/python test_servo.py --pin 18 --closed-angle 0 --open-angle 90
```

Servo phải quay tới 90 độ, giữ 3 giây rồi về 0 độ. Nếu hướng cơ khí ngược, đổi
`SERVO_OPEN_ANGLE=-90`. Không lắp tay servo vào khóa cửa trước khi test đúng
góc để tránh kẹt cơ khí.

Thêm vào `~/hotel-face-id/.env`:

```dotenv
SERVO_ENABLED=true
SERVO_GPIO_PIN=18
SERVO_CLOSED_ANGLE=0
SERVO_OPEN_ANGLE=90
SERVO_HOLD_SECONDS=3
SERVO_COOLDOWN_SECONDS=8
SERVO_DETACH_AFTER_MOVE=true
```

## 6. Chạy dịch vụ nhận diện trên Pi

Chép nội dung `face_recognition/pi/` sang Pi, đặt model YuNet/SFace vào thư mục
`models/`, tạo `.env` từ `.env.example`, rồi chạy:

```bash
cd ~/hotel-face-id
python3 -m venv --system-site-packages .venv
.venv/bin/python -m pip install -r requirements.txt
.venv/bin/python run_recognition.py
```

`run_recognition.py` khởi động API cổng 8002 và worker đọc Camera Module Rev 1.3.
`run_api.py` chỉ khởi động API/cache, không mở camera hoặc servo. Khi
`run_recognition.py` nhận diện đúng khuôn mặt, servo mở một lần, giữ theo
`SERVO_HOLD_SECONDS`, rồi tự về góc đóng.

## 7. Xử lý lỗi

| Hiện tượng | Cách kiểm tra |
|---|---|
| `No cameras available` | Tắt nguồn, cắm lại cáp CSI đúng chiều, rồi chạy `rpicam-hello --list-cameras` |
| `No module named picamera2` | Cài `python3-picamera2`; tạo lại venv với `--system-site-packages` |
| Camera đang được sử dụng | Tắt `rpicam-hello`, preview hoặc tiến trình Python khác đang giữ camera |
| Hình tối/mờ | Chỉnh vòng focus thủ công của Camera Module Rev 1.3 và tăng ánh sáng phía trước |
| Không hiện cửa sổ | Dùng `--headless` khi chạy qua SSH |
| FPS thấp | Giữ 640x480 @ 15 FPS; tăng `PROCESS_EVERY_N_FRAMES` cho nhận diện |
| Servo không quay | Kiểm tra `SERVO_ENABLED=true`, dây signal ở GPIO18 và hai nguồn đã nối chung GND |
| Pi reset khi servo quay | Dùng nguồn 5V rời đủ dòng cho servo, thêm tụ 470-1000 uF gần servo |
| Servo rung hoặc sai góc | Chỉnh `SERVO_MIN_PULSE_WIDTH`, `SERVO_MAX_PULSE_WIDTH` hoặc đổi góc mở/đóng |

Tài liệu camera chính thức: [Raspberry Pi camera software](https://www.raspberrypi.com/documentation/computers/camera_software.html)
và [Picamera2 manual](https://datasheets.raspberrypi.com/camera/picamera2-manual.pdf).
