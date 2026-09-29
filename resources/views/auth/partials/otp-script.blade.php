<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('otp-form');
    const cells = Array.from(document.querySelectorAll('.otp-cell'));
    const hiddenInput = document.getElementById('otp-hidden');
    const grid = document.getElementById('otp-grid');
    const errorEl = document.getElementById('otp-error');
    const submitBtn = document.getElementById('btn-submit-otp');
    const resendBtn = document.getElementById('btn-resend-otp');
    const resendForm = document.getElementById('resend-form');
    const countdownWrap = document.getElementById('otp-countdown');
    const countdownSec = document.getElementById('countdown-sec');
    const toastEl = document.getElementById('otp-toast');

    const storageKey = '{{ $storageKey ?? "last_otp_resend_time" }}';
    let countdownTimer = null;

    // Synchronize cells to hidden input
    function syncCellsToHidden() {
        const value = cells.map(cell => cell.value).join('');
        if (hiddenInput) hiddenInput.value = value;
        return value;
    }

    // Set error message without clearing values
    function setError(msg) {
        if (!errorEl) return;
        if (msg) {
            errorEl.innerHTML = `<i class="bi bi-exclamation-circle" aria-hidden="true"></i> <span>${msg}</span>`;
            errorEl.removeAttribute('data-empty');
            errorEl.style.removeProperty('display');
            errorEl.style.display = 'flex';
            cells.forEach(c => c.classList.add('is-invalid'));
            if (grid) {
                grid.classList.remove('has-error');
                void grid.offsetWidth;
                grid.classList.add('has-error');
            }
        } else {
            errorEl.innerHTML = '';
            errorEl.setAttribute('data-empty', 'true');
            errorEl.style.setProperty('display', 'none', 'important');
            cells.forEach(c => c.classList.remove('is-invalid'));
            if (grid) grid.classList.remove('has-error');
        }
    }

    // Toast feedback helper
    function showToast(msg, isSuccess = true) {
        if (!toastEl) return;
        toastEl.className = 'otp-status-toast ' + (isSuccess ? 'is-success' : 'is-error');
        toastEl.innerHTML = `<i class="bi bi-${isSuccess ? 'check-circle' : 'exclamation-circle'}" aria-hidden="true"></i> <span>${msg}</span>`;
        toastEl.style.display = 'flex';
        setTimeout(() => {
            toastEl.style.display = 'none';
        }, 5000);
    }

    // Cell input handlers
    cells.forEach((cell, idx) => {
        if (cell.value) cell.classList.add('is-filled');

        cell.addEventListener('input', function () {
            const val = this.value.replace(/\D/g, '');
            this.value = val ? val[0] : '';
            this.classList.toggle('is-filled', Boolean(this.value));

            setError('');
            syncCellsToHidden();

            if (this.value && idx < cells.length - 1) {
                cells[idx + 1].focus();
            }
        });

        cell.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace') {
                if (!this.value && idx > 0) {
                    cells[idx - 1].focus();
                    cells[idx - 1].value = '';
                    cells[idx - 1].classList.remove('is-filled');
                    syncCellsToHidden();
                    e.preventDefault();
                } else {
                    this.value = '';
                    this.classList.remove('is-filled');
                    syncCellsToHidden();
                }
            } else if (e.key === 'ArrowLeft' && idx > 0) {
                cells[idx - 1].focus();
                e.preventDefault();
            } else if (e.key === 'ArrowRight' && idx < cells.length - 1) {
                cells[idx + 1].focus();
                e.preventDefault();
            }
        });

        cell.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const digits = text.replace(/\D/g, '').slice(0, 6);
            if (!digits) return;

            digits.split('').forEach((digit, i) => {
                if (cells[i]) {
                    cells[i].value = digit;
                    cells[i].classList.add('is-filled');
                }
            });

            syncCellsToHidden();
            setError('');

            const nextIndex = Math.min(digits.length, cells.length - 1);
            cells[nextIndex].focus();
        });
    });

    // Populate from old value if exists
    if (hiddenInput && hiddenInput.value) {
        const oldDigits = hiddenInput.value.replace(/\D/g, '').slice(0, 6);
        oldDigits.split('').forEach((d, i) => {
            if (cells[i]) {
                cells[i].value = d;
                cells[i].classList.add('is-filled');
            }
        });
    }

    // Submit handler via Fetch (Inline validation, NO PAGE RELOAD)
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const otp = syncCellsToHidden();
            if (otp.length < 6) {
                setError('Vui lòng nhập đầy đủ 6 chữ số mã OTP.');
                const firstEmpty = cells.find(c => !c.value) || cells[0];
                firstEmpty.focus();
                return;
            }

            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="width: 15px; height: 15px; border-width: 2px;"></span> Đang xác thực...';
            }

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json().catch(() => null);

                if (response.ok && data && (data.redirect || data.success)) {
                    window.location.href = data.redirect || '/';
                    return;
                }

                if (response.status === 422 && data) {
                    const message = (data.errors && data.errors.otp)
                        ? (Array.isArray(data.errors.otp) ? data.errors.otp[0] : data.errors.otp)
                        : (data.message || 'Mã OTP không đúng. Vui lòng thử lại.');
                    setError(message);
                    // Retain typed values without clearing!
                    cells[cells.length - 1].focus();
                    return;
                }

                if (response.status === 429) {
                    setError('Bạn đã thao tác quá nhiều lần. Vui lòng chờ 1 phút.');
                    return;
                }

                setError(data?.message || 'Không thể xác thực mã OTP. Vui lòng thử lại.');
            } catch (err) {
                console.error('OTP verify error:', err);
                setError('Không thể kết nối đến máy chủ. Vui lòng kiểm tra đường truyền.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // Resend OTP countdown & handling
    function startCountdown(seconds) {
        let remaining = seconds;
        if (resendBtn) resendBtn.disabled = true;
        if (countdownWrap) countdownWrap.style.display = 'inline';
        if (countdownSec) countdownSec.textContent = remaining;

        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            remaining--;
            if (countdownSec) countdownSec.textContent = remaining;
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                if (resendBtn) resendBtn.disabled = false;
                if (countdownWrap) countdownWrap.style.display = 'none';
            }
        }, 1000);
    }

    // Check if within existing wait time from localStorage
    const lastSent = localStorage.getItem(storageKey);
    const now = Date.now();
    if (lastSent && (now - lastSent) < 60000) {
        const remaining = Math.ceil((60000 - (now - lastSent)) / 1000);
        startCountdown(remaining);
    }

    if (resendBtn && resendForm) {
        resendBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            if (resendBtn.disabled) return;

            const origText = resendBtn.textContent;
            resendBtn.disabled = true;
            resendBtn.textContent = 'Đang gửi...';

            try {
                const formData = new FormData(resendForm);
                const response = await fetch(resendForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json().catch(() => null);

                if (response.ok && data) {
                    localStorage.setItem(storageKey, Date.now());
                    showToast(data.message || 'Mã OTP mới đã được gửi thành công.', true);
                    startCountdown(60);
                } else if (response.status === 429) {
                    showToast(data?.message || 'Vui lòng chờ ít phút trước khi gửi lại.', false);
                    startCountdown(60);
                } else {
                    showToast(data?.message || 'Không thể gửi lại mã OTP. Vui lòng thử lại sau.', false);
                    resendBtn.disabled = false;
                    resendBtn.textContent = origText;
                }
            } catch (err) {
                console.error('OTP resend error:', err);
                showToast('Lỗi kết nối khi gửi lại OTP.', false);
                resendBtn.disabled = false;
                resendBtn.textContent = origText;
            }
        });
    }
});
</script>
