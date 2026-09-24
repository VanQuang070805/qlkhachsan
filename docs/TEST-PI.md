# Test Raspberry Pi 4 ARM64 — mốc camera

## 1. Phân biệt thư mục

- Laptop giữ **toàn bộ dự án Laravel** và MySQL.
- Pi chỉ nhận **nội dung bên trong `raspberry-pi/`**, đặt vào `/home/ph10650/hotel-iot/`.
- Không chép `.env` Laravel, `vendor/`, MySQL dump, mật khẩu demo hay toàn bộ web lên Pi.
- ESP32-CAM tiếp tục dùng firmware stream đã nạp. CH340 `/dev/ttyUSB0` là serial, không phải webcam UVC.

Thư mục sau khi chép:

```text
/home/ph10650/hotel-iot/
  main.py
  camera_test.py
  config.py
  requirements.txt
  .env.example
  camera/
    __init__.py
    esp32_camera.py
  tests/
    test_camera.py
    test_http_stream.py
  venv/                  # venv tạo trên Pi, không copy từ Windows
```

## 2. Chép code từ Windows

Trước tiên trên Pi sao lưu file test cũ (nếu có):

```bash
mkdir -p ~/hotel-iot
cd ~/hotel-iot
if [ -f camera_test.py ]; then cp -n camera_test.py camera_test.py.before-iot; fi
```

Trên PowerShell Windows, thay giá trị `IP_CUA_PI` bằng IP thật của Pi (xem bằng `hostname -I` trên Pi):

```powershell
cd 'C:\Tài liệu học\QLDA\qlkhachsan-main'
$piHost = 'IP_CUA_PI'
scp .\raspberry-pi\main.py .\raspberry-pi\camera_test.py .\raspberry-pi\config.py .\raspberry-pi\requirements.txt .\raspberry-pi\.env.example "ph10650@${piHost}:/home/ph10650/hotel-iot/"
scp -r .\raspberry-pi\camera .\raspberry-pi\tests "ph10650@${piHost}:/home/ph10650/hotel-iot/"
ssh "ph10650@$piHost"
```

Cần bật SSH trên Pi; có thể chép bằng USB/SFTP nếu không dùng SSH. Không cần truy cập Pi từ Codex để thực hiện các lệnh này.

## 3. Kiểm tra Python/OpenCV trên Pi

```bash
uname -m
python3 --version
sudo apt update
sudo apt install -y python3-venv python3-opencv python3-numpy
cd ~/hotel-iot
python3 -m venv --system-site-packages venv
source venv/bin/activate
python -m pip install -r requirements.txt
python main.py --check-env
python -m unittest discover -s tests -v
```

`uname -m` phải là `aarch64`. Venv đã có có thể dùng lại nếu nhìn thấy OpenCV của apt. Không cần cài dlib/face_recognition tại mốc này; không cài lẫn `opencv-python` và OpenCV apt vào cùng venv. `yunet_api`/`sface_api` chỉ cho biết có API, chưa chứng minh model đã chạy.

## 4. Đặt IP camera và thử 60 giây

```bash
cd ~/hotel-iot
source venv/bin/activate
test -f .env || cp .env.example .env
nano .env
set -a
. ./.env
set +a
python main.py --headless --seconds 60 --min-frames 100 --report camera-report.json
echo $?
cat camera-report.json
```

Giữ `CAMERA_URL=http://172.20.10.3/stream` nếu IP chưa đổi; nếu camera đổi IP thì cập nhật. `.env` này chỉ là biến shell và không tự được Python tải; cần chạy `set -a; . ./.env; set +a` trong mỗi terminal mới, hoặc dùng `--url`.

Tiêu chí test ban đầu: `frames >= 100`, kích thước đúng stream (hiện tại 320×240), không mất kết nối liên tục. `received_minimum_frames: true` chỉ kiểm tra số frame, không tự kết luận camera ổn định. Báo cáo không chứa ảnh khuôn mặt.

Nếu có màn hình/desktop gắn vào Pi:

```bash
python camera_test.py --seconds 60 --min-frames 100
```

Bấm `q` hoặc Ctrl+C để thoát. Qua SSH dùng `--headless`; không gọi `imshow` trên phiên không có màn hình.

