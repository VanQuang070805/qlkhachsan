@extends('client.layouts.app')
@section('title', 'Phòng nghỉ · Rosaliza Hotel')

@section('content')
{{-- =========================================================================
     ROOMS HERO SECTION (SUPASTE ELECTRIC BLUE + DUAL-FONT HEADLINE)
     ========================================================================= --}}
<section class="page-hero supaste-rooms-hero" aria-labelledby="rooms-page-title">
    <div class="supaste-rooms-hero__inner">
        <h1 id="rooms-page-title" class="supaste-hero__headline">
            <span class="supaste-hero__headline-sans">A room for</span>
            <span class="supaste-hero__headline-serif">every journey.</span>
        </h1>
        <p class="supaste-hero__subtitle">
            Khám phá bộ sưu tập phòng nghỉ sang trọng, từ hạng phòng tiêu chuẩn tinh tế đến các căn Presidential Suite đỉnh cao tại Rosaliza Hotel.
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
<section class="rooms-search-wrap" aria-label="Tìm phòng">
    <form class="rooms-search" action="{{ route('rooms.index') }}" method="GET">
        <input type="hidden" name="search" value="1">

        {{-- 1. Hạng phòng: Multi-select Checkbox Dropdown (Image 1 reference) --}}
        <div class="rooms-search__field rooms-search__multiselect-field">
            <label id="label-room-type">Hạng phòng</label>
            <div class="rooms-select-container" id="roomTypeDropdownContainer">
                <button type="button" class="rooms-select-trigger" id="roomTypeTrigger"
                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="label-room-type roomTypeTriggerText">
                    <span class="rooms-select-text" id="roomTypeTriggerText">Tất cả hạng phòng</span>
                    <i class="bi bi-chevron-down rooms-select-chevron" aria-hidden="true"></i>
                </button>

                <input type="hidden" name="q" id="room-search-query" value="{{ old('q', $filters['q'] ?? '') }}">

                <div class="rooms-select-dropdown rooms-multiselect-dropdown" id="roomTypeMenu" role="listbox" aria-multiselectable="true" hidden>
                    <div class="rooms-select-dropdown-header">
                        <span class="rooms-select-dropdown-title">Hạng phòng</span>
                        <button type="button" class="rooms-select-clear-btn" id="roomTypeClearBtn">Bỏ chọn</button>
                    </div>
                    <div class="rooms-multiselect-list">
                        @php
                            $availableTypes = $roomTypeOptions ?? collect();
                            $oldQ = old('q', $filters['q'] ?? '');
                            $selectedTypes = array_filter(array_map('trim', explode(',', $oldQ)));
                        @endphp
                        @foreach($availableTypes as $t)
                            @php $isChecked = in_array($t, $selectedTypes) || ($oldQ === $t); @endphp
                            <label class="rooms-multiselect-item {{ $isChecked ? 'is-selected' : '' }}">
                                <input type="checkbox" class="rooms-multiselect-input" value="{{ $t }}" {{ $isChecked ? 'checked' : '' }}>
                                <span class="rooms-checkbox-box">
                                    <i class="bi bi-check2"></i>
                                </span>
                                <span class="rooms-item-label">{{ $t }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @error('q')<small id="room-search-query-error" class="rooms-search__error">{{ $message }}</small>@enderror
        </div>

        {{-- 2. Ngày nhận phòng: Không tự động điền khi chưa tìm kiếm --}}
        <div class="rooms-search__field">
            <label for="room-check-in">Nhận phòng</label>
            <input id="room-check-in" type="date" name="check_in"
                   value="{{ old('check_in', $searchSubmitted ? $filters['check_in'] : '') }}"
                   min="{{ $earliestCheckIn }}" required
                   placeholder="Chọn ngày nhận"
                   aria-invalid="{{ $errors->has('check_in') ? 'true' : 'false' }}"
                   aria-describedby="room-check-in-hint{{ $errors->has('check_in') ? ' room-check-in-error' : '' }}">
            <small id="room-check-in-hint" class="rooms-search__hint">Từ 12:00 · Trước 16:00</small>
            @error('check_in')<small id="room-check-in-error" class="rooms-search__error">{{ $message }}</small>@enderror
        </div>

        {{-- 3. Ngày trả phòng: Không tự động điền khi chưa tìm kiếm --}}
        <div class="rooms-search__field">
            <label for="room-check-out">Trả phòng</label>
            <input id="room-check-out" type="date" name="check_out"
                   value="{{ old('check_out', $searchSubmitted ? $filters['check_out'] : '') }}"
                   min="{{ !empty($filters['check_in']) && $searchSubmitted ? \Illuminate\Support\Carbon::parse($filters['check_in'])->addDay()->toDateString() : \Illuminate\Support\Carbon::parse($earliestCheckIn)->addDay()->toDateString() }}" required
                   placeholder="Chọn ngày trả"
                   aria-invalid="{{ $errors->has('check_out') ? 'true' : 'false' }}"
                   aria-describedby="{{ $errors->has('check_out') ? 'room-check-out-error' : '' }}">
            <small class="rooms-search__hint">Trước 12:00 trưa</small>
            @error('check_out')<small id="room-check-out-error" class="rooms-search__error">{{ $message }}</small>@enderror
        </div>

        {{-- 4. Người lớn: Stepper tăng giảm số lượng (Tối thiểu là 0) --}}
        <div class="rooms-search__field rooms-search__stepper-field">
            <label id="label-adults" for="room-adults">Người lớn</label>
            <div class="rooms-stepper-control">
                <button type="button" class="rooms-stepper-btn" id="btnAdultsDec" aria-label="Giảm số người lớn" onclick="adjustSearchGuest('adults', -1)">
                    <i class="bi bi-dash"></i>
                </button>
                <span class="rooms-stepper-val" id="adultsDisplayVal">{{ old('adults', $filters['adults'] ?? 1) }}</span>
                <button type="button" class="rooms-stepper-btn" id="btnAdultsInc" aria-label="Tăng số người lớn" onclick="adjustSearchGuest('adults', 1)">
                    <i class="bi bi-plus"></i>
                </button>
                <input type="hidden" name="adults" id="room-adults" value="{{ old('adults', $filters['adults'] ?? 1) }}">
            </div>
            @error('adults')<small id="room-adults-error" class="rooms-search__error">{{ $message }}</small>@enderror
        </div>

        {{-- 5. Trẻ em: Stepper tăng giảm số lượng (Tối thiểu là 0) --}}
        <div class="rooms-search__field rooms-search__stepper-field">
            <label id="label-children" for="room-children">Trẻ em</label>
            <div class="rooms-stepper-control">
                <button type="button" class="rooms-stepper-btn" id="btnChildrenDec" aria-label="Giảm số trẻ em" onclick="adjustSearchGuest('children', -1)">
                    <i class="bi bi-dash"></i>
                </button>
                <span class="rooms-stepper-val" id="childrenDisplayVal">{{ old('children', $filters['children'] ?? 0) }}</span>
                <button type="button" class="rooms-stepper-btn" id="btnChildrenInc" aria-label="Tăng số trẻ em" onclick="adjustSearchGuest('children', 1)">
                    <i class="bi bi-plus"></i>
                </button>
                <input type="hidden" name="children" id="room-children" value="{{ old('children', $filters['children'] ?? 0) }}">
            </div>
            @error('children')<small id="room-children-error" class="rooms-search__error">{{ $message }}</small>@enderror
        </div>

        <button class="rooms-search__submit" type="submit">
            <i class="bi bi-search" aria-hidden="true"></i><span>Tìm phòng</span>
        </button>
    </form>

    <p class="rooms-search__result" id="rooms-live-status" style="display: none;" role="status"></p>
    @if($searchSubmitted)
        <p class="rooms-search__result" role="status">Tìm thấy {{ $rooms->count() }} hạng phòng phù hợp với ngày lưu trú và số khách đã chọn.</p>
    @endif
</section>

{{-- =========================================================================
     ROOMS CATALOGUE SECTION (SUPASTE BENTO CARDS GRID)
     ========================================================================= --}}
<section class="supaste-section" style="padding-top: 60px; padding-bottom: 90px; max-width: 1280px; margin: 0 auto;" aria-labelledby="catalogue-title">
    <div class="supaste-section__header" style="margin-bottom: 40px;">
        <p class="supaste-section__eyebrow">BỘ SƯU TẬP PHÒNG NGHỈ</p>
        <h2 id="catalogue-title" class="supaste-section__title">Không gian lưu trú thanh lịch</h2>
        <p class="supaste-section__desc">
            Mỗi phòng nghỉ tại Rosaliza Hotel được thiết kế tỉ mỉ, kết hợp sự tiện nghi tối tân cùng cảm giác an yên tuyệt đối.
        </p>
    </div>

    @if($rooms->isNotEmpty())
        <div class="supaste-rooms-grid" id="rooms-cards-grid">
            @foreach($rooms as $room)
                <article class="supaste-room-card" data-room-name="{{ $room->type_name }}" data-room-id="{{ $room->id }}" data-reveal>
                    <div class="macos-card-bar" style="padding: 10px 16px; background: #ffffff !important; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; border-radius: 22px 22px 0 0;">
                        <div style="display: flex; gap: 6px;">
                            <span class="ctrl-dot ctrl-red"></span>
                            <span class="ctrl-dot ctrl-yellow"></span>
                            <span class="ctrl-dot ctrl-green"></span>
                        </div>
                        <span style="font-size: 11px; font-weight: 600; color: #475569; letter-spacing: -0.01em;">{{ $room->type_name }}</span>
                        <div style="width: 32px;"></div>
                    </div>
                    <a class="supaste-room-card__img" href="{{ route('rooms.detail', array_merge(['id' => $room->id], $roomSearchQuery)) }}">
                        <img src="{{ $room->image_url }}"
                             alt="{{ $room->type_name }} tại Rosaliza Hotel"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}';">
                        <span class="supaste-room-card__badge">
                            {{ $room->available_count }} phòng khả dụng
                        </span>
                    </a>
                    <div class="supaste-room-card__info" style="padding: 18px 20px 22px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 11.5px; color: #64748b; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">
                            <span>HẠNG PHÒNG</span>
                            <span>{{ $room->max_guests }} khách · {{ $room->max_adults }} người lớn · {{ $room->max_children }} trẻ em</span>
                        </div>

                        <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; line-height: 1.3;">
                            <a href="{{ route('rooms.detail', array_merge(['id' => $room->id], $roomSearchQuery)) }}" style="color: inherit; text-decoration: none;">
                                {{ $room->type_name }}
                            </a>
                        </h3>

                        <p style="font-size: 13.5px; color: #64748b; line-height: 1.6; margin: 0 0 16px 0; min-height: 44px;">
                            {{ \Illuminate\Support\Str::limit($room->description ?: 'Mô tả hạng phòng chưa được cập nhật.', 110) }}
                        </p>

                        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px;">
                            @forelse($room->amenities->take(3) as $amenity)
                                <span style="background: #f1f5f9; color: #475569; font-size: 11.5px; padding: 4px 10px; border-radius: 999px; font-weight: 500;">
                                    {{ $amenity->amenity_name }}
                                </span>
                            @empty
                                <span style="font-size: 11.5px; color: #64748b;">Chưa có tiện nghi được cập nhật</span>
                            @endforelse
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 14px; border-top: 1px solid #f1f5f9; margin-top: auto;">
                            <div>
                                <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; display: block;">Giá mỗi đêm</span>
                                <strong style="font-size: 19px; color: #0066ff; font-weight: 700;">
                                    {{ number_format((float) $room->price, 0, ',', '.') }} đ
                                </strong>
                            </div>
                            <a href="{{ route('rooms.detail', array_merge(['id' => $room->id], $roomSearchQuery)) }}" class="supaste-card-btn" style="width: auto; padding: 9px 18px;">
                                Chi tiết →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="rooms-search-empty" role="status">
            <i class="bi bi-calendar2-x" aria-hidden="true"></i>
            <p>{{ $searchSubmitted ? 'Không có hạng phòng phù hợp với ngày ở và số khách này.' : 'Hiện chưa có hạng phòng trong hệ thống.' }}</p>
            @if($searchSubmitted)
                <a href="{{ route('rooms.index') }}">Xóa bộ lọc</a>
            @else
                <a class="button button--small" href="{{ route('contact') }}">Liên hệ tư vấn</a>
            @endif
        </div>
    @endif
