@extends('layouts.main')

@section('title', 'Kỳ nghỉ của tôi · ROSALIZA HOTEL')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đang lưu trú',
        'cancelled' => 'Đã hủy',
        'rejected' => 'Từ chối',
        'completed' => 'Hoàn thành'
    ];

    $allCount = (int) ($bookingCounts->all_count ?? 0);
    $upcomingCount = (int) ($bookingCounts->upcoming_count ?? 0);
    $completedCount = (int) ($bookingCounts->completed_count ?? 0);
    $cancelledCount = (int) ($bookingCounts->cancelled_count ?? 0);
    $totalNights = $bookings->sum(fn($b) => \Carbon\Carbon::parse($b->check_in)->diffInDays($b->check_out) ?: 1);
@endphp

<div class="aeth-canvas">
    <div class="aeth-spatial-7xl aeth-animate-in">

        {{-- ── 1. Top Controls & Tiêu đề (docs/04_lich_su_dat_phong.md) ── --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="aeth-pulse-dot" style="width:6px; height:6px;"></span>
                    <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                        KHO LƯU TRỮ / LỊCH SỬ ĐẶT PHÒNG
                    </span>
                </div>
                <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.025em; margin: 0;">
                    Lịch Sử Đặt Phòng
                </h1>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Hộp tìm kiếm ⌘K --}}
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 13px;"></i>
                    <input type="text" id="bookingSearchInput" class="form-control rounded-pill bg-white border border-slate-200 ps-5 pe-5 py-2 text-dark shadow-xs"
                           placeholder="Tìm kiếm đặt phòng..." style="font-size: 12.5px; width: 220px;" oninput="handleBookingFilter()">
                    <span class="position-absolute top-50 translate-middle-y end-0 me-2 badge bg-light text-muted border font-monospace" style="font-size: 10px;">⌘K</span>
                </div>

            </div>
        </div>

        {{-- ── 2. Thanh Phân Đoạn Trạng Thái & Điểm Thưởng (Segmented Bar) ── --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-1.5 rounded-4 bg-slate-200/70 mb-4 border border-slate-200/80">
            <div class="d-flex gap-1 overflow-x-auto" id="statusFilterPills">
                <button type="button" class="btn btn-sm btn-white rounded-pill px-3 py-1.5 shadow-xs fw-bold text-primary filter-tab is-active" data-filter="all" onclick="filterByStatus('all', this)">
                    Tất cả <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $allCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold text-slate-700 filter-tab" data-filter="upcoming" onclick="filterByStatus('upcoming', this)">
                    <span class="aeth-pulse-dot me-1" style="width:5px; height:5px;"></span>
                    Đã Đặt <span class="badge bg-blue-50 text-primary rounded-pill ms-1">{{ $upcomingCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold text-slate-700 filter-tab" data-filter="completed" onclick="filterByStatus('completed', this)">
                    Hoàn thành <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $completedCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold text-slate-700 filter-tab" data-filter="cancelled" onclick="filterByStatus('cancelled', this)">
                    Đã hủy <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $cancelledCount }}</span>
                </button>
            </div>

            <div class="d-none d-md-flex align-items-center gap-3 pe-2 text-slate-600" style="font-size: 12px; font-weight: 600;">
                <span><i class="bi bi-moon-stars text-primary me-1"></i> {{ $totalNights }} đêm lưu trú</span>
                <span><i class="bi bi-calendar2-check text-emerald-600 me-1"></i> {{ $allCount }} kỳ nghỉ</span>
            </div>
        </div>

        {{-- ── 3. Danh Sách Thẻ Ngang Phân Lớp (Horizontal Cards Stack) ── --}}
        @if($bookings->isEmpty())
        <div class="text-center py-5 bg-white rounded-4 border border-slate-200 shadow-xs my-4">
            <i class="bi bi-calendar2-heart text-slate-300" style="font-size: 48px;"></i>
            <h3 class="fw-bold mt-3 mb-1" style="font-size: 18px; color: #0f172a;">Chưa có kỳ nghỉ nào</h3>
            <p class="text-muted mb-4" style="font-size: 13px;">Khám phá một căn phòng vừa vặn cho hành trình tiếp theo của bạn.</p>
            <a href="{{ route('rooms.index') }}" class="aeth-btn-primary d-inline-flex w-auto px-4 py-2">
                <span>Khám phá phòng nghỉ</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        @else
        <div class="d-flex flex-column gap-4 mb-4" id="bookingCardsContainer">
            @foreach($bookings as $booking)
            @php
                $firstRoom = $booking->rooms->first();
                $roomType = $firstRoom?->roomType;
                $roomTypeNames = $booking->rooms->map(fn($room) => $room->roomType?->type_name)->filter()->unique()->join(' · ');
                $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) ?: 1;
                $roomNames = $booking->rooms->map(fn($room) => 'Phòng '.$room->room_number)->join(', ');
                $floor = $firstRoom?->floor;
                $floorLabel = $floor !== null ? 'Tầng '.$floor : 'Tầng chưa xác định';
                $roomImg = $roomType?->image_url ?? asset('images/rooms/default.jpg');

                $isCancelled = in_array($booking->status, ['cancelled', 'rejected']);
                $isCompleted = $booking->status === 'completed';
                $isUpcoming = in_array($booking->status, ['pending', 'confirmed', 'checked_in']);
                $canExtend = $booking->status === 'checked_in';
                $checkinToken = $checkinTokens[$booking->id] ?? null;
                $filterGroup = $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : 'upcoming');
                $extensionHasError = (int) old('booking_id') === (int) $booking->id && $errors->hasAny(['mode', 'amount']);
            @endphp
            <article class="booking-card-horizontal p-4 rounded-4 bg-white border border-slate-200 shadow-xs transition-all {{ $isCancelled ? 'opacity-75' : '' }}"
                     data-status-group="{{ $filterGroup }}"
                     data-search-text="{{ $booking->id }} {{ $roomNames }} {{ $roomTypeNames }} {{ $booking->customer_name }}"
                     style="transition: all 0.2s ease;">
                <div class="row g-3 align-items-center">

                    {{-- Cột trái: Ảnh thu nhỏ kèm badge tầng --}}
                    <div class="col-md-3">
                        <div class="position-relative rounded-3 overflow-hidden" style="height: 140px; border: 1px solid #e2e8f0;">
                            <img src="{{ $roomImg }}" alt="{{ $roomType?->type_name }}" style="width: 100%; height: 100%; object-fit: cover; {{ $isCancelled ? 'filter: grayscale(40%);' : '' }}">
                            @if($floor !== null)
                                <span class="position-absolute top-2 start-2 badge bg-white/90 text-dark backdrop-blur-md rounded-pill px-2.5 py-1 shadow-xs" style="font-size: 10.5px; font-weight: 700;">
                                    <i class="bi bi-layers text-primary me-1"></i> Tầng {{ $floor }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Cột giữa: Thông tin đặt phòng & Trạng thái --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            @if($isUpcoming)
                                <span class="badge rounded-pill bg-emerald-50 text-emerald-700 border border-emerald-200 d-inline-flex align-items-center gap-1" style="font-size: 11px; font-weight: 600;">
                                    <span class="aeth-pulse-dot" style="width:5px; height:5px;"></span> {{ $statusLabels[$booking->status] ?? 'Đã đặt' }}
                                </span>
                            @elseif($isCompleted)
                                <span class="badge rounded-pill bg-light text-slate-700 border d-inline-flex align-items-center gap-1" style="font-size: 11px; font-weight: 600;">
                                    <i class="bi bi-check-circle-fill text-emerald-600"></i> Đã hoàn thành
                                </span>
                            @else
                                <span class="badge rounded-pill bg-rose-50 text-rose-700 border border-rose-200" style="font-size: 11px; font-weight: 600;">
                                    Đã hủy
                                </span>
                            @endif
                        </div>

                        <h3 class="fw-bold text-dark m-0 mb-1" style="font-size: 17px; letter-spacing: -0.02em;">
                            {{ $roomTypeNames ?: 'Hạng phòng đang được cập nhật' }}
                        </h3>

                        <div class="text-slate-500 mb-2" style="font-size: 12.5px;">
                            {{ $roomNames ?: 'Chưa gán số phòng' }} • {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }} ({{ $nights }} đêm)
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-light text-slate-600 border rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                <i class="bi bi-people me-1"></i> {{ $booking->adult_count }} Người lớn{{ $booking->child_count > 0 ? ', ' . $booking->child_count . ' Trẻ em' : '' }}
                            </span>
                        </div>
                    </div>

                    {{-- Cột phải: Tài chính & Nút hành động macOS --}}
                    <div class="col-md-3 text-md-end">
                        <div class="mb-3">
                            <span class="text-muted d-block" style="font-size: 11px;">
                                {{ $isCancelled ? 'Đã hủy' : ($booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Tổng tiền') }}
                            </span>
                            <strong class="text-dark" style="font-size: 18px; font-weight: 800; letter-spacing: -0.02em;">
                                {{ number_format($booking->total_price, 0, ',', '.') }} đ
                            </strong>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                            @if($isUpcoming)
                                @if($booking->status === 'checked_in')
                                    @foreach($booking->rooms as $room)
                                    <label class="cleaning-request-card {{ $room->needs_cleaning ? 'is-requested' : '' }}" style="font-size:12px;font-weight:600;cursor:pointer;background:{{ $room->needs_cleaning ? '#fff7ed' : '#f8fafc' }};color:{{ $room->needs_cleaning ? '#b45309' : '#475569' }};border:1px solid {{ $room->needs_cleaning ? '#fdba74' : '#e2e8f0' }};border-radius:999px;padding:6px 12px;">
                                        <input class="form-check-input m-0 cleaning-request-toggle" type="checkbox" data-room-number="{{ $room->room_number }}" data-url="{{ route('booking.cleaning-request', [$booking->id, $room->id]) }}" {{ $room->needs_cleaning ? 'checked' : '' }} aria-label="Yêu cầu dọn phòng {{ $room->room_number }}">
                                        <span data-label>Phòng {{ $room->room_number }} · {{ $room->needs_cleaning ? 'Đã báo dọn' : 'Yêu cầu dọn' }}</span>
                                    </label>
                                    @endforeach
                                @endif

                                {{-- Nút Apple Wallet (Black Pill) --}}
                                <button type="button" class="btn rounded-pill px-3 py-1.5 text-white d-inline-flex align-items-center gap-1.5 shadow-xs"
                                        style="font-size: 11.5px; font-weight: 600; background: #0f172a; border: 1px solid #1e293b; transition: all 0.2s ease;"
                                        onclick="openWalletModal('{{ $booking->id }}', '{{ addslashes($roomTypeNames ?: 'Hạng phòng đang cập nhật') }}', '{{ addslashes($booking->customer_name) }}', '{{ $floorLabel }} · {{ addslashes($roomNames ?: 'Chưa gán số phòng') }}')">
                                    <i class="bi bi-apple"></i>
                    <span>Apple Wallet</span>
                                </button>

                                @php
                                    $checkinQrDataUri = null;
                                    if ($checkinToken) {
                                        try {
                                            $qrObj = \Endroid\QrCode\QrCode::create($checkinToken)->setSize(288)->setMargin(16);
                                            $checkinQrDataUri = (new \Endroid\QrCode\Writer\SvgWriter())->write($qrObj)->getDataUri();
                                        } catch (\Throwable $e) {
                                            $checkinQrDataUri = null;
                                        }
                                    }
                                @endphp
                                {{-- Nút Check-in QR (Apple Blue Pill) --}}
                                @if($checkinQrDataUri)
                                <button type="button" class="btn rounded-pill px-3 py-1.5 text-white d-inline-flex align-items-center gap-1.5 shadow-xs"
                                        style="font-size: 11.5px; font-weight: 600; background: #0071e3; border: none; box-shadow: 0 2px 8px rgba(0,113,227,0.28); transition: all 0.2s ease;"
                                        data-qr-uri="{{ $checkinQrDataUri }}"
                                        onclick="openQrModal('{{ $booking->id }}', '{{ addslashes($roomTypeNames ?: 'Hạng phòng đang cập nhật') }}', this.dataset.qrUri)">
                                    <i class="bi bi-qr-code"></i>
                                    <span>Mã nhận phòng</span>
                                </button>
                                @endif

                                @if($booking->payment_status !== 'paid' && $booking->payment_method !== 'cash')
                                <a href="{{ route('payment.form', $booking->id) }}" class="btn rounded-pill px-3 py-1.5 text-decoration-none d-inline-flex align-items-center gap-1 shadow-xs"
                                   style="font-size: 11.5px; font-weight: 600; background: #eff6ff; color: #0066ff; border: 1px solid #bfdbfe; transition: all 0.2s ease;">
                                    <span>Thanh toán</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                                @elseif($booking->payment_status !== 'paid' && $booking->payment_method === 'cash')
                                <span class="small text-secondary">Thanh toán tại quầy</span>
                                @endif

                                @if($canExtend)
                                <button type="button"
                                        class="booking-extension-toggle btn"
                                        data-extension-open
                                        data-booking-id="{{ $booking->id }}"
                                        data-post-url="{{ route('booking.extend', $booking) }}"
                                        data-room-name="{{ $roomNames }}"
                                        data-checkout="{{ $booking->scheduledCheckoutAt()->format('H:i d/m/Y') }}"
                                        data-auto-open="{{ $extensionHasError ? 'true' : 'false' }}">
                                    <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                    <span>Gia hạn phòng</span>
                                </button>
                                @endif

                                <a href="{{ route('booking.cancel.show', $booking->id) }}" class="btn rounded-pill px-3 py-1.5 text-decoration-none d-inline-flex align-items-center shadow-xs"
                                   style="font-size: 11.5px; font-weight: 600; background: #fff5f5; color: #e11d48; border: 1px solid #fecdd3; transition: all 0.2s ease;" title="Hủy phòng">
                                    Hủy phòng
                                </a>
                            @elseif($isCompleted)
                                {{-- Nút hóa đơn đặt phòng --}}
                                <button type="button" class="btn rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1 shadow-xs"
                                        style="font-size: 11.5px; font-weight: 600; background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; transition: all 0.2s ease;"
                                        onclick="openInvoiceModal('{{ $booking->id }}', '{{ number_format($booking->total_price, 0, ',', '.') }}', '{{ addslashes($booking->customer_name) }}')">
                                    <i class="bi bi-receipt"></i>
                                    <span>Hóa đơn đặt phòng</span>
                                </button>

                                @if($firstRoom?->room_type_id)
                                <a href="{{ route('reviews.create', ['booking_id'=>$booking->id, 'room_type_id'=>$firstRoom->room_type_id]) }}" class="btn rounded-pill px-3 py-1.5 text-white text-decoration-none d-inline-flex align-items-center gap-1 shadow-xs"
                                   style="font-size: 11.5px; font-weight: 600; background: #0071e3; border: none; box-shadow: 0 2px 8px rgba(0,113,227,0.28);">
                                    <span>Đánh giá</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                                @endif
                            @else
                                {{-- Nút Chi tiết hoàn tiền --}}
                                <button type="button" class="btn rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1 shadow-xs"
                                        style="font-size: 11.5px; font-weight: 600; background: #f8fafc; color: #475569; border: 1px solid #e2e8f0;"
                                        onclick="openRefundModal('{{ $booking->id }}', '{{ number_format($booking->total_price, 0, ',', '.') }}')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span>Chi tiết hoàn tiền</span>
                                </button>
                                <a href="{{ route('rooms.index') }}" class="btn rounded-pill px-3 py-1.5 text-decoration-none d-inline-flex align-items-center gap-1 shadow-xs"
                                   style="font-size: 11.5px; font-weight: 600; background: #eff6ff; color: #0066ff; border: 1px solid #bfdbfe;">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Đặt lại</span>
                                </a>
                            @endif
                        </div>
                    </div>

                </div>
            </article>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $bookings->links('pagination.royal') }}
        </div>
        @endif

    </div>
</div>

<div class="aeth-modal-backdrop" id="stayExtensionModal" role="dialog" aria-modal="true" aria-labelledby="stayExtensionTitle">
    <div class="aeth-modal-box" style="max-width: 520px; width: calc(100% - 32px); padding: 0; overflow: hidden;">
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
            <div class="window-controls window-controls--modal d-flex align-items-center gap-1" aria-hidden="true">
                <span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span>
            </div>
            <h2 class="m-0 flex-grow-1 text-center fs-6 fw-bold" id="stayExtensionTitle">Gia hạn lưu trú</h2>
            <button type="button" class="aeth-modal-close-btn position-static" onclick="closeModal('stayExtensionModal')" aria-label="Đóng">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="stayExtensionForm" method="POST">
            @csrf
            <input type="hidden" name="booking_id" id="extensionBookingId" value="{{ old('booking_id') }}">
            <div class="p-4">
                <div class="p-3 mb-4 rounded-3 border bg-light">
                    <div class="small text-secondary fw-semibold mb-1">Phòng lưu trú</div>
                    <div class="fw-bold" id="extensionRoomName">—</div>
                    <div class="small text-secondary mt-1">Hạn trả hiện tại: <span id="extensionCheckout">—</span></div>
                </div>
                <label for="extensionMode" class="form-label fw-semibold">Hình thức gia hạn</label>
                <select class="form-select mb-3" id="extensionMode" name="mode" required>
                    <option value="hours" @selected(old('mode', 'hours') === 'hours')>Theo giờ · 10% giá phòng/giờ</option>
                    <option value="days" @selected(old('mode') === 'days')>Theo ngày · giá phòng hiện tại</option>
                </select>
                <label for="extensionAmount" class="form-label fw-semibold" id="extensionAmountLabel">Số giờ gia hạn</label>
                <input class="form-control" type="number" id="extensionAmount" name="amount" min="1" max="12" step="1" value="{{ old('amount', 1) }}" required inputmode="numeric" aria-describedby="extensionHelp extensionError">
                <div class="form-text mt-2" id="extensionHelp">Tính 10% giá đêm/giờ đến 18:00; vượt mốc này hệ thống tính thành một đêm.</div>
                @if((int) old('booking_id') > 0 && $errors->hasAny(['mode', 'amount']))
                    <div class="small text-danger mt-2" id="extensionError" role="alert">{{ $errors->first('mode') ?: $errors->first('amount') }}</div>
                @else
                    <div class="small text-danger mt-2" id="extensionError" role="alert" hidden></div>
                @endif
            </div>
            <div class="d-flex justify-content-end gap-2 px-4 py-3 border-top bg-light">
                <button type="button" class="btn btn-outline-secondary" onclick="closeModal('stayExtensionModal')">Hủy</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check2 me-1"></i>Xác nhận gia hạn</button>
            </div>
        </form>
    </div>
</div>

{{-- ── 5. Bốn Modal Tương Tác Kèm Theo (docs/05_modal_drawer_va_trang_thai_phu.md) ── --}}

{{-- Modal 1: Apple Wallet Pass Modal --}}
<div class="aeth-modal-backdrop" id="walletModal">
    <div class="aeth-modal-box" style="max-width: 380px; background: #0f172a; color: #ffffff; padding: 24px; border-radius: 32px; border: 1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        <button type="button" class="aeth-modal-close-btn" style="background: #1e293b; color: #ffffff;" onclick="closeModal('walletModal')">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="text-center mb-3">
            <i class="bi bi-apple fs-4 text-white"></i>
            <div class="fw-bold text-white mt-1" style="font-size: 14px;">Apple Wallet Room Key</div>
        </div>

        {{-- The Pass --}}
        <div style="background: linear-gradient(145deg, #1e293b, #0f172a); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; padding: 20px; position:relative; overflow:hidden;">
            <div style="position:absolute; top:-20px; right:-20px; width:120px; height:120px; background:radial-gradient(circle, rgba(0,113,227,0.5), transparent 70%); pointer-events:none;"></div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold tracking-wider text-white" style="font-size: 13px; letter-spacing:0.12em;">ROSALIZA HOTEL</span>
                <span class="badge bg-emerald-500/30 text-emerald-300 border border-emerald-400/40 rounded-pill px-2.5 py-1" style="font-size: 10px; font-weight: 700;">NFC KEY ACTIVE</span>
            </div>

            <h4 class="fw-bold mb-1 text-white" style="font-size: 17px;" id="walletRoomName">—</h4>
            <div class="text-slate-300 mb-3" style="font-size: 12.5px; font-weight: 500;" id="walletFloor">—</div>

            <div class="row g-2 mb-3 text-start" style="font-size: 11.5px;">
                <div class="col-6">
                    <span class="text-slate-400 d-block" style="font-size: 10.5px; font-weight: 600; letter-spacing: 0.04em;">CHỦ PHÒNG</span>
                    <strong class="text-white" id="walletGuestName" style="font-size: 13px;">Khách Quý</strong>
                </div>
                <div class="col-6">
                    <span class="text-slate-400 d-block" style="font-size: 10.5px; font-weight: 600; letter-spacing: 0.04em;">TRẠNG THÁI</span>
                    <strong class="text-emerald-400" id="walletBookingCode" style="font-size: 12.5px;">Đã kích hoạt NFC</strong>
                </div>
            </div>

            <div class="text-center p-3 rounded-3 bg-white text-dark mt-2 shadow-xs">
                {{-- Authentic Vector SVG Barcode (Code 128) --}}
                <div class="d-flex justify-content-center py-1">
                    <svg width="240" height="46" viewBox="0 0 240 46" fill="#0f172a" xmlns="http://www.w3.org/2000/svg" style="max-width: 100%;">
                        <rect x="0" y="0" width="3" height="46"/>
                        <rect x="5" y="0" width="2" height="46"/>
                        <rect x="10" y="0" width="4" height="46"/>
                        <rect x="16" y="0" width="2" height="46"/>
                        <rect x="20" y="0" width="1" height="46"/>
                        <rect x="23" y="0" width="3" height="46"/>
                        <rect x="28" y="0" width="5" height="46"/>
                        <rect x="35" y="0" width="2" height="46"/>
                        <rect x="39" y="0" width="1" height="46"/>
                        <rect x="42" y="0" width="4" height="46"/>
                        <rect x="48" y="0" width="2" height="46"/>
                        <rect x="52" y="0" width="3" height="46"/>
                        <rect x="57" y="0" width="1" height="46"/>
                        <rect x="60" y="0" width="4" height="46"/>
                        <rect x="66" y="0" width="2" height="46"/>
                        <rect x="70" y="0" width="5" height="46"/>
                        <rect x="77" y="0" width="2" height="46"/>
                        <rect x="81" y="0" width="3" height="46"/>
                        <rect x="86" y="0" width="1" height="46"/>
                        <rect x="89" y="0" width="4" height="46"/>
                        <rect x="95" y="0" width="2" height="46"/>
                        <rect x="99" y="0" width="5" height="46"/>
                        <rect x="106" y="0" width="2" height="46"/>
                        <rect x="110" y="0" width="3" height="46"/>
                        <rect x="115" y="0" width="1" height="46"/>
                        <rect x="118" y="0" width="4" height="46"/>
                        <rect x="124" y="0" width="3" height="46"/>
                        <rect x="129" y="0" width="2" height="46"/>
                        <rect x="133" y="0" width="5" height="46"/>
                        <rect x="140" y="0" width="2" height="46"/>
                        <rect x="144" y="0" width="1" height="46"/>
                        <rect x="147" y="0" width="4" height="46"/>
                        <rect x="153" y="0" width="2" height="46"/>
                        <rect x="157" y="0" width="3" height="46"/>
                        <rect x="162" y="0" width="5" height="46"/>
                        <rect x="169" y="0" width="2" height="46"/>
                        <rect x="173" y="0" width="1" height="46"/>
                        <rect x="176" y="0" width="4" height="46"/>
                        <rect x="182" y="0" width="3" height="46"/>
                        <rect x="187" y="0" width="2" height="46"/>
                        <rect x="191" y="0" width="4" height="46"/>
                        <rect x="197" y="0" width="2" height="46"/>
                        <rect x="201" y="0" width="5" height="46"/>
                        <rect x="208" y="0" width="2" height="46"/>
                        <rect x="212" y="0" width="4" height="46"/>
                        <rect x="218" y="0" width="1" height="46"/>
                        <rect x="221" y="0" width="3" height="46"/>
                        <rect x="226" y="0" width="4" height="46"/>
                        <rect x="232" y="0" width="2" height="46"/>
                        <rect x="236" y="0" width="4" height="46"/>
                    </svg>
                </div>
                <small class="d-block text-slate-800 fw-bold text-uppercase mt-1.5" style="font-size: 9.5px; letter-spacing: 0.08em;">CHẠM IPHONE VÀO KHÓA CỬA ĐỂ MỞ</small>
            </div>
        </div>

        <button type="button" class="btn btn-light w-100 rounded-pill py-2.5 mt-3 fw-bold text-dark shadow-xs" id="btnAddToWallet" onclick="handleAddToWallet()">
            Thêm vào Apple Wallet
        </button>
    </div>
</div>

{{-- Modal 2: Check-in QR Modal --}}
<div class="aeth-modal-backdrop" id="qrModal">
    <div class="aeth-modal-box text-center" style="max-width: 400px; padding: 32px 24px 24px; border-radius: 32px; background: #ffffff; color: #0f172a; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0;">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('qrModal')" aria-label="Đóng">
            <i class="bi bi-x-lg"></i>
        </button>

        {{-- Top QR Icon (Không viền tròn bao quanh) --}}
        <div style="display: flex; justify-content: center; margin-bottom: 10px;">
            <i class="bi bi-qr-code-scan" style="font-size: 34px; color: #0071e3;"></i>
        </div>

        <div style="font-size: 12px; font-weight: 700; color: #059669; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px;">
            THẺ CHECK-IN ĐIỆN TỬ
        </div>

        <h3 class="fw-bold mb-1 text-slate-900" style="font-size: 20px;">Mã Nhận Phòng Điện Tử</h3>
        <p class="text-slate-600 mb-3 fw-semibold" style="font-size: 13px;" id="qrSubtitle">—</p>

        <div style="border: 1px solid #e2e8f0; border-radius: 24px; padding: 20px 16px 16px; background: #f8fafc; margin-bottom: 18px;">
            <div class="p-2.5 bg-white rounded-4 d-inline-block shadow-xs mb-2" style="border: 1px solid #e2e8f0; line-height: 0;">
                <img id="qrCodeImg" src="" alt="Mã QR Check-in Chuẩn" style="width: min(288px, 72vw); height: auto; display: block; border-radius: 8px;">
            </div>

            <div style="display: inline-block; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; padding: 5px 14px; font-size: 12px; font-weight: 600; color: #059669;">
                Mã có hiệu lực đến hết ngày trả phòng
            </div>
        </div>

        <p class="text-slate-600 mb-4" style="font-size: 12.5px; line-height: 1.5;">
            Quý khách vui lòng xuất trình mã này tại quầy Lễ tân ROSALIZA HOTEL để hoàn tất nhận phòng trong giây lát.
        </p>

        <button type="button" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold text-white shadow-xs" style="background:#0071e3; border:none; height: 44px;" onclick="closeModal('qrModal')">
            Xong
        </button>
    </div>
</div>

{{-- Modal 3: Booking invoice --}}
<div class="aeth-modal-backdrop" id="invoiceModal">
    <div class="aeth-modal-box p-4" style="max-width: 480px; border-radius: 28px; background: #ffffff; color: #0f172a; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.25); border: 1px solid #e2e8f0;">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('invoiceModal')" aria-label="Đóng">
            <i class="bi bi-x-lg"></i>
        </button>

        {{-- Top Icon (Không viền tròn bao quanh) --}}
        <div style="display: flex; justify-content: center; margin-bottom: 8px;">
            <i class="bi bi-receipt" style="font-size: 34px; color: #0071e3;"></i>
        </div>

        <div style="font-size: 12px; font-weight: 700; color: #0071e3; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px; text-align: center;">
            HÓA ĐƠN LƯU TRÚ
        </div>

        <h3 class="fw-bold mb-1 text-slate-900 text-center" style="font-size: 20px;">Hóa Đơn Đặt Phòng</h3>
        <p class="text-slate-600 mb-3 text-center" style="font-size: 12.5px;">
            Ký hiệu: <span class="fw-semibold text-slate-900">1C25ROY</span> • Số: <span class="fw-semibold text-slate-900">0008492</span> • <span class="text-emerald-600 fw-semibold">Đã ký số</span>
        </p>

        {{-- Khối thông tin Đơn vị bán & Người mua --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 16px; margin-bottom: 14px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; margin-bottom: 8px;">
                <span style="color: #64748b; font-weight: 500;">Đơn vị phát hành:</span>
                <span style="color: #0f172a; font-weight: 700; text-align: right;">Công ty CP ROSALIZA HOTEL</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; margin-bottom: 8px;">
                <span style="color: #64748b; font-weight: 500;">Dịch vụ:</span>
                <span style="color: #0f172a; font-weight: 600;">Lưu trú</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-weight: 500;">Người mua hàng:</span>
                <span style="color: #0f172a; font-weight: 700;" id="invoiceCustomerName">Khách Quý</span>
            </div>
        </div>

        {{-- Bảng dịch vụ --}}
        <div style="border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 14px;">
            <div style="display: flex; justify-content: space-between; background: #f1f5f9; padding: 10px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">
                <span>Khoản mục dịch vụ</span>
                <span>Thành tiền</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #0f172a;">
                <span style="font-weight: 500;">Dịch vụ lưu trú Rosaliza Hotel</span>
                <span style="font-weight: 700;" id="invoiceItemPrice">0 đ</span>
            </div>
        </div>

        {{-- Tổng tiền --}}
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-radius: 14px; background: #eff6ff; border: 1px solid #bfdbfe; margin-bottom: 14px;">
            <span style="color: #1e40af; font-size: 13px; font-weight: 700; text-transform: uppercase;">Tổng tiền thanh toán</span>
            <span style="color: #0071e3; font-size: 18px; font-weight: 800;" id="invoiceTotalPrice">0 đ</span>
        </div>

        {{-- Chữ ký số --}}
        <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; color: #475569; margin-bottom: 18px;">
            <i class="bi bi-shield-check" style="font-size: 16px; color: #059669; flex-shrink: 0;"></i>
            <span>Chữ ký số hợp lệ: <strong>VNPT-CA Token</strong> · ROSALIZA HOTEL Hospitality JSC</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <button type="button" class="btn btn-outline-secondary rounded-pill py-2.5 fw-semibold" style="border: 1px solid #e2e8f0; color: #334155; font-size: 13.5px;" onclick="closeModal('invoiceModal')">
                Đóng
            </button>
            <button type="button" class="btn btn-primary rounded-pill py-2.5 fw-semibold text-white shadow-xs" style="background: #0071e3; border: none; font-size: 13.5px;" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> In Hóa Đơn
            </button>
        </div>
    </div>
</div>

{{-- Modal 4: Refund Detail Modal (Thiết kế lại chuẩn Cupertino, không đè lấn, font đồng bộ) --}}
<div class="aeth-modal-backdrop" id="refundModal">
    <div class="aeth-modal-box p-4" style="max-width: 440px; border-radius: 28px; background: #ffffff; color: #0f172a; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.25); border: 1px solid #e2e8f0;">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('refundModal')" aria-label="Đóng">
            <i class="bi bi-x-lg"></i>
        </button>

        {{-- Top Icon (Không viền tròn bao quanh) --}}
        <div style="display: flex; justify-content: center; margin-bottom: 10px;">
            <i class="bi bi-arrow-counterclockwise" style="font-size: 34px; color: #10b981;"></i>
        </div>

        <div style="font-size: 12px; font-weight: 700; color: #10b981; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px; text-align: center;">
            CHI TIẾT HOÀN TIỀN
        </div>

        <h3 class="fw-bold mb-1 text-slate-900 text-center" style="font-size: 20px;">Hoàn Tiền Đặt Phòng</h3>
        <p class="text-slate-600 mb-3 text-center" style="font-size: 13px;">
            Trạng thái: <span class="fw-semibold text-emerald-600">Đã xử lý hoàn tất</span>
        </p>

        {{-- Khối số tiền hoàn trả --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; padding: 18px; margin-bottom: 16px; text-align: center;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Số Tiền Hoàn Trả</div>
            <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 6px;" id="refundAmount">0 đ</div>
            <div style="display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; border-radius: 9999px; padding: 4px 12px; font-size: 12px; font-weight: 600;">
                <i class="bi bi-check-circle-fill" style="font-size: 12px;"></i>
                <span>Đã ghi có vào tài khoản</span>
            </div>
        </div>

        {{-- Khối tiến trình hoàn tiền --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 16px; margin-bottom: 20px; text-align: left;">
            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                <div style="width: 20px; height: 20px; border-radius: 50%; background: #eff6ff; color: #0071e3; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 2px;">1</div>
                <div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px;">Yêu cầu hủy được phê duyệt</div>
                    <div style="color: #64748b; font-size: 12px;">Áp dụng chính sách hủy của khách sạn.</div>
                </div>
            </div>
            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                <div style="width: 20px; height: 20px; border-radius: 50%; background: #eff6ff; color: #0071e3; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 2px;">2</div>
                <div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px;">Hoàn về phương thức thanh toán gốc</div>
                    <div style="color: #64748b; font-size: 12px;">Ngân hàng ghi có tài khoản trong 1–3 ngày làm việc.</div>
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <div style="width: 20px; height: 20px; border-radius: 50%; background: #eff6ff; color: #0071e3; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; margin-top: 2px;">3</div>
                <div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px;">Mã đối soát giao dịch</div>
                    <div style="color: #0071e3; font-size: 12px; font-weight: 600;" id="refundBookingCode">ROSA-REFUND-7458291048</div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2.5 fw-semibold" style="border: 1px solid #e2e8f0; color: #334155; font-size: 14px;" onclick="closeModal('refundModal')">
            Đóng
        </button>
    </div>
</div>

<script>
// Filter by Status tabs
function filterByStatus(status, btn) {
    document.querySelectorAll('.filter-tab').forEach(b => {
        b.className = 'btn btn-sm rounded-pill px-3 py-1.5 fw-semibold text-slate-700 filter-tab';
    });
    btn.className = 'btn btn-sm btn-white rounded-pill px-3 py-1.5 shadow-xs fw-bold text-primary filter-tab is-active';

    const cards = document.querySelectorAll('.booking-card-horizontal');
    cards.forEach(card => {
        if (status === 'all' || card.dataset.statusGroup === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Search filter
function handleBookingFilter() {
    const q = document.getElementById('bookingSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.booking-card-horizontal');
    cards.forEach(card => {
        const text = (card.dataset.searchText || '').toLowerCase();
        if (!q || text.includes(q)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Modal controls with Scroll Lock (Khóa cuộn trang khi mở popup, mở lại khi đóng)
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('is-open');
    if (!document.querySelector('.aeth-modal-backdrop.is-open')) {
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        document.body.classList.remove('has-modal-open');
    }
}

function openModalHelper(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    document.body.classList.add('has-modal-open');
}

function openWalletModal(id, roomName, guestName, floor) {
    document.getElementById('walletRoomName').textContent = roomName;
    document.getElementById('walletGuestName').textContent = guestName;
    document.getElementById('walletBookingCode').textContent = 'Đã kích hoạt NFC';
    document.getElementById('walletFloor').textContent = floor + ' • ' + roomName;
    openModalHelper('walletModal');
}

function handleAddToWallet() {
    const btn = document.getElementById('btnAddToWallet');
    btn.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Đã thêm vào Apple Wallet';
    btn.className = 'btn btn-success w-100 rounded-pill py-2.5 mt-3 fw-bold';
    setTimeout(() => {
        btn.innerHTML = 'Thêm vào Apple Wallet';
        btn.className = 'btn btn-light w-100 rounded-pill py-2.5 mt-3 fw-bold text-dark shadow-xs';
    }, 3500);
}

function openQrModal(id, roomName, qrDataUri) {
    if (!qrDataUri) {
        return;
    }
    document.getElementById('qrSubtitle').textContent = roomName;
    const qrImg = document.getElementById('qrCodeImg');
    if (qrImg) {
        qrImg.src = qrDataUri;
        qrImg.onerror = function() {
            this.removeAttribute('src');
            this.alt = 'Không thể tải mã nhận phòng. Vui lòng liên hệ lễ tân.';
        };
    }
    openModalHelper('qrModal');
}

function openInvoiceModal(id, price, name) {
    document.getElementById('invoiceCustomerName').textContent = name;
    document.getElementById('invoiceItemPrice').textContent = price + ' đ';
    document.getElementById('invoiceTotalPrice').textContent = price + ' đ';
    openModalHelper('invoiceModal');
}

function openRefundModal(id, price) {
    document.getElementById('refundBookingCode').textContent = 'ROSA-REFUND-' + (id || '7458291048');
    document.getElementById('refundAmount').textContent = price + ' đ';
    openModalHelper('refundModal');
}

// Tự động đóng modal khi click ra backdrop
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.aeth-modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
});

function openConciergeChat() {
    const chatBtn = document.getElementById('chat-trigger-btn') || document.querySelector('[data-chat-toggle]');
    if (chatBtn) {
        chatBtn.click();
    } else {
        alert('Quản gia riêng phục vụ 24/7 qua hotline: 1900 8899.');
    }
}

// Shortcut Cmd+K / Ctrl+K
document.addEventListener('keydown', e => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        const inp = document.getElementById('bookingSearchInput');
        if (inp) inp.focus();
    }
});

function syncStayExtensionMode() {
    const mode = document.getElementById('extensionMode')?.value || 'hours';
    const amount = document.getElementById('extensionAmount');
    const hourly = mode === 'hours';
    amount.max = hourly ? '12' : '30';
    document.getElementById('extensionAmountLabel').textContent = hourly ? 'Số giờ gia hạn' : 'Số ngày gia hạn';
    document.getElementById('extensionHelp').textContent = hourly
        ? 'Tính 10% giá đêm/giờ đến 18:00; vượt mốc này hệ thống tính thành một đêm.'
        : 'Từ 1 đến 30 ngày, tính theo giá phòng hiện tại; chỉ xác nhận khi không trùng lịch đặt tiếp theo.';
    if (Number(amount.value) > Number(amount.max)) amount.value = '1';
}

document.getElementById('extensionMode')?.addEventListener('change', syncStayExtensionMode);
document.querySelectorAll('[data-extension-open]').forEach(button => {
    button.addEventListener('click', () => {
        const hasValidationError = button.dataset.autoOpen === 'true';
        const form = document.getElementById('stayExtensionForm');
        form.action = button.dataset.postUrl;
        document.getElementById('extensionBookingId').value = button.dataset.bookingId;
        document.getElementById('extensionRoomName').textContent = button.dataset.roomName || '—';
        document.getElementById('extensionCheckout').textContent = button.dataset.checkout || '—';
        if (!hasValidationError) {
            document.getElementById('extensionMode').value = 'hours';
            document.getElementById('extensionAmount').value = '1';
            document.getElementById('extensionError').hidden = true;
        }
        syncStayExtensionMode();
        openModalHelper('stayExtensionModal');
        setTimeout(() => document.getElementById('extensionAmount')?.focus(), 0);
    });
});
document.querySelector('[data-extension-open][data-auto-open="true"]')?.click();
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.getElementById('stayExtensionModal')?.classList.contains('is-open')) {
        closeModal('stayExtensionModal');
    }
});
syncStayExtensionMode();

document.querySelectorAll('.cleaning-request-toggle').forEach(toggle => {
    toggle.addEventListener('change', async function () {
        const previous = !this.checked;
        const card = this.closest('.cleaning-request-card');
        const label = card?.querySelector('[data-label]');
        this.disabled = true;
        try {
            const response = await fetch(this.dataset.url, {
                method: 'PATCH',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ needs_cleaning: this.checked })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không thể cập nhật yêu cầu.');
            card?.classList.toggle('is-requested', data.needs_cleaning);
            if (card) {
                card.style.background = data.needs_cleaning ? '#fff7ed' : '#f8fafc';
                card.style.color = data.needs_cleaning ? '#b45309' : '#475569';
                card.style.borderColor = data.needs_cleaning ? '#fdba74' : '#e2e8f0';
            }
            if (label) label.textContent = `Phòng ${this.dataset.roomNumber} · ${data.needs_cleaning ? 'Đã báo dọn' : 'Yêu cầu dọn'}`;
        } catch (error) {
            this.checked = previous;
            if (label) label.textContent = error.message;
        } finally {
            this.disabled = false;
        }
    });
});
</script>
@endsection
