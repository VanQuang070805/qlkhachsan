@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Tìm Phòng Trống'; ?>

<style>
/* ══════════════════════════════════════════
   IMPORT FONTS
   Sender font styles
══════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

/* ══════════════════════════════════════════
   CSS VARIABLES
══════════════════════════════════════════ */
:root {
    --gold:       #C9A84C;
    --gold-light: #E8C87A;
    --gold-dark:  #9A7335;
    --ink:        #1A1A2E;
    --ink-soft:   #2D2D44;
    --cream:      #FAF8F3;
    --muted:      #7A7A8A;
    --card-bg:    #FFFFFF;
    --border:     #E8E4D8;
    --success:    #2A7A4F;
    --danger:     #C0392B;
}

/* ══════════════════════════════════════════
   HERO BANNER
══════════════════════════════════════════ */
.search-hero {
    position: relative;
    background: linear-gradient(135deg, #1A1A2E 0%, #16213E 40%, #0F3460 100%);
    padding: 64px 0 100px;
    margin: -24px -12px 0;
    overflow: hidden;
}

.search-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 600px 400px at 80% 50%, rgba(201,168,76,.12) 0%, transparent 70%),
        radial-gradient(ellipse 300px 300px at 10% 80%, rgba(201,168,76,.07) 0%, transparent 60%);
}

.search-hero::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 60px;
    background: var(--cream);
    clip-path: ellipse(55% 100% at 50% 100%);
}

.hero-label {
    font-family: 'DM Sans', sans-serif;
    font-size: .75rem;
    font-weight: 600;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 12px;
}

.hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 300;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 8px;
}

.hero-title strong {
    font-weight: 700;
    color: var(--gold-light);
}

.hero-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: .95rem;
    color: rgba(255,255,255,.55);
}

/* ══════════════════════════════════════════
   SEARCH FORM CARD (floating)
══════════════════════════════════════════ */
.search-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(26,26,46,.15), 0 4px 16px rgba(201,168,76,.08);
    padding: 28px 32px;
    margin-top: -52px;
    position: relative;
    z-index: 10;
    border: 1px solid rgba(201,168,76,.15);
}

.search-field-label {
    font-family: 'DM Sans', sans-serif;
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.search-field-label i { color: var(--gold); font-size: .85rem; }

.search-input {
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 10px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: .95rem;
    color: var(--ink);
    background: var(--cream);
    transition: border-color .2s, box-shadow .2s;
    width: 100%;
}

.search-input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,.12);
    background: #fff;
}

.search-input.is-invalid {
    border-color: var(--danger) !important;
    box-shadow: 0 0 0 3px rgba(192,57,43,.1) !important;
}

.date-error {
    font-family: 'DM Sans', sans-serif;
    font-size: .78rem;
    color: var(--danger);
    margin-top: 4px;
    min-height: 18px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.btn-search {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 11px 28px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600;
    font-size: .95rem;
    letter-spacing: .04em;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    white-space: nowrap;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201,168,76,.35);
}

.search-divider {
    width: 1px;
    background: var(--border);
    align-self: stretch;
    margin: 0 4px;
}
@media (min-width: 768px) {
    .border-md-end {
        border-right: 1px solid var(--border) !important;
    }
}
/* ══════════════════════════════════════════
   RESULTS BAR
══════════════════════════════════════════ */
.results-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 0 6px;
    flex-wrap: wrap;
}

.results-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--ink);
}

.badge-available {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    font-size: .8rem;
    font-weight: 600;
    padding: 5px 14px;
    border-radius: 99px;
    letter-spacing: .04em;
}

.results-meta {
    font-family: 'DM Sans', sans-serif;
    font-size: .85rem;
    color: var(--muted);
}

/* ══════════════════════════════════════════
   FILTER SIDEBAR
══════════════════════════════════════════ */
.filter-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(26,26,46,.06);
    overflow: hidden;
    position: sticky;
    top: 80px;
}

.filter-header {
    background: var(--ink);
    color: #fff;
    padding: 14px 20px;
    font-family: 'DM Sans', sans-serif;
    font-size: .8rem;
    font-weight: 600;
    letter-spacing: .14em;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-header i { color: var(--gold); }

.filter-body { padding: 20px; }

.filter-section-label {
    font-family: 'DM Sans', sans-serif;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 12px;
}

.price-range-track {
    position: relative;
    height: 6px;
    background: var(--border);
    border-radius: 3px;
    margin: 12px 0;
}

.price-range-fill {
    position: absolute;
    height: 100%;
    background: linear-gradient(90deg, var(--gold-dark), var(--gold));
    border-radius: 3px;
    pointer-events: none;
}

.price-range-wrap { position: relative; }

.price-range-wrap input[type=range] {
    position: absolute;
    width: 100%;
    top: -2px;
    -webkit-appearance: none;
    appearance: none;
    background: transparent;
    pointer-events: auto;
}

.price-range-wrap input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: var(--gold);
    border: 3px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
    cursor: grab;
}

.price-inputs { display: flex; gap: 8px; margin-top: 12px; align-items: center; }
.price-input {
    flex: 1;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 6px 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: var(--ink);
    background: var(--cream);
    text-align: center;
}
.price-input:focus { outline: none; border-color: var(--gold); }

.amenity-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-size: .85rem;
    color: var(--ink-soft);
    border-radius: 6px;
    transition: color .15s;
}

.amenity-item:hover { color: var(--gold-dark); }

.amenity-check {
    width: 16px; height: 16px;
    accent-color: var(--gold-dark);
    cursor: pointer;
    flex-shrink: 0;
}