</section>

{{-- =========================================================================
     STANDARDS & SERVICES SECTION (MACOS FROSTED GLASS BENTO)
     ========================================================================= --}}
<section class="supaste-section" style="padding-top: 20px; padding-bottom: 80px; max-width: 1280px; margin: 0 auto;" aria-labelledby="standards-title">
    <div class="supaste-section__header">
        <p class="supaste-section__eyebrow">DỊCH VỤ &amp; TIÊU CHUẨN</p>
        <h2 id="standards-title" class="supaste-section__title">Đặc quyền nghỉ dưỡng chuẩn mực</h2>
        <p class="supaste-section__desc">
            Mỗi khoảnh khắc tại Rosaliza Hotel đều được kiến tạo từ sự chu đáo, tận tâm và tiêu chuẩn dịch vụ khách sạn 5 sao quốc tế.
        </p>
    </div>

    <div class="supaste-features-grid">
        <div class="supaste-feature-card" data-reveal>
            <div style="display: flex; gap: 5px; margin-bottom: 16px;">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <div class="supaste-feature-card__icon" style="color: #0071e3;">
                <i class="bi bi-shield-check"></i>
            </div>
            <h3>Dịch vụ quản gia 24/7</h3>
            <p>Đội ngũ Concierge và quản gia chuyên nghiệp luôn sẵn sàng hỗ trợ mọi nhu cầu lưu trú, đưa đón sân bay và ẩm thực theo yêu cầu.</p>
        </div>

        <div class="supaste-feature-card" data-reveal>
            <div style="display: flex; gap: 5px; margin-bottom: 16px;">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <div class="supaste-feature-card__icon" style="color: #10b981;">
                <i class="bi bi-cup-hot"></i>
            </div>
            <h3>Ẩm thực thượng hạng</h3>
            <p>Bữa sáng buffet phong cách quốc tế cùng thực đơn món Á - Âu tinh tế được chuẩn bị bởi các đầu bếp hàng đầu.</p>
        </div>

        <div class="supaste-feature-card" data-reveal>
            <div style="display: flex; gap: 5px; margin-bottom: 16px;">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <div class="supaste-feature-card__icon" style="color: #8b5cf6;">
                <i class="bi bi-soundwave"></i>
            </div>
            <h3>Không gian tĩnh lặng</h3>
            <p>Hệ thống cửa cách âm chuẩn studio và nệm memory foam cao cấp đảm bảo giấc ngủ sâu và trọn vẹn nhất.</p>
        </div>
    </div>
