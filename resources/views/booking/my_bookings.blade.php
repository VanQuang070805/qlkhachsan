@extends('layouts.main')

@section('title', 'Lịch sử đặt phòng · Rosaliza Hotel')

@section('content')
@php
    $statusLabels = config('statuses.booking');

    $allCount = (int) ($bookingCounts->all_count ?? 0);
    $upcomingCount = (int) ($bookingCounts->upcoming_count ?? 0);
    $completedCount = (int) ($bookingCounts->completed_count ?? 0);
    $cancelledCount = (int) ($bookingCounts->cancelled_count ?? 0);
    $totalNights = $bookings->sum(fn($b) => \Carbon\Carbon::parse($b->check_in)->diffInDays($b->check_out));
    $selectedRoomTypeId = isset($roomTypeId) ? (int) $roomTypeId : 0;
    $availableRoomTypes = $roomTypes ?? collect();
@endphp

<div class="aeth-canvas my-bookings-page">
    <div class="aeth-spatial-7xl aeth-animate-in">

        {{-- 1. Tiêu đề & Thanh tìm kiếm, lọc hạng phòng --}}
        <div class="my-bookings-header">
            <div>
                <div class="my-bookings-eyebrow">KHO LƯU TRỮ KỲ NGHỈ</div>
                <h1 class="my-bookings-title">Lịch Sử Đặt Phòng</h1>
            </div>

            <form class="my-bookings-filter-form" id="bookingFilterForm" action="{{ route('booking.mine') }}" method="GET">
                <input type="hidden" name="status" id="bookingStatusInput" value="{{ $status }}">

                <div class="my-bookings-search-wrap">
                    <button type="submit" class="my-bookings-search-btn" aria-label="Tìm đặt phòng">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <input
                        type="search"
                        name="q"
                        value="{{ $q }}"
                        id="bookingSearchInput"
                        class="my-bookings-search-input"
                        placeholder="Mã #ID, số phòng (101), hạng phòng..."
                        aria-label="Tìm mã đặt phòng, số phòng hoặc hạng phòng"
                        oninput="handleBookingFilter()"
                    >
                    @if($q !== '')
                        <button type="button" class="my-bookings-clear-btn" onclick="clearSearchInput()" aria-label="Xóa từ khóa">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>

                <select
                    name="room_type"
                    id="bookingRoomTypeSelect"
                    class="my-bookings-type-select"
                    aria-label="Lọc theo hạng phòng"
                    onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()"
                >
                    <option value="">Tất cả hạng phòng</option>
                    @foreach($availableRoomTypes as $rt)
                        <option value="{{ $rt->id }}" @selected($selectedRoomTypeId === (int) $rt->id)>{{ $rt->type_name }}</option>
                    @endforeach
                </select>

                @if($q !== '' || $selectedRoomTypeId > 0 || $status !== 'all')
                    <a href="{{ route('booking.mine') }}" class="my-bookings-reset-link">Đặt lại lọc</a>
                @endif
            </form>
        </div>

        {{-- 2. Thanh phân đoạn trạng thái --}}
        <div class="my-bookings-segmented-bar">
            <div class="my-bookings-tabs" id="statusFilterPills" role="tablist" aria-label="Lọc trạng thái đặt phòng">
                <button type="button" class="btn btn-sm rounded-pill px-3 filter-tab {{ $status === 'all' ? 'is-active' : '' }}" data-filter="all" onclick="filterByStatus('all')">
                    Tất cả <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $allCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 filter-tab {{ $status === 'upcoming' ? 'is-active' : '' }}" data-filter="upcoming" onclick="filterByStatus('upcoming')">
                    Đã Đặt <span class="badge bg-blue-50 text-primary rounded-pill ms-1">{{ $upcomingCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 filter-tab {{ $status === 'completed' ? 'is-active' : '' }}" data-filter="completed" onclick="filterByStatus('completed')">
                    Hoàn thành <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $completedCount }}</span>
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 filter-tab {{ $status === 'cancelled' ? 'is-active' : '' }}" data-filter="cancelled" onclick="filterByStatus('cancelled')">
                    Đã hủy <span class="badge bg-slate-100 text-slate-600 rounded-pill ms-1">{{ $cancelledCount }}</span>
                </button>
            </div>

            <div class="d-none d-lg-flex align-items-center gap-3 pe-2 text-slate-600" style="font-size: 12px; font-weight: 500;">
                <span><i class="bi bi-moon-stars me-1" aria-hidden="true"></i> {{ $totalNights }} đêm lưu trú</span>
                <span><i class="bi bi-calendar2-check me-1" aria-hidden="true"></i> {{ $allCount }} đơn đặt phòng</span>
            </div>
        </div>

        {{-- 3. Danh sách thẻ đặt phòng --}}
        @if($bookings->isEmpty())
            <div class="text-center py-5 bg-white rounded-4 border border-slate-200 shadow-xs my-4 px-3">
                <i class="bi bi-calendar2-heart text-slate-300" style="font-size: 44px;" aria-hidden="true"></i>
                <h2 class="fw-semibold mt-3 mb-1" style="font-size: 18px; color: #0f172a;">
                    {{ $allCount ? 'Không tìm thấy đơn đặt phòng phù hợp' : 'Chưa có kỳ nghỉ nào' }}
                </h2>
                <p class="text-muted mb-4" style="font-size: 13.5px;">
                    {{ $allCount ? 'Hãy thử mã đặt phòng khác, số phòng khác hoặc đặt lại bộ lọc.' : 'Khám phá không gian lưu trú yên tĩnh tại Rosaliza Hotel cho hành trình tiếp theo.' }}
                </p>
                @if($allCount && ($q !== '' || $selectedRoomTypeId > 0 || $status !== 'all'))
                    <a href="{{ route('booking.mine') }}" class="aeth-btn-primary d-inline-flex w-auto px-4 py-2 text-decoration-none">
                        <span>Xem toàn bộ đặt phòng</span>
                    </a>
                @else
                    <a href="{{ route('rooms.index') }}" class="aeth-btn-primary d-inline-flex w-auto px-4 py-2 text-decoration-none">
                        <span>Khám phá phòng nghỉ</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        @else
            <div class="my-bookings-list" id="bookingCardsContainer">
                @foreach($bookings as $booking)
                    @php
                        $firstRoom = $booking->rooms->first();
                        $roomType = $firstRoom?->roomType;
                        $roomTypeNames = $booking->rooms->map(fn($room) => $room->roomType?->type_name)->filter()->unique()->join(' · ');
                        $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out));
                        $roomNames = $booking->rooms->map(fn($room) => 'Phòng '.$room->room_number)->join(', ');
                        $floor = $firstRoom?->floor;
                        $roomImg = $roomType?->image_url ?? asset('images/rooms/default.jpg');

                        $isCancelled = in_array($booking->status, ['cancelled', 'rejected']);
                        $isCompleted = $booking->status === 'completed';
                        $isUpcoming = in_array($booking->status, ['pending', 'confirmed', 'checked_in']);
                        $canExtend = $booking->status === 'checked_in';
                        $checkinToken = $checkinTokens[$booking->id] ?? null;
                        $filterGroup = $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : 'upcoming');
                        $extensionHasError = (int) old('booking_id') === (int) $booking->id && $errors->hasAny(['mode', 'amount']);

                        $checkinQrDataUri = null;
                        if ($checkinToken) {
                            try {
                                $qrObj = \Endroid\QrCode\QrCode::create($checkinToken)->setSize(288)->setMargin(16);
                                $checkinQrDataUri = (new \Endroid\QrCode\Writer\SvgWriter())->write($qrObj)->getDataUri();
                            } catch (\Throwable $e) {
                                $checkinQrDataUri = null;
                            }
                        }

                        $statusText = $statusLabels[$booking->status] ?? ($isCompleted ? 'Đã hoàn thành' : ($isCancelled ? 'Đã hủy' : 'Đã đặt'));
                        $paymentText = $booking->isFullyPaid()
                            ? 'Đã thanh toán đủ'
                            : ($booking->isPaid() ? 'Đã đặt cọc 50%' : ($booking->payment_method === 'cash' ? 'Thanh toán tại quầy' : 'Chờ thanh toán cọc 50%'));
                        $depositVal = (float) ($booking->deposit_amount ?: ($booking->total_price * 0.5));
                        $checkInFormatted = \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y');
                        $checkOutFormatted = \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y');
                        $scheduledCheckoutHour = $booking->scheduledCheckoutAt()->format('H:i');
                    @endphp
                    <article
                        class="booking-card-horizontal {{ $isCancelled ? 'is-cancelled' : '' }}"
                        data-status-group="{{ $filterGroup }}"
                        data-search-text="{{ $booking->id }} #{{ $booking->id }} {{ $roomNames }} {{ $roomTypeNames }} {{ $booking->customer_name }}"
                    >
                        <div class="booking-card-grid">
                            {{-- Ảnh phòng --}}
                            <div class="booking-card-media">
                                <img src="{{ $roomImg }}" alt="{{ $roomTypeNames ?: 'Phòng nghỉ' }}" onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}'">
                                @if($floor !== null)
                                    <span class="booking-card-floor">Tầng {{ $floor }}</span>
                                @endif
                            </div>

                            {{-- Nội dung chính --}}
                            <div class="booking-card-body">
                                <div class="booking-card-badges">
                                    <button
                                        type="button"
                                        class="booking-code-pill"
                                        data-booking-detail
                                        data-booking-id="{{ $booking->id }}"
                                        data-room-type="{{ $roomTypeNames ?: 'Hạng phòng tiêu chuẩn' }}"
                                        data-room-names="{{ $roomNames ?: 'Chưa gán số phòng' }}"
                                        data-floor="{{ $floor !== null ? 'Tầng '.$floor : 'Đang cập nhật' }}"
                                        data-check-in="{{ $checkInFormatted }} (14:00 - trước 18:00)"
                                        data-check-out="{{ $checkOutFormatted }} (trước {{ $scheduledCheckoutHour }})"
                                        data-nights="{{ $nights }}"
                                        data-guests="{{ $booking->adult_count }} người lớn{{ $booking->child_count > 0 ? ', '.$booking->child_count.' trẻ em' : '' }}"
                                        data-customer-name="{{ $booking->customer_name }}"
                                        data-customer-phone="{{ $booking->customer_phone }}"
                                        data-status="{{ $statusText }}"
                                        data-payment="{{ $paymentText }}"
                                        data-total="{{ number_format($booking->total_price, 0, ',', '.') }} đ"
                                        data-deposit="{{ number_format($depositVal, 0, ',', '.') }} đ"
                                        data-qr-uri="{{ $checkinQrDataUri ?? '' }}"
                                        onclick="openBookingDetailModal(this)"
                                        title="Nhấn để xem mã đặt phòng và chi tiết đơn"
                                    >
                                        <i class="bi bi-hash" aria-hidden="true"></i>
                                        <span>Mã đơn #{{ $booking->id }}</span>
                                    </button>

                                    @if($isUpcoming)
                                        <span class="booking-status-badge booking-status-badge--upcoming">{{ $statusText }}</span>
                                    @elseif($isCompleted)
                                        <span class="booking-status-badge booking-status-badge--completed">Đã hoàn thành</span>
                                    @else
                                        <span class="booking-status-badge booking-status-badge--cancelled">Đã hủy</span>
                                    @endif

                                    <span class="booking-payment-tag">{{ $paymentText }}</span>
                                </div>

                                <h3 class="booking-card-title">{{ $roomTypeNames ?: 'Hạng phòng đang được cập nhật' }}</h3>

                                <div class="booking-card-meta">
                                    <span><i class="bi bi-door-open" aria-hidden="true"></i> {{ $roomNames ?: 'Chưa gán số phòng' }}</span>
                                    <span><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $checkInFormatted }} - {{ $checkOutFormatted }} ({{ $nights }} đêm)</span>
                                    <span><i class="bi bi-people" aria-hidden="true"></i> {{ $booking->adult_count }} người lớn{{ $booking->child_count > 0 ? ', '.$booking->child_count.' trẻ em' : '' }}</span>
                                </div>
                            </div>

                            {{-- Giá & Hành động --}}
                            <div class="booking-card-aside">
                                <div class="booking-card-price-block">
                                    <span class="booking-card-price-label">Tổng chi phí</span>
                                    <span class="booking-card-price-value">{{ number_format($booking->total_price, 0, ',', '.') }} đ</span>
                                </div>

                                <div class="booking-card-actions">
                                    <button
                                        type="button"
                                        class="booking-action-btn booking-action-btn--ghost"
                                        data-booking-id="{{ $booking->id }}"
                                        data-room-type="{{ $roomTypeNames ?: 'Hạng phòng tiêu chuẩn' }}"
                                        data-room-names="{{ $roomNames ?: 'Chưa gán số phòng' }}"
                                        data-floor="{{ $floor !== null ? 'Tầng '.$floor : 'Đang cập nhật' }}"
                                        data-check-in="{{ $checkInFormatted }} (14:00 - trước 18:00)"
                                        data-check-out="{{ $checkOutFormatted }} (trước {{ $scheduledCheckoutHour }})"
                                        data-nights="{{ $nights }}"
                                        data-guests="{{ $booking->adult_count }} người lớn{{ $booking->child_count > 0 ? ', '.$booking->child_count.' trẻ em' : '' }}"
                                        data-customer-name="{{ $booking->customer_name }}"
                                        data-customer-phone="{{ $booking->customer_phone }}"
                                        data-status="{{ $statusText }}"
                                        data-payment="{{ $paymentText }}"
                                        data-total="{{ number_format($booking->total_price, 0, ',', '.') }} đ"
                                        data-deposit="{{ number_format($depositVal, 0, ',', '.') }} đ"
                                        data-qr-uri="{{ $checkinQrDataUri ?? '' }}"
                                        onclick="openBookingDetailModal(this)"
                                    >
                                        <i class="bi bi-receipt" aria-hidden="true"></i>
                                        <span>Chi tiết đơn</span>
                                    </button>

                                    @if($isUpcoming)
                                        @if($booking->status === 'checked_in')
                                            @foreach($booking->rooms as $room)
                                                <label class="cleaning-request-card {{ $room->needs_cleaning ? 'is-requested' : '' }}">
                                                    <input
                                                        class="form-check-input m-0 cleaning-request-toggle"
                                                        type="checkbox"
                                                        data-room-number="{{ $room->room_number }}"
                                                        data-url="{{ route('booking.cleaning-request', [$booking->id, $room->id]) }}"
                                                        {{ $room->needs_cleaning ? 'checked' : '' }}
                                                        aria-label="Yêu cầu dọn phòng {{ $room->room_number }}"
                                                    >
                                                    <span data-label>P.{{ $room->room_number }} · {{ $room->needs_cleaning ? 'Đã báo dọn' : 'Yêu cầu dọn' }}</span>
                                                </label>
                                            @endforeach
                                        @endif

                                        @if($checkinQrDataUri)
                                            <button
                                                type="button"
                                                class="booking-action-btn booking-action-btn--primary"
                                                data-qr-uri="{{ $checkinQrDataUri }}"
                                                data-booking-id="{{ $booking->id }}"
                                                data-room-type="{{ $roomTypeNames ?: 'Hạng phòng tiêu chuẩn' }}"
                                                data-room-names="{{ $roomNames ?: 'Chưa gán số phòng' }}"
                                                data-dates="{{ $checkInFormatted }} (14:00) - {{ $checkOutFormatted }} (12:00)"
                                                onclick="openQrModalFromBtn(this)"
                                            >
                                                <i class="bi bi-qr-code" aria-hidden="true"></i>
                                                <span>Mã QR nhận phòng</span>
                                            </button>
                                        @endif

                                        @if($booking->payment_status !== 'paid' && $booking->payment_method !== 'cash')
                                            <a href="{{ route('payment.form', $booking->id) }}" class="booking-action-btn booking-action-btn--warm">
                                                <span>Thanh toán cọc</span>
                                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                            </a>
                                        @endif

                                        @if($canExtend)
                                            <button
                                                type="button"
                                                class="booking-action-btn booking-action-btn--ghost"
                                                data-extension-open
                                                data-booking-id="{{ $booking->id }}"
                                                data-post-url="{{ route('booking.extend', $booking) }}"
                                                data-room-name="{{ $roomNames }}"
                                                data-checkout="{{ $booking->scheduledCheckoutAt()->format('H:i d/m/Y') }}"
                                                data-auto-open="{{ $extensionHasError ? 'true' : 'false' }}"
                                            >
                                                <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                                <span>Gia hạn phòng</span>
                                            </button>
                                        @endif

                                        @if(in_array($booking->status, ['pending', 'confirmed'], true))
                                            <a href="{{ route('booking.cancel.show', $booking->id) }}" class="booking-action-btn booking-action-btn--danger">
                                                <span>Hủy phòng</span>
                                            </a>
                                        @endif
                                    @elseif($isCompleted)
                                        @if($firstRoom?->room_type_id)
                                            <a href="{{ route('reviews.create', ['booking_id' => $booking->id, 'room_type_id' => $firstRoom->room_type_id]) }}" class="booking-action-btn booking-action-btn--primary">
                                                <span>Đánh giá kỳ nghỉ</span>
                                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                    @else
                                        <button
                                            type="button"
                                            class="booking-action-btn booking-action-btn--ghost"
                                            data-booking-id="{{ $booking->id }}"
                                            data-refund-amount="{{ $booking->refund_amount === null ? '' : number_format($booking->refund_amount, 0, ',', '.') }}"
                                            data-refund-status="{{ $booking->refund_status ?? '' }}"
                                            onclick="openRefundModal(this)"
                                        >
                                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                            <span>Hoàn tiền</span>
                                        </button>
                                        <a href="{{ route('rooms.index') }}" class="booking-action-btn booking-action-btn--primary">
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

