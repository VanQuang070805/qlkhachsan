@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Chi Tiết – ' . htmlspecialchars($room['type_name'] ?? 'Phòng'); ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

:root {
    --gold:      #C9A84C;
    --gold-dark: #9A7335;
    --ink:       #1A1A2E;
    --cream:     #FAF8F3;
    --muted:     #7A7A8A;
    --border:    #E8E4D8;
    --green:     #2A7A4F;
}
body { font-family: 'DM Sans', sans-serif; background: var(--cream); }

/* ── Breadcrumb ── */
.bc { font-size: .85rem; margin-bottom: 1.5rem; color: var(--muted); }
.bc a { color: var(--ink); text-decoration: none; }
.bc a:hover { text-decoration: underline; }
.bc .sep { margin: 0 .4rem; color: var(--muted); }

/* ══════════════════════════════════════════
   LEFT COLUMN
══════════════════════════════════════════ */
.detail-img {
    width: 100%;
    height: 320px;
    object-fit: cover;
    border-radius: 16px;
    display: block;
    margin-bottom: 1.8rem;
    box-shadow: 0 8px 32px rgba(26,26,46,.13);
}
.room-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.1rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: .9rem;
}
.info-line {
    display: flex; align-items: center; gap: 8px;
    font-size: .92rem; color: #333;
    margin-bottom: .4rem;
}
.info-line i { color: var(--gold); width: 18px; flex-shrink: 0; }
.block-heading {
    font-family: 'DM Sans', sans-serif;
    font-weight: 700; font-size: .97rem;
    color: var(--ink);
    margin: 1.3rem 0 .45rem;
}
.desc-text { font-size: .9rem; color: #555; line-height: 1.7; margin: 0; }

/* amenities grid 2-col */
.am-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    row-gap: 2px; column-gap: 0;
}
.am-row {
    display: flex; align-items: center; gap: 7px;
    font-size: .88rem; color: #333;
    padding: 5px 0;
}
.am-row i { color: var(--green); font-size: .9rem; flex-shrink: 0; }

/* ══════════════════════════════════════════
   RIGHT COLUMN — Booking panel + Room selector
══════════════════════════════════════════ */
.right-sticky { position: sticky; top: 80px; }

/* ── Booking card ── */
.bk-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(26,26,46,.08);
    overflow: hidden;
    margin-bottom: 1.2rem;
}
.bk-header {
    background: var(--ink);
    color: #fff;
    padding: .9rem 1.4rem;
    display: flex; align-items: center; gap: 9px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600; font-size: 1rem;
}
.bk-body { padding: 1.3rem 1.4rem 1.4rem; }

.f-label {
    font-size: .65rem; font-weight: 600;
    letter-spacing: .13em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 5px; display: block;
}
.f-input {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 9px;
    padding: 9px 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem; color: var(--ink);
    background: var(--cream);
    transition: border-color .2s, box-shadow .2s;
    appearance: auto;
}
.f-input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,.13);
    background: #fff;
}
.f-input[readonly] { cursor: default; background: #f5f5f5; }

/* ── Thêm từ detail.php: validate states ── */
.f-input.is-invalid {
    border-color: #C0392B !important;
    box-shadow: 0 0 0 3px rgba(192,57,43,.1) !important;
}
.f-input.is-valid {
    border-color: var(--green) !important;
    box-shadow: 0 0 0 3px rgba(42,122,79,.1) !important;
}
.date-error {
    font-size: .75rem;
    color: #C0392B;
    margin-top: 3px;
    min-height: 16px;
    display: flex;
    align-items: center;
    gap: 3px;
}

.btn-book {
    width: 100%;
    background: var(--ink);
    color: #fff; border: none;
    border-radius: 11px;
    padding: .82rem;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600; font-size: .96rem;
    letter-spacing: .03em;
    cursor: pointer;
    transition: opacity .18s, transform .15s;
    margin-top: .3rem;
    display: block;
    text-align: center;
    text-decoration: none;
}
.btn-book:hover:not(:disabled):not([style*="pointer-events: none"]) {
    opacity: .88; transform: translateY(-1px);
}
.btn-book:disabled { opacity: .5; cursor: not-allowed; }

.bk-hint {
    text-align: center;
    font-size: .77rem; color: var(--muted);
    margin-top: .55rem; margin-bottom: 0;
}

/* ── Room selector card ── */
.rs-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(26,26,46,.08);
    overflow: hidden;
}
.rs-header {
    background: var(--ink); color: #fff;
    padding: .9rem 1.4rem;
    display: flex; align-items: center; gap: 9px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600; font-size: 1rem;
}
.rs-body { padding: 1.1rem 1.4rem 1.3rem; }