.btn-reset {
    width: 100%;
    background: none;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: var(--muted);
    cursor: pointer;
    transition: border-color .15s, color .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.btn-reset:hover { border-color: var(--gold); color: var(--gold-dark); }

/* ══════════════════════════════════════════
   ROOM TYPE CARDS
══════════════════════════════════════════ */
.room-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(26,26,46,.06);
    overflow: hidden;
    margin-bottom: 20px;
    transition: box-shadow .25s, transform .25s;
}

.room-card:hover {
    box-shadow: 0 12px 40px rgba(26,26,46,.12);
    transform: translateY(-2px);
}

.room-card-header {
    display: flex;
    align-items: stretch;
    gap: 0;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    padding: 0;
    position: relative;
}

.room-card-header::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--gold-dark), var(--gold-light), transparent);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .3s ease;
}

.room-card.open .room-card-header::after { transform: scaleX(1); }

.room-img {
    width: 140px;
    min-width: 140px;
    object-fit: cover;
    display: block;
    flex-shrink: 0;
}

@media (max-width: 576px) { .room-img { width: 100px; min-width: 100px; } }

.room-card-info {
    padding: 18px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.room-type-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 4px;
}

.room-price {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--gold-dark);
}

.room-price small {
    font-size: .78rem;
    font-weight: 400;
    color: var(--muted);
}

.room-meta {
    font-family: 'DM Sans', sans-serif;
    font-size: .83rem;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 6px 0 8px;
}

.room-desc {
    font-family: 'DM Sans', sans-serif;
    font-size: .84rem;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 10px;
}

.amenity-tag {
    display: inline-block;
    background: var(--cream);
    border: 1px solid var(--border);
    color: var(--ink-soft);
    font-family: 'DM Sans', sans-serif;
    font-size: .75rem;
    padding: 3px 10px;
    border-radius: 99px;
    margin: 2px 2px 2px 0;
}

.badge-rooms {
    background: rgba(42,122,79,.12);
    color: var(--success);
    font-family: 'DM Sans', sans-serif;
    font-size: .75rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 99px;
    border: 1px solid rgba(42,122,79,.2);
    white-space: nowrap;
}

.chevron-icon {
    font-size: 1.1rem;
    color: var(--muted);
    margin-right: 16px;
    align-self: center;
    transition: transform .3s;
}
.room-card.open .chevron-icon { transform: rotate(180deg); }

/* ══════════════════════════════════════════
   ROOM SELECTION AREA
══════════════════════════════════════════ */
.room-body {
    display: none;
    padding: 0 20px 20px;
    border-top: 1px solid var(--border);
    background: var(--cream);
}

.room-card.open .room-body { display: block; }

.room-body-hint {
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: var(--muted);
    padding: 14px 0 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.room-chip-btn {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 10px 16px;
    min-width: 88px;
    cursor: pointer;
    transition: all .2s;
    font-family: 'DM Sans', sans-serif;
    text-align: center;
    user-select: none;
}

.room-chip-btn:hover { border-color: var(--gold); background: rgba(201,168,76,.05); }

.room-chip-btn.selected {
    background: var(--ink);
    border-color: var(--ink);
    color: #fff;
}
.room-chip-btn.taken {
    background: #f1f3f5;
    border-color: #dee2e6;
    color: #adb5bd;
    cursor: not-allowed;
    opacity: 0.7;
    pointer-events: none;
}

.room-chip-btn.selected .chip-floor { color: rgba(255,255,255,.6); }

.chip-num {
    font-size: .88rem;
    font-weight: 700;
    color: var(--ink);
}

.room-chip-btn.selected .chip-num { color: #fff; }

.chip-floor {
    font-size: .72rem;
    color: var(--muted);
    margin-top: 2px;
}

.quick-book-row { margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--border); }

.quick-book-hint {
    font-family: 'DM Sans', sans-serif;
    font-size: .78rem;
    color: var(--muted);
    margin-bottom: 10px;
}

.btn-quick {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: none;
    border: 1.5px solid var(--gold);
    color: var(--gold-dark);
    border-radius: 8px;
    padding: 5px 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, color .15s;
}

.btn-quick:hover {
    background: var(--gold);
    color: #fff;
}
.btn-quick.taken {
    border-color: #dee2e6;
    color: #adb5bd;
    cursor: not-allowed;
    opacity: 0.7;
    pointer-events: none;
}

/* Nút xem chi tiết phòng */
.btn-detail {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(26,26,46,.05);
    border: 1.5px solid var(--ink);
    color: var(--ink);
    border-radius: 8px;
    padding: 5px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .03em;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-detail:hover {
    background: var(--ink);
    color: var(--gold-light);
    border-color: var(--ink);
    text-decoration: none;
}

.btn-detail i { font-size: .85rem; }

/* ══════════════════════════════════════════
   CART BAR
══════════════════════════════════════════ */
.cart-bar {
    display: none;
    position: sticky;
    top: 0;
    z-index: 9999;
    margin: 0 0 16px 0;
    background: var(--ink);
    padding: 14px 24px;
    border-top: 2px solid var(--gold);
    box-shadow: 0 8px 32px rgba(0,0,0,.25);
    animation: slideUp .25s ease;
    border-radius: 14px;
}
.cart-inner {
    max-width: 1140px;
    margin: 0 auto;
}

@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

.cart-bar.visible { display: block; }

.cart-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.cart-info {
    font-family: 'DM Sans', sans-serif;
    color: rgba(255,255,255,.85);
    font-size: .9rem;
}

.cart-label-badge {
    background: rgba(201,168,76,.2);
    color: var(--gold-light);
    font-size: .78rem;
    padding: 2px 10px;
    border-radius: 99px;
    border: 1px solid rgba(201,168,76,.3);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin: 2px 2px 0 0;
}
.cart-label-badge i {
    cursor: pointer;
    transition: color .15s;
    font-size: .85rem;
}
.cart-label-badge i:hover {
    color: #ff6b6b !important;
}
.cart-total {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--gold-light);
}

.cart-capacity { font-size: .8rem; margin-top: 4px; }
.cart-capacity.ok   { color: #6fcf97; }
.cart-capacity.warn { color: #f2c94c; }

.btn-cart-clear {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2);
    color: rgba(255,255,255,.7);
    border-radius: 8px;
    padding: 7px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    cursor: pointer;
    transition: background .15s;
}
.btn-cart-clear:hover { background: rgba(255,255,255,.18); }

.btn-cart-book {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    border: none;
    color: #fff;
    border-radius: 8px;
    padding: 8px 20px;
    font-family: 'DM Sans', sans-serif;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: transform .15s, box-shadow .15s;
}
.btn-cart-book:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(201,168,76,.4);
}

/* ══════════════════════════════════════════
   EMPTY STATE
══════════════════════════════════════════ */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 3rem;
    color: var(--gold);
    display: block;
    margin-bottom: 16px;
}