{{-- Modal 1: Chi tiết mã đặt phòng & thông tin phòng --}}
<div class="aeth-modal-backdrop" id="bookingDetailModal" role="dialog" aria-modal="true" aria-labelledby="bookingDetailModalTitle">
    <div class="aeth-modal-box" style="max-width: 500px; width: calc(100% - 28px); padding: 26px 24px; border-radius: 24px; background: #ffffff; color: #0f172a;">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div>
                <div style="font-size: 11px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">THÔNG TIN ĐẶT PHÒNG</div>
                <h2 class="m-0 fw-semibold" style="font-size: 19px; color: #0f172a;" id="bookingDetailModalTitle">Đơn đặt phòng</h2>
            </div>
            <button type="button" class="aeth-modal-close-btn position-static" onclick="closeModal('bookingDetailModal')" aria-label="Đóng">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <div>
                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Mã đặt phòng</div>
                <strong id="detailCodeValue" style="font-size: 18px; font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums;">#--</strong>
            </div>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" style="min-height: 38px; font-size: 12.5px; font-weight: 600;" onclick="copyDetailBookingCode(this)">
                <i class="bi bi-clipboard me-1" aria-hidden="true"></i><span>Sao chép mã</span>
            </button>
        </div>

        <div class="my-booking-detail-rows">
            <div class="my-booking-detail-row"><span>Hạng phòng</span><strong id="detailRoomType">--</strong></div>
            <div class="my-booking-detail-row"><span>Phòng đã đặt</span><strong id="detailRoomNames">--</strong></div>
            <div class="my-booking-detail-row"><span>Vị trí tầng</span><strong id="detailFloor">--</strong></div>
            <div class="my-booking-detail-row"><span>Nhận phòng</span><strong id="detailCheckIn">--</strong></div>
            <div class="my-booking-detail-row"><span>Trả phòng</span><strong id="detailCheckOut">--</strong></div>
            <div class="my-booking-detail-row"><span>Khách lưu trú</span><strong id="detailCustomer">--</strong></div>
            <div class="my-booking-detail-row"><span>Số lượng khách</span><strong id="detailGuests">--</strong></div>
            <div class="my-booking-detail-row"><span>Trạng thái đơn</span><strong id="detailStatus">--</strong></div>
            <div class="my-booking-detail-row"><span>Thanh toán</span><strong id="detailPayment">--</strong></div>
            <div class="my-booking-detail-row"><span>Tiền cọc (50%)</span><span id="detailDeposit" style="font-weight: 500; font-variant-numeric: tabular-nums;">--</span></div>
            <div class="my-booking-detail-row" style="border-bottom: none; padding-bottom: 0;"><span>Tổng giá trị đơn</span><span id="detailTotal" style="font-size: 16px; font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums;">--</span></div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="button" id="detailOpenQrBtn" class="booking-action-btn booking-action-btn--primary flex-grow-1 justify-content-center d-none">
                <i class="bi bi-qr-code" aria-hidden="true"></i>
                <span>Mở mã QR nhận phòng</span>
            </button>
            <button type="button" class="booking-action-btn booking-action-btn--ghost flex-grow-1 justify-content-center" onclick="closeModal('bookingDetailModal')">
                <span>Đóng</span>
            </button>
        </div>
    </div>
