# Công tắc yêu cầu dọn phòng 501

## 1. Đấu dây

KCD1 được dùng như công tắc tín hiệu thấp áp, không nối điện lưới hoặc chân 5V:

```text
Raspberry Pi GPIO17 (chân vật lý 11) --- KCD1 --- GND
```

Có thể dùng bất kỳ chân GND nào trên Pi. Chương trình dùng cách đánh số BCM, vì vậy giá trị cấu hình là `17`, không phải `11`.

## 2. Cấu hình máy tính Laravel

Trong `.env` của Laravel:

```dotenv
IOT_DEVICE_API_KEY=mot-khoa-bi-mat-giong-voi-pi
IOT_CLEANING_ROOM_NUMBER=501
```

Máy chủ phải lắng nghe mạng LAN:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Cho phép PHP/cổng TCP 8000 qua Windows Firewall khi Windows hỏi. Xem IPv4 của Wi-Fi bằng `ipconfig`.

## 3. Cấu hình Raspberry Pi

```bash
cd ~/qlkhachsan-main/raspberry-pi
cp .env.example .env
nano .env
```

Đặt địa chỉ IPv4 của máy tính và cùng khóa bí mật với Laravel:

```dotenv
HOTEL_API_BASE_URL=http://172.20.10.4:8000
IOT_DEVICE_API_KEY=mot-khoa-bi-mat-giong-voi-laravel
IOT_CLEANING_ROOM_NUMBER=501
IOT_CLEANING_SWITCH_GPIO=17
```

Cài GPIO Zero và chạy:

```bash
sudo apt update
sudo apt install -y python3-gpiozero
set -a
. ./.env
set +a
python3 cleaning_switch.py
```

Chương trình gửi trạng thái hiện tại ngay khi khởi động. Nếu máy tính hoặc Wi-Fi tạm thời mất kết nối, chương trình tiếp tục thử gửi trạng thái mới nhất.

## 4. Chạy tự động khi Pi bật nguồn

Tạo `/etc/systemd/system/hotel-cleaning-switch.service`:

```ini
[Unit]
Description=Royal Hotel room 501 cleaning switch
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=pi
WorkingDirectory=/home/pi/qlkhachsan-main/raspberry-pi
EnvironmentFile=/home/pi/qlkhachsan-main/raspberry-pi/.env
ExecStart=/usr/bin/python3 /home/pi/qlkhachsan-main/raspberry-pi/cleaning_switch.py
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Nếu tài khoản hoặc vị trí dự án khác, sửa `User`, `WorkingDirectory`, `EnvironmentFile` và `ExecStart`. Sau đó:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now hotel-cleaning-switch
sudo systemctl status hotel-cleaning-switch
journalctl -u hotel-cleaning-switch -f
```
