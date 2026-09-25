<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Face ID & IoT · Royal Hotel</title>
    <style>
        :root{color-scheme:light;--ink:#17342f;--muted:#536b66;--line:#dbe4df;--green:#176451;--red:#a23a34;--surface:#fff}
        *{box-sizing:border-box}body{margin:0;background:#f3f6f3;color:var(--ink);font:16px/1.5 system-ui,sans-serif}
        a{color:var(--green)}nav{background:#fff;border-bottom:1px solid var(--line);padding:16px max(24px,calc((100vw - 1140px)/2));display:flex;align-items:center;gap:22px;flex-wrap:wrap}nav strong{margin-right:auto}nav a{text-decoration:none;font-size:14px}
        main{max-width:1188px;margin:34px auto;padding:0 24px 48px}.eyebrow{font-size:12px;text-transform:uppercase;color:var(--green);font-weight:700}h1{font-size:34px;line-height:1.2;margin:8px 0}h2{font-size:20px;margin:0 0 12px}p{margin:7px 0 16px}.muted{color:var(--muted)}
        .service-bar{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0}.chip{border:1px solid var(--line);background:#fff;padding:7px 11px;border-radius:6px;font-size:13px}.chip.ok{border-color:#9bc9b5;color:var(--green)}.chip.bad{border-color:#ddb0aa;color:var(--red)}
        .grid{display:grid;grid-template-columns:1.25fr .75fr;gap:22px}.panel{background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:24px;margin-bottom:22px}.tag{display:inline-block;color:var(--green);font-size:12px;font-weight:700;margin-bottom:10px}
        .preview{background:#102b25;aspect-ratio:16/10;display:flex;align-items:center;justify-content:center;margin:16px 0;overflow:hidden;position:relative;border-radius:6px}.preview span{color:#bad2c7;text-align:center;padding:20px}.preview video{width:100%;height:100%;object-fit:cover}.preview video:not([hidden])+span{display:none}
        label{display:block;font-size:14px;font-weight:650;margin:12px 0 6px}select{width:100%;padding:10px;border:1px solid #aebdb7;border-radius:5px;background:#fff;font:inherit}.consent{display:flex;gap:9px;align-items:flex-start;font-weight:400}.consent input{margin-top:5px}
        button,.button{border:1px solid var(--green);border-radius:5px;padding:10px 15px;background:var(--green);color:#fff;font:600 14px system-ui;cursor:pointer;display:inline-flex;align-items:center;text-decoration:none}button.secondary{background:#fff;color:var(--green)}button.danger{background:#fff;color:var(--red);border-color:var(--red)}button:disabled{opacity:.45;cursor:default}.actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:14px}
        progress{width:100%;height:12px;margin-top:14px;accent-color:var(--green)}.status{min-height:44px;font-size:14px;margin:8px 0 0}.error{color:var(--red)}.success{color:var(--green)}
        .metric{display:flex;justify-content:space-between;border-bottom:1px solid var(--line);padding:10px 0}.metric:last-of-type{border-bottom:0}code{background:#edf2ed;padding:2px 5px;border-radius:4px;overflow-wrap:anywhere}
        table{width:100%;border-collapse:collapse;font-size:14px}th,td{text-align:left;padding:11px 8px;border-bottom:1px solid var(--line)}th{color:var(--muted);font-weight:600}.badge{font-size:12px;font-weight:700}.badge.yes{color:var(--green)}.badge.no{color:var(--muted)}.table-wrap{overflow:auto}
        @media(max-width:800px){.grid{grid-template-columns:1fr}h1{font-size:28px}.panel{padding:19px}main{margin-top:24px}}
    </style>
</head>
<body>
<nav aria-label="Điều hướng nhân viên">
    <strong>ROYAL HOTEL <span class="muted">/ Face ID</span></strong>
    <a href="{{ route('staff.bookings') }}">Sơ đồ phòng & đặt phòng</a>
    <a href="{{ route('home') }}">Trang chủ</a>
    <form method="post" action="{{ route('internalauth.logout') }}">@csrf<button class="secondary" type="submit">Đăng xuất</button></form>
</nav>
<main id="face-id-app"
      data-create-session="{{ route('staff.face-id.sessions.create') }}"
      data-sample-url="{{ route('staff.face-id.samples.store', ['sessionId' => '__SESSION__']) }}"
      data-cancel-url="{{ route('staff.face-id.sessions.cancel', ['sessionId' => '__SESSION__']) }}"
      data-recognize-url="{{ route('staff.face-id.recognize') }}"
      data-health-url="{{ route('staff.face-id.health') }}"
      data-sync-url="{{ route('staff.face-id.sync') }}"
      data-full-sync-url="{{ route('staff.face-id.full-sync') }}">
    <div class="eyebrow">Máy tính lễ tân · Raspberry Pi</div>
    <h1>Face ID khách đang lưu trú</h1>
    <p class="muted">Đăng ký bằng webcam và theo dõi đồng bộ nội bộ tới Raspberry Pi.</p>
    <div class="service-bar" aria-live="polite">
        <span id="pc-health" class="chip">Dịch vụ PC: đang kiểm tra</span>
        <span id="pi-health" class="chip">Raspberry Pi: đang kiểm tra</span>
        <span id="queue-health" class="chip">Đồng bộ: đang kiểm tra</span>
    </div>
    <div class="grid">
        <section class="panel">
            <span class="tag">ĐĂNG KÝ KHUÔN MẶT</span>
            <h2>Webcam lễ tân</h2>
            <label for="booking-id">Khách đang check-in</label>
            <select id="booking-id">
                <option value="">Chọn khách</option>
                @foreach($bookings as $booking)
                    <option value="{{ $booking->id }}">#{{ $booking->id }} · {{ $booking->customer_name }} · Phòng {{ $booking->rooms->pluck('room_number')->join(', ') ?: 'chưa gán' }} {{ $booking->faceProfile?->active ? '· Đã đăng ký' : '' }}</option>
                @endforeach
            </select>
            <div class="preview"><video id="laptop-camera" autoplay muted playsinline hidden aria-label="Hình ảnh camera laptop"></video><span>Camera đang tắt</span></div>
            <label class="consent"><input id="face-consent" type="checkbox">Khách đồng ý đăng ký Face ID trong thời gian lưu trú.</label>
            <progress id="sample-progress" value="0" max="15"></progress>
            <p id="camera-status" class="status" role="status" aria-live="polite">Chọn khách, bật camera và bắt đầu đăng ký.</p>
            <div class="actions">
                <button id="start-camera" type="button">Bật camera</button>
                <button id="register-face" type="button">Đăng ký Face ID</button>
                <button id="test-face" class="secondary" type="button">Test Face ID</button>
                <button id="cancel-enrollment" class="danger" type="button" disabled>Hủy</button>
                <button id="stop-camera" class="secondary" type="button" disabled>Tắt camera</button>
            </div>
        </section>
        <section class="panel">
            <span class="tag">ĐỒNG BỘ LAN</span>
            <h2>Raspberry Pi</h2>
            <div class="metric"><span>Face ID hoạt động</span><strong id="active-count">0</strong></div>
            <div class="metric"><span>Yêu cầu chờ gửi</span><strong id="pending-count">0</strong></div>
            <div class="metric"><span>Stream ESP32-CAM</span><span><code>{{ $cameraUrl }}</code></span></div>
            <div class="actions">
                <button id="sync-now" type="button">Retry ngay</button>
                <button id="full-sync" class="secondary" type="button">Full sync</button>
                @if($cameraLinkAllowed)<a class="button" href="{{ $cameraUrl }}" target="_blank" rel="noopener noreferrer">Mở stream</a>@endif
            </div>
            <p id="sync-status" class="status muted" role="status" aria-live="polite"></p>
        </section>
    </div>
    <section class="panel">
        <h2>Khách đang check-in</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Booking</th><th>Khách</th><th>Phòng</th><th>Face ID</th></tr></thead>
            <tbody>
            @forelse($bookings as $booking)
                <tr><td>#{{ $booking->id }}</td><td>{{ $booking->customer_name }}</td><td>{{ $booking->rooms->pluck('room_number')->join(', ') ?: '—' }}</td><td><span class="badge {{ $booking->faceProfile?->active ? 'yes' : 'no' }}">{{ $booking->faceProfile?->active ? 'Đã đăng ký' : 'Chưa đăng ký' }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="muted">Không có booking đang check-in.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</main>
<script src="{{ asset('js/iot-camera-test.js') }}?v={{ filemtime(public_path('js/iot-camera-test.js')) }}" defer></script>
</body>
</html>