</div>

{{-- Modal 2: Gia hạn lưu trú --}}
<div class="aeth-modal-backdrop" id="stayExtensionModal" role="dialog" aria-modal="true" aria-labelledby="stayExtensionTitle">
    <div class="aeth-modal-box" style="max-width: 500px; width: calc(100% - 28px); padding: 0; overflow: hidden; border-radius: 24px;">
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
            <h2 class="m-0 fs-6 fw-semibold" id="stayExtensionTitle">Gia hạn lưu trú</h2>
            <button type="button" class="aeth-modal-close-btn position-static" onclick="closeModal('stayExtensionModal')" aria-label="Đóng">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <form id="stayExtensionForm" method="POST">
            @csrf
            <input type="hidden" name="booking_id" id="extensionBookingId" value="{{ old('booking_id') }}">
            <div class="p-4">
                <div class="p-3 mb-4 rounded-3 border bg-light">
                    <div class="small text-secondary fw-semibold mb-1">Phòng lưu trú</div>
                    <div class="fw-semibold" id="extensionRoomName">Chưa chọn phòng</div>
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
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="closeModal('stayExtensionModal')">Hủy</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4 fw-semibold"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Xác nhận gia hạn</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 3: Thẻ Check-in QR Điện Tử (chứa đầy đủ thông tin phòng đã đặt) --}}
<div class="aeth-modal-backdrop" id="qrModal" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle">
    <div class="aeth-modal-box text-center" style="max-width: 420px; width: calc(100% - 28px); padding: 28px 22px 22px; border-radius: 28px; background: #ffffff; color: #0f172a; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0;">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('qrModal')" aria-label="Đóng">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>

        <div style="display: flex; justify-content: center; margin-bottom: 8px;">
            <i class="bi bi-qr-code-scan" style="font-size: 32px; color: #181715;" aria-hidden="true"></i>
        </div>

        <div style="font-size: 11.5px; font-weight: 600; color: #059669; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px;">
            THẺ NHẬN PHÒNG ĐIỆN TỬ · ROSALIZA HOTEL
        </div>

        <h3 class="fw-semibold mb-1 text-slate-900" style="font-size: 19px;" id="qrModalTitle">Mã QR Nhận Phòng</h3>
        <p class="text-slate-600 mb-3" style="font-size: 13px;" id="qrSubtitle">Đang tải thông tin phòng</p>

        <div style="border: 1px solid #e2e8f0; border-radius: 22px; padding: 18px 16px 14px; background: #f8fafc; margin-bottom: 14px;">
            <div class="p-2.5 bg-white rounded-4 d-inline-block shadow-xs mb-2" style="border: 1px solid #e2e8f0; line-height: 0;">
                <img id="qrCodeImg" src="" alt="Mã QR Check-in Chuẩn ISO" style="width: min(248px, 66vw); height: auto; display: block; border-radius: 8px;">
            </div>

            <div style="text-align: left; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 10px 14px; margin-top: 8px; font-size: 12.5px; display: grid; gap: 5px;">
                <div class="d-flex justify-content-between gap-2"><span class="text-slate-500">Mã đơn:</span><strong id="qrBookingIdLabel" class="text-slate-900">#--</strong></div>
                <div class="d-flex justify-content-between gap-2"><span class="text-slate-500">Phòng đặt:</span><strong id="qrRoomNamesLabel" class="text-slate-900 text-end">--</strong></div>
                <div class="d-flex justify-content-between gap-2"><span class="text-slate-500">Thời gian:</span><strong id="qrDatesLabel" class="text-slate-900 text-end">--</strong></div>
            </div>
        </div>

        <p class="text-slate-600 mb-3" style="font-size: 12.5px; line-height: 1.5;">
            Xuất trình mã QR này tại quầy Lễ tân từ 14:00 đến trước 18:00 ngày nhận phòng để làm thủ tục nhanh.
        </p>

        <button type="button" class="btn w-100 rounded-pill fw-semibold text-white" style="background: #181715; border: none; min-height: 44px;" onclick="closeModal('qrModal')">
            Đóng
        </button>
    </div>
