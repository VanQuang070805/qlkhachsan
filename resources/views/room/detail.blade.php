@extends('layouts.main')

@section('content')
@php
    $pageTitle = 'Chi Tiết - ' . htmlspecialchars($room->type_name ?? 'Phòng');
    $earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 16 ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString() : now('Asia/Ho_Chi_Minh')->toDateString();
    $adults = (int)($adults ?? 1);
    $children = (int)($children ?? 0);
    $initialNights = max(1, \Carbon\Carbon::parse($checkIn)->diffInDays($checkOut));
    $initialEstimate = (float) $room->price * $initialNights;
    $availableRoomsCount = $allRoomsOfType->where('is_booked', false)->where('status', 'available')->count();
    $maxCapacity = (int) $room->max_guests;
    $maxAdults = (int) $room->max_adults;
    $maxChildren = (int) $room->max_children;
    $isInsufficient = ($adults + $children) > $maxCapacity || $adults > $maxAdults || ($children > 0 && $children > $maxChildren);
    $roomsNeeded = $isInsufficient ? (int)ceil(($adults + $children) / max($maxCapacity, 1)) : 1;
    $multiRoomMode = $roomsNeeded > 1;

    $heroPhoto = $room->image_url;
@endphp

<div class="aeth-canvas">
    <div class="aeth-spatial-7xl aeth-animate-in">

        {{-- ── 1. Suite Breadcrumb & Badges (Ảnh 1) ── --}}
        <div class="aeth-top-meta">
            <div class="aeth-breadcrumb">
                <a href="{{ route('rooms.index') }}">BỘ SƯU TẬP PHÒNG</a>
                <span>/</span>
                <span class="highlight">{{ $room->type_name }}</span>
            </div>

            <div class="aeth-top-badges">
                <div class="aeth-badge-urgency">
                    <span class="aeth-pulse-dot"></span>
                    <span>{{ $availableRoomsCount }} phòng còn khả dụng</span>
                </div>
                <div class="aeth-badge-rating">
                    <i class="bi bi-star-fill"></i>
                    <span>{{ $avgRating ? number_format($avgRating, 1) : 'Chưa có' }} • {{ $room->reviews->count() }} đánh giá</span>
                </div>
            </div>
        </div>

        {{-- ── 2. Tiêu đề chính & Phân hạng (Ảnh 1) ── --}}
        <div class="aeth-title-row">
            <h1 class="aeth-room-title">{{ $room->type_name }}</h1>
        </div>
        @if(filled($room->description))
            <p class="text-slate-600" style="max-width: 78ch; line-height: 1.7; margin: 0 0 22px;">{{ $room->description }}</p>
        @endif

        {{-- ── 3. Lưới Thư Viện Ảnh Kiến Trúc (Architectural Gallery Grid - Ảnh 1) ── --}}
        <div class="aeth-gallery-grid aeth-gallery-grid--single">
            <div class="aeth-gallery-hero">
                <img src="{{ $heroPhoto }}" alt="{{ $room->type_name }}" loading="eager" onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}'">
                <div class="aeth-gallery-scrim"></div>
            </div>
        </div>

        {{-- ── 4. Bốn Thẻ Đo Lường Nhanh (Metric Spec Cards - Ảnh 1 & 2) ── --}}
        <div class="aeth-specs-row">
            <div class="aeth-spec-card">
                <div class="aeth-spec-icon-box"><i class="bi bi-person"></i></div>
                <div class="aeth-spec-text">
                    <strong>{{ $maxAdults }}</strong>
                    <span>Người lớn tối đa</span>
                </div>
            </div>
            <div class="aeth-spec-card">
                <div class="aeth-spec-icon-box"><i class="bi bi-person-hearts"></i></div>
                <div class="aeth-spec-text">
                    <strong>{{ $maxChildren }}</strong>
                    <span>Trẻ em tối đa</span>
                </div>
            </div>
            <div class="aeth-spec-card">
                <div class="aeth-spec-icon-box"><i class="bi bi-people"></i></div>
                <div class="aeth-spec-text">
                    <strong>{{ $maxCapacity }}</strong>
                    <span>Khách tối đa</span>
                </div>
            </div>
            <div class="aeth-spec-card">
                <div class="aeth-spec-icon-box"><i class="bi bi-door-open"></i></div>
                <div class="aeth-spec-text">
                    <strong>{{ $availableRoomsCount }}</strong>
                    <span>Phòng khả dụng</span>
                </div>
            </div>
        </div>

        {{-- ── 5. Main Layout: Cột trái (8) & Cột phải dính (4) (Ảnh 2) ── --}}
        <div class="aeth-main-layout">

            {{-- ══ CỘT TRÁI (8 COLS) ══ --}}
            <div class="aeth-content-left">

                {{-- Tiện nghi --}}
                <div class="aeth-section-card">
                    <div class="aeth-section-title-wrap">
                        <h2 class="aeth-section-title">
                            <span>Tiện nghi</span>
                        </h2>
                    </div>

                    <div class="aeth-amenities-grid">
                        @forelse($room->amenities as $amenity)
                            <div class="aeth-amenity-card">
                                <div class="aeth-amenity-icon"><i class="bi bi-check2-circle"></i></div>
                                <span class="aeth-amenity-name">{{ $amenity->amenity_name }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Hạng phòng chưa có tiện nghi được cập nhật.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Đánh Giá Thực Tế (Lấy thực tế từ cơ sở dữ liệu) --}}
                <div class="aeth-section-card">
                    <div class="aeth-section-title-wrap">
                        <h2 class="aeth-section-title">
                            <i class="bi bi-chat-heart"></i>
                            <span>Đánh Giá</span>
                        </h2>
                        <span class="badge rounded-pill bg-emerald-50 text-emerald-700 px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1" style="font-size:11.5px; border:none; background:#ecfdf5;">
                            <i class="bi bi-shield-check"></i> Xác minh trải nghiệm
                        </span>
                    </div>

                    @if($room->reviews && $room->reviews->isNotEmpty())
                    <div class="aeth-reviews-grid">
                        @foreach($room->reviews as $rev)
                        <div class="aeth-review-card">
                            <div class="aeth-stars">
                                @for($s = 1; $s <= 5; $s++)
                                <i class="bi bi-star{{ $s <= $rev->rating ? '-fill' : '' }}"></i>
                                @endfor
                                <span class="ms-2 text-muted fw-semibold" style="font-size:11px;">
                                    {{ $rev->created_at ? $rev->created_at->format('d/m/Y') : 'Khách đã lưu trú' }}
                                </span>
                            </div>
                            <p class="aeth-review-quote">"{{ $rev->comment }}"</p>
                            <div class="aeth-review-author">
                                <div class="aeth-author-avatar">
                                    {{ strtoupper(mb_substr($rev->user->fullname ?? $rev->user->username ?? 'K', 0, 2)) }}
                                </div>
                                <span class="aeth-author-name">{{ $rev->user->fullname ?? $rev->user->username ?? 'Khách nghỉ dưỡng' }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4" style="background: #f8fafc; border-radius: 16px; border: 1px dashed #cbd5e1;">
                        <i class="bi bi-chat-square-text text-muted" style="font-size: 26px;"></i>
                        <p class="text-muted mt-2 mb-0" style="font-size: 13px; font-weight: 500;">Chưa có đánh giá nào cho hạng phòng này.</p>
                    </div>
                    @endif
                </div>

            </div>

            {{-- ══ CỘT PHẢI DÍNH: STICKY BOOKING MODULE (4 COLS) ══ --}}
            <div class="aeth-sticky-sidebar">
                <div class="aeth-booking-box">

                    {{-- Khu vực Giá niêm yết --}}
                    <div class="aeth-rate-header">
                        <div class="aeth-rate-main">
                            <span class="aeth-rate-price" id="displayRate">{{ number_format($room->price, 0, ',', '.') }}đ</span>
                            <span class="aeth-rate-unit">/ đêm trung bình</span>
                        </div>
                    </div>

                    {{-- Khay Phân Đoạn Chọn Ngày (Native Overlay Date Picker) --}}
                    <div class="aeth-date-pill-box">
                        <div class="aeth-date-cols">
                            <div class="aeth-date-cell">
                                <label class="aeth-date-label" for="bkCheckIn">NHẬN PHÒNG</label>
                                <div class="aeth-date-val" id="textCheckInVal">{{ $checkIn ? date('d/m/Y', strtotime($checkIn)) : date('d/m/Y', strtotime($earliestCheckIn)) }}</div>
                                <div class="aeth-date-sub">Từ 14:00</div>
                                <input type="date" id="bkCheckIn" min="{{ $earliestCheckIn }}" value="{{ $checkIn ?: $earliestCheckIn }}" class="aeth-date-native-input" onchange="handleDateChange()" oninput="handleDateChange()">
                            </div>
                            <div class="aeth-date-cell">
                                <label class="aeth-date-label" for="bkCheckOut">TRẢ PHÒNG</label>
                                <div class="aeth-date-val" id="textCheckOutVal">{{ $checkOut ? date('d/m/Y', strtotime($checkOut)) : date('d/m/Y', strtotime($earliestCheckIn . ' +1 day')) }}</div>
                                <div class="aeth-date-sub">Trước 12:00</div>
                                <input type="date" id="bkCheckOut" min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ $checkOut ?: date('Y-m-d', strtotime($earliestCheckIn . ' +1 day')) }}" class="aeth-date-native-input" onchange="handleDateChange()" oninput="handleDateChange()">
                            </div>
                        </div>
                        <div class="aeth-date-duration-bar">
                            <span><i class="bi bi-moon-stars me-1 text-primary"></i> Thời gian</span>
                            <strong class="text-slate-900" id="durationText">1 đêm liên tiếp</strong>
                        </div>
                    </div>

                    {{-- Bộ Tăng Giảm Số Khách (Interactive Steppers) --}}
                    <div class="aeth-steppers-card">
                        <div class="aeth-stepper-row">
                            <div class="aeth-stepper-info">
                                <strong>Người lớn</strong>
                                <small>Từ 12 tuổi trở lên</small>
                            </div>
                            <div class="aeth-stepper-ctrl">
                                <button type="button" class="aeth-step-btn" onclick="adjustGuest('adults', -1)">-</button>
                                <span class="aeth-step-val" id="adultsVal">{{ $adults }}</span>
                                <button type="button" class="aeth-step-btn" onclick="adjustGuest('adults', 1)">+</button>
                            </div>
                            <input type="hidden" id="bkAdults" value="{{ $adults }}">
                        </div>

                        <div class="aeth-stepper-row">
                            <div class="aeth-stepper-info">
                                <strong>Trẻ em</strong>
                                <small>Dưới 12 tuổi</small>
                            </div>
                            <div class="aeth-stepper-ctrl">
                                <button type="button" class="aeth-step-btn" onclick="adjustGuest('children', -1)">-</button>
                                <span class="aeth-step-val" id="childrenVal">{{ $children }}</span>
                                <button type="button" class="aeth-step-btn" onclick="adjustGuest('children', 1)">+</button>
                            </div>
                            <input type="hidden" id="bkChildren" value="{{ $children }}">
                        </div>
                    </div>

                    {{-- Khối Chọn Số Phòng & Tầng (Chuyển sang card bên phải trên tổng tiền) --}}
                    @php
                        $byFloor = $allRoomsOfType->groupBy('floor');
                    @endphp
                    <div class="border-top pt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="m-0" style="font-size:12.5px; font-weight:700; color:#0f172a;">
                                <i class="bi bi-door-open text-primary me-1"></i> Chọn phòng theo tầng
                            </label>
                            <span class="text-muted" style="font-size:11px;" id="selectedRoomCountLabel">Tự động chọn 1 phòng</span>
                        </div>

                        @if($byFloor->isEmpty())
                            <p class="text-muted text-center py-2 mb-0" style="font-size:12px;">Hiện không có phòng nào sẵn sàng.</p>
                        @else
                            <div id="floorRoomsScrollList" data-lenis-prevent class="floor-rooms-scroll-list" style="max-height: 220px; overflow-y: auto; overscroll-behavior: contain; padding: 2px 4px 2px 0;">
                                @foreach($byFloor as $floor => $fRooms)
                                <div class="mb-3" data-floor-group="{{ $floor }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5" style="font-size: 12px; font-weight: 700; color: #475569;">
                                        <span>Tầng {{ $floor }}</span>
                                    </div>
                                    <div class="floor-rooms-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(76px, 1fr)); gap: 8px;">
                                        @foreach($fRooms as $r)
                                        @php
                                            $taken = $r->is_booked || $r->status !== 'available';
                                        @endphp
                                        <button type="button"
                                            class="aeth-room-pill btn {{ $taken ? 'btn-light disabled' : 'btn-outline-primary' }}"
                                            data-room-id="{{ $r->id }}"
                                            data-room-number="{{ $r->room_number }}"
                                            data-floor="{{ $r->floor }}"
                                            data-room-status="{{ $r->is_booked ? 'reserved' : $r->status }}"
                                            data-is-booked="{{ $taken ? 'true' : 'false' }}"
                                            aria-pressed="false"
                                            @if($taken) disabled aria-disabled="true" @else aria-disabled="false" onclick="toggleRoom(this)" @endif
                                            style="border-radius: 10px; padding: 7px 8px; font-weight: 600; font-size: 13px; min-height: 38px; display: flex; align-items: center; justify-content: center; width: 100%;">
                                            <span>P.{{ $r->room_number }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Bảng Phân Tích Chi Phí Minh Bạch (Đã xóa phí dịch vụ) --}}
                    <div class="aeth-breakdown pt-3" style="border-top: none !important;">
                        <div class="aeth-breakdown-row">
                            <span id="breakdownNightsLabel">{{ $initialNights }} đêm × 1 phòng</span>
                            <strong id="breakdownBase">{{ number_format($initialEstimate, 0, ',', '.') }}đ</strong>
                        </div>
                        <div class="aeth-total-box" style="border: none !important; box-shadow: none !important; outline: none !important;">
                            <span>Tổng thanh toán</span>
                            <strong id="breakdownTotal">{{ number_format($initialEstimate, 0, ',', '.') }}đ</strong>
                        </div>
                    </div>

                    {{-- Nút Đặt Phòng Chính (Primary Apple Blue Button) --}}
                    <button type="button" class="aeth-btn-primary" id="btnBook" onclick="submitBooking()" {{ $isInsufficient ? 'disabled aria-disabled=true' : '' }} aria-describedby="capacityErrorMsg">
                        <span>Đặt phòng ngay</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>

                    <p class="text-danger small text-center m-0 d-none" id="capacityErrorMsg" aria-live="polite"></p>

                    {{-- Huy hiệu bảo chứng --}}
                    <div class="aeth-trust-note">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Giá cuối cùng được xác nhận trước khi thanh toán</span>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