## 5. Kiểm tra tự kết nối lại

Đóng các tab đang xem ESP32 stream trước, rồi:

```bash
python main.py --headless --seconds 300 --min-frames 300 --report reconnect-report.json
```

1. Đợi log bắt đầu đếm frame.
2. Ngắt nguồn ESP32 khoảng 10 giây, giữ Pi chạy.
3. Phải thấy cảnh báo lỗi và retry với khoảng chờ tăng dần, tối đa 15 giây.
4. Cấp nguồn ESP32 lại. Sau khi Wi-Fi khôi phục, phải thấy connection mới và số frame tiếp tục tăng mà không khởi động lại Python.
5. Ctrl+C phải dừng chương trình, giải phóng stream; thời gian dừng có thể chậm vài giây do timeout mạng.

Tiếp tục chạy 10 phút ở mạng ổn định. Nếu mất frame liên tục, kiểm tra nguồn, Wi-Fi và firmware trước khi triển khai nhận diện.

## 6. Xử lý lỗi

| Hiện tượng | Kiểm tra |
|---|---|
| `frames=0`, kết nối timeout | IP ESP32, cùng mạng LAN/hotspot, camera có nguồn; laptop/Pi phải tự truy cập được stream |
| Có web ESP32 nhưng Python không đọc | Endpoint phải trả MJPEG `multipart/x-mixed-replace`, không phải trang HTML; đúng `/stream` và port firmware đang dùng |
| Vừa mở thì ngắt | Đóng trình duyệt/VLC/client khác đang giữ stream ESP32 |
| `No module named cv2` | Cài `python3-opencv`, dùng venv `--system-site-packages` |
| Không hiện cửa sổ | Dùng `--headless` khi SSH; muốn xem hình cần desktop Pi |
| `/dev/ttyUSB0` nhưng không `/dev/video0` | Bình thường với ESP32-CAM-MB CH340; dùng stream Wi-Fi |

## 7. Những mốc chưa triển khai

Sau khi bạn xác nhận camera Pi ổn định mới triển khai tiếp theo thứ tự trong yêu cầu:

1. YuNet (`FaceDetectorYN`) phát hiện mặt và bounding box trên Pi.
2. SFace (`FaceRecognizerSF`) tạo embedding, so sánh local; benchmark độ trễ trên Pi 4 thật. Laptop và Pi phải dùng cùng model/preprocessing/version. Hiện chưa tải model, chưa đo độ chính xác hay độ trễ.
3. SQLite lưu quyền theo guest/room/thời hạn, thu hồi quyền khi đồng bộ và kiểm thử mất mạng.
4. GPIO relay, timeout/cooldown, mặc định khóa khi lỗi.
5. API Laravel/MySQL cho sync, door log, cleaning request; token thiết bị, HTTPS, phân quyền theo phòng, event ID chống gửi lặp.
6. Đăng ký mặt ở lễ tân gắn với check-in/check-out, sau đó nút dọn phòng và giao diện xử lý yêu cầu.

Nguyên tắc: Pi tự nhận diện, kiểm tra phòng + thời gian + active và quyết định mở cửa, không phụ thuộc cloud. Laptop chỉ đồng bộ dữ liệu/quyền. Khi offline, Pi chỉ biết quyền đã sync trước đó; thu hồi mới chưa đến Pi không thể có hiệu lực ngay, cần chính sách thời gian offline rõ ràng ở mốc quyền truy cập. Dữ liệu sinh trắc học sẽ cần mã hóa, giới hạn quyền và thời hạn lưu; nhận diện khuôn mặt đơn thuần chưa chứng minh chống ảnh/video giả.

Tài liệu kỹ thuật dùng cho lựa chọn tiếp theo: [OpenCV YuNet/SFace](https://docs.opencv.org/4.12.0/d0/dd4/tutorial_dnn_face.html). Module camera hiện đọc HTTP có timeout rồi dùng OpenCV giải mã JPEG, tránh phụ thuộc khả năng timeout của từng backend VideoCapture ([OpenCV video flags](https://docs.opencv.org/4.10.0/d4/d15/group__videoio__flags__base.html)).