</div>

{{-- Modal 4: Chi tiết hoàn tiền --}}
<div class="aeth-modal-backdrop" id="refundModal" role="dialog" aria-modal="true" aria-labelledby="refundModalTitle">
    <div class="aeth-modal-box p-4" style="max-width: 430px; width: calc(100% - 28px); border-radius: 24px; background: #fff; color: #0f172a;">
        <button type="button" class="aeth-modal-close-btn" onclick="closeModal('refundModal')" aria-label="Đóng"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        <h3 class="fw-semibold mb-3" style="font-size: 18px;" id="refundModalTitle">Chi tiết hoàn tiền</h3>
        <div class="my-booking-detail-rows mb-3">
            <div class="my-booking-detail-row"><span>Mã đặt phòng</span><strong id="refundBookingCode">--</strong></div>
            <div class="my-booking-detail-row"><span>Trạng thái</span><strong id="refundStatus">--</strong></div>
            <div class="my-booking-detail-row" style="border-bottom: none;"><span>Số tiền hoàn</span><span id="refundAmount" style="font-weight: 500; font-variant-numeric: tabular-nums;">--</span></div>
        </div>
        <p class="text-muted small mb-3">Lễ tân xác nhận hoàn tiền sau khi đối soát giao dịch qua cổng thanh toán.</p>
        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" style="min-height: 44px;" onclick="closeModal('refundModal')">Đóng</button>
    </div>
