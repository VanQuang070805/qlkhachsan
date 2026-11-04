<div class="melia-container">
    <div class="melia-section-header">
        <div>
            <span class="melia-tag">ĐẶT PHÒNG TRỰC TUYẾN</span>
            <h2 id="iphone-showcase-heading" class="melia-title-section">Bốn bước cho kỳ nghỉ của bạn</h2>
        </div>
        <p class="melia-subtitle">Tìm phòng phù hợp và hoàn tất đặt phòng ngay trên điện thoại.</p>
    </div>
</div>

<div class="melia-container rb-iphone-showcase__grid booking-preview">
    <div class="rb-iphone-showcase__info">
        <div class="stepper stepper--framed" data-stepper data-sync-mockup>
            <div class="stepper__track" role="tablist" aria-label="Các bước đặt phòng">
                <span class="stepper__glider" aria-hidden="true"></span>
                @foreach ([['search','Tìm phòng'], ['room','Chọn phòng'], ['details','Thông tin'], ['payment','Thanh toán']] as $index => [$screen, $label])
                    <button type="button" role="tab" id="booking-preview-tab-{{ $index + 1 }}" aria-controls="booking-preview-panel-{{ $index + 1 }}" class="stepper__segment {{ $index === 0 ? 'is-active' : '' }}" data-step="{{ $index + 1 }}" data-status="{{ $index === 0 ? 'active' : 'upcoming' }}" data-screen="{{ $screen }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        <span class="stepper__badge" data-status="{{ $index === 0 ? 'active' : 'upcoming' }}">{{ $index + 1 }}</span><span class="stepper__text"><span class="stepper__title" data-status="{{ $index === 0 ? 'active' : 'upcoming' }}">{{ $label }}</span></span>
                    </button>
                @endforeach
            </div>
            <div class="stepper__body"><div class="stepper__viewport">
                @foreach ([['Chọn ngày và số khách','Nhập ngày nhận phòng, ngày trả phòng và số khách để tìm phòng phù hợp.'], ['Chọn phòng phù hợp','Xem hạng phòng, sức chứa và giá áp dụng cho ngày bạn chọn.'], ['Nhập thông tin đặt phòng','Kiểm tra thông tin liên hệ và chi tiết kỳ nghỉ trước khi tiếp tục.'], ['Thanh toán và xác nhận','Chọn phương thức thanh toán đang được hỗ trợ và kiểm tra trạng thái đơn.']] as $index => [$title, $description])
                    <div role="tabpanel" id="booking-preview-panel-{{ $index + 1 }}" aria-labelledby="booking-preview-tab-{{ $index + 1 }}" class="stepper__pane {{ $index === 0 ? 'is-active' : '' }}" data-pane="{{ $index + 1 }}">
                        <h3 class="stepper__compact-title">{{ $index + 1 }}. {{ $title }}</h3><p class="stepper__compact-desc">{{ $description }}</p>
                        @if ($index < 3)<button type="button" class="stepper__next-btn" data-stepper-goto="{{ $index + 2 }}">Bước tiếp theo <span aria-hidden="true">→</span></button>@else<a class="melia-btn-primary" href="{{ route('rooms.index') }}">Tìm phòng ngay</a>@endif
                    </div>
                @endforeach
            </div></div>
        </div>
    </div>

    @php
        $previewRooms = ($featuredRooms ?? collect())->take(2);
        $previewRoom = $previewRooms->first();
        $previewIn = \Carbon\Carbon::parse($heroCheckIn);
        $previewOut = \Carbon\Carbon::parse($heroCheckOut);
        $previewNights = max(1, $previewIn->diffInDays($previewOut));
        $previewTotal = $previewRoom
            ? \App\Models\PriceSetting::calculateTotalPrice((float) $previewRoom->price, $previewIn->toDateString(), $previewOut->toDateString())
            : null;
    @endphp

    <div class="rb-iphone-stage silver-phone-stage">
        <div class="silver-phone">
            <div class="silver-phone__viewport">
                {{-- iOS status bar: time left of the Dynamic Island, indicators on the right --}}
                <div class="silver-phone__statusbar" aria-hidden="true">
                    <span class="silver-phone__time">9:41</span>
                    <span class="silver-phone__island-gap"></span>
                    <span class="silver-phone__status-icons">
                        <svg class="sb-signal" viewBox="0 0 19 12"><rect x="0" y="7.5" width="3.2" height="4.5" rx="1"/><rect x="5.2" y="5" width="3.2" height="7" rx="1"/><rect x="10.4" y="2.5" width="3.2" height="9.5" rx="1"/><rect x="15.6" y="0" width="3.2" height="12" rx="1"/></svg>
                        <svg class="sb-wifi" viewBox="0 0 17 12"><path d="M8.5 2.3c2.4 0 4.6.9 6.3 2.5l1.3-1.3A10.6 10.6 0 0 0 8.5.4 10.6 10.6 0 0 0 .9 3.5l1.3 1.3a8.8 8.8 0 0 1 6.3-2.5Zm0 3.6c1.4 0 2.7.5 3.7 1.4l1.3-1.3a7.1 7.1 0 0 0-10 0l1.3 1.3c1-.9 2.3-1.4 3.7-1.4Zm0 3.5c-.5 0-.9.2-1.3.5l1.3 1.6 1.3-1.6c-.4-.3-.8-.5-1.3-.5Z"/></svg>
                        <svg class="sb-battery" viewBox="0 0 27 13"><rect x=".5" y=".5" width="23" height="12" rx="3.8" fill="none" stroke="currentColor" opacity=".4"/><rect x="2" y="2" width="20" height="9" rx="2.5"/><path d="M25 4.4v4.2c.8-.3 1.4-1.2 1.4-2.1S25.8 4.7 25 4.4Z" opacity=".45"/></svg>
                    </span>
                </div>

                <div class="silver-phone__navbar">
                    <strong>ROSALIZA <span>HOTEL</span></strong>
                    <i class="bi bi-list" aria-hidden="true"></i>
                </div>

                <div class="silver-phone__screens">
                    {{-- 1. Tìm phòng --}}
                    <section class="silver-screen is-active" data-iphone-screen="search" aria-label="Bước 1: Tìm phòng">
                        <h3>Tìm phòng</h3>
                        <div class="ss-hero">
                            <img src="{{ asset('images/melia/hero-hanoi-suite.jpg') }}" alt="" loading="lazy">
                            <span><strong>Rosaliza Hotel</strong><small>Hoàn Kiếm, Hà Nội</small></span>
                        </div>
                        <div class="ss-group">
                            <div class="ss-row"><i class="bi bi-calendar3"></i><span>Ngày ở</span><strong>{{ $previewIn->format('d/m') }} đến {{ $previewOut->format('d/m') }}</strong></div>
                            <div class="ss-row"><i class="bi bi-people"></i><span>Khách</span><strong>2 người lớn</strong></div>
                        </div>
                        <a href="{{ route('rooms.index') }}" class="ss-cta">Tìm phòng trống</a>
                    </section>

                    {{-- 2. Chọn phòng --}}
                    <section class="silver-screen" data-iphone-screen="room" aria-label="Bước 2: Chọn phòng">
                        <h3>Chọn phòng</h3>
                        <div class="ss-rooms">
                            @forelse ($previewRooms as $i => $room)
                                <a class="ss-room {{ $i === 0 ? 'is-selected' : '' }}" href="{{ route('rooms.detail', ['id' => $room->id]) }}">
                                    <img src="{{ $room->image_url }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}'">
                                    <span class="ss-room__body">
                                        <strong>{{ $room->type_name }}</strong>
                                        <small>{{ $room->max_guests }} khách</small>
                                    </span>
                                    <b>{{ number_format(\App\Models\PriceSetting::calculateTotalPrice((float) $room->price, $previewIn->toDateString(), $previewOut->toDateString()) / $previewNights, 0, ',', '.') }}đ/đêm</b>
                                </a>
                            @empty
                                <p class="ss-empty">Danh sách phòng đang được cập nhật.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('rooms.index') }}" class="ss-cta">Tiếp tục</a>
                    </section>

                    {{-- 3. Thông tin --}}
                    <section class="silver-screen" data-iphone-screen="details" aria-label="Bước 3: Thông tin đặt phòng">
                        <h3>Thông tin</h3>
                        @if ($previewRoom)
                            <div class="ss-chip"><i class="bi bi-door-closed"></i>{{ $previewRoom->type_name }} · {{ $previewNights }} đêm</div>
                        @endif
                        <div class="ss-fields">
                            <label class="ss-field"><span>Họ và tên</span><em>Nhập khi đặt phòng</em></label>
                            <label class="ss-field"><span>Số điện thoại</span><em>Nhập khi đặt phòng</em></label>
                            <label class="ss-field"><span>Email</span><em>Nhập khi đặt phòng</em></label>
                        </div>
                        <a href="{{ route('rooms.index') }}" class="ss-cta">Tiếp tục</a>
                    </section>

                    {{-- 4. Thanh toán --}}
                    <section class="silver-screen" data-iphone-screen="payment" aria-label="Bước 4: Thanh toán">
                        <h3>Thanh toán</h3>
                        <div class="ss-total">
                            <span>Giá tham khảo · {{ $previewNights }} đêm</span>
                            <strong>{{ $previewTotal === null ? 'Chưa cập nhật' : number_format($previewTotal, 0, ',', '.') . 'đ' }}</strong>
                        </div>
                        <div class="ss-methods">
                            <div class="ss-method is-selected"><i class="bi bi-qr-code"></i><span>VietQR</span><i class="bi bi-check-circle-fill ss-method__tick"></i></div>
                            <div class="ss-method"><i class="bi bi-building"></i><span>Tại quầy lễ tân</span><i class="bi bi-circle ss-method__tick"></i></div>
                        </div>
                        <a href="{{ route('rooms.index') }}" class="ss-cta">Xác nhận đặt phòng</a>
                    </section>
                </div>
                <span class="silver-phone__home-indicator" aria-hidden="true"></span>
            </div>
            <img class="silver-phone__frame" src="{{ asset('images/mockups/iphone-17-pro-silver-frame.svg') }}" alt="Khung iPhone 17 Pro Silver" width="439" height="916" loading="lazy" decoding="async">
        </div>
    </div>
</div>