{{-- ── Hidden Form Submit (Giữ nguyên contract với controller) ── --}}
<form method="GET" action="{{ route('booking.create') }}" id="bookingForm" style="display:none;">
    <input type="hidden" name="check_in" id="formCheckIn" value="{{ $checkIn }}">
    <input type="hidden" name="check_out" id="formCheckOut" value="{{ $checkOut }}">
    <input type="hidden" name="adults" id="formAdults" value="{{ $adults }}">
    <input type="hidden" name="children" id="formChildren" value="{{ $children }}">
    <div id="formRoomIds"></div>
</form>

{{-- ── Script tương tác đầy đủ ── --}}
<script>
let BASE_NIGHTLY_PRICE = {{ (float)$room->price }};
const MAX_GUESTS_PER_ROOM = {{ $maxCapacity }};
const MAX_ADULTS_PER_ROOM = {{ $maxAdults }};
const MAX_CHILDREN_PER_ROOM = {{ $maxChildren }};
let selectedRooms = new Map();
const availabilityEndpoint = @json(route('rooms.detail', $room->id));
let availabilityRequestId = 0;
let availabilityTimer;

// Ngày mặc định
const inEl = document.getElementById('bkCheckIn');
const outEl = document.getElementById('bkCheckOut');
const adultsEl = document.getElementById('bkAdults');
const childrenEl = document.getElementById('bkChildren');