</div>

@push('styles')
<style>
.my-bookings-page {
    padding-bottom: 48px;
}
.my-bookings-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}
.my-bookings-eyebrow {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 4px;
}
.my-bookings-title {
    font-size: clamp(22px, 3vw, 28px);
    font-weight: 600;
    color: #0f172a;
    letter-spacing: -0.03em;
    margin: 0;
}
.my-bookings-filter-form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.my-bookings-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
    min-width: min(100%, 290px);
    flex: 1 1 260px;
}
.my-bookings-search-btn {
    position: absolute;
    left: 6px;
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: #64748b;
    display: grid;
    place-items: center;
}
.my-bookings-search-input {
    width: 100%;
    min-height: 44px;
    padding: 8px 38px 8px 40px;
    border-radius: 999px;
    border: 1px solid #dce3ea;
    background: #ffffff;
    color: #0f172a;
    font-size: 13.5px;
}
.my-bookings-search-input:focus {
    outline: none;
    border-color: #64748b;
    box-shadow: none;
}
.my-bookings-clear-btn {
    position: absolute;
    right: 8px;
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 50%;
    background: #f1f5f9;
    color: #475569;
    display: grid;
    place-items: center;
    font-size: 11px;
}
.my-bookings-type-select {
    min-height: 44px;
    padding: 0 34px 0 16px;
    border-radius: 999px;
    border: 1px solid #dce3ea;
    background-color: #ffffff;
    color: #0f172a;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
}
.my-bookings-reset-link {
    min-height: 44px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #334155;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}
.my-bookings-segmented-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px;
    border-radius: 18px;
    background: #f4f5f7;
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
}
.my-bookings-tabs {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    max-width: 100%;
    scrollbar-width: none;
}
.my-bookings-tabs::-webkit-scrollbar {
    display: none;
}
.my-bookings-tabs .filter-tab {
    min-height: 40px;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 500;
}
.my-bookings-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.booking-card-horizontal {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.booking-card-horizontal.is-cancelled {
    opacity: 0.82;
}
.booking-card-grid {
    display: grid;
    grid-template-columns: 200px minmax(0, 1fr) auto;
    gap: 18px;
    align-items: center;
}
.booking-card-media {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 10;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}
.booking-card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.booking-card-floor {
    position: absolute;
    top: 8px;
    left: 8px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(6px);
    color: #0f172a;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
}
.booking-card-body {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.booking-card-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.booking-code-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    min-height: 32px;
    padding: 4px 12px;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #0f172a;
    font-size: 12px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease;
}
.booking-code-pill:hover {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}
.booking-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
}
.booking-status-badge--upcoming {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.booking-status-badge--completed {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
}
.booking-status-badge--cancelled {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}
.booking-payment-tag {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}
.booking-card-title {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.3;
}
.booking-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
    font-size: 13px;
    color: #475569;
}
.booking-card-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.booking-card-aside {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    min-width: 210px;
}
.booking-card-price-block {
    text-align: right;
}
.booking-card-price-label {
    display: block;
    font-size: 11.5px;
    color: #64748b;
}
.booking-card-price-value {
    display: block;
    font-size: 19px;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.booking-card-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}
