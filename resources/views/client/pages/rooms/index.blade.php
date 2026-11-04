@extends('client.layouts.app')
@section('title', 'Phòng nghỉ & Đặt phòng · Rosaliza Hotel HaNoi')

@section('content')
<div class="melia-page-shell melia-rooms-shell">

    {{-- =========================================================================
         1. EDITORIAL ROOMS HERO SECTION
         ========================================================================= --}}
    <section class="melia-page-hero" aria-labelledby="rooms-page-title">
        <div class="melia-page-hero__inner">
            <span class="melia-page-hero__tag">
                Rosaliza Accommodation &bull; Hoàn Kiếm, Hanoi
            </span>

            <h1 id="rooms-page-title" class="melia-page-hero__title" data-rb-split="words,chars">
                A room for <em>every quiet journey.</em>
            </h1>

            <p class="melia-page-hero__desc" data-rb-blur="words">
                Khám phá bộ sưu tập phòng nghỉ boutique sang trọng tại Rosaliza Hotel HaNoi, từ các không gian Deluxe tinh tế đến các căn Grand Suite với view phố cổ thoáng đãng, mang đến cảm giác an yên tuyệt đối.
            </p>
        </div>
    </section>

    @php
        $roomSearchQuery = [
            'check_in' => $filters['check_in'],
            'check_out' => $filters['check_out'],
            'adults' => $filters['adults'],
            'children' => $filters['children'],
        ];
    @endphp

    {{-- =========================================================================
         2. MELIÁ QUIET LUXURY SEARCH & FILTER WIDGET
         ========================================================================= --}}
    <div class="melia-filter-wrap" aria-label="Tìm kiếm phòng nghỉ">
        <div class="melia-search-bar">
            <form action="{{ route('rooms.index') }}" method="GET">
                <input type="hidden" name="search" value="1">

                <div class="melia-search-grid">
                    {{-- Field 1: Hạng phòng (Multi-select Dropdown) --}}
                    <div class="melia-search-field rooms-search__multiselect-field">
                        <label id="label-room-type">Hạng phòng</label>
                        <div class="rooms-select-container" id="roomTypeDropdownContainer">
                            <button type="button" class="melia-select-trigger" id="roomTypeTrigger"
                                    aria-haspopup="listbox" aria-expanded="false" aria-labelledby="label-room-type roomTypeTriggerText">
                                <span class="melia-select-text" id="roomTypeTriggerText">Tất cả hạng phòng</span>
                                <i class="bi bi-chevron-down" style="font-size: 11px; color: var(--melia-text-soft);" aria-hidden="true"></i>
                            </button>

                            <input type="hidden" name="q" id="room-search-query" value="{{ old('q', $filters['q'] ?? '') }}">

                            <div class="melia-select-dropdown rooms-multiselect-dropdown" id="roomTypeMenu" role="listbox" aria-multiselectable="true" hidden>
                                <div class="melia-select-header">
                                    <span>Hạng phòng</span>
                                    <button type="button" class="melia-clear-btn" id="roomTypeClearBtn">Bỏ chọn</button>
                                </div>
                                <div class="melia-select-list">
                                    @php
                                        $availableTypes = $roomTypeOptions ?? collect();
                                        $oldQ = old('q', $filters['q'] ?? '');
                                        $selectedTypes = array_filter(array_map('trim', explode(',', $oldQ)));
                                    @endphp
                                    @foreach($availableTypes as $t)
                                        @php $isChecked = in_array($t, $selectedTypes) || ($oldQ === $t); @endphp
                                        <label class="melia-select-item rooms-multiselect-item {{ $isChecked ? 'is-selected' : '' }}">
                                            <input type="checkbox" class="rooms-multiselect-input" value="{{ $t }}" {{ $isChecked ? 'checked' : '' }} style="accent-color: var(--melia-text);">
                                            <span>{{ $t }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('q')<small class="text-danger" style="font-size: 10px;">{{ $message }}</small>@enderror
                    </div>

                    {{-- Field 2: Ngày nhận phòng --}}
                    <div class="melia-search-field">
                        <label for="room-check-in">Nhận phòng</label>
                        <input id="room-check-in" type="date" name="check_in"
                               value="{{ old('check_in', $filters['check_in']) }}"
                               min="{{ $earliestCheckIn }}" required>
                        <small class="melia-field-hint">Từ 14:00 chiều</small>
                        @error('check_in')<small class="text-danger" style="font-size: 10px;">{{ $message }}</small>@enderror
                    </div>

                    {{-- Field 3: Ngày trả phòng --}}
                    <div class="melia-search-field">
                        <label for="room-check-out">Trả phòng</label>
                        <input id="room-check-out" type="date" name="check_out"
                               value="{{ old('check_out', $filters['check_out']) }}"
                               min="{{ !empty($filters['check_in']) && $searchSubmitted ? \Illuminate\Support\Carbon::parse($filters['check_in'])->addDay()->toDateString() : \Illuminate\Support\Carbon::parse($earliestCheckIn)->addDay()->toDateString() }}" required>
                        <small class="melia-field-hint">Trước 12:00 trưa</small>
                        @error('check_out')<small class="text-danger" style="font-size: 10px;">{{ $message }}</small>@enderror
                    </div>

                    {{-- Field 4: Người lớn (Stepper) --}}
                    <div class="melia-search-field">
                        <label id="label-adults">Người lớn</label>
                        <div class="melia-stepper-row">
                            <button type="button" class="melia-stepper-btn" id="btnAdultsDec" aria-label="Giảm người lớn" onclick="adjustSearchGuest('adults', -1)">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="melia-stepper-val" id="adultsDisplayVal">{{ old('adults', $filters['adults'] ?? 1) }}</span>
                            <button type="button" class="melia-stepper-btn" id="btnAdultsInc" aria-label="Tăng người lớn" onclick="adjustSearchGuest('adults', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                            <input type="hidden" name="adults" id="room-adults" value="{{ old('adults', $filters['adults'] ?? 1) }}">
                        </div>
                        @error('adults')<small class="text-danger" style="font-size: 10px;">{{ $message }}</small>@enderror
                    </div>

                    {{-- Field 5: Trẻ em (Stepper) --}}
                    <div class="melia-search-field">
                        <label id="label-children">Trẻ em</label>
                        <div class="melia-stepper-row">
                            <button type="button" class="melia-stepper-btn" id="btnChildrenDec" aria-label="Giảm trẻ em" onclick="adjustSearchGuest('children', -1)">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="melia-stepper-val" id="childrenDisplayVal">{{ old('children', $filters['children'] ?? 0) }}</span>
                            <button type="button" class="melia-stepper-btn" id="btnChildrenInc" aria-label="Tăng trẻ em" onclick="adjustSearchGuest('children', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                            <input type="hidden" name="children" id="room-children" value="{{ old('children', $filters['children'] ?? 0) }}">
                        </div>
                        @error('children')<small class="text-danger" style="font-size: 10px;">{{ $message }}</small>@enderror
                    </div>

                    {{-- Submit Button --}}
                    <div>
                        <button class="melia-search-submit" type="submit">
                            <i class="bi bi-search"></i>
                            <span>Tìm phòng</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Live Filter Status Feedback --}}
        <p class="rooms-search__result" id="rooms-live-status" style="display: none; margin-top: 14px; text-align: center; font-size: 13.5px; color: var(--melia-text-muted);" role="status"></p>
        @if($searchSubmitted)
            <p class="rooms-search__result" style="margin-top: 14px; text-align: center; font-size: 13.5px; color: var(--melia-text-muted);" role="status">
                Tìm thấy <strong>{{ $rooms->count() }}</strong> hạng phòng phù hợp với kỳ nghỉ đã chọn.
            </p>
        @endif
    </div>

    {{-- =========================================================================
         3. ROOMS CATALOGUE GRID SECTION
         ========================================================================= --}}
    <div class="melia-page-container">
        <section class="melia-rooms-section" aria-labelledby="catalogue-title">
            <div class="melia-sec-header">
                <span class="melia-sec-tag">Available Collection</span>
                <h2 id="catalogue-title" class="melia-sec-title">Boutique Accommodations</h2>
                <p class="melia-sec-subtitle">
                    Không gian nghỉ ngơi thanh lịch, tích hợp nệm cao cấp, phòng tắm đá cẩm thạch và dịch vụ phòng 24/7.
                </p>
            </div>

            @if($rooms->isNotEmpty())
                <div class="melia-rooms-grid" id="rooms-cards-grid">
                    @foreach($rooms as $index => $room)
                        @php
                            $roomImg = $room->image_url;
                            $fallbackIdx = ($index % 6) + 1;
                            $fallbackImg = asset('images/rooms/' . $fallbackIdx . '.jpg');
                        @endphp
                        <article class="melia-room-card supaste-room-card" data-room-name="{{ $room->type_name }}" data-room-id="{{ $room->id }}" data-reveal>
                            <div class="melia-room-card__thumb">
                                <a href="{{ route('rooms.detail', array_merge(['id' => $room->id], $roomSearchQuery)) }}" aria-label="Xem chi tiết {{ $room->type_name }}">
                                    <img src="{{ $roomImg }}"
                                         alt="{{ $room->type_name }} tại Rosaliza Hotel HaNoi"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='{{ $fallbackImg }}';">
                                </a>
                                <div class="melia-room-card__badge-wrap">
                                    <span class="melia-card-badge">Hạng phòng</span>
                                    @if(isset($room->available_count))
                                        <span class="melia-card-avail">
                                            <i class="bi bi-check-circle-fill" style="color: #16a34a; font-size: 10px;"></i>
                                            {{ $room->available_count }} phòng trống
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="melia-room-card__body">
                                <div class="melia-room-card__meta-bar">
                                    <span>Boutique Room</span>
                                    <span>&bull;</span>
                                    <span>{{ $room->max_guests }} khách tối đa</span>
                                </div>

                                <h3 class="melia-room-card__name">
                                    <a href="{{ route('rooms.detail', array_merge(['id' => $room->id], $roomSearchQuery)) }}">
                                        {{ $room->type_name }}
                                    </a>
                                </h3>

                                <div class="melia-room-card__specs">
                                    <span><i class="bi bi-people"></i> {{ $room->max_adults }} người lớn, {{ $room->max_children }} trẻ em</span>
                                </div>

                                <p class="melia-room-card__desc">
                                    {{ \Illuminate\Support\Str::limit($room->description ?: 'Thông tin hạng phòng đang được cập nhật.', 95) }}
                                </p>

                                <div class="melia-room-card__amenities">
                                    @forelse($room->amenities->take(3) as $amenity)
                                        <span class="melia-amenity-tag">
                                            {{ $amenity->amenity_name }}
                                        </span>
                                    @empty
                                        <span class="melia-amenity-tag">Chưa cập nhật tiện nghi</span>
                                    @endforelse
                                </div>

                                <div class="melia-room-card__footer">
                                    <div class="melia-room-price">
                                        <span class="melia-price-label">Giá mỗi đêm</span>
                                        <span class="melia-price-amount">
                                            {{ number_format((float) $room->price, 0, ',', '.') }} đ
                                        </span>
                                        <span class="melia-price-unit">Giá theo ngày đã chọn</span>
                                    </div>
                                    <a href="{{ route('rooms.detail', array_merge(['id' => $room->id], $roomSearchQuery)) }}" class="melia-card-btn">
                                        <span>Đặt phòng</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="melia-empty-state" role="status">
                    <i class="bi bi-calendar2-x"></i>
                    <h3>Không tìm thấy phòng phù hợp</h3>
                    <p>{{ $searchSubmitted ? 'Không có hạng phòng phù hợp với ngày ở và số khách đã chọn. Quý khách vui lòng thử chọn ngày lưu trú khác hoặc liên hệ Concierge.' : 'Hiện chưa có hạng phòng trong hệ thống.' }}</p>
                    @if($searchSubmitted)
                        <a href="{{ route('rooms.index') }}" class="melia-btn-primary">
                            <span>Xóa bộ lọc &amp; Xem tất cả phòng</span>
                        </a>
                    @else
                        <a href="{{ route('contact') }}" class="melia-btn-primary">
                            <span>Liên hệ Lễ tân tư vấn</span>
                        </a>
                    @endif
                </div>
            @endif
        </section>

        {{-- =========================================================================
             4. STANDARDS & BESPOKE SERVICES (3-BENTO GRID)
             ========================================================================= --}}
        <section class="melia-values-section" style="margin-left: -24px; margin-right: -24px; padding-left: 24px; padding-right: 24px; border-radius: 28px; margin-bottom: 72px;">
            <div class="melia-page-container" style="padding: 0;">
                <div class="melia-sec-header melia-sec-header--center">
                    <span class="melia-sec-tag">Hospitality Standards</span>
                    <h2 class="melia-sec-title">Đặc quyền nghỉ dưỡng chuẩn mực</h2>
                    <p class="melia-sec-subtitle">
                        Khám phá không gian lưu trú và những thông tin hữu ích cho kỳ nghỉ của bạn tại Rosaliza Hotel HaNoi.
                    </p>
                </div>

                <div class="melia-values-grid">
                    <article class="melia-value-card">
                        <span class="melia-value-index">01</span>
                        <h3 class="melia-value-title">Dịch vụ Quản gia 24/7</h3>
                        <p class="melia-value-desc">
                            Đội ngũ Concierge và quản gia chuyên nghiệp luôn sẵn sàng hỗ trợ mọi nhu cầu lưu trú, đưa đón sân bay Nội Bài và gợi ý trải nghiệm văn hóa.
                        </p>
                    </article>

                    <article class="melia-value-card">
                        <span class="melia-value-index">02</span>
                        <h3 class="melia-value-title">Ẩm thực Le Rosaliza</h3>
                        <p class="melia-value-desc">
                            Bữa sáng buffet phong phú kết hợp tinh hoa món ngon Hà Nội truyền thống cùng thực đơn Á - Âu tinh tế được chuẩn bị công phu.
                        </p>
                    </article>

                    <article class="melia-value-card">
                        <span class="melia-value-index">03</span>
                        <h3 class="melia-value-title">Không gian tĩnh lặng</h3>
                        <p class="melia-value-desc">
                            Hệ thống cửa cách âm chuẩn studio, nệm memory foam cao cấp và ánh sáng ấm dịu đảm bảo giấc ngủ sâu trọn vẹn nhất cho bạn.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        {{-- =========================================================================
             5. FAQ ACCORDION (1:1 MELIÁ ACCORDION)
             ========================================================================= --}}
        <section class="melia-faqs" style="padding-bottom: 80px;" aria-labelledby="faq-title">
            <div class="melia-sec-header melia-sec-header--center">
                <span class="melia-sec-tag">Stay Questions</span>
                <h2 id="faq-title" class="melia-sec-title">Câu hỏi thường gặp khi đặt phòng</h2>
                <p class="melia-sec-subtitle">
                    Những thông tin hữu ích giúp kỳ nghỉ của Quý khách tại Rosaliza Hotel HaNoi diễn ra suôn sẻ và thuận tiện nhất.
                </p>
            </div>

            <div class="melia-faq-list" style="max-width: 860px; margin: 0 auto;">
                <div class="melia-faq-item is-open">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Thời gian nhận phòng và trả phòng tiêu chuẩn là khi nào?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Giờ nhận phòng (Check-in) từ 14:00 và giờ trả phòng (Check-out) trước 12:00 trưa hôm sau. Quý khách có thể yêu cầu nhận phòng sớm hoặc trả phòng muộn tùy thuộc vào tình trạng phòng trống thực tế thông qua Quầy Lễ Tân.
                    </div>
                </div>

                <div class="melia-faq-item">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Chính sách hủy phòng và hoàn tiền được áp dụng ra sao?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Điều kiện hoàn tiền phụ thuộc ngày nhận phòng, thời điểm hủy và khoản thanh toán đã ghi nhận. Quý khách có thể xem thời hạn và số tiền dự kiến tại trang hủy của từng đơn; việc hoàn tiền được nhân viên xác nhận.
                    </div>
                </div>

                <div class="melia-faq-item">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Khách sạn có hỗ trợ dịch vụ đón tiễn sân bay Nội Bài không?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Có. Chúng tôi cung cấp dịch vụ đưa đón sân bay quốc tế Nội Bài 2 chiều bằng xe Limousine cao cấp 24/7 (khoảng 35-40 phút di chuyển). Quý khách có thể ghi chú yêu cầu khi đặt phòng hoặc liên hệ trực tiếp với Concierge.
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Date pickers logic
    const checkIn = document.getElementById('room-check-in');
    const checkOut = document.getElementById('room-check-out');
    const toCivil = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    checkIn?.addEventListener('change', () => {
        if (!checkIn.value || !checkOut) return;
        const [year, month, day] = checkIn.value.split('-').map(Number);
        const earliestCheckout = new Date(year, month - 1, day + 1, 12);
        checkOut.min = toCivil(earliestCheckout);
        if (checkOut.value && checkOut.value < checkOut.min) checkOut.value = '';
    });

    // 2. Dropdown management
    const roomTypeTrigger = document.getElementById('roomTypeTrigger');
    const roomTypeMenu = document.getElementById('roomTypeMenu');
    const roomTypeTriggerText = document.getElementById('roomTypeTriggerText');
    const roomTypeClearBtn = document.getElementById('roomTypeClearBtn');
    const roomQueryInput = document.getElementById('room-search-query');
    const roomTypeCheckboxes = document.querySelectorAll('.rooms-multiselect-input');

    function closeAllDropdowns() {
        roomTypeTrigger?.setAttribute('aria-expanded', 'false');
        roomTypeMenu?.setAttribute('hidden', '');
    }

    // Toggle Multi-select
    roomTypeTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        const isExpanded = roomTypeTrigger.getAttribute('aria-expanded') === 'true';
        closeAllDropdowns();
        if (!isExpanded) {
            roomTypeTrigger.setAttribute('aria-expanded', 'true');
            roomTypeMenu?.removeAttribute('hidden');
        }
    });

    // Update Multi-select State & Live filter
    function updateRoomTypeState() {
        const checked = Array.from(roomTypeCheckboxes).filter(cb => cb.checked).map(cb => cb.value.trim());

        if (checked.length === 0) {
            if (roomTypeTriggerText) roomTypeTriggerText.textContent = 'Tất cả hạng phòng';
            if (roomQueryInput) roomQueryInput.value = '';
        } else if (checked.length === 1) {
            if (roomTypeTriggerText) roomTypeTriggerText.textContent = checked[0];
            if (roomQueryInput) roomQueryInput.value = checked[0];
        } else {
            if (roomTypeTriggerText) roomTypeTriggerText.textContent = `${checked.length} đã chọn`;
            if (roomQueryInput) roomQueryInput.value = checked.join(', ');
        }

        roomTypeCheckboxes.forEach(cb => {
            const item = cb.closest('.rooms-multiselect-item');
            if (item) {
                item.classList.toggle('is-selected', cb.checked);
            }
        });

        // Filter room cards live on page
        const cards = document.querySelectorAll('#rooms-cards-grid .supaste-room-card');
        if (cards.length > 0) {
            let visibleCount = 0;
            cards.forEach(card => {
                const cardName = (card.getAttribute('data-room-name') || '').trim();
                const match = checked.length === 0 || checked.some(c => cardName.toLowerCase().includes(c.toLowerCase()) || c.toLowerCase().includes(cardName.toLowerCase()));
                if (match) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const liveStatus = document.getElementById('rooms-live-status');
            if (liveStatus) {
                if (checked.length > 0) {
                    liveStatus.textContent = `Hiển thị ${visibleCount} hạng phòng phù hợp.`;
                    liveStatus.style.display = 'block';
                } else {
                    liveStatus.style.display = 'none';
                }
            }
        }
    }

    roomTypeCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            updateRoomTypeState();
        });
    });

    roomTypeClearBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        roomTypeCheckboxes.forEach(cb => { cb.checked = false; });
        updateRoomTypeState();
    });

    // 3. Stepper Logic for Adults and Children
    window.adjustSearchGuest = function(type, delta) {
        const input = document.getElementById(type === 'adults' ? 'room-adults' : 'room-children');
        const display = document.getElementById(type === 'adults' ? 'adultsDisplayVal' : 'childrenDisplayVal');
        const btnDec = document.getElementById(type === 'adults' ? 'btnAdultsDec' : 'btnChildrenDec');
        if (!input || !display) return;

        let currentVal = parseInt(input.value, 10);
        if (isNaN(currentVal)) currentVal = (type === 'adults' ? 1 : 0);

        let newVal = currentVal + delta;
        if (newVal < (type === 'adults' ? 1 : 0)) newVal = type === 'adults' ? 1 : 0;
        if (newVal > 20) newVal = 20;

        input.value = newVal;
        display.textContent = newVal;

        if (btnDec) {
            btnDec.disabled = (newVal === (type === 'adults' ? 1 : 0));
        }
    };

    function initSearchSteppers() {
        ['adults', 'children'].forEach(type => {
            const input = document.getElementById(type === 'adults' ? 'room-adults' : 'room-children');
            const display = document.getElementById(type === 'adults' ? 'adultsDisplayVal' : 'childrenDisplayVal');
            const btnDec = document.getElementById(type === 'adults' ? 'btnAdultsDec' : 'btnChildrenDec');
            if (input && display) {
                let val = parseInt(input.value, 10);
                const min = type === 'adults' ? 1 : 0;
                if (isNaN(val) || val < min) val = min;
                input.value = val;
                display.textContent = val;
                if (btnDec) btnDec.disabled = (val === min);
            }
        });
    }

    initSearchSteppers();

    // Global outside click and escape key
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.rooms-select-container')) {
            closeAllDropdowns();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    // Initial state check
    updateRoomTypeState();

    // Accordion interaction
    document.querySelectorAll('[data-faq-toggle]').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.melia-faq-item');
            const isOpen = item.classList.contains('is-open');

            document.querySelectorAll('.melia-faq-item').forEach(i => {
                if (i !== item) i.classList.remove('is-open');
            });

            if (isOpen) {
                item.classList.remove('is-open');
            } else {
                item.classList.add('is-open');
            }
        });
    });

    // GSAP animations if present
    if (window.gsap && window.ScrollTrigger) {
        gsap.from('.melia-room-card', {
            scrollTrigger: {
                trigger: '#rooms-cards-grid',
                start: 'top 85%',
                toggleActions: 'play none none none'
            },
            y: 35,
            opacity: 0,
            duration: 0.65,
            stagger: 0.1,
            ease: 'power3.out',
            clearProps: 'transform'
        });
    }
});
</script>
@endpush