function handleDateChange() {
    if (!inEl.value) return;
    
    // Auto set check-out next day if invalid
    if (!outEl.value || outEl.value <= inEl.value) {
        const nextDay = new Date(inEl.value);
        nextDay.setDate(nextDay.getDate() + 1);
        outEl.value = nextDay.toISOString().split('T')[0];
    }
    outEl.min = inEl.value;

    const inDate = new Date(inEl.value);
    const outDate = new Date(outEl.value);
    const nights = Math.max(1, Math.round((outDate - inDate) / (1000 * 60 * 60 * 24)));

    const pad = n => String(n).padStart(2, '0');
    document.getElementById('textCheckInVal').textContent = `${pad(inDate.getDate())}/${pad(inDate.getMonth()+1)}/${inDate.getFullYear()}`;
    document.getElementById('textCheckOutVal').textContent = `${pad(outDate.getDate())}/${pad(outDate.getMonth()+1)}/${outDate.getFullYear()}`;
    document.getElementById('durationText').textContent = `${nights} đêm liên tiếp`;

    recalcTotals();
    scheduleAvailabilityRefresh();
}

function adjustGuest(type, delta) {
    const el = type === 'adults' ? adultsEl : childrenEl;
    const dispEl = type === 'adults' ? document.getElementById('adultsVal') : document.getElementById('childrenVal');
    let val = parseInt(el.value) + delta;
    if (type === 'adults' && val < 1) val = 1;
    if (type === 'children' && val < 0) val = 0;
    el.value = val;
    dispEl.textContent = val;
    recalcTotals();
}