.booking-action-btn {
    min-height: 40px;
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.16s ease, background 0.16s ease;
}
.booking-action-btn--primary {
    background: #181715;
    color: #ffffff;
    border: 1px solid #181715;
}
.booking-action-btn--primary:hover {
    background: #2c2a26;
    color: #ffffff;
}
.booking-action-btn--warm {
    background: #f3efe9;
    color: #181715;
    border: 1px solid #dfd8cc;
}
.booking-action-btn--ghost {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #dce3ea;
}
.booking-action-btn--ghost:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.booking-action-btn--danger {
    background: #fff5f5;
    color: #be123c;
    border: 1px solid #fecdd3;
}
.cleaning-request-card {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 6px 12px;
}
.cleaning-request-card.is-requested {
    background: #fff7ed;
    color: #b45309;
    border-color: #fdba74;
}
.my-booking-detail-rows {
    display: flex;
    flex-direction: column;
}
.my-booking-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    padding: 9px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13.5px;
}
.my-booking-detail-row > span:first-child {
    color: #64748b;
    flex-shrink: 0;
}
.my-booking-detail-row > strong,
.my-booking-detail-row > span:last-child {
    text-align: right;
    font-weight: 600;
    color: #0f172a;
}

@media (max-width: 991px) {
    .booking-card-grid {
        grid-template-columns: 170px minmax(0, 1fr);
    }
    .booking-card-aside {
        grid-column: 1 / -1;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid #f1f5f9;
        padding-top: 12px;
        min-width: 0;
    }
    .booking-card-price-block {
        text-align: left;
    }
}

