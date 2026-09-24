@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Trang Chủ – PHP Hotel'; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

:root {
    --gold:       #C9A84C;
    --gold-light: #E8C87A;
    --gold-dark:  #9A7335;
    --ink:        #1A1A2E;
    --cream:      #FAF8F3;
    --muted:      #7A7A8A;
    --border:     #E8E4D8;
    --danger:     #C0392B;
}

body { font-family: 'Montserrat', sans-serif; background: var(--cream); }

/* ══ HERO ══ */
.hero-wrap {
    position: relative;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    text-align: center;
    background: url('https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1800&q=85') center/cover no-repeat;
    margin: -24px -12px 0;
}
.hero-wrap::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(10,14,32,.58) 0%, rgba(10,14,32,.74) 100%);
}
.hero-inner { position: relative; z-index: 2; color: #fff; padding: 0 1rem; width: 100%; }

.hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(3rem, 8vw, 6.5rem);
    font-weight: 700;
    line-height: 1.0;
    text-transform: uppercase;
    letter-spacing: .05em;
    text-shadow: 0 4px 30px rgba(0,0,0,.4);
}
.hero-sub {
    font-size: .82rem;
    letter-spacing: .32em;
    text-transform: uppercase;
    color: rgba(255,255,255,.6);
    margin-top: .6rem;
}

/* ── SEARCH BOX ── */
.search-box {
    background: rgba(255,255,255,.97);
    border-radius: 20px;
    padding: 1.8rem 2rem;
    max-width: 820px;
    margin: 2.2rem auto 0;
    box-shadow: 0 24px 64px rgba(0,0,0,.28), 0 0 0 1px rgba(201,168,76,.18);
}
.search-box h5 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem; font-style: italic; font-weight: 600;
    color: var(--ink); margin-bottom: 1.1rem;
}
.sf-label {
    font-size: .68rem; font-weight: 600;
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 5px;
    display: flex; align-items: center; gap: 5px;
}
.sf-label i { color: var(--gold); }
.sf-input {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 10px 13px;
    font-family: 'DM Sans', sans-serif;
    font-size: .93rem;
    color: var(--ink);
    background: var(--cream);
    transition: border-color .2s, box-shadow .2s;
}
.sf-input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,.14);
    background: #fff;
}
.sf-input.sf-error {
    border-color: var(--danger) !important;
    box-shadow: 0 0 0 3px rgba(192,57,43,.12) !important;
    background: #fff9f9;
}
.sf-error-msg {
    font-size: .75rem;
    color: var(--danger);
    margin-top: 5px;
    display: none;
    align-items: center;
    gap: 4px;
    font-weight: 500;
    text-align: left;
}
.sf-error-msg.visible { display: flex; }

.btn-find {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #fff; border: none;
    border-radius: 10px;
    padding: 11px 0; width: 100%;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600; font-size: .95rem;
    letter-spacing: .04em;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
}
.btn-find:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(201,168,76,.38); }

/* ══ ROOMS SECTION ══ */
.rooms-section { padding: 5rem 0 4rem; }

.section-heading {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 700;
    color: var(--ink);
    text-align: center;
    text-transform: uppercase;
    letter-spacing: .1em;
    margin-bottom: 3rem;
}
.section-heading span {
    display: block;
    font-size: .72rem;
    font-family: 'DM Sans', sans-serif;
    letter-spacing: .25em;
    font-weight: 600;
    color: var(--gold);
    text-transform: uppercase;
    margin-bottom: .5rem;
}