function toggleRoom(btn) {
    if (btn.disabled || btn.dataset.isBooked === 'true') return;
    const id = btn.dataset.roomId;
    const num = btn.dataset.roomNumber;
    if (selectedRooms.has(id)) {
        selectedRooms.delete(id);
        btn.setAttribute('aria-pressed', 'false');
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    } else {
        selectedRooms.set(id, num);
        btn.setAttribute('aria-pressed', 'true');
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');
    }
    recalcTotals();
}

function recalcTotals() {
    const inDate = inEl.value ? new Date(inEl.value) : new Date();
    const outDate = outEl.value ? new Date(outEl.value) : new Date(Date.now() + 86400000);
    const nights = Math.max(1, Math.round((outDate - inDate) / (1000 * 60 * 60 * 24)));
    const roomCount = Math.max(1, selectedRooms.size);

    const baseAmount = roomCount * nights * BASE_NIGHTLY_PRICE;
    const grandTotal = baseAmount;

    document.getElementById('breakdownNightsLabel').textContent = `${nights} đêm × ${roomCount} phòng`;
    document.getElementById('breakdownBase').textContent = baseAmount.toLocaleString('vi-VN') + 'đ';
    document.getElementById('breakdownTotal').textContent = grandTotal.toLocaleString('vi-VN') + 'đ';

    const countLbl = document.getElementById('selectedRoomCountLabel');
    if (countLbl) {
        countLbl.textContent = selectedRooms.size > 0 ? `Đã chọn: ${selectedRooms.size} phòng` : 'Tự động chọn 1 phòng';
    }

    // Check capacity
    const totalGuests = parseInt(adultsEl.value) + parseInt(childrenEl.value);
    const capacityLimit = roomCount * MAX_GUESTS_PER_ROOM;
    const adultsLimit = roomCount * MAX_ADULTS_PER_ROOM;
    const childrenLimit = roomCount * MAX_CHILDREN_PER_ROOM;
    const errEl = document.getElementById('capacityErrorMsg');
    const bookButton = document.getElementById('btnBook');
    const noAvailableRooms = document.querySelectorAll('.aeth-room-pill:not(:disabled)').length === 0;
    const exceedsGuestRules = totalGuests > capacityLimit
        || parseInt(adultsEl.value, 10) > adultsLimit
        || parseInt(childrenEl.value, 10) > childrenLimit;
    if (exceedsGuestRules) {
        errEl.textContent = `Vượt sức chứa hoặc giới hạn người lớn/trẻ em (${roomCount} phòng). Hãy chọn thêm phòng hoặc giảm số khách.`;
        errEl.classList.remove('d-none');
        bookButton.disabled = true;
        bookButton.setAttribute('aria-disabled', 'true');
    } else if (noAvailableRooms) {
        errEl.textContent = 'Loại phòng này hiện không còn phòng trống trong ngày đã chọn.';
        errEl.classList.remove('d-none');
        bookButton.disabled = true;
        bookButton.setAttribute('aria-disabled', 'true');
    } else {
        errEl.classList.add('d-none');
        bookButton.disabled = false;
        bookButton.setAttribute('aria-disabled', 'false');
    }
}