.empty-state h5 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    color: var(--ink);
    margin-bottom: 8px;
}

.empty-state p {
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    color: var(--muted);
}

/* ══════════════════════════════════════════
   CAPACITY WARNING MODAL
══════════════════════════════════════════ */
.modal-luxury .modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    font-family: 'DM Sans', sans-serif;
}

.modal-luxury .modal-header {
    background: linear-gradient(135deg, #7B5E00, var(--gold));
    color: #fff;
    border: none;
    padding: 18px 24px;
}

.modal-luxury .modal-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
    font-weight: 700;
}

/* ══════════════════════════════════════════
   PAGE BODY BG
══════════════════════════════════════════ */
body { background: var(--cream) !important; }
</style>

<!-- ══════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════ -->
<div class="search-hero">
    <div class="container position-relative" style="z-index:2">
        <div class="text-center">
            <div class="hero-label">
                <i class="bi bi-stars me-1"></i>Khách sạn cao cấp
            </div>
            <h1 class="hero-title">
                Tìm <strong>Phòng Trống</strong><br>Hoàn Hảo Cho Bạn
            </h1>
            <p class="hero-sub">Kiểm tra tình trạng phòng và đặt ngay trong vài giây</p>
        </div>
    </div>
</div>

<div class="container pb-5">

<!-- ══════════════════════════════════════════
     SEARCH FORM CARD (floating)
══════════════════════════════════════════ -->
<div class="search-card">
    <form method="GET" action="{{ route('rooms.search') }}" id="searchForm" novalidate>
        <div class="row g-3 align-items-end">

            <!-- Ngày nhận phòng -->
            <div class="col-md-3 border-md-end pe-md-4">
                <div class="search-field-label">
                    <i class="bi bi-box-arrow-in-right"></i>Ngày nhận phòng
                </div>
                <input type="date" name="check_in" id="srchCheckIn" class="search-input form-control"
                       value="<?= htmlspecialchars($checkIn ?? '') ?>"
                       min="<?= date('Y-m-d') ?>" required>
                <div class="date-error d-none" id="errCheckIn">
                    <i class="bi bi-exclamation-circle"></i><span></span>
                </div>
            </div>


            <!-- Ngày trả phòng -->
            <div class="col-md-3 border-md-end pe-md-4">
                <div class="search-field-label">
                    <i class="bi bi-box-arrow-right"></i>Ngày trả phòng
                </div>
                <input type="date" name="check_out" id="srchCheckOut" class="search-input form-control"
                       value="<?= htmlspecialchars($checkOut ?? '') ?>"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                <div class="date-error d-none" id="errCheckOut">
                    <i class="bi bi-exclamation-circle"></i><span></span>
                </div>
            </div>

            <!-- Người lớn -->
            <div class="col-md-2 col-6">
                <div class="search-field-label">
                    <i class="bi bi-person-fill"></i>Người lớn
                </div>
                <input type="number" name="adults" class="search-input form-control"
                       value="<?= (int)($adults ?? 1) ?>" min="1" max="10">
            </div>

            <!-- Trẻ em -->
            <div class="col-md-2 col-6">
                <div class="search-field-label">
                    <i class="bi bi-person"></i>Trẻ em
                </div>
                <input type="number" name="children" class="search-input form-control"
                       value="<?= (int)($children ?? 0) ?>" min="0" max="10">
            </div>

            <!-- Nút tìm -->
            <div class="col-md-2 ms-auto col-12 mt-md-0 mt-3">
                <button type="submit" class="btn-search w-100">
                    <i class="bi bi-search me-2"></i>Tìm Kiếm
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($checkIn) && !empty($checkOut)): ?>

<!-- RESULTS BAR -->
<div class="results-bar">
    <div class="results-title">Kết quả tìm kiếm</div>
    <span class="badge-available" id="totalBadge"><?= $totalAvailable ?> phòng trống</span>
    <span class="results-meta">
        <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($checkIn) ?> → <?= htmlspecialchars($checkOut) ?>
        &nbsp;·&nbsp;
        <i class="bi bi-people me-1"></i><?= $adults ?? 1 ?> người lớn, <?= $children ?? 0 ?> trẻ em
    </span>
</div>

