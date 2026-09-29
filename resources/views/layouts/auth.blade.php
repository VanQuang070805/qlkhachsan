<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Tài khoản · Royal Hotel' }}</title>
    <link rel="icon" href="{{ asset('royal-hotel-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="auth-shell">

    {{-- Top Notch Header (Identical to Homepage Notch, Without Login Button) --}}
    <header class="site-header" data-site-header data-home-header data-homepage-header aria-label="Thanh điều hướng tài khoản">
        <div class="site-header__inner">
            <a href="{{ route('home') }}" class="site-header__brand-spacer" aria-label="Về trang chủ Royal Hotel"></a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle>
                <span class="sr-only">Mở menu</span><span></span><span></span>
            </button>

            <nav class="primary-nav" id="primary-navigation" aria-label="Điều hướng chính" data-primary-nav>
                <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Trang chủ</a>
                <a class="{{ request()->routeIs('rooms.*') ? 'is-active' : '' }}" href="{{ route('rooms.index') }}">Phòng nghỉ</a>
                <a class="{{ request()->routeIs('contact') ? 'is-active' : '' }}" href="{{ route('contact') }}">Chính sách &amp; Điều khoản</a>
                <a href="{{ route('home') }}#faq">FAQ</a>
            </nav>

            <div class="site-header__actions">
                <a class="button button--small" href="{{ route('rooms.index') }}">Đặt phòng</a>
            </div>
        </div>
    </header>

    {{-- Split 2-Column Main Layout --}}
    <main class="sana-auth-main">
        {{-- Left Column: Form Content --}}
        <div class="sana-form-col">
            @yield('content')
        </div>

        {{-- Right Column: Dark Luxury Laptop Showcase (Adapted for Royal Hotel) --}}
        <div class="sana-showcase-col" aria-hidden="true">
            <div class="sana-laptop">
                {{-- macOS Traffic Lights Header --}}
                <div class="macos-card-bar">
                    <div class="window-controls">
                        <span class="ctrl-dot ctrl-red"></span>
                        <span class="ctrl-dot ctrl-yellow"></span>
                        <span class="ctrl-dot ctrl-green"></span>
                    </div>
                    <span class="macos-card-title">Royal Hotel &mdash; Cổng trải nghiệm dịch vụ</span>
                    <div class="macos-card-spacer" aria-hidden="true"></div>
                </div>

                {{-- Laptop Body Inside --}}
                <div class="sana-laptop__body">
                    {{-- Sidebar --}}
                    <div class="sana-laptop__sidebar">
                        <div class="sana-laptop__workspace">
                            <span class="sana-laptop__workspace-dot"></span>
                            <span>Royal Suites</span>
                        </div>
                        <ul class="sana-laptop__nav-list">
                            <li class="sana-laptop__nav-item is-active">
                                <i class="bi bi-house-door"></i>
                                <span>Tổng quan</span>
                            </li>
                            <li class="sana-laptop__nav-item">
                                <i class="bi bi-door-open"></i>
                                <span>Hạng phòng</span>
                            </li>
                            <li class="sana-laptop__nav-item">
                                <i class="bi bi-cup-hot"></i>
                                <span>Ẩm thực 24/7</span>
                            </li>
                            <li class="sana-laptop__nav-item">
                                <i class="bi bi-water"></i>
                                <span>Spa &amp; Hồ bơi</span>
                            </li>
                            <li class="sana-laptop__nav-item">
                                <i class="bi bi-chat-dots"></i>
                                <span>Concierge AI</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Main Area --}}
                    <div class="sana-laptop__content">
                        <div>
                            <div class="sana-laptop__greeting">
                                <h3>Welcome to Royal Hotel</h3>
                                <p>Không gian lưu trú tinh tế &amp; dịch vụ chuẩn mực 5 sao</p>
                            </div>

                            <div class="sana-laptop__cards">
                                {{-- Card 1: Presidential Suite --}}
                                <div class="sana-laptop__card">
                                    <img src="{{ asset('images/rooms/6.jpg') }}" alt="Presidential Suite" class="sana-laptop__card-img" onerror="this.src='{{ asset('images/rooms/default.jpg') }}'">
                                    <p class="sana-laptop__card-title">Presidential Suite</p>
                                    <p class="sana-laptop__card-sub">Skyline view &amp; Private pool</p>
                                </div>

                                {{-- Card 2: Royal Executive --}}
                                <div class="sana-laptop__card">
                                    <img src="{{ asset('images/rooms/1.jpg') }}" alt="Royal Executive" class="sana-laptop__card-img" onerror="this.src='{{ asset('images/rooms/default.jpg') }}'">
                                    <p class="sana-laptop__card-title">Executive Suite</p>
                                    <p class="sana-laptop__card-sub">24/7 Butler &amp; Jacuzzi</p>
                                </div>

                                {{-- Card 3: Wellness Spa --}}
                                <div class="sana-laptop__card">
                                    <img src="{{ asset('images/rooms/2.jpg') }}" alt="Wellness Spa" class="sana-laptop__card-img" onerror="this.src='{{ asset('images/rooms/default.jpg') }}'">
                                    <p class="sana-laptop__card-title">Spa &amp; Dining</p>
                                    <p class="sana-laptop__card-sub">Fine Dining &amp; Massage</p>
                                </div>
                            </div>
                        </div>

                        {{-- Bottom Prompt Bar --}}
                        <div class="sana-laptop__prompt-bar">
                            <i class="bi bi-stars" style="color: #60a5fa;"></i>
                            <span>Hỏi Royal Concierge về phòng nghỉ, đưa đón hoặc dịch vụ...</span>
                        </div>
                    </div>
                </div>

                {{-- Keyboard Deck --}}
                <div class="sana-laptop__keyboard-deck">
                    <div class="sana-laptop__notch-lip"></div>
                </div>
            </div>
        </div>
    </main>

    {{-- Bottom Bar --}}
    <footer class="sana-bottom-bar" aria-label="Thông tin chân trang đăng nhập">
        <div class="sana-bottom-bar__left">
            <span class="dot-mark" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
            </span>
            <span>Royal Hotel</span>
        </div>
        <div class="sana-bottom-bar__right">
            <span>Curated for a quieter kind of stay</span>
            <span>·</span>
            <strong>✦ 5-Star Luxury Retreat</strong>
        </div>
    </footer>

    {{-- Password Toggle and Inline Validation Scripts --}}
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.toggle-password, .sana-password-toggle');
            if (!button) return;
            const wrap = button.closest('.sana-password-wrap, .password-wrapper');
            const input = wrap?.querySelector('input');
            if (!input) return;
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', reveal ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            button.innerHTML = reveal ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>' : '<i class="bi bi-eye" aria-hidden="true"></i>';
        });

        document.querySelectorAll('form[data-inline-validation]').forEach((form) => {
            const fields = [...form.querySelectorAll('input[data-validate]')];

            const getFieldErrorMessage = (field) => {
                const val = field.value.trim();
                // 1. Required and empty
                if (field.hasAttribute('required') && !val) {
                    return field.dataset.requiredMessage || 'Vui lòng điền thông tin này.';
                }
                // If empty and not required, no error
                if (!val) {
                    return '';
                }
                // 2. Email format check
                if (field.type === 'email' || field.getAttribute('inputmode') === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(val)) {
                        return field.dataset.typeMessage || 'Email chưa đúng định dạng.';
                    }
                }
                // 3. Min length check
                if (field.minLength > 0 && field.value.length < field.minLength) {
                    return field.dataset.minMessage || `Vui lòng nhập ít nhất ${field.minLength} ký tự.`;
                }
                // 4. Pattern check (e.g. phone 10 digits starting with 0)
                if (field.pattern) {
                    const reg = new RegExp('^' + field.pattern + '$');
                    if (!reg.test(val)) {
                        return field.dataset.patternMessage || 'Thông tin chưa đúng định dạng.';
                    }
                }
                // 5. Password confirmation match check
                if (field.name === 'password_confirmation') {
                    const pwd = form.querySelector('[name="password"]')?.value;
                    if (pwd && field.value !== pwd) {
                        return 'Mật khẩu xác nhận chưa trùng khớp.';
                    }
                }
                return '';
            };

            const setFieldError = (field, message) => {
                const errorEl = form.querySelector(`[data-error-for="${field.name}"]`);
                if (errorEl) {
                    errorEl.innerHTML = message ? `<i class="bi bi-exclamation-circle" aria-hidden="true"></i> ${message}` : '';
                }
                field.classList.toggle('is-invalid', Boolean(message));
                field.classList.toggle('is-valid', !message && field.value.length > 0);
                field.setAttribute('aria-invalid', String(Boolean(message)));
                // NOTE: DO NOT CLEAR FIELD VALUE! Retain user inputs at all times.
                return !message;
            };

            const clearFieldError = (field) => {
                const errorEl = form.querySelector(`[data-error-for="${field.name}"]`);
                if (errorEl) {
                    errorEl.innerHTML = '';
                }
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
            };

            fields.forEach((field) => {
                // Blur: show error if invalid, but NEVER clear value
                field.addEventListener('blur', () => {
                    const msg = getFieldErrorMessage(field);
                    if (msg) {
                        setFieldError(field, msg);
                    }
                });

                // Input: clear error as soon as user types
                field.addEventListener('input', () => {
                    if (field.classList.contains('is-invalid')) {
                        clearFieldError(field);
                    }
                });
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                // 1. Client-side validation: check all fields
                let hasError = false;
                let firstInvalid = null;

                for (const field of fields) {
                    const msg = getFieldErrorMessage(field);
                    if (msg) {
                        setFieldError(field, msg);
                        if (!firstInvalid) firstInvalid = field;
                        hasError = true;
                    } else {
                        clearFieldError(field);
                    }
                }

                if (hasError && firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                // 2. Submit via Fetch (No page reload, keep all values!)
                const submitBtn = form.querySelector('button[type="submit"]');
                const origBtnText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="width: 15px; height: 15px; border-width: 2px;"></span> Đang xử lý...';
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

                    // Parse JSON response
                    const data = await response.json().catch(() => null);

                    // Check if success and redirect
                    if (response.ok && data && (data.redirect || data.success)) {
                        window.location.href = data.redirect || '/';
                        return;
                    }

                    // 422: Validation / Authentication error (from Laravel)
                    if (response.status === 422 && data && data.errors) {
                        let focused = false;
                        for (const [errKey, errVal] of Object.entries(data.errors)) {
                            const matchingField = form.querySelector(`[name="${errKey}"]`);
                            const errorMsg = Array.isArray(errVal) ? errVal[0] : errVal;
                            if (matchingField) {
                                setFieldError(matchingField, errorMsg);
                                if (!focused) {
                                    matchingField.focus();
                                    focused = true;
                                }
                            }
                        }
                        return;
                    }

                    // 429: Too Many Requests (Rate limit)
                    if (response.status === 429) {
                        const targetField = form.querySelector('[name="password"]') || fields[0];
                        if (targetField) {
                            setFieldError(targetField, 'Bạn đã thử quá nhiều lần. Vui lòng chờ 1 phút rồi thử lại.');
                            targetField.focus();
                        }
                        return;
                    }

                    // Other error message
                    if (data && data.message) {
                        const targetField = form.querySelector('[name="password"]') || fields[0];
                        if (targetField) {
                            setFieldError(targetField, data.message);
                            targetField.focus();
                        }
                    } else {
                        const targetField = form.querySelector('[name="password"]') || fields[0];
                        if (targetField) {
                            setFieldError(targetField, 'Thông tin chưa chính xác. Vui lòng kiểm tra lại.');
                            targetField.focus();
                        }
                    }
                } catch (err) {
                    console.error('Auth request error:', err);
                    const firstField = fields[0];
                    if (firstField) {
                        setFieldError(firstField, 'Không thể kết nối đến máy chủ. Vui lòng thử lại.');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnText;
                    }
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