/* Legend */
.rs-legend {
    display: flex; gap: 1rem; flex-wrap: wrap;
    margin-bottom: .9rem;
    font-size: .78rem; color: #555;
    font-family: 'DM Sans', sans-serif;
}
.rs-legend-item { display: flex; align-items: center; gap: 5px; }
.rs-dot {
    width: 13px; height: 13px; border-radius: 3px; border: 1.5px solid;
    flex-shrink: 0;
}
.rs-dot.avail  { background: #EEF2FF; border-color: #C5CFE8; }
.rs-dot.sel    { background: var(--ink); border-color: var(--ink); }
.rs-dot.taken  { background: #e9ecef; border-color: #ced4da; }

/* floor label */
.floor-lbl {
    font-size: .65rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--muted); margin: .85rem 0 .4rem;
}
.floor-lbl:first-of-type { margin-top: 0; }
.floor-rooms { display: flex; flex-wrap: wrap; gap: 0; }

/* room button */
.rm-btn {
    display: inline-flex; flex-direction: column;
    align-items: center; justify-content: center;
    width: 64px; height: 56px;
    border-radius: 9px;
    border: 1.5px solid #C5CFE8;
    background: #EEF2FF;
    color: var(--ink);
    font-family: 'DM Sans', sans-serif;
    font-weight: 700; font-size: .88rem;
    cursor: pointer;
    transition: all .17s;
    margin: 3px;
}
.rm-btn .rm-sub { font-size: .57rem; font-weight: 400; opacity: .65; margin-top: 1px; }
.rm-btn:hover:not(.taken) {
    border-color: var(--ink); background: var(--ink); color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26,26,46,.2);
}
.rm-btn.selected {
    border-color: var(--ink); background: var(--ink); color: #fff;
    box-shadow: 0 4px 12px rgba(26,26,46,.2);
}
.rm-btn.taken {
    background: #e9ecef; border-color: #ced4da;
    color: #adb5bd; cursor: not-allowed; opacity: .65;
}

.rs-empty { font-size: .87rem; color: var(--muted); padding: .3rem 0; }

