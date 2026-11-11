@extends('client.layouts.app')
@section('title', 'Rosaliza Hotel HaNoi · Boutique Luxury in Hoàn Kiếm, Hanoi')
@section('content')
@php
    $heroCheckIn = $checkIn;
    $heroCheckOut = $checkOut;
@endphp

<div class="melia-home-shell">

    {{-- =========================================================================
         1. HERO SECTION — SPLIT SCREEN CINEMATIC ARRIVAL
         ========================================================================= --}}
    <section id="hero" class="melia-hero" aria-label="Chào mừng đến với Rosaliza Hotel HaNoi">
        <div class="melia-hero__split">
            {{-- Left Column: Hotel Info --}}
            <div class="melia-hero__info-col">
                <h1 class="melia-title-display" data-rb-split="words,chars">
                    Rosaliza Hotel HaNoi
                </h1>


                <p class="melia-hero__desc" data-rb-blur="words">
                    Nép mình trên phố Trần Quốc Toản, Rosaliza Hotel HaNoi mang đến một khoảng nghỉ thanh lịch giữa lòng Thủ đô, nơi di sản Hà Nội gặp gỡ phong cách Quiet Luxury.
                </p>

                <div class="melia-hero__action">
                    <a href="{{ route('rooms.index') }}" class="melia-btn-primary rb-star-border">
                        <i class="bi bi-calendar-check" aria-hidden="true"></i>
                        <span>Khám phá &amp; Đặt phòng</span>
                        <span style="opacity: 0.6; margin-left: 2px;" aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            {{-- Right Column: React Bits AccordionGallery with GSAP 3D Interactive Panels --}}
            <div class="melia-hero__image-col">
                @php
                    $heroGalleryRooms = collect($featuredRooms ?? [])->concat($roomTypes ?? [])->unique('id')->values();
                    $heroItems = [];
                    foreach ($heroGalleryRooms->take(5) as $idx => $room) {
                        $img = is_array($room->images) && count($room->images) ? $room->images[0] : $room->image;
                        $imgUrl = $img
                            ? (str_starts_with($img, 'images/') ? asset($img) : asset('storage/'.$img))
                            : asset('images/rooms/'.(($idx % 6) + 1).'.jpg');
                        $heroItems[] = [
                            'image' => $imgUrl,
                            'label' => $room->type_name,
                        ];
                    }
                    $fallbackHeroItems = [
                        ['image' => asset('images/rooms/albums/room-5-vip-1.jpg'), 'label' => 'Presidential Suite'],
                        ['image' => asset('images/rooms/albums/room-4-family-1.jpg'), 'label' => 'Royal Family Suite'],
                        ['image' => asset('images/rooms/albums/room-2-double-1.jpg'), 'label' => 'Deluxe Twin View'],
                    ];
                    $fIdx = 0;
                    while (count($heroItems) < 5 && $fIdx < count($fallbackHeroItems)) {
                        $heroItems[] = $fallbackHeroItems[$fIdx++];
                    }
                @endphp
                <div class="accordion-gallery" data-accordion-gallery data-default-index="1" role="list" aria-label="Bộ sưu tập không gian Rosaliza Hotel">
                    @foreach ($heroItems as $idx => $item)
                        <div class="ag-panel {{ $idx === 1 ? 'ag-panel--active' : '' }}" role="listitem" tabindex="0" aria-current="{{ $idx === 1 ? 'true' : 'false' }}" aria-label="{{ $item['label'] }}">
                            <span class="ag-panel__frame">
                                <span class="ag-panel__media">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['label'] }}" draggable="false" loading="{{ $idx <= 1 ? 'eager' : 'lazy' }}">
                                </span>
                                <span class="ag-panel__overlay" aria-hidden="true"></span>
                            </span>
                            <span class="ag-panel__label" aria-hidden="true">
                                <span class="ag-panel__bar"></span>
                                <span class="ag-panel__text">{{ $item['label'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         2.5. IPHONE 17 PRO SILVER MOCKUP: BOOKING JOURNEY
         ========================================================================= --}}
    <section id="hotel-interactive-mockup" class="rb-iphone-showcase" data-iphone-showcase aria-labelledby="iphone-showcase-heading">
        @include('client.partials.booking-preview')
    </section>



            {{-- =========================================================================
         4. ACCOMMODATION: FIGMA 5-SLOT COVERFLOW CAROUSEL WITH ALBUMS
         - 3 Center cards: 300x406, sharp, full size, interactive photo albums
         - 2 Edge cards: 300x358, blurred (1.5px), scaled down, lower opacity
         - Smooth coverflow transition: auto-sharpens and expands on moving to center
         ========================================================================= --}}
    <section id="hotel-accommodation-rooms" class="figma-rooms-section">
        <div class="figma-rooms-header">
            <h2 class="figma-rooms-title">Hạng phòng tại Rosaliza</h2>
            <p class="figma-rooms-availability-note">Giá và số phòng trống cho kỳ nghỉ {{ \Carbon\Carbon::parse($checkIn)->format('d/m') }}–{{ \Carbon\Carbon::parse($checkOut)->format('d/m/Y') }}; cập nhật từ hệ thống khi tải trang.</p>
        </div>

        <div class="figma-carousel-wrapper">
            {{-- Navigation Left Arrow --}}
            <button type="button" class="figma-carousel-btn figma-carousel-btn--prev" data-carousel-prev aria-label="Xem phòng trước">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Viewport containing the 5 cards --}}
            <div class="figma-carousel-viewport" data-carousel-viewport>
                <div class="figma-carousel-track" data-carousel-track>
                    @php
                        $baseRooms = (isset($featuredRooms) && $featuredRooms->count() > 0) ? $featuredRooms : (isset($roomTypes) ? $roomTypes : collect());
                        // Loop to provide continuous coverflow (12 cards total)
                        $repeatedRooms = collect();
                        for ($i = 0; $i < 3; $i++) {
                            $repeatedRooms = $repeatedRooms->concat($baseRooms);
                        }

                        // Curated luxury photo albums for each room type (1080p HD, authentic to room type)
                        $roomAlbums = [
                            1 => [
                                asset('images/rooms/albums/room-1-single-1.jpg'),
                                asset('images/rooms/albums/room-1-single-2.jpg'),
                                asset('images/rooms/albums/room-1-single-3.jpg'),
                                asset('images/rooms/albums/room-1-single-4.jpg'),
                            ],
                            2 => [
                                asset('images/rooms/albums/room-2-double-1.jpg'),
                                asset('images/rooms/albums/room-2-double-2.jpg'),
                                asset('images/rooms/albums/room-2-double-3.jpg'),
                                asset('images/rooms/albums/room-2-double-4.jpg'),
                            ],
                            3 => [
                                asset('images/rooms/albums/room-3-triple-1.jpg'),
                                asset('images/rooms/albums/room-3-triple-2.jpg'),
                                asset('images/rooms/albums/room-3-triple-3.jpg'),
                                asset('images/rooms/albums/room-3-triple-4.jpg'),
                            ],
                            4 => [
                                asset('images/rooms/albums/room-4-family-1.jpg'),
                                asset('images/rooms/albums/room-4-family-2.jpg'),
                                asset('images/rooms/albums/room-4-family-3.jpg'),
                                asset('images/rooms/albums/room-4-family-4.jpg'),
                            ],
                            5 => [
                                asset('images/rooms/albums/room-5-vip-1.jpg'),
                                asset('images/rooms/albums/room-5-vip-2.jpg'),
                                asset('images/rooms/albums/room-5-vip-3.jpg'),
                                asset('images/rooms/albums/room-5-vip-4.jpg'),
                            ],
                        ];
                    @endphp

                    @foreach($repeatedRooms as $cardIndex => $room)
                        @php
                            $roomName = $room->type_name;
                            $roomPrice = $room->display_price;
                            $roomCapacity = (int) $room->max_guests;
                            $roomReviewCount = (int) ($room->reviews_count ?? 0);
                            $roomRating = $roomReviewCount > 0
                                ? number_format((float) $room->reviews_avg_rating, 1, ',', '.')
                                : null;
                            $album = $roomAlbums[$room->id] ?? [
                                asset('images/rooms/albums/room-'.((($room->id - 1) % 5) + 1).'-single-1.jpg'),
                                asset('images/rooms/albums/room-'.((($room->id - 1) % 5) + 1).'-single-2.jpg'),
                                asset('images/rooms/albums/room-'.((($room->id - 1) % 5) + 1).'-single-3.jpg'),
                                asset('images/rooms/albums/room-'.((($room->id - 1) % 5) + 1).'-single-4.jpg'),
                            ];
                        @endphp

                        <div class="figma-room-card" data-card-index="{{ $cardIndex }}">
                            {{-- Image Album Box --}}
                            <div class="figma-room-card__media" data-card-album>
                                @foreach($album as $imgIdx => $imgSrc)
                                    <img src="{{ $imgSrc }}" alt="{{ $roomName }} - Ảnh {{ $imgIdx + 1 }}" class="figma-album-img {{ $imgIdx === 0 ? 'is-active' : '' }}" data-album-img="{{ $imgIdx }}" loading="lazy">
                                @endforeach

                                {{-- Rating Pill (Top Left) --}}
                                <div class="figma-room-card__rating" title="{{ $roomReviewCount > 0 ? $roomReviewCount . ' đánh giá đã xác thực' : 'Chưa có đánh giá đã xác thực' }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#C9BEB3" aria-hidden="true">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <span>{{ $roomRating ?? 'Chưa có đánh giá' }}</span>
                                </div>

                                {{-- Favorite Button (Top Right) --}}
                                <button type="button" class="figma-room-card__fav" aria-label="Yêu thích phòng" onclick="event.preventDefault(); event.stopPropagation(); this.classList.toggle('is-favorited');">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>

                                {{-- Album Dots (Bottom Center) --}}
                                <div class="figma-room-card__dots" data-album-dots>
                                    @foreach($album as $imgIdx => $imgSrc)
                                        <button type="button" class="dot {{ $imgIdx === 0 ? 'dot--active' : '' }}" data-dot-idx="{{ $imgIdx }}" aria-label="Ảnh {{ $imgIdx + 1 }}"></button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Card Information (Under Image) --}}
                            <div class="figma-room-card__info">
                                {{-- Row 1: Title & Price --}}
                                <div class="figma-room-card__row figma-room-card__row--top">
                                    <a href="{{ route('rooms.index', ['search' => 1, 'q' => $roomName, 'check_in' => $heroCheckIn, 'check_out' => $heroCheckOut, 'adults' => 1, 'children' => 0]) }}" class="figma-room-card__title-link">
                                        <h3 class="figma-room-card__name">{{ $roomName }}</h3>
                                    </a>
                                    <div class="figma-room-card__price" @if($roomPrice !== null) data-count-up="{{ (int) $roomPrice }}" @endif>
                                        {{ $roomPrice === null ? 'Chưa cập nhật' : number_format($roomPrice, 0, ',', '.') . ' đ' }}
                                    </div>
                                </div>

                                {{-- Row 2: Specs (Bed, Area) & Unit --}}
                                <div class="figma-room-card__row figma-room-card__row--bottom">
                                    <div class="figma-room-card__specs">
                                        <span class="figma-spec-item" title="Sức chứa">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: #C9BEB3;" aria-hidden="true">
                                                <path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/>
                                            </svg>
                                            <span>{{ $roomCapacity }} khách</span>
                                        </span>

                                        <span class="figma-spec-item" title="Số phòng đang sẵn sàng">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C9BEB3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="3" y="3" width="18" height="18" rx="4" ry="4"></rect>
                                                <path d="M3 9h4M3 15h4M9 3v4M15 3v4"></path>
                                            </svg>
                                            <span data-count-up="{{ (int) ($room->available_count ?? 0) }}">{{ (int) ($room->available_count ?? 0) }} phòng trống</span>
                                        </span>
                                    </div>

                                    <span class="figma-room-card__unit">/ đêm</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation Right Arrow --}}
            <button type="button" class="figma-carousel-btn figma-carousel-btn--next" data-carousel-next aria-label="Xem phòng tiếp theo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const track = document.querySelector('[data-carousel-track]');
        const viewport = document.querySelector('[data-carousel-viewport]');
        const prevBtn = document.querySelector('[data-carousel-prev]');
        const nextBtn = document.querySelector('[data-carousel-next]');
        const cards = document.querySelectorAll('.figma-room-card');
        if (!track || !cards.length) return;

        const isCompact = () => window.matchMedia('(max-width: 1024px)').matches;
        const cardStep = () => cards[0].offsetWidth + parseFloat(getComputedStyle(track).gap || 0);
        let centerStart = 1; // index 1, 2, 3 are center; index 0 is left edge; index 4 is right edge
        const maxIndex = () => isCompact() ? cards.length - 1 : cards.length - 4;

        function updateCoverflow() {
            cards.forEach((card, idx) => {
                const isCenter = isCompact() ? idx === centerStart : (idx >= centerStart && idx <= centerStart + 2);
                const isEdge = isCompact() ? idx !== centerStart : (idx === centerStart - 1 || idx === centerStart + 3);

                if (isCenter) {
                    card.classList.add('is-center');
                    card.classList.remove('is-edge');
                } else if (isEdge) {
                    card.classList.add('is-edge');
                    card.classList.remove('is-center');
                } else {
                    card.classList.remove('is-center');
                    card.classList.add('is-edge');
                }
            });

            // Translate track so that the 5 cards are positioned with the 3 center cards centered
            const offset = isCompact()
                ? cards[centerStart].offsetLeft + cards[centerStart].offsetWidth / 2 - viewport.clientWidth / 2
                : cards[centerStart + 1].offsetLeft + cards[centerStart + 1].offsetWidth / 2 - viewport.clientWidth / 2;
            track.style.transform = `translateX(-${offset}px)`;
        }

        updateCoverflow();
        window.addEventListener('resize', updateCoverflow);

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (centerStart < maxIndex()) {
                    centerStart++;
                } else {
                    centerStart = 1; // Loop back
                }
                track.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
                updateCoverflow();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                if (centerStart > 1) {
                    centerStart--;
                } else {
                    centerStart = maxIndex(); // Loop to end
                }
                track.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
                updateCoverflow();
            });
        }

        // =====================================================================
        // MOUSE DRAG SCRUBBING (Hold left-click to scrub cards smoothly)
        // =====================================================================
        let isDragging = false;
        let startX = 0;
        let currentX = 0;
        let initialTranslate = 0;
        let hasDragged = false;

        const getTranslateX = () => new DOMMatrixReadOnly(getComputedStyle(track).transform).m41;

        const onPointerDown = (e) => {
            // Only primary button (left mouse) or touch
            if (e.button !== undefined && e.button !== 0) return;
            // Don't drag if clicking dot or fav button
            if (e.target.closest('.figma-room-card__dots') || e.target.closest('.figma-room-card__fav')) return;

            isDragging = true;
            hasDragged = false;
            startX = e.clientX;
            currentX = e.clientX;
            initialTranslate = getTranslateX();
            track.style.transition = 'none'; // Instant responsive scrub without lag
            track.classList.add('is-dragging');
            if (viewport) viewport.classList.add('is-dragging');
        };

        const onPointerMove = (e) => {
            if (!isDragging) return;
            currentX = e.clientX;
            const deltaX = currentX - startX;
            if (Math.abs(deltaX) > 6) {
                hasDragged = true;
            }
            track.style.transform = `translateX(${initialTranslate + deltaX}px)`;
        };

        const onPointerUp = (e) => {
            if (!isDragging) return;
            isDragging = false;
            track.classList.remove('is-dragging');
            if (viewport) viewport.classList.remove('is-dragging');
            track.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)';

            const deltaX = currentX - startX;
            const threshold = isCompact() ? 38 : 70;

            if (deltaX < -threshold) {
                const steps = Math.min(3, Math.max(1, Math.round(Math.abs(deltaX) / cardStep())));
                centerStart = Math.min(maxIndex(), centerStart + steps);
            } else if (deltaX > threshold) {
                const steps = Math.min(3, Math.max(1, Math.round(deltaX / cardStep())));
                centerStart = Math.max(1, centerStart - steps);
            }

            updateCoverflow();
        };

        track.addEventListener('pointerdown', onPointerDown);
        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', onPointerUp);
        window.addEventListener('pointercancel', onPointerUp);

        // Suppress room link navigation if the user was dragging/scrubbing
        track.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function (e) {
                if (hasDragged) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });

        // Photo Album Inside Each Card
        cards.forEach((card) => {
            const albumContainer = card.querySelector('[data-card-album]');
            const dots = card.querySelectorAll('[data-dot-idx]');
            const images = card.querySelectorAll('[data-album-img]');
            if (!albumContainer || !dots.length || !images.length) return;

            dots.forEach((dot) => {
                const switchDot = function (e) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    const targetIdx = parseInt(this.getAttribute('data-dot-idx'), 10);

                    // Switch image
                    images.forEach((img, i) => {
                        img.classList.toggle('is-active', i === targetIdx);
                    });

                    // Switch dot
                    dots.forEach((d, i) => {
                        d.classList.toggle('dot--active', i === targetIdx);
                    });
                };

                dot.addEventListener('click', switchDot);
                dot.addEventListener('mouseenter', switchDot);
            });

            // Hover scrubbing across the album: moving mouse from left to right previews photos
            albumContainer.addEventListener('mousemove', function (e) {
                if (e.target.closest('.figma-room-card__fav') || e.target.closest('.figma-room-card__dots')) return;
                const rect = albumContainer.getBoundingClientRect();
                if (rect.width <= 0) return;
                const ratio = Math.max(0, Math.min(0.999, (e.clientX - rect.left) / rect.width));
                const targetIdx = Math.floor(ratio * images.length);
                images.forEach((img, i) => {
                    img.classList.toggle('is-active', i === targetIdx);
                });
                dots.forEach((d, i) => {
                    d.classList.toggle('dot--active', i === targetIdx);
                });
            });

            // Clicking on left/right side of image switches to prev/next photo
            albumContainer.addEventListener('click', function (e) {
                // If clicked favorite button or dot, ignore
                if (e.target.closest('.figma-room-card__fav') || e.target.closest('.figma-room-card__dots')) return;

                const rect = albumContainer.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const isRightHalf = clickX > (rect.width / 2);

                let currentActive = 0;
                images.forEach((img, i) => {
                    if (img.classList.contains('is-active')) currentActive = i;
                });

                let nextIdx = isRightHalf ? (currentActive + 1) % images.length : (currentActive - 1 + images.length) % images.length;

                images.forEach((img, i) => {
                    img.classList.toggle('is-active', i === nextIdx);
                });
                dots.forEach((d, i) => {
                    d.classList.toggle('dot--active', i === nextIdx);
                });
            });
        });
    });
    </script>

    {{-- =========================================================================
         4.5. EDITORIAL VISUAL LOOKBOOK & REACT BITS FLOWING MENU
         ========================================================================= --}}
    <section id="hotel-visual-lookbook" class="rb-lookbook-section" aria-labelledby="lookbook-heading">
        <div class="melia-container">
            <div class="melia-section-header">
                <div>
                    <h2 id="lookbook-heading" class="melia-title-section" data-rb-blur="words">
                        Visual Lookbook &amp; Sensory Moments
                    </h2>
                </div>
                <p class="melia-subtitle">Chạm vào nhịp sống Hà Nội qua những góc phố, món ngon và dấu ấn di sản quanh Rosaliza Hotel.</p>
            </div>
        </div>

        @php
            $hanoiGalleryItems = collect([
                [
                    'src' => asset('images/hanoi/hanoi-hoan-kiem.jpg'),
                    'alt' => 'Hồ Hoàn Kiếm & Đền Ngọc Sơn',
                    'title' => 'Hồ Hoàn Kiếm & Cầu Thê Húc',
                    'subtitle' => 'Dạo bước bên hồ, ngắm Tháp Rùa và nhịp phố thức giấc trong sương sớm.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-pho-bo.jpg'),
                    'alt' => 'Phở Bò Hà Nội Cổ Truyền',
                    'title' => 'Phở Bò Hà Nội Cổ Truyền',
                    'subtitle' => 'Thưởng thức bát phở nóng với nước dùng trong, thơm dịu và đậm vị Hà Nội.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-long-bien.jpg'),
                    'alt' => 'Hoàng Hôn Cầu Long Biên',
                    'title' => 'Hoàng Hôn Cầu Long Biên',
                    'subtitle' => 'Ngắm hoàng hôn trên cây cầu lịch sử bắc qua sông Hồng.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-egg-coffee.jpg'),
                    'alt' => 'Cà Phê Trứng Hà Thành',
                    'title' => 'Cà Phê Trứng Giảng',
                    'subtitle' => 'Vị cà phê đậm hòa cùng lớp kem trứng mềm mịn đặc trưng phố cổ.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-temple-literature.jpg'),
                    'alt' => 'Văn Miếu Quốc Tử Giám',
                    'title' => 'Văn Miếu Quốc Tử Giám',
                    'subtitle' => 'Khám phá không gian cổ kính và truyền thống hiếu học của người Việt.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-spring-rolls.jpg'),
                    'alt' => 'Nem Rán & Bún Chả Phố Cổ',
                    'title' => 'Nem Rán & Bún Chả Phố Cổ',
                    'subtitle' => 'Nếm thử nem rán giòn và bún chả thơm lừng giữa lòng phố cổ.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-old-quarter.jpg'),
                    'alt' => 'Phố Cổ 36 Phố Phường',
                    'title' => 'Phố Cổ 36 Phố Phường',
                    'subtitle' => 'Len qua mái ngói cũ, hàng quán nhỏ và nhịp sống đặc trưng của Hà Nội.'
                ],
                [
                    'src' => asset('images/hanoi/hanoi-culinary-heritage.jpg'),
                    'alt' => 'Tinh Hoa Mỹ Vị Hà Thành',
                    'title' => 'Tinh Hoa Mỹ Vị Hà Thành',
                    'subtitle' => 'Cảm nhận sự giao hòa giữa hương vị truyền thống và phong vị Hà Nội đương đại.'
                ],
            ]);
        @endphp
        @if ($hanoiGalleryItems->isNotEmpty())
        <div class="flex-carousel-stage flex-carousel-stage--fullbleed">
            <div class="flex-carousel"
                 data-flex-carousel
                 data-items="{{ $hanoiGalleryItems->toJson() }}"
                 data-preset="liquid"
                 data-intro="rise"
                 data-card-height="0.52"
                 data-gap="16"
                 data-radius="18"
                 data-squeeze="0.2"
                 data-focus-on-click="true"
                 data-captions="true"
                 role="region"
                 aria-roledescription="carousel"
                 aria-label="Khám phá ẩm thực & cảnh quan Hà Nội">
            </div>
        </div>
        @endif

        <div class="melia-container">
            {{-- React Bits FlowingMenu Interactive Strip --}}
            <div class="rb-flowing-menu" data-rb-flowing-menu aria-label="Danh mục trải nghiệm đặc quyền">
                <div class="rb-flowing-item">
                    <a href="{{ route('rooms.index') }}" class="rb-flowing-link">
                        <div class="rb-flowing-link__left">
                            <span class="rb-flowing-link__idx">I</span>
                            <span class="rb-flowing-link__title">Các hạng phòng</span>
                        </div>
                        <span class="rb-flowing-link__meta">Khám phá phòng <i class="bi bi-arrow-up-right"></i></span>
                    </a>
                    <div class="rb-flowing-marquee" aria-hidden="true">
                        <div class="rb-flowing-marquee__track">
                            @for($m = 0; $m < 4; $m++)
                                <span class="rb-flowing-marquee__unit">
                                    <span>Tìm phòng phù hợp</span>
                                    <img src="{{ asset('images/rooms/1.jpg') }}" alt="" class="rb-flowing-marquee__thumb">
                                    <span>Chọn ngày lưu trú</span>
                                    <img src="{{ asset('images/rooms/2.jpg') }}" alt="" class="rb-flowing-marquee__thumb">
                                </span>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="rb-flowing-item">
                    <a href="#hotel-activities" class="rb-flowing-link" data-gsap-scroll-to>
                        <div class="rb-flowing-link__left">
                            <span class="rb-flowing-link__idx">II</span>
                            <span class="rb-flowing-link__title">Ẩm thực Hà Nội</span>
                        </div>
                        <span class="rb-flowing-link__meta">Khám phá Hà Nội <i class="bi bi-arrow-down-right"></i></span>
                    </a>
                    <div class="rb-flowing-marquee" aria-hidden="true">
                        <div class="rb-flowing-marquee__track">
                            @for($m = 0; $m < 4; $m++)
                                <span class="rb-flowing-marquee__unit">
                                    <span>Ẩm thực Hà Thành Đương Đại</span>
                                    <img src="{{ asset('images/hanoi/hanoi-pho-bo.jpg') }}" alt="" class="rb-flowing-marquee__thumb">
                                    <span>Hương vị Hà Nội</span>
                                    <img src="{{ asset('images/hanoi/hanoi-egg-coffee.jpg') }}" alt="" class="rb-flowing-marquee__thumb">
                                </span>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="rb-flowing-item">
                    <a href="{{ route('contact') }}" class="rb-flowing-link">
                        <div class="rb-flowing-link__left">
                            <span class="rb-flowing-link__idx">III</span>
                            <span class="rb-flowing-link__title">Khám phá Hà Nội</span>
                        </div>
                        <span class="rb-flowing-link__meta">Liên hệ khách sạn <i class="bi bi-arrow-up-right"></i></span>
                    </a>
                    <div class="rb-flowing-marquee" aria-hidden="true">
                        <div class="rb-flowing-marquee__track">
                            @for($m = 0; $m < 4; $m++)
                                <span class="rb-flowing-marquee__unit">
                                    <span>Lên kế hoạch chuyến đi</span>
                                    <img src="{{ asset('images/hanoi/hanoi-hoan-kiem.jpg') }}" alt="" class="rb-flowing-marquee__thumb">
                                    <span>Khám phá Phố Cổ</span>
                                    <img src="{{ asset('images/hanoi/hanoi-old-quarter.jpg') }}" alt="" class="rb-flowing-marquee__thumb">
                                </span>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         6. ACTIVITIES & EXCURSIONS (1:1 MELIÁ ACTIVITIES)
         ========================================================================= --}}
    <section id="hotel-activities" class="melia-activities">
        <div class="melia-container">
            <div class="melia-section-header">
                <div>
                    <h2 class="melia-title-section">Explore Hoan Kiem &amp; Cultural Experiences</h2>
                </div>
                <p class="melia-subtitle">
                    Ba gợi ý nhẹ nhàng để cảm nhận văn hóa, ẩm thực và nhịp sống Hà Nội.
                </p>
            </div>

            <div class="melia-activities__grid" data-scroll-stack>
                {{-- Activity 1: Hoan Kiem Walk --}}
                <div class="melia-activity-slot" data-stack-slot>
                <article class="melia-activity-card" data-rb-spotlight>
                    <div class="melia-activity-card__thumb" data-rb-glare>
                        <img src="{{ asset('images/hanoi/hanoi-hoan-kiem.jpg') }}" alt="Khám phá Hồ Gươm và 36 Phố Phường" loading="lazy">
                    </div>
                    <div class="melia-activity-card__content">
                        <h3 class="melia-activity-card__title">Hồ Gươm &amp; 36 Phố Phường</h3>
                        <p class="melia-activity-card__desc">Tản bộ bên Hồ Hoàn Kiếm, ngắm Tháp Rùa trong sương sớm và cảm nhận nhịp phố vừa thức giấc.</p>
                    </div>
                </article>
                </div>

                {{-- Activity 2: Lotus Tea Ceremony --}}
                <div class="melia-activity-slot" data-stack-slot>
                <article class="melia-activity-card" data-rb-spotlight>
                    <div class="melia-activity-card__thumb" data-rb-glare>
                        <img src="{{ asset('images/hanoi/hanoi-egg-coffee.jpg') }}" alt="Văn hóa trà Việt" loading="lazy">
                    </div>
                    <div class="melia-activity-card__content">
                        <h3 class="melia-activity-card__title">Văn hóa trà Việt</h3>
                        <p class="melia-activity-card__desc">Dành một khoảng lặng để thưởng trà và tìm hiểu nét thanh nhã trong văn hóa Việt.</p>
                    </div>
                </article>
                </div>

                {{-- Activity 3: Culinary & Heritage Walk --}}
                <div class="melia-activity-slot" data-stack-slot>
                <article class="melia-activity-card" data-rb-spotlight>
                    <div class="melia-activity-card__thumb" data-rb-glare>
                        <img src="{{ asset('images/hanoi/hanoi-pho-bo.jpg') }}" alt="Ẩm thực Phố Cổ & Di sản Pháp Cổ" loading="lazy">
                    </div>
                    <div class="melia-activity-card__content">
                        <h3 class="melia-activity-card__title">Ẩm thực &amp; Di sản Pháp Cổ</h3>
                        <p class="melia-activity-card__desc">Thưởng thức cà phê trứng, phở Hà Nội và dạo quanh những công trình kiến trúc Pháp cổ.</p>
                    </div>
                </article>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         8. FAQ ACCORDION (1:1 MELIÁ FAQS)
         ========================================================================= --}}
    <section id="hotel-faqs" class="melia-faqs">
        <div class="melia-container">
            <div class="melia-section-header">
                <div>
                    <h2 class="melia-title-section">FAQ for Rosaliza Hotel HaNoi</h2>
                </div>
                <p class="melia-subtitle">Thông tin cần biết trước khi đặt phòng và bắt đầu kỳ nghỉ.</p>
            </div>

            <div class="melia-faq-list">
                {{-- FAQ 1 --}}
                <div class="melia-faq-item is-open">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Giờ nhận và trả phòng là khi nào?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Hệ thống cho phép nhận phòng từ 12:00 đến trước 16:00 và trả phòng trước 12:00. Vui lòng liên hệ lễ tân nếu cần hỗ trợ thêm.
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="melia-faq-item">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Vị trí khách sạn có gần Hồ Hoàn Kiếm và các điểm tham quan không?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Khách sạn nằm tại 5B Trần Quốc Toản, Hàng Bài, Hoàn Kiếm, Hà Nội. Bạn có thể xem vị trí và đường đi tại trang liên hệ.
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="melia-faq-item">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Khách sạn có dịch vụ xe đưa đón sân bay Nội Bài không?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Vui lòng liên hệ lễ tân để kiểm tra khả năng hỗ trợ, chi phí và điều kiện đưa đón cho chuyến đi của bạn.
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="melia-faq-item">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Các tiện ích và dịch vụ nổi bật tại Rosaliza Hotel HaNoi gồm những gì?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Tiện nghi được cập nhật theo từng hạng phòng. Bạn có thể xem chi tiết khi chọn phòng hoặc liên hệ lễ tân để được tư vấn.
                    </div>
                </div>

                {{-- FAQ 5 --}}
                <div class="melia-faq-item">
                    <button type="button" class="melia-faq-btn" data-faq-toggle>
                        <span>Những phương thức thanh toán nào được chấp nhận?</span>
                        <i class="bi bi-plus-lg melia-faq-icon"></i>
                    </button>
                    <div class="melia-faq-answer">
                        Các phương thức khả dụng được hiển thị tại bước thanh toán và có thể khác nhau tùy đơn đặt phòng.
                    </div>
                </div>
            </div>
        </div>
    </section>