async function refreshAvailability() {
    if (!inEl.value || !outEl.value || outEl.value <= inEl.value) return;
    const requestId = ++availabilityRequestId;
    const params = new URLSearchParams({ check_in: inEl.value, check_out: outEl.value });
    try {
        const response = await fetch(`${availabilityEndpoint}?${params}`, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        if (!response.ok || requestId !== availabilityRequestId) return;
        const data = await response.json();
        const nightlyPrice = Number(data.nightly_price);
        if (Number.isFinite(nightlyPrice) && nightlyPrice >= 0) {
            BASE_NIGHTLY_PRICE = nightlyPrice;
            document.getElementById('displayRate').textContent = new Intl.NumberFormat('vi-VN').format(nightlyPrice) + 'đ';
        }
        const states = new Map((data.rooms || []).map(room => [String(room.id), room]));
        const freeByFloor = new Map();

        document.querySelectorAll('.aeth-room-pill').forEach(button => {
            const state = states.get(String(button.dataset.roomId));
            if (!state) return;
            const available = state.available === true;
            if (!available && selectedRooms.has(button.dataset.roomId)) {
                selectedRooms.delete(button.dataset.roomId);
                button.setAttribute('aria-pressed', 'false');
            }
            button.disabled = !available;
            button.dataset.isBooked = String(!available);
            button.dataset.roomStatus = state.status;
            button.setAttribute('aria-disabled', String(!available));
            button.classList.toggle('disabled', !available);
            button.classList.toggle('btn-light', !available);
            button.classList.toggle('btn-outline-primary', available && !selectedRooms.has(button.dataset.roomId));
            button.classList.toggle('btn-primary', available && selectedRooms.has(button.dataset.roomId));
            if (available) {
                const floor = button.dataset.floor;
                freeByFloor.set(floor, (freeByFloor.get(floor) || 0) + 1);
            }
        });

        recalcTotals();
    } catch (error) {
        // Keep the last server-rendered availability when a refresh cannot reach the server.
    }
}

function scheduleAvailabilityRefresh() {
    clearTimeout(availabilityTimer);
    availabilityTimer = setTimeout(refreshAvailability, 180);
}

function submitBooking() {
    const roomCount = Math.max(1, selectedRooms.size);
    const capacityLimit = roomCount * MAX_GUESTS_PER_ROOM;
    const totalGuests = parseInt(adultsEl.value, 10) + parseInt(childrenEl.value, 10);
    if (totalGuests > capacityLimit
        || parseInt(adultsEl.value, 10) > roomCount * MAX_ADULTS_PER_ROOM
        || parseInt(childrenEl.value, 10) > roomCount * MAX_CHILDREN_PER_ROOM) {
        recalcTotals();
        return;
    }

    if (!inEl.value || !outEl.value) {
        alert('Vui lòng chọn ngày nhận phòng và trả phòng.');
        return;
    }

    const form = document.getElementById('bookingForm');
    document.getElementById('formCheckIn').value = inEl.value;
    document.getElementById('formCheckOut').value = outEl.value;
    document.getElementById('formAdults').value = adultsEl.value;
    document.getElementById('formChildren').value = childrenEl.value;

    const formRoomsDiv = document.getElementById('formRoomIds');
    formRoomsDiv.innerHTML = '';
    
    // If no room explicitly chosen via buttons, pick first available
    if (selectedRooms.size === 0) {
        const firstAvail = document.querySelector('.aeth-room-pill:not([disabled])');
        if (firstAvail) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'room_ids[]';
            hidden.value = firstAvail.dataset.roomId;
            formRoomsDiv.appendChild(hidden);
        } else {
            alert('Rất tiếc loại phòng này hiện không còn phòng trống.');
            return;
        }
    } else {
        selectedRooms.forEach((num, id) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'room_ids[]';
            hidden.value = id;
            formRoomsDiv.appendChild(hidden);
        });
    }

    form.submit();
}

// Initial calculation
document.addEventListener('DOMContentLoaded', () => {
    if (inEl && inEl.value) {
        handleDateChange();
    } else {
        recalcTotals();
    }
    refreshAvailability();
    window.setInterval(() => {
        if (document.visibilityState === 'visible') refreshAvailability();
    }, 15000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refreshAvailability();
    });
    window.addEventListener('pageshow', refreshAvailability);

    const floorList = document.getElementById('floorRoomsScrollList');
    if (floorList) {
        floorList.addEventListener('wheel', (e) => {
            const isScrollable = floorList.scrollHeight > floorList.clientHeight;
            if (!isScrollable) return;
            const atTop = floorList.scrollTop <= 0 && e.deltaY < 0;
            const atBottom = Math.ceil(floorList.scrollTop + floorList.clientHeight) >= floorList.scrollHeight && e.deltaY > 0;
            if (!atTop && !atBottom) {
                e.stopPropagation();
            }
        }, { passive: true });
    }
});
</script>
@endsection