/* ── ROOM CARD ── */
.rc {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(26,26,46,.07), 0 1px 4px rgba(201,168,76,.06);
    border: 1px solid var(--border);
    transition: transform .25s, box-shadow .25s;
    height: 100%;
    display: flex; flex-direction: column;
}
.rc:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 48px rgba(26,26,46,.14), 0 2px 8px rgba(201,168,76,.1);
}
.rc-img { width: 100%; height: 220px; object-fit: cover; display: block; }
.rc-body { padding: 1.4rem 1.6rem; flex: 1; display: flex; flex-direction: column; }
.rc-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.25rem; font-weight: 700;
    color: var(--ink); margin-bottom: .35rem;
}
.rc-meta {
    font-size: .8rem; color: var(--muted);
    display: flex; align-items: center; gap: 5px;
    margin-bottom: .22rem;
}
.rc-meta i { color: var(--gold); font-size: .85rem; }
.rc-footer {
    display: flex; align-items: center;
    justify-content: space-between;
    gap: .5rem; margin-top: 1rem;
}
.rc-price {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.35rem; font-weight: 700; color: var(--ink);
}
.rc-price small { font-family: 'DM Sans'; font-size: .75rem; color: var(--muted); font-weight: 400; }
.btn-rc-detail {
    border: 1.5px solid var(--ink);
    color: var(--ink); background: transparent;
    border-radius: 8px;
    font-size: .8rem; font-weight: 600;
    padding: .42rem 1.1rem;
    text-decoration: none;
    transition: all .2s;
    white-space: nowrap;
    font-family: 'DM Sans', sans-serif;
}
.btn-rc-detail:hover { background: var(--ink); color: #fff; }

/* ── PAGINATION ── */
.pag { display: flex; gap: 8px; justify-content: center; margin-top: 3rem; flex-wrap: wrap; }
.pag a {
    min-width: 42px; height: 42px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    background: #fff;
    font-size: .88rem; font-weight: 600; color: var(--ink);
    display: inline-flex; align-items: center; justify-content: center;
    text-decoration: none; padding: 0 12px;
    transition: all .18s;
    font-family: 'DM Sans', sans-serif;
}
.pag a.active, .pag a:hover { background: var(--ink); color: #fff; border-color: var(--ink); }
.pag a.disabled { color: #ccc; pointer-events: none; border-color: #eee; }
</style>

<!-- ══ HERO ══ -->
<div class="hero-wrap">
    <div class="hero-inner">
        <div class="hero-title">Chào Mừng Đến Với<br>Royal Hotel</div>
        <div class="hero-sub">Cổ Điển, Sang Trọng</div>

        <div class="search-box">
            <h5>Tìm Phòng</h5>
            <form action="{{ route('rooms.search') }}"
                  method="GET"
                  id="heroSearchForm"
                  novalidate>
                <div class="row g-3 align-items-end">

                    <!-- Ngày nhận phòng -->
                    <div class="col-12 col-sm-3">
                        <label class="sf-label"><i class="bi bi-calendar3"></i>Ngày Nhận Phòng</label>
                        <input type="date" name="check_in" id="heroCheckIn"
                               class="sf-input"
                               min="<?= date('Y-m-d') ?>" required>
                        <div class="sf-error-msg" id="errHeroIn">
                            <i class="bi bi-exclamation-circle-fill"></i><span></span>
                        </div>
                    </div>

                    <!-- Ngày trả phòng -->
                    <div class="col-12 col-sm-3">
                        <label class="sf-label"><i class="bi bi-calendar3"></i>Ngày Trả Phòng</label>
                        <input type="date" name="check_out" id="heroCheckOut"
                               class="sf-input"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        <div class="sf-error-msg" id="errHeroOut">
                            <i class="bi bi-exclamation-circle-fill"></i><span></span>
                        </div>
                    </div>

                    <!-- Người lớn -->
                    <div class="col-6 col-sm-2">
                        <label class="sf-label"><i class="bi bi-person-fill"></i>Người Lớn</label>
                        <input type="number" name="adults" class="sf-input"
                               min="1" max="10" value="1">
                    </div>

                    <!-- Trẻ em -->
                    <div class="col-6 col-sm-2">
                        <label class="sf-label"><i class="bi bi-person"></i>Trẻ Em</label>
                        <input type="number" name="children" class="sf-input"
                               min="0" max="10" value="0">
                    </div>

                    <!-- Nút tìm -->
                    <div class="col-12 col-sm-2 d-grid">
                        <button type="submit" class="btn-find">
                            <i class="bi bi-search me-1"></i>Tìm
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ ROOM TYPES ══ -->
<div class="rooms-section" id="rooms-section">
    <div class="container">
        <div class="section-heading">
            <span>Lựa Chọn Của Bạn</span>
            Khám Phá Các Phòng Nghỉ
        </div>

        <?php
        $perPage    = 3;
        $total      = count($roomTypes);
        $totalPages = max(1, ceil($total / $perPage));
        $page       = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $pageTypes = $roomTypes->slice(($page - 1) * $perPage, $perPage);
        ?>

        <div class="row g-4 justify-content-center">
            <?php foreach ($pageTypes as $type): 
                $imgSrc = url('/') . '/images/rooms/' . $type['id'] . '.jpg';
                $imgFallback = url('/') . '/images/rooms/default.jpg';
            ?>
            
            <div class="col-12 col-md-6 col-lg-4">
                <div class="rc">
                    <img src="<?= $imgSrc ?>"
                         onerror="this.src='<?= $imgFallback ?>'"
                         class="rc-img" alt="<?= htmlspecialchars($type['type_name']) ?>">
                    <div class="rc-body">
                        <div class="rc-name"><?= htmlspecialchars($type['type_name']) ?></div>
                        <div class="rc-rating mb-2" style="font-size: 0.88rem; display: flex; align-items: center; gap: 4px;">
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
                        <div class="rc-meta">
                            <i class="bi bi-people"></i>Tối đa: <?= $type['max_adults'] ?> người lớn, <?= $type['max_children'] ?> trẻ em
                        </div>
                        <div class="rc-meta">
                            <i class="bi bi-info-circle"></i>
                            <?= htmlspecialchars(mb_substr($type['description'] ?? '', 0, 72)) ?>...
                        </div>
                        <div class="rc-footer">
                            <div class="rc-price">
                                <?= number_format($type['price'], 0, ',', '.') ?> VNĐ
                                <small>/đêm</small>
                            </div>
                            <a href="{{ route('rooms.detail', $type['id']) }}"
                               class="btn btn-outline-dark btn-sm px-3 fw-semibold">
                                Chi Tiết
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pag">
            <a href="?controller=home&page=<?= max(1,$page-1) ?>"
               class="<?= $page<=1?'disabled':'' ?>">« Trang trước</a>
            <?php for($i=1;$i<=$totalPages;$i++): ?>
            <a href="?controller=home&page=<?= $i ?>"
               class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?controller=home&page=<?= min($totalPages,$page+1) ?>"
               class="<?= $page>=$totalPages?'disabled':'' ?>">Trang sau »</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ════════════════════════════════════════════════
// VALIDATE NGÀY – TRANG CHỦ
// ════════════════════════════════════════════════
(function () {
    const TODAY   = '<?= date('Y-m-d') ?>';
    const inEl    = document.getElementById('heroCheckIn');
    const outEl   = document.getElementById('heroCheckOut');
    const errIn   = document.getElementById('errHeroIn');
    const errOut  = document.getElementById('errHeroOut');

    // ── helpers ──────────────────────────────────
    function showErr(errDiv, input, msg) {
        errDiv.querySelector('span').textContent = msg;
        errDiv.classList.add('visible');
        input.classList.add('sf-error');
    }
    function clearErr(errDiv, input) {
        errDiv.classList.remove('visible');
        input.classList.remove('sf-error');
    }

    // ── cập nhật min checkOut khi checkIn thay đổi ──
    function syncOutMin() {
        if (!inEl.value) return;
        const d = new Date(inEl.value);
        d.setDate(d.getDate() + 1);
        outEl.min = d.toISOString().split('T')[0];

        // nếu checkOut đang chọn ngày không hợp lệ → xoá & báo lỗi ngay
        if (outEl.value && outEl.value <= inEl.value) {
            outEl.value = '';
            showErr(errOut, outEl, 'Ngày trả phòng phải sau ngày nhận phòng.');
        } else if (outEl.value) {
            clearErr(errOut, outEl);
        }
    }

    // ── sự kiện checkIn ──────────────────────────
    inEl.addEventListener('change', function () {
        if (!inEl.value) {
            showErr(errIn, inEl, 'Vui lòng chọn ngày nhận phòng.'); return;
        }
        if (inEl.value < TODAY) {
            showErr(errIn, inEl, 'Không thể chọn ngày trong quá khứ.'); return;
        }
        clearErr(errIn, inEl);
        syncOutMin();
    });

    // ── sự kiện checkOut ─────────────────────────
    outEl.addEventListener('change', function () {
        if (!inEl.value) {
            outEl.value = '';
            showErr(errOut, outEl, 'Vui lòng chọn ngày nhận phòng trước.'); return;
        }
        if (!outEl.value) {
            showErr(errOut, outEl, 'Vui lòng chọn ngày trả phòng.'); return;
        }
        if (outEl.value <= inEl.value) {
            outEl.value = '';
            showErr(errOut, outEl, 'Ngày trả phòng phải sau ngày nhận phòng.'); return;
        }
        clearErr(errOut, outEl);
    });

    // ── chặn submit nếu sai ───────────────────────
    document.getElementById('heroSearchForm').addEventListener('submit', function (e) {
        let ok = true;

        if (!inEl.value) {
            showErr(errIn, inEl, 'Vui lòng chọn ngày nhận phòng.'); ok = false;
        } else if (inEl.value < TODAY) {
            showErr(errIn, inEl, 'Không thể chọn ngày trong quá khứ.'); ok = false;
        } else {
            clearErr(errIn, inEl);
        }

        if (!outEl.value) {
            showErr(errOut, outEl, 'Vui lòng chọn ngày trả phòng.'); ok = false;
        } else if (outEl.value <= inEl.value) {
            outEl.value = '';
            showErr(errOut, outEl, 'Ngày trả phòng phải sau ngày nhận phòng.'); ok = false;
        } else {
            clearErr(errOut, outEl);
        }

        if (!ok) e.preventDefault();
    });
})();
</script>
@endsection