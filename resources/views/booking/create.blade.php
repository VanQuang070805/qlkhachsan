@extends('layouts.main')

@section('content')
<?php
$pageTitle = 'Đặt Phòng';

$adults = (int)($adults ?? 1);
$children = (int)($children ?? 0);
$people = $adults + $children;

// Tính tổng max_guests của tất cả phòng đã chọn
// $totalMaxGuests = 0;
// foreach ($rooms as $r) $totalMaxGuests += (int)$r['max_guests'];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('rooms.search') }}">Tìm phòng</a></li>
        <li class="breadcrumb-item active">Đặt phòng</li>
    </ol>
</nav>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ── Cột trái: Danh sách phòng đã chọn ── -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="bi bi-cart-check me-2"></i>
                    Phòng Đã Chọn
                    <span class="badge bg-success ms-1"><?= count($rooms) ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php
                $totalBase = 0;
                foreach ($rooms as $r) $totalBase += (float)($r->roomType?->price ?? 0);
                ?>

                <ul class="list-group list-group-flush">
                <?php foreach ($rooms as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">Phòng <?= htmlspecialchars($r['room_number']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($r->roomType?->type_name ?? '') ?> · Tầng <?= $r['floor'] ?></div>
                            <div class="text-muted small">Tối đa: <?= $r->roomType?->max_adults ?? 0 ?> người lớn, <?= $r->roomType?->max_children ?? 0 ?> trẻ em</div>
                        </div>
                        <div class="text-primary fw-bold text-end" style="white-space:nowrap">
                            <?= number_format($r->roomType?->price ?? 0, 0, ',', '.') ?><br>
                            <small class="fw-normal text-muted">VNĐ/đêm</small>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
                </ul>

                <div class="p-3 border-top">
                    <div id="totalBox" class="alert alert-success text-center mb-0 py-2 d-none">
                        <div class="small text-muted" id="nightsText"></div>
                        <div class="fw-bold fs-5" id="totalText"></div>
                    </div>
                    <?php if (count($rooms) === 0): ?>
                    <p class="text-muted small mb-0">Chưa có phòng nào.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Cột phải: Form đặt phòng ── -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-4">Thông Tin Đặt Phòng</h4>

                <form method="POST"
                    action="{{ route('booking.store') }}"
                    id="bookingForm"
                    novalidate>
                    @csrf

                    <!-- Truyền tất cả room IDs -->
                    <?php foreach ($roomIds as $rid): ?>
                    <input type="hidden" name="room_ids[]" value="<?= (int)$rid ?>">
                    <?php endforeach; ?>

                    <!-- Ngày -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Ngày Nhận Phòng <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="check_in" id="checkIn" value="<?= htmlspecialchars($checkIn ?? '') ?>">
                            <input type="date" class="form-control locked-input"
                                value="<?= htmlspecialchars($checkIn ?? '') ?>" disabled>
                            <div class="invalid-feedback" id="checkInFeedback">Vui lòng chọn ngày nhận phòng.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Ngày Trả Phòng <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="check_out" id="checkOut" value="<?= htmlspecialchars($checkOut ?? '') ?>">
                            <input type="date" class="form-control locked-input"
                                value="<?= htmlspecialchars($checkOut ?? '') ?>" disabled>
                            <div class="invalid-feedback" id="checkOutFeedback">Ngày trả phòng phải sau ngày nhận phòng.</div>
                        </div>
                    </div>

                    <!-- Số khách: người lớn & trẻ em -->
                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Người Lớn <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="adult_count" id="peopleInput" value="<?= (int)($adults ?? 1) ?>">
                            <input type="number" class="form-control locked-input"
                                value="<?= (int)($adults ?? 1) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Trẻ Em
                            </label>
                            <input type="hidden" name="child_count" id="childrenInput" value="<?= (int)($children ?? 0) ?>">
                            <input type="number" class="form-control locked-input"
                                value="<?= (int)($children ?? 0) ?>" disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-text">
                            <?php if (count($rooms) > 1): ?>
                            Tối đa <strong><?= $totalMaxGuests ?></strong> khách (tổng sức chứa <?= count($rooms) ?> phòng)
                            <?php else: ?>
                            Tối đa <strong><?= $totalMaxGuests ?></strong> khách
                            <?php endif; ?>
                        </div>
                        <div class="invalid-feedback" id="peopleFeedback">
                            Tổng số khách phải nhỏ hơn hoặc bằng <?= $totalMaxGuests ?>.
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="fw-bold mb-3">Thông Tin Người Đặt</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Họ Và Tên <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="customer_name" id="customerName" class="form-control"
                               value="<?= htmlspecialchars($old['customer_name'] ?? (session('user.fullname') ?? '')) ?>"
                               placeholder="Nguyễn Văn A"
                               minlength="2"
                               required>
                        <div class="invalid-feedback" id="nameFeedback">Vui lòng nhập họ tên (ít nhất 2 ký tự).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="customer_email" id="customerEmail" class="form-control"
                               value="<?= htmlspecialchars($old['customer_email'] ?? (session('user.email') ?? '')) ?>"
                               placeholder="email@example.com" required>
                        <div class="invalid-feedback" id="emailFeedback">Vui lòng nhập địa chỉ email hợp lệ.</div>
                    </div>

                    <!-- Số điện thoại với validate -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Số Điện Thoại <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" name="customer_phone" id="customerPhone" class="form-control"
                                   value="<?= htmlspecialchars($old['customer_phone'] ?? (session('user.phone') ?? '')) ?>"
                                   placeholder="0901234567"
                                   maxlength="15"
                                   required>
                        </div>
                        <div id="phoneFeedback" class="form-text d-none"></div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" id="submitBtn" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-calendar-check me-1"></i>Chọn phương thức thanh toán
                        </button>
                        <a href="{{ route('rooms.search', ['check_in' => $checkIn ?? '', 'check_out' => $checkOut ?? '', 'adults' => $adults ?? 1, 'children' => $children ?? 0]) }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Quay Lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ── Hằng số từ PHP ──────────────────────────────────
const pricesPerNight = <?= json_encode($rooms->map(fn($r) => (float)($r->roomType->price ?? 0))->toArray()) ?>;
const totalPerNight  = pricesPerNight.reduce((a, b) => a + b, 0);
const MAX_GUESTS     = <?= $totalMaxGuests ?>;
const TODAY          = '<?= date('Y-m-d') ?>';

// ── Refs ────────────────────────────────────────────
const checkInEl  = document.getElementById('checkIn');
const checkOutEl = document.getElementById('checkOut');
const peopleEl   = document.getElementById('peopleInput');
const childrenEl = document.getElementById('childrenInput');
const nameEl     = document.getElementById('customerName');
const emailEl    = document.getElementById('customerEmail');
const phoneInput = document.getElementById('customerPhone');
const phoneFeedback = document.getElementById('phoneFeedback');

// ── 1. Validate ngày ────────────────────────────────
function showDateErr(el, feedbackId, msg) {
    el.classList.add('is-invalid');
    el.classList.remove('is-valid');
    const fb = document.getElementById(feedbackId);
    if (fb) { fb.textContent = msg; fb.style.display = 'block'; }
}
function clearDateErr(el, feedbackId) {
    el.classList.remove('is-invalid');
    el.classList.add('is-valid');
    const fb = document.getElementById(feedbackId);
    if (fb) fb.style.display = 'none';
}

function syncCheckOutMin() {
    if (!checkInEl.value) return;
    const d = new Date(checkInEl.value);
    d.setDate(d.getDate() + 1);
    checkOutEl.min = d.toISOString().split('T')[0];

    if (checkOutEl.value && checkOutEl.value <= checkInEl.value) {
        checkOutEl.value = '';
        showDateErr(checkOutEl, 'checkOutFeedback', 'Ngày trả phòng phải sau ngày nhận phòng.');
    } else if (checkOutEl.value) {
        clearDateErr(checkOutEl, 'checkOutFeedback');
    }
    calcTotal();
}

function validateDates() {
    let ok = true;
    if (!checkInEl.value) {
        showDateErr(checkInEl, 'checkInFeedback', 'Vui lòng chọn ngày nhận phòng.'); ok = false;
    } else if (checkInEl.value < TODAY) {
        showDateErr(checkInEl, 'checkInFeedback', 'Ngày nhận phòng không được là ngày trong quá khứ.'); ok = false;
    } else {
        clearDateErr(checkInEl, 'checkInFeedback');
    }
    if (!checkOutEl.value) {
        showDateErr(checkOutEl, 'checkOutFeedback', 'Vui lòng chọn ngày trả phòng.'); ok = false;
    } else if (checkOutEl.value <= checkInEl.value) {
        showDateErr(checkOutEl, 'checkOutFeedback', 'Ngày trả phòng phải sau ngày nhận phòng.'); ok = false;
    } else {
        clearDateErr(checkOutEl, 'checkOutFeedback');
    }
    return ok;
}

if (checkInEl) {
    checkInEl.addEventListener('change', () => {
        if (checkInEl.value < TODAY) {
            showDateErr(checkInEl, 'checkInFeedback', 'Ngày nhận phòng không được là ngày trong quá khứ.');
        } else {
            clearDateErr(checkInEl, 'checkInFeedback');
            syncCheckOutMin();
        }
        calcTotal();
    });
}

if (checkOutEl) {
    checkOutEl.addEventListener('change', () => {
        if (!checkInEl.value) {
            checkOutEl.value = '';
            showDateErr(checkOutEl, 'checkOutFeedback', 'Vui lòng chọn ngày nhận phòng trước.');
            return;
        }
        if (checkOutEl.value <= checkInEl.value) {
            checkOutEl.value = '';
            showDateErr(checkOutEl, 'checkOutFeedback', 'Ngày trả phòng phải sau ngày nhận phòng.');
        } else {
            clearDateErr(checkOutEl, 'checkOutFeedback');
        }
        calcTotal();
    });
}

if (checkInEl && checkInEl.value) syncCheckOutMin();

// ── 2. Tính tổng tiền động ──────────────────────────
function calcTotal() {
    const ci = checkInEl.value;
    const co = checkOutEl.value;
    const box = document.getElementById('totalBox');

    if (!ci || !co || co <= ci) {
        box.classList.add('d-none');
        return;
    }
    const nights = Math.round((new Date(co) - new Date(ci)) / 86400000);
    if (nights <= 0) { box.classList.add('d-none'); return; }

    const total = nights * totalPerNight;
    document.getElementById('nightsText').textContent =
        nights + ' đêm × ' + totalPerNight.toLocaleString('vi-VN') + ' VNĐ/đêm';
    document.getElementById('totalText').textContent =
        'Tổng: ' + total.toLocaleString('vi-VN') + ' VNĐ';
    box.classList.remove('d-none');
}

// ── 3. Validate số khách ────────────────────────────
function validatePeople() {
    const adults = parseInt(peopleEl.value, 10) || 1;
    const children = parseInt(childrenEl.value, 10) || 0;
    const val = adults + children;
    if (val < 1) {
        setFieldState(peopleEl, 'error', 'peopleFeedback', 'Số khách tối thiểu là 1.');
        return false;
    }
    if (val > MAX_GUESTS) {
        setFieldState(peopleEl, 'error', 'peopleFeedback', `Tổng số khách (${val}) không được vượt quá sức chứa tối đa của các phòng đã chọn (${MAX_GUESTS}).`);
        return false;
    }
    setFieldState(peopleEl, 'success', 'peopleFeedback', '');
    return true;
}
if (peopleEl) {
    peopleEl.addEventListener('input', validatePeople);
    peopleEl.addEventListener('blur', validatePeople);
}

// ── 4. Validate họ tên ──────────────────────────────
function validateName() {
    const val = nameEl.value.trim();
    if (!val) {
        setFieldState(nameEl, 'error', 'nameFeedback', 'Vui lòng nhập họ tên.');
        return false;
    }
    if (val.length < 2) {
        setFieldState(nameEl, 'error', 'nameFeedback', 'Họ tên phải có ít nhất 2 ký tự.');
        return false;
    }
    if (!/\s/.test(val)) {
        setFieldState(nameEl, 'warning-only', 'nameFeedback', '⚠ Nên nhập đầy đủ họ và tên.');
        return true;
    }
    setFieldState(nameEl, 'success', 'nameFeedback', '');
    return true;
}
nameEl.addEventListener('blur', validateName);
nameEl.addEventListener('input', () => {
    if (nameEl.classList.contains('is-invalid')) validateName();
});

// ── 5. Validate email ───────────────────────────────
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
function validateEmail() {
    const val = emailEl.value.trim();
    if (!val) {
        setFieldState(emailEl, 'error', 'emailFeedback', 'Vui lòng nhập email.');
        return false;
    }
    if (!EMAIL_RE.test(val)) {
        setFieldState(emailEl, 'error', 'emailFeedback', 'Email không hợp lệ. Ví dụ: ten@example.com');
        return false;
    }
    setFieldState(emailEl, 'success', 'emailFeedback', '');
    return true;
}
emailEl.addEventListener('blur', validateEmail);
emailEl.addEventListener('input', () => {
    if (emailEl.classList.contains('is-invalid')) validateEmail();
});

// ── 6. Validate số điện thoại ───────────────────────
const PHONE_VN_RE  = /^(\+84|0)(3[2-9]|5[25689]|7[06-9]|8[1-9]|9[0-9])\d{7}$/;
const PHONE_GEN_RE = /^\+?[0-9\s\-]{7,15}$/;

function validatePhone() {
    const raw    = phoneInput.value.trim();
    const digits = raw.replace(/[\s\-]/g, '');

    phoneFeedback.classList.remove('d-none', 'text-danger', 'text-success', 'text-warning');

    if (!raw) {
        setPhoneState('error', 'Vui lòng nhập số điện thoại.');
        return false;
    }
    if (PHONE_VN_RE.test(digits)) {
        setPhoneState('success', '✓ Số điện thoại Việt Nam hợp lệ.');
        return true;
    }
    if (PHONE_GEN_RE.test(raw)) {
        setPhoneState('warning', '⚠ Có thể là số quốc tế — vui lòng kiểm tra lại.');
        return true;
    }
    setPhoneState('error', '✗ Số điện thoại không hợp lệ. Ví dụ: 0901234567 hoặc +84901234567.');
    return false;
}

function setPhoneState(type, msg) {
    phoneFeedback.textContent = msg;
    phoneFeedback.classList.remove('d-none');
    phoneInput.classList.remove('is-valid', 'is-invalid');
    if (type === 'success') {
        phoneFeedback.classList.add('text-success');
        phoneInput.classList.add('is-valid');
    } else if (type === 'warning') {
        phoneFeedback.classList.add('text-warning');
    } else {
        phoneFeedback.classList.add('text-danger');
        phoneInput.classList.add('is-invalid');
    }
}

let phoneTimer;
phoneInput.addEventListener('input', () => {
    clearTimeout(phoneTimer);
    phoneTimer = setTimeout(validatePhone, 400);
});
phoneInput.addEventListener('blur', validatePhone);

// ── Helper: set trạng thái field ────────────────────
function setFieldState(el, state, feedbackId, msg) {
    const fb = document.getElementById(feedbackId);
    el.classList.remove('is-valid', 'is-invalid');

    if (state === 'success') {
        el.classList.add('is-valid');
        if (fb) { fb.textContent = msg; }
    } else if (state === 'error') {
        el.classList.add('is-invalid');
        if (fb) { fb.textContent = msg; fb.style.display = 'block'; }
    } else if (state === 'warning-only') {
        if (fb) {
            fb.textContent = msg;
            fb.style.color = '#ffc107';
            fb.style.display = 'block';
            fb.classList.remove('d-none');
        }
    }
}


// ── Submit: validate toàn bộ ────────────────────────
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    let valid = true;

    if (!validateDates())         valid = false;
    if (!validatePeople())        valid = false;
    if (!validateName())          valid = false;
    if (!validateEmail())         valid = false;
    if (!validatePhone())         valid = false;

    if (!valid) {
        e.preventDefault();
        e.stopPropagation();
        const firstError = document.querySelector('.is-invalid');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    } else {
    sessionStorage.removeItem('booking_cart');
});
</script>
@endsection