/* ── Thêm từ detail.php: Booking bar dính dưới ── */
.bk-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 300;
    background: var(--ink);
    color: #fff;
    padding: 12px 24px;
    display: none;
    border-top: 3px solid var(--gold);
    box-shadow: 0 -4px 20px rgba(0,0,0,.18);
}
.bk-bar.visible {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.bk-bar-labels { display: flex; flex-wrap: wrap; gap: 6px; }
.bk-label-badge {
    background: rgba(255,255,255,.15);
    border-radius: 20px;
    padding: 3px 10px;
    font-size: .8rem;
    font-weight: 600;
}
.bk-capacity.ok   { color: #6ee7a0; font-size: .82rem; }
.bk-capacity.warn { color: #fbbf24; font-size: .82rem; }
.btn-bk-submit {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #fff; border: none;
    border-radius: 9px;
    padding: 9px 24px;
    font-weight: 700; font-size: .93rem;
    cursor: pointer;
    transition: opacity .18s, transform .15s;
    white-space: nowrap;
}
.btn-bk-submit:disabled { opacity: .45; cursor: not-allowed; }
.btn-bk-submit:not(:disabled):hover { opacity: .88; transform: translateY(-1px); }
.btn-bk-clear {
    background: rgba(255,255,255,.12);
    color: #fff; border: none;
    border-radius: 9px;
    padding: 9px 16px;
    font-size: .85rem; cursor: pointer;
    transition: background .15s;
}
.btn-bk-clear:hover { background: rgba(255,255,255,.22); }
</style>

<!-- Breadcrumb -->
<nav class="bc">
    <a href="{{ route('home') }}">Trang Chủ</a>
    <span class="sep">/</span>
    <span><?= htmlspecialchars($room['type_name'] ?? '') ?></span>
</nav>

<?php
$roomImages = [
    'Phòng Đơn Tiêu Chuẩn'  => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=900&q=85',
    'Phòng Đôi Tiêu Chuẩn'  => 'https://images.unsplash.com/photo-1631049552057-403cdb8f0658?w=900&q=85',
    'Phòng 3 Người (Triple)' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=900&q=85',
    'Phòng Gia Đình'         => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=900&q=85',
    'Phòng VIP Cao Cấp'      => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=900&q=85',
];
$heroImg = url('/') . '/images/rooms/' . $room['id'] . '.jpg';
$imgFallback = url('/') . '/images/rooms/default.jpg';

$adults      = max(1, (int)($_GET['adults'] ?? 1));
$children    = max(0, (int)($_GET['children'] ?? 0));
$maxAdults   = (int)($room['max_adults'] ?? 1);
$maxChildren = (int)($room['max_children'] ?? 0);
$maxGuests   = (int)($room['max_guests'] ?? 1);

// Calculate rooms needed dynamically
$isInsufficient = ($maxAdults < $adults || $maxChildren < $children || $maxGuests < ($adults + $children));

$roomsNeeded = 1;
if ($isInsufficient) {
    $roomsNeeded = 999;
} else {
    while (true) {
        $totalMaxAdults = $roomsNeeded * $maxAdults;
        $totalMaxChildren = $roomsNeeded * $maxChildren;
        $totalMaxGuests = $roomsNeeded * $maxGuests;
        if ($totalMaxAdults >= $adults && $totalMaxChildren >= $children && $totalMaxGuests >= ($adults + $children)) {
            break;
        }
        $roomsNeeded++;
        if ($roomsNeeded > 100) {
            $roomsNeeded = 999;
            break;
        }
    }
}
$multiRoomMode = $roomsNeeded > 1 && $roomsNeeded < 999;

// Group rooms by floor
$byFloor = [];
foreach (($allRoomsOfType ?? []) as $r) {
    $byFloor[(int)$r['floor']][] = $r;
}
ksort($byFloor);
?>

<div class="row g-4 align-items-start">

    <!-- ══ LEFT ══ -->
    <div class="col-lg-7">

         <img src="<?= $heroImg ?>"
             onerror="this.src='<?= $imgFallback ?>'"
             class="detail-img"
             alt="<?= htmlspecialchars($room['type_name']) ?>">

        <h1 class="room-title"><?= htmlspecialchars($room['type_name']) ?></h1>
        <div class="rc-rating mb-3" style="font-size: 1.1rem; display: flex; align-items: center; gap: 5px;">
            <?php
            $avgRating = $room->averageRating();
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
                echo ' <span class="ms-2 text-muted fw-bold" style="font-size: 1rem;">' . number_format($avgRating, 1) . ' / 5.0</span>';
                echo ' <span class="ms-1 text-muted" style="font-size: 0.85rem;">(' . $room->reviews()->count() . ' đánh giá)</span>';
            } else {
                echo ' <span class="ms-2 text-muted" style="font-size: 0.88rem;">(Chưa có đánh giá)</span>';
            }
            ?>
        </div>

        <div class="info-line">
            <i class="bi bi-people-fill"></i>
            <span><strong>Sức chứa tối đa:</strong> <?= $room['max_adults'] ?> người lớn, <?= $room['max_children'] ?> trẻ em (tối đa <?= $room['max_guests'] ?> khách)</span>
        </div>
        <div class="info-line">
            <i class="bi bi-currency-dollar"></i>
            <span><strong>Giá:</strong> <?= number_format($room['price'], 0, ',', '.') ?> VNĐ/đêm</span>
        </div>

        <div class="block-heading">Mô tả:</div>
        <p class="desc-text"><?= htmlspecialchars($room['description'] ?? '') ?></p>

        <?php if (!empty($room['amenities'])): ?>
        <div class="block-heading">Tiện nghi:</div>
        <div class="am-grid">
            <?php foreach ($room['amenities'] as $a): ?>
            <div class="am-row">
                <i class="bi bi-check2"></i>
                <?= htmlspecialchars($a['amenity_name']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Đánh giá từ khách hàng -->
        <div class="block-heading mt-4" style="font-size: 1.15rem; font-weight: 700; color: var(--ink); border-bottom: 2px solid var(--gold); padding-bottom: 8px; margin-bottom: 15px;">
            <i class="bi bi-chat-square-text-fill text-gold me-2"></i>Đánh giá từ khách hàng
        </div>
        <div class="reviews-list">
            <?php if ($room->reviews->isEmpty()): ?>
                <div class="p-3 mb-2 bg-white rounded shadow-sm border text-muted text-center" style="font-size: 0.9rem;">
                    Chưa có đánh giá nào cho loại phòng này.
                </div>
            <?php else: ?>
                <?php foreach ($room->reviews as $review): ?>
                <div class="review-item p-3 mb-3 bg-white rounded shadow-sm border border-light" style="font-family: 'DM Sans', sans-serif;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark" style="font-size: 0.9rem;"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($review->user->fullname ?? 'Khách hàng') ?></strong>
                        <span class="text-muted" style="font-size: 0.78rem;"><i class="bi bi-calendar-event me-1"></i><?= $review->created_at ? $review->created_at->format('d/m/Y') : '' ?></span>
                    </div>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star-fill" style="color: <?= $i <= $review->rating ? '#C9A84C' : '#e4e5e9' ?>; font-size: 0.85rem;"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-0 text-muted" style="font-size: 0.88rem; line-height: 1.5;"><?= nl2br(htmlspecialchars($review->comment ?? '')) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- ══ RIGHT ══ -->
    <div class="col-lg-5">
        <div class="right-sticky">

            <!-- Booking card -->
            <div class="bk-card">
                <div class="bk-header">
                    <i class="bi bi-calendar-check"></i>
                    Đặt Phòng Nhanh
                </div>
                <div class="bk-body">

                    <div class="row g-2 mb-2">
                        <!-- Ngày nhận -->
                        <div class="col-6">
                            <label class="f-label">Ngày Nhận</label>
                            <input type="date" id="bkCheckIn" class="f-input"
                                   min="<?= date('Y-m-d') ?>" required
                                   value="<?= htmlspecialchars($_GET['check_in'] ?? '') ?>">
                            <div class="date-error" id="errCheckIn"></div>
                        </div>
                        <!-- Ngày trả -->
                        <div class="col-6">
                            <label class="f-label">Ngày Trả</label>
                            <input type="date" id="bkCheckOut" class="f-input"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required
                                   value="<?= htmlspecialchars($_GET['check_out'] ?? '') ?>">
                            <div class="date-error" id="errCheckOut"></div>
                        </div>
                        <!-- Người lớn -->
                        <div class="col-4">
                            <label class="f-label">Người Lớn</label>
                            <input type="number" id="bkAdults" class="f-input"
                                   min="1" value="<?= $adults ?>">
                        </div>
                        <!-- Trẻ em -->
                        <div class="col-4">
                            <label class="f-label">Trẻ Em</label>
                            <input type="number" id="bkChildren" class="f-input"
                                   min="0" value="<?= $children ?>">
                        </div>
                        <!-- Số đêm -->
                        <div class="col-4">
                            <label class="f-label">Số Đêm</label>
                            <input type="text" id="bkNights" class="f-input"
                                   placeholder="–" readonly>
                        </div>
                    </div>

                    <!-- Thông báo không đủ sức chứa -->
                    <div id="insufficientCapacityNotice"
                         style="background:#ffebee;border:1px solid #ffcdd2;border-radius:8px;
                                padding:8px 12px;font-size:.82rem;margin-bottom:10px;color:#c62828;
                                <?= $isInsufficient ? '' : 'display:none;' ?>">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#c62828;"></i>
                        Loại phòng này không đủ sức chứa cho số lượng khách đã chọn.
                    </div>

                    <!-- Thông báo multi-room -->
                    <div id="multiRoomNotice"
                         style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;
                                padding:8px 12px;font-size:.82rem;margin-bottom:10px;
                                <?= $multiRoomMode ? '' : 'display:none;' ?>">
                        <i class="bi bi-people-fill" style="color:#f59e0b;"></i>
                        Với <span id="noticeGuests"><?= $adults + $children ?></span> khách, bạn cần chọn
                        ít nhất <strong id="roomsNeededLabel"><?= $roomsNeeded ?></strong> phòng.
                    </div>

                    <?php if (empty(session('user_id'))): ?>
                        <!-- Chưa đăng nhập -->
                        <button type="button" class="btn-book" id="btnBook" onclick="submitBooking()">
                            <i class="bi bi-lock me-1"></i>Đăng nhập để đặt phòng
                        </button>
                        <p class="bk-hint">
                            Vui lòng chọn ngày và phòng để tiếp tục
                        </p>
                    <?php else: ?>
                        <!-- Đã đăng nhập -->
                        <button type="button" class="btn-book" id="btnBook"
                                disabled onclick="submitBooking()">
                            <i class="bi bi-calendar-plus me-1"></i>Đặt Ngay
                        </button>
                        <p class="bk-hint" id="bkHint">
                            <?= $isInsufficient
                                ? 'Loại phòng này không đủ sức chứa cho số lượng khách đã chọn.'
                                : ($multiRoomMode
                                    ? "← Chọn đủ $roomsNeeded phòng bên dưới"
                                    : '← Vui lòng chọn một phòng bên dưới') ?>
                        </p>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Room selector -->
            <div class="rs-card">
                <div class="rs-header">
                    <i class="bi bi-grid-3x3-gap"></i>
                    Chọn Phòng
                    <?php if ($multiRoomMode): ?>
                    <span style="font-size:.75rem;opacity:.75;margin-left:6px;">
                        (chọn <?= $roomsNeeded ?> phòng)
                    </span>
                    <?php endif; ?>
                </div>
                <div class="rs-body">

                    <div class="rs-legend">
                        <div class="rs-legend-item">
                            <div class="rs-dot avail"></div><span>Còn trống</span>
                        </div>
                        <div class="rs-legend-item">
                            <div class="rs-dot sel"></div><span>Đang chọn</span>
                        </div>
                        <div class="rs-legend-item">
                            <div class="rs-dot taken"></div><span>Đã đặt</span>
                        </div>
                    </div>

                    <?php
                    $datesSelected = !empty($checkIn) && !empty($checkOut);
                    $byFloorDisplay = [];
                    foreach ($byFloor as $floor => $floorRooms) {
                        $byFloorDisplay[$floor] = $floorRooms;
                    }
                    ?>
                    <?php if (empty($byFloorDisplay)): ?>
                        <p class="rs-empty">Chưa có phòng nào cho loại này.</p>
                    <?php else: ?>
                        <?php foreach ($byFloorDisplay as $floor => $floorRooms): ?>
                        <div class="floor-lbl">Tầng <?= $floor ?></div>
                        <div class="floor-rooms">
                            <?php foreach ($floorRooms as $r):
                                $taken = ($bookedRoomIds ?? collect())->contains($r['id']);
                            ?>
                            <button type="button"
                                    class="rm-btn <?= ($taken || $isInsufficient) ? 'taken' : '' ?>"
                                    data-room-id="<?= $r['id'] ?>"
                                    data-room-number="<?= htmlspecialchars($r['room_number']) ?>"
                                    data-is-booked="<?= $taken ? 'true' : 'false' ?>"
                                    <?= ($taken || $isInsufficient) ? 'disabled title="' . ($isInsufficient ? 'Loại phòng không đủ sức chứa' : 'Phòng đã được đặt') . '"' : 'onclick="toggleRoom(this)"' ?>>
                                <?= htmlspecialchars($r['room_number']) ?>
                                <span class="rm-sub"><?= $taken ? 'Đã đặt' : ($isInsufficient ? 'Không đủ chỗ' : 'Trống') ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>

        </div><!-- /sticky -->
    </div><!-- /right col -->

</div><!-- /row -->

<!-- Booking bar dính dưới -->
<?php if (!empty(session('user_id'))): ?>
<div class="bk-bar" id="bkBar">
    <div>
        <div style="font-weight:700;font-size:.95rem;margin-bottom:4px;">
            Đã chọn <span id="bkBarCount">0</span> phòng
            <span id="bkBarMin" style="font-size:.8rem;opacity:.75;">
                (tối thiểu <span id="bkBarNeeded"><?= $roomsNeeded ?></span>)
            </span>
            <span id="bkBarTotal" style="color:var(--gold);margin-left:10px;"></span>
        </div>
        <div class="bk-bar-labels" id="bkBarLabels"></div>
        <div class="bk-capacity" id="bkBarCapacity"></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <button class="btn-bk-clear" onclick="clearRooms()">
            <i class="bi bi-x-circle me-1"></i>Bỏ chọn
        </button>
        <button class="btn-bk-submit" id="btnBarSubmit" disabled onclick="submitBooking()">
            <i class="bi bi-calendar-check me-1"></i>Đặt Phòng Đã Chọn
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Hidden form để submit -->
<form method="GET" action="{{ route('booking.create') }}" id="bookingForm" style="display:none;">Vous avez dit : cụ thể ra
    <input type="hidden" name="check_in"   id="formCheckIn"  value="">
    <input type="hidden" name="check_out"  id="formCheckOut" value="">
    <input type="hidden" name="adults"     id="formAdults"   value="<?= $adults ?>">
    <input type="hidden" name="children"   id="formChildren" value="<?= $children ?>">
    <div id="formRoomIds"></div>
</form>

<!-- Modal cảnh báo sức chứa -->
<div class="modal fade" id="capacityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Chưa đủ sức chứa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="capacityModalMsg"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Quay lại chọn phòng</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
// ═══════════════════════════════════════════════
// 1. KHAI BÁO & BIẾN TOÀN CỤC
// ═══════════════════════════════════════════════
const IS_LOGGED_IN = <?= empty(session('user_id')) ? 'false' : 'true' ?>;
const TODAY        = '<?= date('Y-m-d') ?>';
const inEl         = document.getElementById('bkCheckIn');
const outEl        = document.getElementById('bkCheckOut');
const adultsInput  = document.getElementById('bkAdults');
const childrenInput= document.getElementById('bkChildren');

let   ROOMS_NEEDED = <?= $roomsNeeded ?>;
const MAX_ADULTS   = <?= $maxAdults ?>;
const MAX_CHILDREN = <?= $maxChildren ?>;
const MAX_GUESTS   = <?= $maxGuests ?>;
const PRICE        = <?= (float)$room['price'] ?>;

const selectedRooms = new Map(); // roomId → roomNumber

// ═══════════════════════════════════════════════
// 2. XỬ LÝ NGÀY THÁNG & SỐ ĐÊM
// ═══════════════════════════════════════════════
function markInvalid(el) { el.classList.remove('is-valid');   el.classList.add('is-invalid'); }
function markValid(el)   { el.classList.remove('is-invalid'); el.classList.add('is-valid');   }
function showErr(id, msg) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i> ${msg}`;
}
function hideErr(id) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = '';
}

function calcNightsNum() {
    if (inEl && outEl && inEl.value && outEl.value && outEl.value > inEl.value) {
        return Math.round((new Date(outEl.value) - new Date(inEl.value)) / 86400000);
    }
    return 0;
}

function calcNights() {
    const ni = document.getElementById('bkNights');
    const n  = calcNightsNum();
    if (ni) ni.value = n > 0 ? n + ' đêm' : '–';
    updateBookBtn();
}

function syncOutMin() {
    if (!inEl || !inEl.value) return;
    const next = new Date(inEl.value);
    next.setDate(next.getDate() + 1);
    if (outEl) {
        outEl.min = next.toISOString().split('T')[0];
        if (outEl.value && outEl.value <= inEl.value) {
            outEl.value = '';
            markInvalid(outEl);
            showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.');
        }
    }
    calcNights();
}

if (inEl) {
    inEl.addEventListener('change', function () {
        if (!inEl.value) {
            markInvalid(inEl); showErr('errCheckIn', 'Vui lòng chọn ngày nhận phòng.'); return;
        }
        if (inEl.value < TODAY) {
            markInvalid(inEl); showErr('errCheckIn', 'Không thể chọn ngày trong quá khứ.'); return;
        }
        markValid(inEl); hideErr('errCheckIn');
        syncOutMin();
    });
}

if (outEl) {
    outEl.addEventListener('change', function () {
        if (!outEl.value) {
            markInvalid(outEl); showErr('errCheckOut', 'Vui lòng chọn ngày trả phòng.'); return;
        }
        if (outEl.value <= inEl.value) {
            outEl.value = '';
            markInvalid(outEl); showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.'); return;
        }
        markValid(outEl); hideErr('errCheckOut');
        calcNights();

        // Reload để server tính lại phòng trống
        const adults = adultsInput ? adultsInput.value : 1;
        const children = childrenInput ? childrenInput.value : 0;
        const params = new URLSearchParams(window.location.search);
        params.set('check_in',  inEl.value);
        params.set('check_out', outEl.value);
        params.set('adults',    adults);
        params.set('children',  children);
        window.location.search = params.toString();
    });
}

// ═══════════════════════════════════════════════
// 3. CHỌN PHÒNG & VALIDATE
// ═══════════════════════════════════════════════
window.toggleRoom = function(btn) {
    const id  = btn.dataset.roomId;
    const num = btn.dataset.roomNumber;

    if (btn.classList.contains('selected')) {
        btn.classList.remove('selected');
        const sub = btn.querySelector('.rm-sub');
        if (sub) sub.textContent = 'Trống';
        selectedRooms.delete(id);
    } else {
        btn.classList.add('selected');
        const sub = btn.querySelector('.rm-sub');
        if (sub) sub.textContent = 'Đã chọn';
        selectedRooms.set(id, num);
    }
    updateBookBtn();
    if (typeof updateBar === 'function') updateBar();
};

window.clearRooms = function() {
    selectedRooms.clear();
    document.querySelectorAll('.rm-btn.selected').forEach(b => {
        b.classList.remove('selected');
        const sub = b.querySelector('.rm-sub');
        if (sub) sub.textContent = 'Trống';
    });
    updateBookBtn();
    if (typeof updateBar === 'function') updateBar();
};

function updateBookBtn() {
    const ready = selectedRooms.size >= ROOMS_NEEDED
                  && inEl.value && outEl.value
                  && outEl.value > inEl.value;
    const btn = document.getElementById('btnBook');
    if (btn) btn.disabled = !ready;
    const barBtn = document.getElementById('btnBarSubmit');
    if (barBtn) barBtn.disabled = !ready;
}

function recalculateRoomsNeeded() {
    const adults = Math.max(1, parseInt(adultsInput.value) || 1);
    const children = Math.max(0, parseInt(childrenInput.value) || 0);
    const totalGuests = adults + children;

    const isInsufficient = (MAX_ADULTS < adults || MAX_CHILDREN < children || MAX_GUESTS < totalGuests);
    
    const insNotice = document.getElementById('insufficientCapacityNotice');
    if (insNotice) {
        insNotice.style.display = isInsufficient ? '' : 'none';
    }

    document.querySelectorAll('.rm-btn').forEach(btn => {
        const isBooked = btn.dataset.isBooked === 'true';
        if (isInsufficient) {
            btn.classList.add('taken');
            btn.disabled = true;
            btn.title = 'Loại phòng không đủ sức chứa';
            const sub = btn.querySelector('.rm-sub');
            if (sub) sub.textContent = 'Không đủ chỗ';
        } else {
            if (isBooked) {
                btn.classList.add('taken');
                btn.disabled = true;
                btn.title = 'Phòng đã được đặt';
                const sub = btn.querySelector('.rm-sub');
                if (sub) sub.textContent = 'Đã đặt';
            } else {
                const isSelected = btn.classList.contains('selected');
                btn.classList.remove('taken');
                btn.disabled = false;
                btn.title = '';
                const sub = btn.querySelector('.rm-sub');
                if (sub) sub.textContent = isSelected ? 'Đang chọn' : 'Trống';
            }
        }
    });

    if (isInsufficient) {
        ROOMS_NEEDED = 999;
    } else {
        ROOMS_NEEDED = 1;
        while (true) {
            const totalMaxAdults = ROOMS_NEEDED * MAX_ADULTS;
            const totalMaxChildren = ROOMS_NEEDED * MAX_CHILDREN;
            const totalMaxGuests = ROOMS_NEEDED * MAX_GUESTS;
            if (totalMaxAdults >= adults && totalMaxChildren >= children && totalMaxGuests >= totalGuests) {
                break;
            }
            ROOMS_NEEDED++;
            if (ROOMS_NEEDED > 100) {
                ROOMS_NEEDED = 999;
                break;
            }
        }
    }

    const barNeeded = document.getElementById('bkBarNeeded');
    if (barNeeded) barNeeded.textContent = ROOMS_NEEDED;

    const lbl = document.getElementById('roomsNeededLabel');
    if (lbl) lbl.textContent = ROOMS_NEEDED;

    const noticeGuests = document.getElementById('noticeGuests');
    if (noticeGuests) noticeGuests.textContent = totalGuests;

    const notice = document.getElementById('multiRoomNotice');
    if (notice) notice.style.display = (ROOMS_NEEDED > 1 && ROOMS_NEEDED < 999) ? '' : 'none';

    const hint = document.getElementById('bkHint');
    if (hint) {
        if (isInsufficient) {
            hint.textContent = 'Loại phòng này không đủ sức chứa cho số lượng khách đã chọn.';
        } else {
            hint.textContent = ROOMS_NEEDED === 1
                ? '← Vui lòng chọn một phòng bên dưới'
                : `← Chọn đủ ${ROOMS_NEEDED} phòng bên dưới`;
        }
    }
    clearRooms();
}

if (adultsInput)   adultsInput.addEventListener('input', recalculateRoomsNeeded);
if (childrenInput) childrenInput.addEventListener('input', recalculateRoomsNeeded);

// ═══════════════════════════════════════════════
// 4. SUBMIT ĐẶT PHÒNG
// ═══════════════════════════════════════════════
window.submitBooking = function() {
    if (!inEl.value) {
        markInvalid(inEl); showErr('errCheckIn', 'Vui lòng chọn ngày nhận phòng.'); return;
    }
    if (!outEl.value || outEl.value <= inEl.value) {
        markInvalid(outEl); showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.'); return;
    }
    if (selectedRooms.size === 0) {
        alert('Vui lòng chọn ít nhất một phòng.'); return;
    }

    const totalMaxAdults = selectedRooms.size * MAX_ADULTS;
    const totalMaxChildren = selectedRooms.size * MAX_CHILDREN;
    const totalMaxGuests = selectedRooms.size * MAX_GUESTS;
    
    const neededAdults = parseInt(adultsInput.value) || 1;
    const neededChildren = parseInt(childrenInput.value) || 0;
    const neededGuests = neededAdults + neededChildren;

    if (totalMaxAdults > neededAdults || totalMaxChildren > neededChildren || totalMaxGuests > neededGuests) {
        if (!confirm(`Bạn đang đặt ${selectedRooms.size} phòng với tổng sức chứa cho ${totalMaxAdults} người lớn và ${totalMaxChildren} trẻ em. Bạn có chắc chắn muốn đặt số lượng phòng này cho ${neededAdults} người lớn và ${neededChildren} trẻ em không?`)) {
            return;
        }
    }

    document.getElementById('formCheckIn').value  = inEl.value;
    document.getElementById('formCheckOut').value = outEl.value;
    document.getElementById('formAdults').value   = adultsInput.value;
    document.getElementById('formChildren').value = childrenInput.value;

    const formRoomIds = document.getElementById('formRoomIds');
    formRoomIds.innerHTML = '';
    selectedRooms.forEach((num, id) => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'room_ids[]'; inp.value = id;
        formRoomIds.appendChild(inp);
    });

    document.getElementById('bookingForm').submit();
};

// ═══════════════════════════════════════════════
// 5. KHỞI TẠO BAN ĐẦU & LOGGED IN ONLY
// ═══════════════════════════════════════════════
if (inEl && inEl.value) syncOutMin();
if (inEl && outEl && inEl.value && outEl.value) calcNights();
updateBookBtn();

if (IS_LOGGED_IN) {
    window.updateBar = function() {
        const count  = selectedRooms.size;
        const bar    = document.getElementById('bkBar');
        if (!bar) return;

        if (count === 0) {
            bar.classList.remove('visible');
            document.body.style.paddingBottom = '0';
            return;
        }

        bar.classList.add('visible');
        document.body.style.paddingBottom = '80px';

        const nights   = calcNightsNum();
        const total    = count * nights * PRICE;
        const totalMaxAdults = count * MAX_ADULTS;
        const totalMaxChildren = count * MAX_CHILDREN;
        const totalMaxGuests = count * MAX_GUESTS;

        document.getElementById('bkBarCount').textContent = count;

        const labelsEl = document.getElementById('bkBarLabels');
        if (labelsEl) {
            labelsEl.innerHTML = '';
            selectedRooms.forEach(num => {
                const span = document.createElement('span');
                span.className   = 'bk-label-badge';
                span.textContent = 'Phòng ' + num;
                labelsEl.appendChild(span);
            });
        }

        const totalEl = document.getElementById('bkBarTotal');
        if (totalEl) {
            totalEl.textContent = nights > 0 ? total.toLocaleString('vi-VN') + ' VNĐ (' + nights + ' đêm)' : '';
        }

        const capEl  = document.getElementById('bkBarCapacity');
        const neededAdults = parseInt(adultsInput.value) || 1;
        const neededChildren = parseInt(childrenInput.value) || 0;
        const neededGuests = neededAdults + neededChildren;
        if (capEl) {
            capEl.className = 'bk-capacity ok';
            capEl.innerHTML = '';
        }
    };
}
</script>
@endsection