<?php if (empty($roomTypes)): ?>
<div class="empty-state">
    <i class="bi bi-calendar-x"></i>
    <h5>Không có phòng trống</h5>
    <p>Không có phòng phù hợp trong khoảng thời gian và số khách đã chọn.<br>Thử thay đổi ngày hoặc giảm số khách.</p>
</div>

<?php else: ?>

<div class="row g-4 mt-1">

    <!-- ══ CỘT BỘ LỌC ══ -->
    <div class="col-lg-3">
        <div class="filter-card">
            <div class="filter-header">
                <i class="bi bi-sliders"></i>Bộ lọc
            </div>
            <div class="filter-body">

                <!-- Lọc giá -->
                <div class="filter-section-label">Khoảng giá (VNĐ/đêm)</div>
                <?php
                $allPrices = $roomTypes->pluck('price')->all();
                $minPrice  = $allPrices ? (int)min($allPrices) : 0;
                $maxPrice  = $allPrices ? (int)max($allPrices) : 10000000;
                $filterMin = (int)floor($minPrice / 100000) * 100000;
                $filterMax = (int)ceil($maxPrice  / 100000) * 100000;
                if ($filterMin === $filterMax) $filterMax = $filterMin + 500000;
                ?>
                <div class="d-flex justify-content-between mb-1">
                    <small style="font-family:'DM Sans';font-size:.75rem;color:var(--muted)" id="priceMinLabel"><?= number_format($filterMin, 0, ',', '.') ?></small>
                    <small style="font-family:'DM Sans';font-size:.75rem;color:var(--muted)" id="priceMaxLabel"><?= number_format($filterMax, 0, ',', '.') ?></small>
                </div>
                <div class="price-range-track">
                    <div class="price-range-fill" id="rangeFill"></div>
                    <div class="price-range-wrap" style="position:relative;height:6px;">
                        <input type="range" id="priceRangeMin"
                               min="<?= $filterMin ?>" max="<?= $filterMax ?>"
                               value="<?= $filterMin ?>" step="100000"
                               style="margin:0;padding:0;height:20px;top:-7px;">
                        <input type="range" id="priceRangeMax"
                               min="<?= $filterMin ?>" max="<?= $filterMax ?>"
                               value="<?= $filterMax ?>" step="100000"
                               style="margin:0;padding:0;height:20px;top:-7px;position:absolute;left:0;width:100%">
                    </div>
                </div>
                <div class="price-inputs">
                    <input type="number" id="priceInputMin" class="price-input"
                           value="<?= $filterMin ?>" min="<?= $filterMin ?>" max="<?= $filterMax ?>" step="100000">
                    <span style="color:var(--muted);font-size:.9rem">—</span>
                    <input type="number" id="priceInputMax" class="price-input"
                           value="<?= $filterMax ?>" min="<?= $filterMin ?>" max="<?= $filterMax ?>" step="100000">
                </div>

                <hr style="border-color:var(--border);margin:18px 0">

                <!-- Lọc tiện nghi -->
                <?php if (!empty($allAmenities)): ?>
                <div class="filter-section-label">Tiện nghi</div>
                <div id="amenityFilters" style="max-height:240px;overflow-y:auto;">
                    <?php foreach ($allAmenities as $am): ?>
                    <label class="amenity-item">
                        <input class="amenity-check amenity-filter"
                               type="checkbox"
                               value="<?= (int)$am['id'] ?>">
                        <?= htmlspecialchars($am['amenity_name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <hr style="border-color:var(--border);margin:14px 0">
                <?php endif; ?>

                <button type="button" class="btn-reset" onclick="resetFilters()">
                    <i class="bi bi-arrow-counterclockwise"></i>Xoá bộ lọc
                </button>
            </div>
        </div>
    </div>

    <!-- ══ CỘT KẾT QUẢ ══ -->
    <div class="col-lg-9">

        <!-- Modal cảnh báo sức chứa -->
        <div class="modal fade modal-luxury" id="capacityWarningModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Sức chứa chưa đủ
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="font-family:'DM Sans';padding:20px 24px">
                        <p id="capacityWarningMsg" class="mb-2"></p>
                        <p class="text-muted small mb-0">Hãy chọn thêm phòng bằng tính năng <strong>Giỏ phòng</strong> bên dưới để đủ sức chứa.</p>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--border);justify-content:center">
                        <button class="btn btn-warning fw-bold px-4" data-bs-dismiss="modal">
                            <i class="bi bi-arrow-left me-1"></i>OK – Chọn thêm phòng
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form giỏ phòng -->
        <form id="multiBookingForm" method="GET"
                action="{{ route('booking.create') }}">   
            <input type="hidden" name="check_in"   value="<?= htmlspecialchars($checkIn) ?>">
            <input type="hidden" name="check_out"  value="<?= htmlspecialchars($checkOut) ?>">
            <input type="hidden" name="adults"     value="<?= (int)$adults ?>">
            <input type="hidden" name="children"   value="<?= (int)$children ?>">

            <!-- Cart Bar -->
            <div class="cart-bar" id="cartBar">
                <div class="cart-inner">
                    <div class="cart-info">
                        <div>
                            <i class="bi bi-bag-check me-1" style="color:var(--gold-light)"></i>
                            Đã chọn <strong id="cartCount" style="color:var(--gold-light)">0</strong> phòng:
                            <span id="cartLabels"></span>
                        </div>
                        <div class="cart-capacity" id="cartCapacity"></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <div class="cart-total" id="cartTotal"></div>
                        <button type="button" class="btn-cart-clear" onclick="clearCart()">
                            <i class="bi bi-x me-1"></i>Bỏ chọn
                        </button>
                        <button type="submit" class="btn-cart-book">
                            <i class="bi bi-calendar-check"></i>Đặt phòng đã chọn
                        </button>
                    </div>
                </div>
            </div>

            <!-- Room type cards -->
            <?php 
            foreach ($roomTypes as $i => $type):
                $typeId     = $type['id'];
                $imgSrc     = url('/') . '/images/rooms/' . $typeId . '.jpg';
                $imgFallback= url('/') . '/images/rooms/default.jpg';
                $roomCount  = count($type['available_rooms']);
                $amenityIds = collect($type['amenities'] ?? [])->pluck('id')->all();
            ?>
            <div class="room-card room-type-card <?= $i === 0 ? 'open' : '' ?>"
                 data-price="<?= (float)$type['price'] ?>"
                 data-amenities="<?= htmlspecialchars(json_encode($amenityIds)) ?>">

                <!-- Header -->
                <button type="button" class="room-card-header" onclick="toggleCard(this.closest('.room-card'))">
                    <img src="<?= $imgSrc ?>"
                         onerror="this.src='<?= $imgFallback ?>'"
                         alt="<?= htmlspecialchars($type['type_name']) ?>"
                         class="room-img">

                    <div class="room-card-info">
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px;margin-bottom:4px">
                                <div class="room-type-name"><?= htmlspecialchars($type['type_name']) ?></div>
                                <div class="rc-rating" style="font-size: 0.88rem; display: flex; align-items: center; gap: 4px; margin-top: 2px; margin-bottom: 6px; width: 100%;">
                                    <?php
                                    $avgRating = $type->averageRating();
                                    $fullStars = floor($avgRating);
                                    $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                                    $emptyStars = 5 - $fullStars - $halfStar;
                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '<i class="bi bi-star-fill" style="color: #C9A84C;"></i>';
                                    }
                                    if ($halfStar) {
                                        echo '<i class="bi bi-star-half" style="color: #C9A84C;"></i>';
                                    }
                                    for ($i = 0; $i < $emptyStars; $i++) {
                                        echo '<i class="bi bi-star" style="color: #ccc;"></i>';
                                    }
                                    if ($avgRating > 0) {
                                        echo ' <span class="ms-1 text-muted fw-semibold" style="font-size: 0.82rem;">' . number_format($avgRating, 1) . '</span>';
                                    } else {
                                        echo ' <span class="ms-1 text-muted" style="font-size: 0.78rem;">(Chưa có đánh giá)</span>';
                                    }
                                    ?>
                                </div>
                                <span class="badge-rooms"><?= $roomCount ?> phòng trống</span>
                            </div>
                            <div class="room-meta">
                                <span class="room-price"><?= number_format($type['price'], 0, ',', '.') ?> VNĐ<small>/đêm</small></span>
                                <span style="color:var(--border)">|</span>
                                <i class="bi bi-people"></i>Tối đa <?= $type['max_adults'] ?> người lớn, <?= $type['max_children'] ?> trẻ em
                            </div>
                            <div class="room-desc"><?= htmlspecialchars(mb_substr($type['description'] ?? '', 0, 100)) ?>...</div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:4px">
                            <div>
                                <?php foreach (collect($type['amenities'] ?? [])->take(5) as $am): ?>
                                <span class="amenity-tag"><?= htmlspecialchars($am['amenity_name']) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($type['amenities'] ?? []) > 5): ?>
                                <span class="amenity-tag" style="color:var(--muted)">+<?= count($type['amenities']) - 5 ?> nữa</span>
                                <?php endif; ?>
                            </div>
                            <!-- Nút xem chi tiết — stopPropagation để không toggle accordion -->
                            <a href="{{ route('rooms.detail', $type['id']) }}?check_in={{ urlencode($checkIn) }}&check_out={{ urlencode($checkOut) }}&adults={{ $adults }}&children={{ $children }}"
                               class="btn-detail"
                               onclick="event.stopPropagation()">
                                <i class="bi bi-eye"></i>Xem chi tiết
                            </a>
                        </div>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </button>

                <!-- Body: chọn phòng -->
                <div class="room-body">
                    <div class="room-body-hint">
                        <i class="bi bi-hand-index" style="color:var(--gold)"></i>
                        Chọn một hoặc nhiều phòng, sau đó bấm <strong style="color:var(--ink)">"Đặt phòng đã chọn"</strong> bên dưới.
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                        <?php 
                        $displayRooms = $type['all_rooms'] ?? $type['available_rooms'] ?? [];
                        foreach ($displayRooms as $room): 
                            $isTaken = $room['is_booked'] ?? false;
                            if ($isTaken) continue; // Ẩn các phòng đã đặt
                        ?>
                        <div class="room-chip room-chip-btn <?= $isTaken ? 'taken' : '' ?>"
                            id="chip-<?= $room['id'] ?>"
                            data-room-id="<?= $room['id'] ?>"
                            data-room-label="Phòng <?= htmlspecialchars($room['room_number']) ?>"
                            data-price="<?= (float)$type['price'] ?>"
                            data-max-guests="<?= (int)$type['max_guests'] ?>"
                            data-max-adults="<?= (int)$type['max_adults'] ?>"
                            data-max-children="<?= (int)$type['max_children'] ?>"
                            data-room-type-name="<?= htmlspecialchars($type['type_name']) ?>"
                            <?= $isTaken ? 'title="Phòng đã được đặt"' : 'onclick="toggleRoom(this)"' ?>>
                            <span class="chip-num">P.<?= htmlspecialchars($room['room_number']) ?></span>
                            <span class="chip-floor">Tầng <?= $room['floor'] ?></span>
                            <span class="chip-floor">
                                <?php if ($isTaken): ?>
                                    <i class="bi bi-x-circle-fill"></i> Đã đặt
                                <?php else: ?>
                                    <i class="bi bi-people"></i> <?= $type['max_guests'] ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="quick-book-row">
                        <div class="quick-book-hint"><i class="bi bi-lightning-charge me-1" style="color:var(--gold)"></i>Đặt nhanh 1 phòng:</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px">
                            <?php foreach ($displayRooms as $room): 
                                $isTaken = $room['is_booked'] ?? false;
                                 if ($isTaken) continue; // Ẩn các phòng đã đặt
                            ?>
                            <button type="button" class="btn-quick <?= $isTaken ? 'taken' : '' ?>"
                                    data-href="{{ route('booking.create') }}?room_id={{ $room['id'] }}&check_in={{ urlencode($checkIn) }}&check_out={{ urlencode($checkOut) }}&adults={{ $adults }}&children={{ $children }}"
                                    data-max-guests="<?= (int)$type['max_guests'] ?>"
                                    data-max-adults="<?= (int)$type['max_adults'] ?>"
                                    data-max-children="<?= (int)$type['max_children'] ?>"
                                    data-room-label="Phòng <?= htmlspecialchars($room['room_number']) ?>"
                                    data-room-type-name="<?= htmlspecialchars($type['type_name']) ?>"
                                    <?= $isTaken ? 'disabled title="Phòng đã được đặt"' : 'onclick="quickBook(this)"' ?>>
                                <i class="bi <?= $isTaken ? 'bi-x-circle' : 'bi-calendar-check' ?>"></i>
                                P.<?= htmlspecialchars($room['room_number']) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div id="noFilterResult" class="empty-state d-none">
                <i class="bi bi-funnel"></i>
                <h5>Không khớp bộ lọc</h5>
                <p>Thử điều chỉnh khoảng giá hoặc bỏ bớt tiện nghi.</p>
                <button type="button" onclick="resetFilters()" style="background:var(--ink);color:#fff;border:none;border-radius:8px;padding:8px 20px;font-family:'DM Sans';cursor:pointer;font-size:.85rem">Xoá bộ lọc</button>
            </div>

        </form>
    </div><!-- /col -->
