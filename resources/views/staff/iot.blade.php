@extends('layouts.dashboard')

@section('title', 'Face ID & IoT · Rosaliza Hotel')
@section('page-title', 'Face ID & IoT')

@push('styles')
<style>
    .iot-page {
        max-width: 1440px;
        margin: 0 auto;
        padding: 24px 28px 48px;
        width: 100%;
        box-sizing: border-box;
    }
    .iot-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
    }
    .iot-header__info {
        max-width: 600px;
    }
    .iot-header__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #0071e3;
        margin-bottom: 6px;
    }
    .iot-header__title {
        font-size: clamp(20px, 2.2vw, 26px);
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px 0;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }
    .iot-header__subtitle {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        line-height: 1.45;
    }
    .iot-header__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .iot-room-select {
        min-width: 260px;
        height: 42px;
        font-size: 13.5px;
        border-radius: 10px !important;
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
    }
    .iot-manage-btn {
        height: 42px;
        padding: 0 18px;
        border-radius: 10px !important;
        font-size: 13.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #000000 !important;
        color: #ffffff !important;
        border: none !important;
        transition: all 0.16s ease;
    }
    .iot-manage-btn:hover:not(:disabled) {
        background: #262626 !important;
        transform: translateY(-1px);
    }
    .iot-manage-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .iot-health {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 22px;
    }
    .iot-chip, .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 5px 12px;
        color: #475569;
        background: #f8fafc;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .iot-chip.ok, .chip.ok {
        border-color: #bbf7d0;
        color: #15803d;
        background: #f0fdf4;
    }
    .iot-chip.bad, .chip.bad {
        border-color: #fecaca;
        color: #b91c1c;
        background: #fef2f2;
    }
    .iot-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    .iot-panel {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        padding: 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .iot-panel__title {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 14px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    /* Compact camera view with Cupertino dark viewport */
    .iot-preview {
        height: 240px;
        max-height: 240px;
        border-radius: 12px;
        background: #090d16;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #94a3b8;
        border: 1px solid #1e293b;
    }
    .iot-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .iot-preview video:not([hidden]) + span {
        display: none;
    }
    .iot-preview-indicator {
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .iot-status, .status {
        min-height: 42px;
        font-size: 12.5px;
        line-height: 1.45;
        padding: 10px 14px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        margin: 14px 0;
        display: flex;
        align-items: center;
    }
    .iot-status.error, .status.error {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
        font-weight: 500;
    }
    .iot-status.success, .status.success {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #166534;
        font-weight: 500;
    }
    .iot-metric {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding: 11px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .iot-metric:last-of-type {
        border-bottom: 0;
    }
    .iot-metric span:first-child {
        color: #64748b;
        font-weight: 500;
        flex-shrink: 0;
    }
    .iot-metric strong, .iot-metric span:last-child {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }
    .iot-metric strong {
        font-variant-numeric: tabular-nums;
        font-size: 14px;
    }
    .iot-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .iot-actions .btn {
        height: 38px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .iot-actions .btn-primary {
        background: #000000 !important;
        border-color: #000000 !important;
        color: #ffffff !important;
    }
    .iot-actions .btn-outline-secondary {
        border-color: #cbd5e1 !important;
        color: #334155 !important;
        background: #ffffff !important;
    }
    .iot-actions .btn-outline-secondary:hover:not(:disabled) {
        background: #f1f5f9 !important;
    }
    /* Active profiles table */
    .iot-table-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    .iot-table {
        margin-bottom: 0;
        font-size: 13px;
    }
    .iot-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 11px 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .iot-table tbody td {
        padding: 12px 16px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .iot-table tbody tr:last-child td {
        border-bottom: 0;
    }
    /* Modal face guest manager */
    .face-guest-layout {
        display: grid;
        grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
        gap: 24px;
    }
    .face-camera-preview {
        height: 220px;
        max-height: 220px;
        border-radius: 12px;
        background: #090d16;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #94a3b8;
        border: 1px solid #1e293b;
    }
    .face-camera-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .face-camera-preview video:not([hidden]) + span {
        display: none;
    }
    .face-guest-list-wrap {
        max-height: 380px;
        overflow: auto;
    }
    .face-window-close {
        width: 14px;
        height: 14px;
        border: 0;
        border-radius: 50%;
        background: #ff5f57;
        color: #7f1d1d;
        padding: 0;
        display: inline-grid;
        place-items: center;
    }
    .face-window-close span {
        opacity: 0;
        font: 700 10px/1 Arial, sans-serif;
    }
    .face-window-close:hover span, .face-window-close:focus-visible span {
        opacity: 1;
    }
    /* Responsive breakpoints */
    @media (max-width: 992px) {
        .iot-grid, .face-guest-layout {
            grid-template-columns: 1fr;
        }
        .iot-page {
            padding: 20px 20px 40px;
        }
    }
    @media (max-width: 768px) {
        .iot-page {
            padding: 16px 14px 40px;
        }
        .iot-header {
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }
        .iot-header__actions {
            flex-direction: column;
            align-items: stretch;
        }
        .iot-room-select {
            width: 100%;
            min-width: 0;
        }
        .iot-manage-btn {
            width: 100%;
            justify-content: center;
        }
        .iot-preview {
            height: 200px;
        }
    }
</style>
@endpush

@section('content')
<main class="iot-page" id="face-id-app"
      data-recognize-url="{{ route('staff.face-id.recognize') }}"
      data-health-url="{{ route('staff.face-id.health') }}"
      data-sync-url="{{ route('staff.face-id.sync') }}"
      data-full-sync-url="{{ route('staff.face-id.full-sync') }}">
    
    {{-- Header with clear spacing below internal topbar --}}
    <div class="iot-header">
        <div class="iot-header__info">
            <div class="iot-header__eyebrow">
                <i class="bi bi-cpu" aria-hidden="true"></i> HỆ THỐNG PHẦN CỨNG &amp; NHẬN DIỆN
            </div>
            <h1 class="iot-header__title">Nhận diện khách lưu trú</h1>
            <p class="iot-header__subtitle">Đăng ký khuôn mặt khi khách đồng ý; bảo mật thông tin nhận diện theo tiêu chuẩn khách sạn.</p>
        </div>
        
        <div class="iot-header__actions">
            <select id="face-room-select" class="form-select iot-room-select" aria-label="Chọn phòng đang lưu trú">
                <option value="">Chọn phòng đang có khách…</option>
                @foreach($rooms as $room)
                    <option value="{{ $room['id'] }}"
                            data-room-id="{{ $room['id'] }}"
                            data-room-number="{{ $room['room_number'] }}"
                            data-booking-id="{{ $room['booking']->id }}"
                            data-customer-name="{{ $room['booking']->customer_name }}"
                            data-customer-phone="{{ $room['booking']->customer_phone }}">
                        Phòng {{ $room['room_number'] }} · {{ $room['booking']->customer_name }}
                    </option>
                @endforeach
            </select>
            <button id="open-face-manager" class="btn iot-manage-btn" type="button" disabled onclick="openFaceGuestManager()">
                <i class="bi bi-person-bounding-box" aria-hidden="true"></i>
                <span>Quản lý Face ID</span>
            </button>
        </div>
    </div>

    {{-- System Health Status Chips --}}
    <div class="iot-health" aria-live="polite">
        <span id="pc-health" class="iot-chip">
            <i class="bi bi-display" aria-hidden="true"></i> Dịch vụ PC: đang kiểm tra
        </span>
        <span id="pi-health" class="iot-chip">
            <i class="bi bi-cpu" aria-hidden="true"></i> Raspberry Pi: đang kiểm tra
        </span>
        <span id="queue-health" class="iot-chip">
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Đồng bộ: đang kiểm tra
        </span>
    </div>

    {{-- Two-Column Operational Panels (Height-Balanced) --}}
    <div class="iot-grid">
        {{-- Card 1: Camera Test --}}
        <section class="iot-panel">
            <div class="iot-panel__title">
                <span><i class="bi bi-camera-video me-1 text-primary" aria-hidden="true"></i> Kiểm tra nhận diện</span>
                <span class="badge text-bg-light border" style="font-weight: 500; font-size: 11.5px;">Trực tiếp</span>
            </div>
            
            <div class="iot-preview mb-3">
                <video id="laptop-camera" autoplay muted playsinline hidden aria-label="Camera kiểm tra khuôn mặt"></video>
                <span class="iot-preview-indicator">
                    <i class="bi bi-camera-video-off" aria-hidden="true"></i> Camera đang tắt
                </span>
            </div>
            
            <p id="camera-status" class="iot-status" role="status" aria-live="polite">
                Chỉ bật camera khi cần kiểm tra tại quầy.
            </p>
            
            <div class="iot-actions">
                <button id="start-camera" class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-camera" aria-hidden="true"></i> Bật camera
                </button>
                <button id="test-face" class="btn btn-primary" type="button">
                    <i class="bi bi-shield-check" aria-hidden="true"></i> Kiểm tra Face ID
                </button>
                <button id="stop-camera" class="btn btn-outline-secondary" type="button" disabled>
                    <i class="bi bi-power" aria-hidden="true"></i> Tắt camera
                </button>
            </div>
        </section>

        {{-- Card 2: IoT Devices & Sync --}}
        <section class="iot-panel">
            <div class="iot-panel__title">
                <span><i class="bi bi-hdd-network me-1 text-primary" aria-hidden="true"></i> Thiết bị tại quầy</span>
                <span class="badge text-bg-light border" style="font-weight: 500; font-size: 11.5px;">Phần cứng IoT</span>
            </div>
            
            <div class="iot-metric">
                <span>Face ID hoạt động</span>
                <strong id="active-count">0</strong>
            </div>
            <div class="iot-metric">
                <span>Yêu cầu đồng bộ chờ xử lý</span>
                <strong id="pending-count">0</strong>
            </div>
            <div class="iot-metric">
                <span>Phòng kết nối Raspberry Pi</span>
                <strong>{{ config('face_id.pi_room_number') }}</strong>
            </div>
            <div class="iot-metric">
                <span>Mẫu camera</span>
                <span>{{ config('iot.pi_camera_model') }}</span>
            </div>
            
            <div class="iot-actions mt-3">
                <button id="sync-now" class="btn btn-primary" type="button">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Đồng bộ lại
                </button>
                <button id="full-sync" class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Đồng bộ toàn bộ
                </button>
            </div>
            
            <p id="sync-status" class="iot-status mt-2 mb-0" role="status" aria-live="polite"></p>
        </section>
    </div>

    {{-- Card 3: Active Face ID Profiles Table --}}
    <section class="iot-panel">
        <div class="iot-panel__title">
            <span><i class="bi bi-person-lines-fill me-1 text-primary" aria-hidden="true"></i> Hồ sơ Face ID đang hoạt động</span>
            <span class="badge text-bg-secondary" style="font-weight: 500; font-size: 11.5px;">{{ count($profiles) }} hồ sơ</span>
        </div>
        
        <div class="table-responsive iot-table-wrapper">
            <table class="table align-middle iot-table">
                <thead>
                    <tr>
                        <th>Khách</th>
                        <th>Phòng</th>
                        <th>Cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($profiles as $profile)
                    <tr>
                        <td class="fw-medium">{{ $profile->guest_name ?: $profile->booking?->customer_name }}</td>
                        <td>
                            <span class="badge text-bg-light border fw-semibold">
                                Phòng {{ $profile->room?->room_number ?? '—' }}
                            </span>
                        </td>
                        <td class="text-secondary small">{{ optional($profile->updated_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-secondary py-4">Chưa có Face ID đang hoạt động.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>

{{-- Modal Face Guest Manager --}}
<div id="face-guest-manager"
     data-profiles-url="{{ route('staff.face-id.profiles.index') }}"
     data-update-url="{{ route('staff.face-id.profiles.update', ['profile' => '__PROFILE__']) }}"
     data-delete-url="{{ route('staff.face-id.profiles.delete', ['profile' => '__PROFILE__']) }}"
     data-create-session-url="{{ route('staff.face-id.sessions.create') }}"
     data-sample-url="{{ route('staff.face-id.samples.store', ['sessionId' => '__SESSION__']) }}"
     data-cancel-url="{{ route('staff.face-id.sessions.cancel', ['sessionId' => '__SESSION__']) }}">
    <div class="modal fade" id="faceGuestModal" tabindex="-1" aria-labelledby="faceGuestModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header border-bottom py-3 px-4">
                    <button class="face-window-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"><span>×</span></button>
                    <div class="flex-grow-1 text-center">
                        <h2 class="modal-title h6 fw-bold mb-0" id="faceGuestModalLabel">Đăng ký Face ID khách</h2>
                        <div class="small text-secondary" id="face-room-label">Chọn phòng đang lưu trú</div>
                    </div>
                    <span style="width:14px" aria-hidden="true"></span>
                </div>
                <div class="modal-body p-4">
                    <div class="face-guest-layout">
                        <section>
                            <h3 class="h6 fw-bold mb-3" id="face-form-title">Thêm khách và khuôn mặt</h3>
                            <label class="form-label small fw-medium text-secondary" for="face-guest-name">Họ tên khách</label>
                            <input class="form-control mb-3" id="face-guest-name" maxlength="255" autocomplete="name" placeholder="Ví dụ: Nguyễn Văn A">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium text-secondary" for="face-guest-cccd">CCCD (không bắt buộc)</label>
                                    <input class="form-control" id="face-guest-cccd" maxlength="12" inputmode="numeric" autocomplete="off" placeholder="12 số căn cước">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium text-secondary" for="face-guest-phone">Điện thoại</label>
                                    <input class="form-control" id="face-guest-phone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="09xxxxxxxx">
                                </div>
                            </div>
                            <div id="face-enrollment-controls">
                                <div class="face-camera-preview mb-3">
                                    <video id="face-guest-camera" autoplay muted playsinline hidden aria-label="Camera đăng ký Face ID"></video>
                                    <span>Camera đăng ký đang tắt</span>
                                </div>
                                <label class="form-check mb-3">
                                    <input class="form-check-input" id="face-guest-consent" type="checkbox">
                                    <span class="form-check-label small text-secondary">Khách đã đồng ý lưu Face ID trong thời gian lưu trú.</span>
                                </label>
                                <div class="progress mb-2" style="height:8px; border-radius: 999px;">
                                    <div id="face-sample-progress" class="progress-bar bg-primary" role="progressbar" style="width:0" aria-valuemin="0" aria-valuemax="15" aria-valuenow="0"></div>
                                </div>
                            </div>
                            <p id="face-guest-status" class="face-status small text-secondary mb-3" role="status" aria-live="polite">Điền thông tin và chỉ tiếp tục khi khách đồng ý.</p>
                            <div class="iot-actions">
                                <button class="btn btn-outline-secondary" id="face-start-camera" type="button">Bật camera</button>
                                <button class="btn btn-primary" id="face-register-guest" type="button">Đăng ký khách</button>
                                <button class="btn btn-primary d-none" id="face-save-guest" type="button">Lưu thông tin</button>
                                <button class="btn btn-outline-secondary d-none" id="face-cancel-edit" type="button">Hủy sửa</button>
                                <button class="btn btn-outline-danger" id="face-cancel-enrollment" type="button" disabled>Hủy đăng ký</button>
                                <button class="btn btn-outline-secondary" id="face-stop-camera" type="button" disabled>Tắt camera</button>
                            </div>
                        </section>
                        <section>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 fw-bold mb-0">Khách đã đăng ký</h3>
                                <span class="badge text-bg-secondary" id="face-guest-count">0 khách</span>
                            </div>
                            <div class="face-guest-list-wrap border rounded-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Khách</th>
                                            <th>CCCD</th>
                                            <th>Điện thoại</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="face-guest-list">
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary py-4">Chưa có dữ liệu.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const select = document.getElementById('face-room-select');
    const open = document.getElementById('open-face-manager');
    const selectedRoom = () => {
        const option = select?.selectedOptions[0];
        if (!option?.value) return null;
        return {
            id: option.dataset.roomId,
            active_booking_id: option.dataset.bookingId,
            room_number: option.dataset.roomNumber,
            customer_name: option.dataset.customerName,
            customer_phone: option.dataset.customerPhone,
        };
    };
    window.getSelectedFaceGuestRoom = selectedRoom;
    select?.addEventListener('change', () => { open.disabled = !selectedRoom(); });
})();
</script>
<script src="{{ asset('js/iot-camera-test.js') }}?v={{ filemtime(public_path('js/iot-camera-test.js')) }}" defer></script>
<script src="{{ asset('js/face-guest-manager.js') }}?v={{ filemtime(public_path('js/face-guest-manager.js')) }}" defer></script>
@endpush