@media (max-width: 639px) {
    .my-bookings-page {
        padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px));
    }
    .my-bookings-filter-form {
        width: 100%;
    }
    .my-bookings-search-wrap,
    .my-bookings-type-select {
        width: 100%;
        flex: 1 1 100%;
    }
    .booking-card-horizontal {
        padding: 14px;
    }
    .booking-card-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .booking-card-aside {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .booking-card-price-block {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
    }
    .booking-card-actions {
        justify-content: stretch;
    }
    .booking-card-actions .booking-action-btn {
        flex: 1 1 calc(50% - 6px);
        justify-content: center;
        min-height: 44px;
    }
}
</style>
@endpush

<script>
function filterByStatus(status) {
    const statusInput = document.getElementById('bookingStatusInput');
    const form = document.getElementById('bookingFilterForm');
    if (statusInput && form) {
        statusInput.value = status;
        if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            form.submit();
        }
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    url.searchParams.delete('page');
    window.location.assign(url);
}

function handleBookingFilter() {
    clearTimeout(window.bookingSearchTimer);
    window.bookingSearchTimer = setTimeout(() => {
        const form = document.getElementById('bookingFilterForm');
        if (form) {
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    }, 400);
}

function clearSearchInput() {
    const inp = document.getElementById('bookingSearchInput');
    const form = document.getElementById('bookingFilterForm');
    if (inp) inp.value = '';
    if (form) {
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }
}

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

function openQrModal(id, roomType, qrDataUri, roomNames = '', dates = '') {
    if (!qrDataUri) return;
    document.getElementById('qrSubtitle').textContent = roomType || 'Phòng lưu trú';
    document.getElementById('qrBookingIdLabel').textContent = '#' + id;
    document.getElementById('qrRoomNamesLabel').textContent = roomNames || 'Theo xác nhận đặt phòng';
    document.getElementById('qrDatesLabel').textContent = dates || 'Nhận 14:00 - Trả 12:00';
    const qrImg = document.getElementById('qrCodeImg');
    if (qrImg) {
        qrImg.src = qrDataUri;
        qrImg.onerror = function () {
            this.removeAttribute('src');
            this.alt = 'Không thể tải mã nhận phòng. Vui lòng liên hệ lễ tân.';
        };
    }
    openModalHelper('qrModal');
}

function openQrModalFromBtn(button) {
    openQrModal(
        button.dataset.bookingId,
        button.dataset.roomType,
        button.dataset.qrUri,
        button.dataset.roomNames,
        button.dataset.dates
    );
}

function openBookingDetailModal(button) {
    const id = button.dataset.bookingId || '';
    document.getElementById('bookingDetailModalTitle').textContent = 'Chi tiết đơn #' + id;
    document.getElementById('detailCodeValue').textContent = '#' + id;
    document.getElementById('detailRoomType').textContent = button.dataset.roomType || '--';
    document.getElementById('detailRoomNames').textContent = button.dataset.roomNames || '--';
    document.getElementById('detailFloor').textContent = button.dataset.floor || '--';
    document.getElementById('detailCheckIn').textContent = button.dataset.checkIn || '--';
    document.getElementById('detailCheckOut').textContent = button.dataset.checkOut || '--';
    document.getElementById('detailCustomer').textContent =
        (button.dataset.customerName || '--') + (button.dataset.customerPhone ? ' · ' + button.dataset.customerPhone : '');
    document.getElementById('detailGuests').textContent = button.dataset.guests || '--';
    document.getElementById('detailStatus').textContent = button.dataset.status || '--';
    document.getElementById('detailPayment').textContent = button.dataset.payment || '--';
    document.getElementById('detailDeposit').textContent = button.dataset.deposit || '--';
    document.getElementById('detailTotal').textContent = button.dataset.total || '--';

    const qrBtn = document.getElementById('detailOpenQrBtn');
    if (qrBtn) {
        if (button.dataset.qrUri) {
            qrBtn.classList.remove('d-none');
            qrBtn.onclick = () => {
                closeModal('bookingDetailModal');
                openQrModal(
                    id,
                    button.dataset.roomType,
                    button.dataset.qrUri,
                    button.dataset.roomNames,
                    (button.dataset.checkIn || '') + ' -> ' + (button.dataset.checkOut || '')
                );
            };
        } else {
            qrBtn.classList.add('d-none');
            qrBtn.onclick = null;
        }
    }

    openModalHelper('bookingDetailModal');
}

function copyDetailBookingCode(btn) {
    const code = document.getElementById('detailCodeValue')?.textContent?.trim() || '';
    if (!code) return;
    navigator.clipboard?.writeText(code).then(() => {
        const span = btn.querySelector('span');
        if (span) {
            const prev = span.textContent;
            span.textContent = 'Đã sao chép';
            setTimeout(() => {
                span.textContent = prev;
            }, 1600);
        }
    });
}

function openRefundModal(button) {
    const labels = @json(config('statuses.refund'));
    document.getElementById('refundBookingCode').textContent = '#' + button.dataset.bookingId;
    document.getElementById('refundStatus').textContent = labels[button.dataset.refundStatus] || 'Đang đối soát';
    document.getElementById('refundAmount').textContent = button.dataset.refundAmount
        ? button.dataset.refundAmount + ' đ'
        : 'Chưa ghi nhận khoản hoàn';
    openModalHelper('refundModal');
}

function openConciergeChat() {
    if (typeof window.openRoyalChat === 'function') {
        window.openRoyalChat();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.aeth-modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
});

document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        document.getElementById('bookingSearchInput')?.focus();
    }
    if (e.key === 'Escape') {
        document.querySelectorAll('.aeth-modal-backdrop.is-open').forEach((el) => closeModal(el.id));
    }
});