</div><!-- /row -->

<?php endif; // roomTypes ?>
<?php endif; // checkIn && checkOut ?>

</div><!-- /container -->

<script>
// ════════════════════════════════════════════════
// 1. VALIDATE NGÀY TÌM KIẾM
// ════════════════════════════════════════════════
const TODAY_SRCH = '<?= date('Y-m-d') ?>';
const inEl  = document.getElementById('srchCheckIn');
const outEl = document.getElementById('srchCheckOut');

function showErr(errDivId, msg) {
    const div = document.getElementById(errDivId);
    if (!div) return;
    div.querySelector('span').textContent = msg;
    div.classList.remove('d-none');
}
function hideErr(errDivId) {
    const div = document.getElementById(errDivId);
    if (div) div.classList.add('d-none');
}
function markInvalid(el)  { el.classList.add('is-invalid');    el.classList.remove('is-valid'); }
function markValid(el)    { el.classList.remove('is-invalid'); el.classList.add('is-valid'); }
function markNeutral(el)  { el.classList.remove('is-invalid', 'is-valid'); }

// Cập nhật min của checkOut mỗi khi checkIn thay đổi
function syncOutMin() {
    if (!inEl.value) return;
    const d = new Date(inEl.value);
    d.setDate(d.getDate() + 1);
    outEl.min = d.toISOString().split('T')[0];

    // Nếu checkOut đang có giá trị không hợp lệ → xoá & báo lỗi
    if (outEl.value && outEl.value <= inEl.value) {
        outEl.value = '';
        markInvalid(outEl);
        showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.');
    } else if (outEl.value) {
        markValid(outEl);
        hideErr('errCheckOut');
    }
}

