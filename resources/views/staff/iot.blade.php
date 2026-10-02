@extends('layouts.dashboard')

@section('title', 'Face ID & IoT · Posh Boutique')
@section('page-title', 'Face ID & IoT')

@push('styles')
<style>
    .iot-page { max-width: 1420px; margin: 0 auto; }
    .iot-panel { border: 1px solid var(--internal-border, #e5e7eb); border-radius: 14px; background: var(--internal-surface, #fff); padding: 22px; }
    .iot-health { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0 22px; }
    .iot-chip { border: 1px solid #e2e8f0; border-radius: 999px; padding: 6px 10px; color: #475569; background: #f8fafc; font-size: 12px; }
    .iot-chip.ok { border-color: #bbf7d0; color: #166534; background: #f0fdf4; }
    .iot-chip.bad { border-color: #fecaca; color: #991b1b; background: #fef2f2; }
    .iot-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, .8fr); gap: 16px; }
    .iot-preview { aspect-ratio: 16/9; border-radius: 10px; background: #101820; display: flex; align-items: center; justify-content: center; overflow: hidden; color: #cbd5e1; }
    .iot-preview video { width: 100%; height: 100%; object-fit: cover; }
    .iot-preview video:not([hidden]) + span { display: none; }
    .iot-status { min-height: 40px; font-size: 13px; }
    .iot-status.error { color: #b91c1c; }
    .iot-status.success { color: #15803d; }
    .iot-metric { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
    .iot-metric:last-child { border-bottom: 0; }
    .iot-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .face-guest-layout { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 20px; }
    .face-camera-preview { aspect-ratio: 16/10; border-radius: 10px; background: #101820; display: flex; align-items: center; justify-content: center; overflow: hidden; color: #cbd5e1; }
    .face-camera-preview video { width: 100%; height: 100%; object-fit: cover; }
    .face-camera-preview video:not([hidden]) + span { display: none; }
    .face-guest-list-wrap { max-height: 420px; overflow: auto; }
    .face-table-actions { white-space: nowrap; text-align: right; }
    .face-window-close { width: 14px; height: 14px; border: 0; border-radius: 50%; background: #ff5f57; color: #7f1d1d; padding: 0; display: inline-grid; place-items: center; }
    .face-window-close span { opacity: 0; font: 700 10px/1 Arial, sans-serif; }
    .face-window-close:hover span, .face-window-close:focus-visible span { opacity: 1; }
    @media (max-width: 900px) { .iot-grid, .face-guest-layout { grid-template-columns: 1fr; } .iot-panel { padding: 16px; } }
</style>
@endpush

@section('content')
<main class="iot-page" id="face-id-app"
      data-recognize-url="{{ route('staff.face-id.recognize') }}"
      data-health-url="{{ route('staff.face-id.health') }}"
      data-sync-url="{{ route('staff.face-id.sync') }}"
      data-full-sync-url="{{ route('staff.face-id.full-sync') }}">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="h4 mb-1">Nhận diện khách lưu trú</h1>
            <p class="text-secondary mb-0 small">Đăng ký chỉ sau khi khách đồng ý; dữ liệu khuôn mặt được mã hóa trong database.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <select id="face-room-select" class="form-select" aria-label="Chọn phòng đang lưu trú" style="min-width:240px">
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
            <button id="open-face-manager" class="btn btn-primary" type="button" disabled onclick="openFaceGuestManager()">
                <i class="bi bi-person-bounding-box me-1"></i> Quản lý Face ID
            </button>
        </div>
    </div>

    <div class="iot-health" aria-live="polite">
        <span id="pc-health" class="iot-chip">Dịch vụ PC: đang kiểm tra</span>
        <span id="pi-health" class="iot-chip">Raspberry Pi: đang kiểm tra</span>
        <span id="queue-health" class="iot-chip">Đồng bộ: đang kiểm tra</span>
    </div>

    <div class="iot-grid">
        <section class="iot-panel">
            <h2 class="h6 mb-3">Kiểm tra nhận diện</h2>
            <div class="iot-preview mb-3">
                <video id="laptop-camera" autoplay muted playsinline hidden aria-label="Camera kiểm tra khuôn mặt"></video>
                <span>Camera đang tắt</span>
            </div>
            <p id="camera-status" class="iot-status" role="status" aria-live="polite">Chỉ bật camera khi cần kiểm tra tại quầy.</p>
            <div class="iot-actions">
                <button id="start-camera" class="btn btn-outline-secondary" type="button"><i class="bi bi-camera me-1"></i>Bật camera</button>
                <button id="test-face" class="btn btn-primary" type="button">Kiểm tra Face ID</button>
                <button id="stop-camera" class="btn btn-outline-secondary" type="button" disabled>Tắt camera</button>
            </div>
        </section>

        <section class="iot-panel">
            <h2 class="h6 mb-2">Thiết bị tại quầy</h2>
            <div class="iot-metric"><span>Face ID hoạt động</span><strong id="active-count">0</strong></div>
            <div class="iot-metric"><span>Yêu cầu đồng bộ chờ xử lý</span><strong id="pending-count">0</strong></div>
            <div class="iot-metric"><span>Phòng kết nối Raspberry Pi</span><strong>{{ config('face_id.pi_room_number') }}</strong></div>
            <div class="iot-metric"><span>Camera</span><span>{{ config('iot.pi_camera_model') }}</span></div>
            <div class="iot-actions mt-3">
                <button id="sync-now" class="btn btn-primary" type="button">Đồng bộ lại</button>
                <button id="full-sync" class="btn btn-outline-secondary" type="button">Đồng bộ toàn bộ</button>
            </div>
            <p id="sync-status" class="iot-status mt-2 mb-0" role="status" aria-live="polite"></p>
        </section>
    </div>

    <section class="iot-panel mt-3">
        <h2 class="h6 mb-3">Hồ sơ đang hoạt động</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Khách</th><th>Phòng</th><th>Cập nhật</th></tr></thead>
                <tbody>
                @forelse($profiles as $profile)
                    <tr>
                        <td>{{ $profile->guest_name ?: $profile->booking?->customer_name }}</td>
                        <td>{{ $profile->room?->room_number ?? '—' }}</td>
                        <td>{{ optional($profile->updated_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-secondary py-4">Chưa có Face ID đang hoạt động.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>

<div id="face-guest-manager"
     data-profiles-url="{{ route('staff.face-id.profiles.index') }}"
     data-update-url="{{ route('staff.face-id.profiles.update', ['profile' => '__PROFILE__']) }}"
     data-delete-url="{{ route('staff.face-id.profiles.delete', ['profile' => '__PROFILE__']) }}"
     data-create-session-url="{{ route('staff.face-id.sessions.create') }}"
     data-sample-url="{{ route('staff.face-id.samples.store', ['sessionId' => '__SESSION__']) }}"
     data-cancel-url="{{ route('staff.face-id.sessions.cancel', ['sessionId' => '__SESSION__']) }}">
    <div class="modal fade" id="faceGuestModal" tabindex="-1" aria-labelledby="faceGuestModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
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
                            <label class="form-label" for="face-guest-name">Họ tên khách</label>
                            <input class="form-control mb-3" id="face-guest-name" maxlength="255" autocomplete="name">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6"><label class="form-label" for="face-guest-cccd">CCCD (không bắt buộc)</label><input class="form-control" id="face-guest-cccd" maxlength="12" inputmode="numeric" autocomplete="off"></div>
                                <div class="col-sm-6"><label class="form-label" for="face-guest-phone">Điện thoại</label><input class="form-control" id="face-guest-phone" maxlength="30" inputmode="tel" autocomplete="tel"></div>
                            </div>
                            <div id="face-enrollment-controls">
                                <div class="face-camera-preview mb-3"><video id="face-guest-camera" autoplay muted playsinline hidden aria-label="Camera đăng ký Face ID"></video><span>Camera đăng ký đang tắt</span></div>
                                <label class="form-check mb-3"><input class="form-check-input" id="face-guest-consent" type="checkbox"><span class="form-check-label">Khách đã đồng ý lưu Face ID trong thời gian lưu trú.</span></label>
                                <div class="progress mb-2" style="height:8px"><div id="face-sample-progress" class="progress-bar" role="progressbar" style="width:0" aria-valuemin="0" aria-valuemax="15" aria-valuenow="0"></div></div>
                            </div>
                            <p id="face-guest-status" class="face-status small text-secondary" role="status" aria-live="polite">Điền thông tin và chỉ tiếp tục khi khách đồng ý.</p>
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
                            <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="h6 fw-bold mb-0">Khách đã đăng ký</h3><span class="badge text-bg-secondary" id="face-guest-count">0 khách</span></div>
                            <div class="face-guest-list-wrap border rounded-3">
                                <table class="table table-sm align-middle mb-0"><thead class="table-light sticky-top"><tr><th>Khách</th><th>CCCD</th><th>Điện thoại</th><th class="text-end">Thao tác</th></tr></thead><tbody id="face-guest-list"><tr><td colspan="4" class="text-center text-secondary py-4">Chưa có dữ liệu.</td></tr></tbody></table>
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