function syncStayExtensionMode() {
    const mode = document.getElementById('extensionMode')?.value || 'hours';
    const amount = document.getElementById('extensionAmount');
    if (!amount) return;
    const hourly = mode === 'hours';
    amount.max = hourly ? '12' : '30';
    document.getElementById('extensionAmountLabel').textContent = hourly ? 'Số giờ gia hạn' : 'Số ngày gia hạn';
    document.getElementById('extensionHelp').textContent = hourly
        ? 'Tính 10% giá đêm/giờ đến 18:00; vượt mốc này hệ thống tính thành một đêm.'
        : 'Từ 1 đến 30 ngày, tính theo giá phòng hiện tại; chỉ xác nhận khi không trùng lịch đặt tiếp theo.';
    if (Number(amount.value) > Number(amount.max)) amount.value = '1';
}

document.getElementById('extensionMode')?.addEventListener('change', syncStayExtensionMode);
document.querySelectorAll('[data-extension-open]').forEach((button) => {
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
            const errEl = document.getElementById('extensionError');
            if (errEl) errEl.hidden = true;
        }
        syncStayExtensionMode();
        openModalHelper('stayExtensionModal');
        setTimeout(() => document.getElementById('extensionAmount')?.focus(), 0);
    });
});
document.querySelector('[data-extension-open][data-auto-open="true"]')?.click();
syncStayExtensionMode();

document.querySelectorAll('.cleaning-request-toggle').forEach((toggle) => {
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
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ needs_cleaning: this.checked }),
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không thể cập nhật yêu cầu.');
            card?.classList.toggle('is-requested', data.needs_cleaning);
            if (label) label.textContent = `P.${this.dataset.roomNumber} · ${data.needs_cleaning ? 'Đã báo dọn' : 'Yêu cầu dọn'}`;
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