</div>

{{-- Interactive Accordion Script for FAQs --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const faqButtons = document.querySelectorAll('[data-faq-toggle]');
    faqButtons.forEach((btn, index) => {
        const item = btn.closest('.melia-faq-item');
        const answer = item?.querySelector('.melia-faq-answer');
        const answerId = `hotel-faq-answer-${index + 1}`;
        if (answer) {
            answer.id = answerId;
            answer.setAttribute('role', 'region');
            answer.setAttribute('aria-labelledby', `hotel-faq-button-${index + 1}`);
            answer.hidden = !item.classList.contains('is-open');
        }
        btn.id = `hotel-faq-button-${index + 1}`;
        btn.setAttribute('aria-controls', answerId);
        btn.setAttribute('aria-expanded', String(item?.classList.contains('is-open')));
        btn.addEventListener('click', function() {
            const item = this.closest('.melia-faq-item');
            const isOpen = item.classList.contains('is-open');

            // Close other items
            document.querySelectorAll('.melia-faq-item').forEach(i => {
                if (i === item) return;
                i.classList.remove('is-open');
                i.querySelector('[data-faq-toggle]')?.setAttribute('aria-expanded', 'false');
                const otherAnswer = i.querySelector('.melia-faq-answer');
                if (otherAnswer) otherAnswer.hidden = true;
            });

            // Toggle current
            if (isOpen) {
                item.classList.remove('is-open');
            } else {
                item.classList.add('is-open');
            }
            this.setAttribute('aria-expanded', String(!isOpen));
            const currentAnswer = item.querySelector('.melia-faq-answer');
            if (currentAnswer) currentAnswer.hidden = isOpen;
        });
    });
});
</script>

@endsection