</section>

{{-- =========================================================================
     FAQ SECTION (MACOS ACCORDION)
     ========================================================================= --}}
<section class="supaste-section" style="padding-top: 20px; padding-bottom: 100px; max-width: 1280px; margin: 0 auto;" aria-labelledby="faq-title">
    <div class="supaste-section__header">
        <p class="supaste-section__eyebrow">HỎI ĐÁP LƯU TRÚ</p>
        <h2 id="faq-title" class="supaste-section__title">Câu hỏi thường gặp</h2>
        <p class="supaste-section__desc">
            Những thông tin cần biết để kỳ nghỉ của bạn diễn ra suôn sẻ và tiện lợi nhất.
        </p>
    </div>

    <div class="supaste-faq-container" style="max-width: 820px; margin: 0 auto;">
        <div class="supaste-faq-item">
            <details>
                <summary>Thời gian nhận phòng và trả phòng tiêu chuẩn là khi nào?</summary>
                <p>Giờ nhận phòng từ 12:00 đến trước 16:00 và giờ trả phòng trước 12:00 trưa hôm sau. Quý khách có thể yêu cầu trả phòng muộn tùy thuộc vào tình trạng phòng trống.</p>
            </details>
        </div>
        <div class="supaste-faq-item">
            <details>
                <summary>Chính sách hủy phòng và hoàn tiền như thế nào?</summary>
                <p>Rosaliza Hotel hỗ trợ hủy phòng hoàn toàn miễn phí nếu thực hiện trước 48 giờ so với thời điểm nhận phòng. Tiền đặt cọc sẽ được hoàn trả tự động theo phương thức thanh toán ban đầu.</p>
            </details>
        </div>
        <div class="supaste-faq-item">
            <details>
                <summary>Khách sạn có dịch vụ đón tiễn sân bay không?</summary>
                <p>Có. Chúng tôi cung cấp dịch vụ đưa đón sân bay 2 chiều bằng xe Limousine cao cấp. Đối với hạng phòng Presidential Suite, dịch vụ đón tiễn sân bay được phục vụ hoàn toàn miễn phí.</p>
            </details>
        </div>
    </div>