if (inEl) {
    inEl.addEventListener('change', () => {
        if (!inEl.value) {
            markInvalid(inEl); showErr('errCheckIn', 'Vui lòng chọn ngày nhận phòng.'); return;
        }
        if (inEl.value < TODAY_SRCH) {
            markInvalid(inEl); showErr('errCheckIn', 'Không thể chọn ngày trong quá khứ.'); return;
        }
        markValid(inEl); hideErr('errCheckIn');
        syncOutMin();
    });
}

if (outEl) {
    outEl.addEventListener('change', () => {
        if (!inEl.value) {
            outEl.value = '';
            markInvalid(outEl); showErr('errCheckOut', 'Vui lòng chọn ngày nhận phòng trước.');
            return;
        }
        if (!outEl.value) {
            markInvalid(outEl); showErr('errCheckOut', 'Vui lòng chọn ngày trả phòng.'); return;
        }
        if (outEl.value <= inEl.value) {
            outEl.value = '';
            markInvalid(outEl); showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.'); return;
        }
        markValid(outEl); hideErr('errCheckOut');
    });
}

document.getElementById('searchForm')?.addEventListener('submit', function(e) {
    let ok = true;

    if (!inEl.value) {
        markInvalid(inEl); showErr('errCheckIn', 'Vui lòng chọn ngày nhận phòng.'); ok = false;
    } else if (inEl.value < TODAY_SRCH) {
        markInvalid(inEl); showErr('errCheckIn', 'Không thể chọn ngày trong quá khứ.'); ok = false;
    } else {
        markValid(inEl); hideErr('errCheckIn');
    }

    if (!outEl.value) {
        markInvalid(outEl); showErr('errCheckOut', 'Vui lòng chọn ngày trả phòng.'); ok = false;
    } else if (outEl.value <= inEl.value) {
        outEl.value = '';
        markInvalid(outEl); showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.'); ok = false;
    } else {
        markValid(outEl); hideErr('errCheckOut');
    }

    if (!ok) {
        e.preventDefault();
        inEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Khởi tạo khi trang load (đã có giá trị từ server)
if (inEl && inEl.value) syncOutMin();

// ════════════════════════════════════════════════
// 2. ACCORDION CARD TOGGLE
// ════════════════════════════════════════════════
function toggleCard(card) {
    card.classList.toggle('open');
}

// ════════════════════════════════════════════════
// 3. GIỎ PHÒNG
// ════════════════════════════════════════════════
const neededAdults = <?= (int)($adults ?? 1) ?>;
const neededChildren = <?= (int)($children ?? 0) ?>;
const neededGuests = neededAdults + neededChildren;
const selected = {};

// Helper to save cart to sessionStorage
function saveCart() {
    sessionStorage.setItem('booking_cart', JSON.stringify({
        check_in: '<?= $checkIn ?? '' ?>',
        check_out: '<?= $checkOut ?? '' ?>',
        selected: selected
    }));
}

// Load saved cart from sessionStorage if the dates match
const savedCartJson = sessionStorage.getItem('booking_cart');
if (savedCartJson) {
    try {
        const savedCart = JSON.parse(savedCartJson);
        if (savedCart.check_in === '<?= $checkIn ?? '' ?>' && savedCart.check_out === '<?= $checkOut ?? '' ?>') {
            Object.assign(selected, savedCart.selected || {});
        } else {
            sessionStorage.removeItem('booking_cart');
        }
    } catch (e) {
        console.error('Error parsing saved cart:', e);
    }
}
function toggleRoom(chip) {
    const id = chip.dataset.roomId;
    if (selected[id]) {
        delete selected[id];
        chip.classList.remove('selected');
    } else {
        selected[id] = {
            label: chip.dataset.roomLabel,
            price: parseFloat(chip.dataset.price),
            maxGuests: parseInt(chip.dataset.maxGuests),
            maxAdults: parseInt(chip.dataset.maxAdults || 0),
            maxChildren: parseInt(chip.dataset.maxChildren || 0),
            roomTypeName: chip.dataset.roomTypeName || 'phòng'
        };
        chip.classList.add('selected');
    }
    saveCart();
    updateCartBar();
}

function updateCartBar() {
    const ids = Object.keys(selected);
    const bar = document.getElementById('cartBar');

    if (ids.length === 0) {
        bar.classList.remove('visible');
        document.body.style.paddingBottom = '0';
        return;
    }

    bar.classList.add('visible');
    document.body.style.paddingBottom = '90px';

    const totalMaxAdults = ids.reduce((a, id) => a + selected[id].maxAdults, 0);
    const totalMaxChildren = ids.reduce((a, id) => a + selected[id].maxChildren, 0);
    const totalMaxGuests = ids.reduce((a, id) => a + selected[id].maxGuests, 0);
    document.getElementById('cartCount').textContent = ids.length;

    // Labels
    const labelsEl = document.getElementById('cartLabels');
    labelsEl.innerHTML = ids.map(id =>
        `<span class="cart-label-badge">${selected[id].label} <i class="bi bi-x" onclick="removeCartItem('${id}')"></i></span>`
    ).join('');

    // Capacity
    const capEl = document.getElementById('cartCapacity');
    capEl.innerHTML = '';
    capEl.className = 'cart-capacity ok';

    // Total price
    const ci = new Date('<?= $checkIn ?? '' ?>');
    const co = new Date('<?= $checkOut ?? '' ?>');
    const nights = Math.abs(Math.round((co - ci) / 86400000)) || 1;
    const sum = ids.reduce((a, id) => a + selected[id].price * nights, 0);
    document.getElementById('cartTotal').textContent =
        sum.toLocaleString('vi-VN') + ' VNĐ (' + nights + ' đêm)';
}

function clearCart() {
    Object.keys(selected).forEach(id => {
        delete selected[id];
        const chip = document.getElementById('chip-' + id);
        if (chip) chip.classList.remove('selected');
    });
    sessionStorage.removeItem('booking_cart');
    updateCartBar();
}

function removeCartItem(id) {
    if (selected[id]) {
        delete selected[id];
        const chip = document.getElementById('chip-' + id);
        if (chip) chip.classList.remove('selected');
        saveCart();
        updateCartBar();
    }
}

document.getElementById('multiBookingForm')?.addEventListener('submit', function(e) {
    const ids = Object.keys(selected);
    if (ids.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 phòng.');
        return;
    }
    const totalMaxAdults = ids.reduce((a, id) => a + selected[id].maxAdults, 0);
    const totalMaxChildren = ids.reduce((a, id) => a + selected[id].maxChildren, 0);
    const totalMaxGuests = ids.reduce((a, id) => a + selected[id].maxGuests, 0);

    if (totalMaxAdults > neededAdults || totalMaxChildren > neededChildren || totalMaxGuests > neededGuests) {
       const counts = {};
        ids.forEach(id => {
            const name = selected[id].roomTypeName || 'phòng';
            counts[name] = (counts[name] || 0) + 1;
        });
        const parts = Object.keys(counts).map(name => `${counts[name]} ${name.toLowerCase()}`);
        let roomsDescription = "";
        if (parts.length === 1) {
            roomsDescription = parts[0];
        } else {
            roomsDescription = parts.slice(0, -1).join(', ') + ' và ' + parts[parts.length - 1];
        }
        if (!confirm(`Bạn đang đặt ${roomsDescription}. Bạn có chắc chắn muốn đặt các phòng này không?`)) {
            e.preventDefault();
            return;
        }
    }
    this.querySelectorAll('input[name="room_ids[]"]').forEach(el => el.remove());
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'room_ids[]'; inp.value = id;
        this.appendChild(inp);
    });
});

// ════════════════════════════════════════════════
// 4. ĐẶT NHANH 1 PHÒNG
// ════════════════════════════════════════════════
function quickBook(btn) {
    const maxAdults = parseInt(btn.dataset.maxAdults || 0);
    const maxChildren = parseInt(btn.dataset.maxChildren || 0);
    const maxGuests = parseInt(btn.dataset.maxGuests || 0);
    const typeName = btn.dataset.roomTypeName || 'phòng';
    const href      = btn.dataset.href;
    
    if (maxAdults > neededAdults || maxChildren > neededChildren || maxGuests > neededGuests) {
        if (!confirm(`Bạn đang đặt 1 ${typeName.toLowerCase()}. Bạn có chắc chắn muốn đặt phòng này không?`)) {            return;
        }
    }
    window.location.href = href;
}

// ════════════════════════════════════════════════
// 5. BỘ LỌC GIÁ & TIỆN NGHI
// ════════════════════════════════════════════════
const FILTER_MIN = <?= $filterMin ?? 0 ?>;
const FILTER_MAX = <?= $filterMax ?? 10000000 ?>;

const rangeMin = document.getElementById('priceRangeMin');
const rangeMax = document.getElementById('priceRangeMax');
const inputMin = document.getElementById('priceInputMin');
const inputMax = document.getElementById('priceInputMax');
const labelMin = document.getElementById('priceMinLabel');
const labelMax = document.getElementById('priceMaxLabel');
const fillEl   = document.getElementById('rangeFill');

function fmt(n) { return Number(n).toLocaleString('vi-VN'); }

function updateFill() {
    if (!rangeMin || !fillEl) return;
    const lo = parseInt(rangeMin.value);
    const hi = parseInt(rangeMax.value);
    const pct = (v) => ((v - FILTER_MIN) / (FILTER_MAX - FILTER_MIN)) * 100;
    fillEl.style.left  = pct(Math.min(lo, hi)) + '%';
    fillEl.style.width = Math.abs(pct(hi) - pct(lo)) + '%';
}

function syncFromRange() {
    let lo = parseInt(rangeMin.value), hi = parseInt(rangeMax.value);
    if (lo > hi) { const t = lo; lo = hi; hi = t; }
    inputMin.value = lo; inputMax.value = hi;
    labelMin.textContent = fmt(lo); labelMax.textContent = fmt(hi);
    updateFill(); applyFilters();
}

function syncFromInput() {
    let lo = parseInt(inputMin.value) || FILTER_MIN;
    let hi = parseInt(inputMax.value) || FILTER_MAX;
    lo = Math.max(FILTER_MIN, Math.min(lo, FILTER_MAX));
    hi = Math.max(FILTER_MIN, Math.min(hi, FILTER_MAX));
    if (lo > hi) { const t = lo; lo = hi; hi = t; }
    rangeMin.value = lo; rangeMax.value = hi;
    labelMin.textContent = fmt(lo); labelMax.textContent = fmt(hi);
    updateFill(); applyFilters();
}

if (rangeMin) {
    rangeMin.addEventListener('input', syncFromRange);
    rangeMax.addEventListener('input', syncFromRange);
    inputMin.addEventListener('change', syncFromInput);
    inputMax.addEventListener('change', syncFromInput);
    updateFill();
}

document.querySelectorAll('.amenity-filter').forEach(cb => cb.addEventListener('change', applyFilters));

function applyFilters() {
    const lo = parseInt(inputMin?.value || FILTER_MIN);
    const hi = parseInt(inputMax?.value || FILTER_MAX);
    const checked = [...document.querySelectorAll('.amenity-filter:checked')].map(cb => parseInt(cb.value));
    const cards = document.querySelectorAll('.room-type-card');
    let visible = 0;
    cards.forEach(card => {
        const price     = parseFloat(card.dataset.price);
        const amenities = JSON.parse(card.dataset.amenities || '[]');
        const show = price >= lo && price <= hi
            && (checked.length === 0 || checked.every(id => amenities.includes(id)));
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const badge = document.getElementById('totalBadge');
    if (badge) {
        let total = 0;
        cards.forEach(c => { if (c.style.display !== 'none') total += c.querySelectorAll('.room-chip').length; });
        badge.textContent = total + ' phòng trống';
    }
    document.getElementById('noFilterResult')?.classList.toggle('d-none', visible > 0);
}

function resetFilters() {
    if (rangeMin) {
        rangeMin.value = FILTER_MIN; rangeMax.value = FILTER_MAX;
        inputMin.value = FILTER_MIN; inputMax.value = FILTER_MAX;
        labelMin.textContent = fmt(FILTER_MIN); labelMax.textContent = fmt(FILTER_MAX);
        updateFill();
    }
    document.querySelectorAll('.amenity-filter').forEach(cb => cb.checked = false);
    applyFilters();
}

// Restore selected status for chips on page load and update the cart bar
Object.keys(selected).forEach(id => {
    const chip = document.getElementById('chip-' + id);
    if (chip && !chip.classList.contains('taken')) {
        chip.classList.add('selected');
    } else {
        delete selected[id];
    }
});
saveCart();
if (Object.keys(selected).length > 0) {
    updateCartBar();
}
</script>
@endsection
