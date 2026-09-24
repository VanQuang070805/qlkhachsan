<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiểm tra camera & IoT · Royal Hotel</title>
    <style>
        :root{color-scheme:light;--ink:#17342f;--muted:#536b66;--line:#dbe4df;--green:#176451}
        *{box-sizing:border-box}body{margin:0;background:#f3f6f3;color:var(--ink);font:16px/1.6 system-ui,sans-serif}
        a{color:var(--green)}nav{background:white;border-bottom:1px solid var(--line);padding:18px max(24px,calc((100vw - 1140px)/2));display:flex;align-items:center;gap:24px;flex-wrap:wrap}
        nav strong{margin-right:auto}nav a{text-decoration:none;font-size:14px}main{max-width:1188px;margin:42px auto;padding:0 24px 48px}
        .eyebrow{font-size:12px;letter-spacing:.13em;text-transform:uppercase;color:var(--green);font-weight:700}h1{font-size:36px;line-height:1.2;margin:10px 0 14px;letter-spacing:-1px}h2{font-size:21px;margin:0 0 12px}h3{font-size:16px;margin:0 0 8px}p{margin:8px 0 18px}.muted{color:var(--muted)}
        .notice{background:#fff6dc;border:1px solid #ead99c;border-radius:12px;padding:16px 20px;margin:26px 0}.grid{display:grid;grid-template-columns:1.1fr 1fr;gap:22px}.card{background:white;border:1px solid var(--line);border-radius:16px;padding:26px;margin-bottom:22px}.tag{display:inline-block;border-radius:30px;padding:4px 12px;background:#e7f2ec;color:var(--green);font-size:12px;font-weight:650;margin-bottom:15px}
        .preview{background:#132e28;border-radius:12px;min-height:260px;display:flex;align-items:center;justify-content:center;margin:18px 0;overflow:hidden;position:relative}.preview span{color:#bad2c7;text-align:center;padding:20px}.preview video{width:100%;max-height:340px;background:#132e28}.preview video:not([hidden])+span{display:none}
        button,.button{border:1px solid var(--green);border-radius:8px;padding:11px 18px;background:var(--green);color:white;font:600 14px system-ui;cursor:pointer;display:inline-block;text-decoration:none}button.secondary{background:white;color:var(--green)}button:disabled{opacity:.45;cursor:default}.actions{display:flex;gap:10px;flex-wrap:wrap}
        #camera-status{min-height:52px;font-size:14px;margin-top:14px}code{background:#edf2ed;border-radius:4px;padding:2px 5px;overflow-wrap:anywhere}ol{padding-left:22px}li{margin-bottom:10px}.flow{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:20px 0}.flow div{background:#eef4ef;padding:14px 20px;border-radius:10px;flex:1;min-width:170px}.flow small{display:block;color:var(--muted)}table{width:100%;border-collapse:collapse;font-size:14px}th,td{text-align:left;padding:12px 8px;border-bottom:1px solid var(--line);vertical-align:top}th{color:var(--muted);font-weight:500}.table-wrap{overflow:auto}footer{font-size:13px;color:var(--muted);margin-top:20px}
        @media(max-width:760px){.grid{grid-template-columns:1fr}h1{font-size:29px}main{margin-top:28px}.card{padding:20px}nav{gap:14px}}
    </style>
</head>
<body>
<nav aria-label="Điều hướng nhân viên"><strong>ROYAL HOTEL <span class="muted">/ IoT</span></strong><a href="{{ route('staff.bookings') }}">Sơ đồ phòng & đặt phòng</a><a href="{{ route('home') }}">Trang chủ</a><form method="post" action="{{ route('internalauth.logout') }}">@csrf<button class="secondary" type="submit">Đăng xuất</button></form></nav>
<main>
    <div class="eyebrow">Giai đoạn 01 · Kiểm tra camera</div>
    <h1>Chuẩn bị camera trước khi kết nối cửa</h1>
    <p class="muted">Kiểm tra trên máy tính trước, sau đó chạy module camera trên Raspberry Pi.</p>
    <div class="notice"><strong>Trạng thái triển khai:</strong> đã có công cụ kiểm tra camera và tự kết nối lại. Chưa xác nhận stream ESP32 trên Pi; đăng ký khuôn mặt, đồng bộ quyền, lịch sử cửa và nút dọn phòng thuộc các mốc tiếp theo.</div>
    <div class="grid">
        <section class="card">
            <span class="tag">MÁY TÍNH LỄ TÂN</span>
            <h2>Thử camera laptop</h2>
            <p class="muted">Cho phép camera để kiểm tra hình ảnh. Hình chỉ hiển thị trên trình duyệt; thao tác này chưa đăng ký khuôn mặt.</p>
            <div class="preview"><video id="laptop-camera" autoplay muted playsinline hidden aria-label="Hình ảnh camera laptop"></video><span>Camera đang tắt<br><small>Bấm “Bật camera laptop” để bắt đầu</small></span></div>
            <div class="actions"><button id="start-camera" type="button">Bật camera laptop</button><button id="stop-camera" class="secondary" type="button" disabled>Tắt camera</button></div>
            <p id="camera-status" role="status" aria-live="polite">Mở bằng localhost hoặc HTTPS. Không có ảnh nào được lưu.</p>
        </section>
        <section class="card">
            <span class="tag">ESP32-CAM → RASPBERRY PI</span>
            <h2>Kiểm tra stream Wi-Fi</h2>
            <p class="muted">Địa chỉ được cấu hình: <code>{{ $cameraUrl }}</code></p>
            <ol>
                <li>Cấp nguồn ESP32-CAM và kết nối cùng mạng với máy tính/Pi.</li>
                <li>Mở stream để xem hình. Nếu không truy cập được, kiểm tra lại IP và Wi-Fi.</li>
                <li>Đóng tab stream trước khi chạy Python trên Pi để tránh chiếm kết nối camera.</li>
            </ol>
            @if($cameraLinkAllowed)
                <a class="button" href="{{ $cameraUrl }}" target="_blank" rel="noopener noreferrer">Mở stream ESP32-CAM ↗</a>
            @else
                <p>Địa chỉ stream chưa hợp lệ. Kiểm tra IOT_CAMERA_STREAM_URL trong cấu hình máy tính.</p>
            @endif
            <p class="muted" style="font-size:14px;margin-top:18px">Trang này không đo trạng thái Pi. ESP32-CAM-MB qua USB là cổng CH340/UART, không phải webcam <code>/dev/video0</code>.</p>
        </section>
    </div>
    <section class="card">
        <h2>Phân biệt phần chạy trên từng thiết bị</h2>
        <div class="flow"><div><strong>Laptop · Laravel + MySQL</strong><small>Quản lý lưu trú & đăng ký khách</small></div><span aria-hidden="true">→</span><div><strong>Pi · Python + SQLite</strong><small>Nhận diện và kiểm tra quyền tại chỗ</small></div><span aria-hidden="true">→</span><div><strong>GPIO · Relay</strong><small>Mở khóa có thời hạn & cooldown</small></div></div>
        <p class="muted">Đây là kiến trúc đích. Laptop đồng bộ dữ liệu và quyền; Pi sẽ tự quyết định mở cửa. Hiện tại chỉ module camera đã được triển khai.</p>
        <div class="table-wrap"><table><thead><tr><th>Nơi chạy</th><th>Thư mục</th><th>Trách nhiệm</th></tr></thead><tbody>
            <tr><td>Máy tính</td><td><code>app/</code>, <code>resources/</code>, <code>routes/</code>, <code>database/</code></td><td>Web Laravel hiện có, MySQL, trang kiểm tra camera laptop.</td></tr>
            <tr><td>Raspberry Pi</td><td><code>raspberry-pi/</code></td><td>Chỉ chép nội dung thư mục này vào <code>~/hotel-iot</code>. Đọc MJPEG, xử lý timeout và reconnect.</td></tr>
            <tr><td>ESP32-CAM</td><td>Firmware đang nạp trên board</td><td>Phát MJPEG qua Wi-Fi. Giữ nguyên firmware đã chạy được.</td></tr>
        </tbody></table></div>
    </section>
    <section class="card">
        <h2>Trình tự test trên Pi</h2>
        <ol><li>Trong <code>~/hotel-iot</code>: <code>source venv/bin/activate</code>, rồi <code>python main.py --check-env</code>.</li>
        <li>Qua SSH: <code>python main.py --headless --seconds 60 --min-frames 100 --report camera-report.json</code>.</li>
        <li>Chạy tiếp 5 phút, ngắt nguồn ESP32 khoảng 10 giây rồi bật lại. Kiểm tra chương trình tự nhận frame trở lại.</li>
        <li>Chỉ chuyển sang phát hiện khuôn mặt khi camera ổn định. Hướng dẫn cài mới và tiêu chí chi tiết nằm trong <code>docs/TEST-PI.md</code>.</li></ol>
    </section>
    <footer>Mốc tiếp theo dự kiến: YuNet phát hiện mặt → SFace tạo embedding → SQLite & quyền theo phòng → GPIO → API Laravel → đăng ký mặt → nút dọn phòng.</footer>
</main>
<script src="{{ asset('js/iot-camera-test.js') }}" defer></script>
</body>
</html>