</section>
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

    // 3. Stepper Logic for Adults and Children (0 is the minimum)
    window.adjustSearchGuest = function(type, delta) {
        const input = document.getElementById(type === 'adults' ? 'room-adults' : 'room-children');
        const display = document.getElementById(type === 'adults' ? 'adultsDisplayVal' : 'childrenDisplayVal');
        const btnDec = document.getElementById(type === 'adults' ? 'btnAdultsDec' : 'btnChildrenDec');
        if (!input || !display) return;

        let currentVal = parseInt(input.value, 10);
        if (isNaN(currentVal)) currentVal = (type === 'adults' ? 1 : 0);

        let newVal = currentVal + delta;
        if (newVal < 0) newVal = 0; // 0 mà bé nhất nha!
        if (newVal > 20) newVal = 20;

        input.value = newVal;
        display.textContent = newVal;

        if (btnDec) {
            btnDec.disabled = (newVal === 0);
        }
    };

    function initSearchSteppers() {
        ['adults', 'children'].forEach(type => {
            const input = document.getElementById(type === 'adults' ? 'room-adults' : 'room-children');
            const display = document.getElementById(type === 'adults' ? 'adultsDisplayVal' : 'childrenDisplayVal');
            const btnDec = document.getElementById(type === 'adults' ? 'btnAdultsDec' : 'btnChildrenDec');
            if (input && display) {
                let val = parseInt(input.value, 10);
                if (isNaN(val) || val < 0) val = 0;
                display.textContent = val;
                if (btnDec) btnDec.disabled = (val === 0);
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

    // GSAP animations
    if (window.gsap && window.ScrollTrigger) {
        gsap.from('.supaste-room-card', {
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
