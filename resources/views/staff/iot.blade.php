<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test khuôn mặt · Royal Hotel</title>
    <style>
        :root{color-scheme:light;--ink:#17342f;--muted:#536b66;--line:#dbe4df;--green:#176451;--red:#a23a34;--surface:#fff}
        *{box-sizing:border-box}body{margin:0;background:#f3f6f3;color:var(--ink);font:16px/1.5 system-ui,sans-serif}
        a{color:var(--green)}nav{background:#fff;border-bottom:1px solid var(--line);padding:16px max(24px,calc((100vw - 1140px)/2));display:flex;align-items:center;gap:22px;flex-wrap:wrap}nav strong{margin-right:auto}nav a{text-decoration:none;font-size:14px}
        main{max-width:1188px;margin:34px auto;padding:0 24px 48px}.eyebrow{font-size:12px;text-transform:uppercase;color:var(--green);font-weight:700}h1{font-size:34px;line-height:1.2;margin:8px 0}h2{font-size:20px;margin:0 0 12px}p{margin:7px 0 16px}.muted{color:var(--muted)}
        .service-bar{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0}.chip{border:1px solid var(--line);background:#fff;padding:7px 11px;border-radius:6px;font-size:13px}.chip.ok{border-color:#9bc9b5;color:var(--green)}.chip.bad{border-color:#ddb0aa;color:var(--red)}
        .grid{display:grid;grid-template-columns:1.25fr .75fr;gap:22px}.panel{background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:24px;margin-bottom:22px}.tag{display:inline-block;color:var(--green);font-size:12px;font-weight:700;margin-bottom:10px}
        .preview{background:#102b25;aspect-ratio:16/10;display:flex;align-items:center;justify-content:center;margin:16px 0;overflow:hidden;position:relative;border-radius:6px}.preview span{color:#bad2c7;text-align:center;padding:20px}.preview video{width:100%;height:100%;object-fit:cover}.preview video:not([hidden])+span{display:none}
        button,.button{border:1px solid var(--green);border-radius:5px;padding:10px 15px;background:var(--green);color:#fff;font:600 14px system-ui;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none}button.secondary{background:#fff;color:var(--green)}button:disabled{opacity:.45;cursor:default}.actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:14px}
        .status{min-height:44px;font-size:14px;margin:8px 0 0}.error{color:var(--red)}.success{color:var(--green)}
        .metric{display:flex;justify-content:space-between;border-bottom:1px solid var(--line);padding:10px 0}.metric:last-of-type{border-bottom:0}code{background:#edf2ed;padding:2px 5px;border-radius:4px;overflow-wrap:anywhere}
        table{width:100%;border-collapse:collapse;font-size:14px}th,td{text-align:left;padding:11px 8px;border-bottom:1px solid var(--line)}th{color:var(--muted);font-weight:600}.table-wrap{overflow:auto}
        @media(max-width:800px){.grid{grid-template-columns:1fr}h1{font-size:28px}.panel{padding:19px}main{margin-top:24px}}
    </style>
</head>
<body>
<nav aria-label="Điều hướng nhân viên">
    <strong>ROYAL HOTEL <span class="muted">/ Test khuôn mặt</span></strong>
    <a href="{{ route('staff.bookings') }}">Sơ đồ phòng & đặt phòng</a>
    <a href="{{ route('home') }}">Trang chủ</a>
    <form method="post" action="{{ route('internalauth.logout') }}">@csrf<button class="secondary" type="submit">Đăng xuất</button></form>
</nav>
<main id="face-id-app"
      data-recognize-url="{{ route('staff.face-id.recognize') }}"
      data-health-url="{{ route('staff.face-id.health') }}"
      data-sync-url="{{ route('staff.face-id.sync') }}"
      data-full-sync-url="{{ route('staff.face-id.full-sync') }}">
    <div class="eyebrow">Máy tính lễ tân · Raspberry Pi</div>
    <h1>Test khuôn mặt đã đăng ký</h1>
    <p class="muted">Dùng webcam để kiểm tra khách và phòng tương ứng với Face ID đã lưu.</p>
    <div class="service-bar" aria-live="polite">
        <span id="pc-health" class="chip">Dịch vụ PC: đang kiểm tra</span>
        <span id="pi-health" class="chip">Raspberry Pi: đang kiểm tra</span>
        <span id="queue-health" class="chip">Đồng bộ: đang kiểm tra</span>
    </div>
    <div class="grid">
        <section class="panel">
            <span class="tag">NHẬN DIỆN KHUÔN MẶT</span>
            <h2>Webcam lễ tân</h2>
            <div class="preview">
                <video id="laptop-camera" autoplay muted playsinline hidden aria-label="Hình ảnh camera laptop"></video>
                <span>Camera đang tắt</span>
            </div>
            <p id="camera-status" class="status" role="status" aria-live="polite">Bật camera để kiểm tra khuôn mặt.</p>
            <div class="actions">
                <button id="start-camera" type="button">Bật camera</button>
                <button id="test-face" class="secondary" type="button">Test khuôn mặt</button>
                <button id="stop-camera" class="secondary" type="button" disabled>Tắt camera</button>
            </div>
        </section>
        <section class="panel">
            <span class="tag">ĐỒNG BỘ LAN</span>
            <h2>Raspberry Pi · Phòng {{ config('face_id.pi_room_number', '501') }}</h2>
            <div class="metric"><span>Face ID hoạt động</span><strong id="active-count">0</strong></div>
            <div class="metric"><span>Yêu cầu chờ gửi</span><strong id="pending-count">0</strong></div>
            <div class="metric"><span>Camera nhận diện</span><strong>{{ config('iot.pi_camera_model') }}</strong></div>
            <div class="metric"><span>Kết nối camera</span><span>CSI trực tiếp trên Pi</span></div>
            <div class="actions">
                <button id="sync-now" type="button">Retry ngay</button>
                <button id="full-sync" class="secondary" type="button">Full sync</button>
            </div>
            <p id="sync-status" class="status muted" role="status" aria-live="polite"></p>
        </section>
    </div>
    <section class="panel">
        <h2>Khuôn mặt đang hoạt động</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Khách</th><th>CCCD</th><th>Số điện thoại</th><th>Phòng</th></tr></thead>
            <tbody>
            @forelse($profiles as $profile)
                <tr>
                    <td>{{ $profile->guest_name ?: $profile->booking?->customer_name }}</td>
                    <td>{{ $profile->guest_cccd ?: '—' }}</td>
                    <td>{{ $profile->guest_phone ?: '—' }}</td>
                    <td>{{ $profile->room?->room_number ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Chưa có khuôn mặt đang hoạt động.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</main>
<script src="{{ asset('js/iot-camera-test.js') }}?v={{ filemtime(public_path('js/iot-camera-test.js')) }}" defer></script>
</body>
</html>
