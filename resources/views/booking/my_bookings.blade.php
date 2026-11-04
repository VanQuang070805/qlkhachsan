@extends('layouts.main')

@section('title', 'Kỳ nghỉ của tôi · ROSALIZA HOTEL')

@section('content')
@php
    $statusLabels = config('statuses.booking');

    $allCount = (int) ($bookingCounts->all_count ?? 0);
    $upcomingCount = (int) ($bookingCounts->upcoming_count ?? 0);
    $completedCount = (int) ($bookingCounts->completed_count ?? 0);
    $cancelledCount = (int) ($bookingCounts->cancelled_count ?? 0);
    $totalNights = $bookings->sum(fn($b) => \Carbon\Carbon::parse($b->check_in)->diffInDays($b->check_out));
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
                <form class="position-relative" action="{{ route('booking.mine') }}" method="GET">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y text-slate-400 ms-3" style="font-size: 13px;"></i>
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="search" name="q" value="{{ $q }}" id="bookingSearchInput" class="form-control rounded-pill bg-white border border-slate-200 ps-5 pe-5 py-2 text-dark shadow-xs"
                           placeholder="Tìm kiếm đặt phòng..." style="font-size: 12.5px; width: 220px;" oninput="handleBookingFilter()">
                    <span class="position-absolute top-50 translate-middle-y end-0 me-2 badge bg-light text-muted border font-monospace" style="font-size: 10px;">⌘K</span>
                </form>

            </div>
        </div>

        {{-- ── 2. Thanh Phân Đoạn Trạng Thái & Điểm Thưởng (Segmented Bar) ── --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-1.5 rounded-4 bg-slate-200/70 mb-4 border border-slate-200/80">
            <div class="d-flex gap-1 overflow-x-auto" id="statusFilterPills">
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 filter-tab {{ $status === 'all' ? 'btn-white shadow-xs fw-bold text-primary is-active' : 'fw-semibold text-slate-700' }}" data-filter="all" onclick="filterByStatus('all', this)">
                    Tất cả <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $allCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 filter-tab {{ $status === 'upcoming' ? 'btn-white shadow-xs fw-bold text-primary is-active' : 'fw-semibold text-slate-700' }}" data-filter="upcoming" onclick="filterByStatus('upcoming', this)">
                    <span class="aeth-pulse-dot me-1" style="width:5px; height:5px;"></span>
                    Đã Đặt <span class="badge bg-blue-50 text-primary rounded-pill ms-1">{{ $upcomingCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 filter-tab {{ $status === 'completed' ? 'btn-white shadow-xs fw-bold text-primary is-active' : 'fw-semibold text-slate-700' }}" data-filter="completed" onclick="filterByStatus('completed', this)">
                    Hoàn thành <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $completedCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 filter-tab {{ $status === 'cancelled' ? 'btn-white shadow-xs fw-bold text-primary is-active' : 'fw-semibold text-slate-700' }}" data-filter="cancelled" onclick="filterByStatus('cancelled', this)">
                    Đã hủy <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $cancelledCount }}</span>
                </button>
            </div>

            <div class="d-none d-md-flex align-items-center gap-3 pe-2 text-slate-600" style="font-size: 12px; font-weight: 600;">
                <span><i class="bi bi-moon-stars text-primary me-1"></i> {{ $totalNights }} đêm theo lịch trên trang này</span>
                <span><i class="bi bi-calendar2-check text-emerald-600 me-1"></i> {{ $allCount }} đơn đặt phòng</span>
            </div>
        </div>

        {{-- ── 3. Danh Sách Thẻ Ngang Phân Lớp (Horizontal Cards Stack) ── --}}
        @if($bookings->isEmpty())
        <div class="text-center py-5 bg-white rounded-4 border border-slate-200 shadow-xs my-4">
            <i class="bi bi-calendar2-heart text-slate-300" style="font-size: 48px;"></i>
            <h3 class="fw-bold mt-3 mb-1" style="font-size: 18px; color: #0f172a;">{{ $allCount ? 'Không có đặt phòng phù hợp' : 'Chưa có kỳ nghỉ nào' }}</h3>
            <p class="text-muted mb-4" style="font-size: 13px;">{{ $allCount ? 'Thử từ khóa hoặc trạng thái khác.' : 'Khám phá một căn phòng vừa vặn cho hành trình tiếp theo của bạn.' }}</p>
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
                                Tổng giá đặt phòng
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
                                {{-- Nút Check-in QR (Quiet Luxury Charcoal Pill) --}}
                                @if($checkinQrDataUri)
                                <button type="button" class="btn rounded-pill px-3 py-1.5 text-white d-inline-flex align-items-center gap-1.5 shadow-xs"
                                        style="font-size: 11.5px; font-weight: 600; background: #181715; border: none; box-shadow: 0 2px 8px rgba(24,23,21,0.22); transition: all 0.2s ease;"
                                        data-qr-uri="{{ $checkinQrDataUri }}"
                                        onclick="openQrModal('{{ $booking->id }}', '{{ addslashes($roomTypeNames ?: 'Hạng phòng đang cập nhật') }}', this.dataset.qrUri)">
                                    <i class="bi bi-qr-code"></i>
                                    <span>Mã nhận phòng</span>
                                </button>
                                @endif

                                @if($booking->payment_status !== 'paid' && $booking->payment_method !== 'cash')
                                <a href="{{ route('payment.form', $booking->id) }}" class="btn rounded-pill px-3 py-1.5 text-decoration-none d-inline-flex align-items-center gap-1 shadow-xs"
                                   style="font-size: 11.5px; font-weight: 600; background: #F3EFE9; color: #181715; border: 1px solid #E5E1D8; transition: all 0.2s ease;">
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
                                        data-booking-id="{{ $booking->id }}" data-price="{{ number_format($booking->total_price, 0, ',', '.') }}" data-name="{{ $booking->customer_name }}" onclick="openInvoiceModal(this)">
                                    <i class="bi bi-receipt"></i>
                                    <span>Chi tiết đặt phòng</span>
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
                                        data-booking-id="{{ $booking->id }}" data-refund-amount="{{ $booking->refund_amount === null ? '' : number_format($booking->refund_amount, 0, ',', '.') }}" data-refund-status="{{ $booking->refund_status ?? '' }}" onclick="openRefundModal(this)">
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
                    <div class="fw-bold" id="extensionRoomName">Chưa chọn phòng</div>
                    <div class="small text-secondary mt-1">Hạn trả hiện tại: <span id="extensionCheckout">Chưa cập nhật</span></div>
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
        <p class="text-slate-600 mb-3 fw-semibold" style="font-size: 13px;" id="qrSubtitle">Chưa có thông tin</p>

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
    <div class="aeth-modal-box p-4" style="max-width:480px;border-radius:24px;background:#fff;color:#0f172a">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('invoiceModal')" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        <h3 class="fw-bold mb-3">Chi tiết đặt phòng</h3>
        <p>Mã đặt phòng: <strong id="invoiceBookingId"></strong></p>
        <p>Khách hàng: <strong id="invoiceCustomerName"></strong></p>
        <p>Tổng giá đặt phòng: <strong id="invoiceTotalPrice"></strong></p>
        <p class="text-muted small">Đây là thông tin đặt phòng; vui lòng liên hệ khách sạn nếu cần hóa đơn.</p>
        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" onclick="closeModal('invoiceModal')">Đóng</button>
    </div>
</div>
{{-- Modal 4: Refund Detail Modal (Thiết kế lại chuẩn Cupertino, không đè lấn, font đồng bộ) --}}
<div class="aeth-modal-backdrop" id="refundModal">
    <div class="aeth-modal-box p-4" style="max-width:440px;border-radius:24px;background:#fff;color:#0f172a">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('refundModal')" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        <h3 class="fw-bold mb-3">Chi tiết hoàn tiền</h3>
        <p>Mã đặt phòng: <strong id="refundBookingCode"></strong></p>
        <p>Trạng thái: <strong id="refundStatus"></strong></p>
        <p>Số tiền hoàn ghi nhận: <strong id="refundAmount"></strong></p>
        <p class="text-muted small">Thông tin theo hồ sơ đặt phòng của khách sạn.</p>
        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" onclick="closeModal('refundModal')">Đóng</button>
    </div>
</div>
<script>
// Filter by Status tabs
function filterByStatus(status, btn) {
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    url.searchParams.delete('page');
    window.location.assign(url);
}

// Search filter
function handleBookingFilter() {
    clearTimeout(window.bookingSearchTimer);
    window.bookingSearchTimer = setTimeout(() => document.getElementById('bookingSearchInput').form.requestSubmit(), 350);
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

function openInvoiceModal(button) {
    document.getElementById('invoiceBookingId').textContent = '#' + button.dataset.bookingId;
    document.getElementById('invoiceCustomerName').textContent = button.dataset.name;
    document.getElementById('invoiceTotalPrice').textContent = button.dataset.price + ' đ';
    openModalHelper('invoiceModal');
}

function openRefundModal(button) {
    const labels = @json(config('statuses.refund'));
    document.getElementById('refundBookingCode').textContent = '#' + button.dataset.bookingId;
    document.getElementById('refundStatus').textContent = labels[button.dataset.refundStatus] || 'Chưa xác định';
    document.getElementById('refundAmount').textContent = button.dataset.refundAmount ? button.dataset.refundAmount + ' đ' : 'Chưa ghi nhận';
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

function openTrợ lýChat() {
    const chatBtn = document.getElementById('chat-trigger-btn') || document.querySelector('[data-chat-toggle]');
    if (chatBtn) {
        chatBtn.click();
    } else {
        alert('Kênh hỗ trợ chưa sẵn sàng. Vui lòng xem thông tin liên hệ trên website.');
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
        document.getElementById('extensionRoomName').textContent = button.dataset.roomName || 'Chưa chọn phòng';
        document.getElementById('extensionCheckout').textContent = button.dataset.checkout || 'Chưa cập nhật';
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
