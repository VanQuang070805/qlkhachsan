<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#779bc1">
    <title>{{ $pageTitle ?? 'Tài khoản · Royal Hotel' }}</title>
    <link rel="icon" href="{{ asset('royal-hotel-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --auth-ink:#151515; --auth-blue:#779bc1; --auth-sky:#9abfda; }
        * { box-sizing:border-box; }
        body.auth-shell { margin:0; min-height:100vh; color:var(--auth-ink); font-family:Inter, sans-serif; background:linear-gradient(180deg,#779bc1 0%,#9abfda 57%,#cbdcec 100%); }
        .auth-container { min-height:100svh; display:grid; place-items:center; padding:clamp(24px,5vw,64px) 18px; overflow:hidden; position:relative; isolation:isolate; }
        .auth-container::before,.auth-container::after { content:""; position:absolute; z-index:-1; border-radius:50%; pointer-events:none; filter:blur(1px); }
        .auth-container::before { width:min(58vw,680px); aspect-ratio:1; top:-34%; right:-8%; background:radial-gradient(circle,rgba(255,255,255,.26),transparent 68%); }
        .auth-container::after { width:min(42vw,500px); aspect-ratio:1; bottom:-30%; left:-10%; background:radial-gradient(circle,rgba(37,151,208,.18),transparent 70%); }
        .auth-wrapper { width:min(100%,390px); margin:auto; }
        .auth-card { width:100%; padding:clamp(25px,3.8vw,34px); border:1px solid rgba(255,255,255,.8); border-radius:22px; background:rgba(255,255,255,.92); box-shadow:0 24px 66px rgba(24,50,76,.17); backdrop-filter:blur(18px); animation:auth-enter .7s cubic-bezier(.2,.75,.25,1) both; }
        .auth-card.register-card { width:100%; }
        .auth-card.register-card { padding:24px 28px; }
        .auth-card.register-card .auth-intro { margin-bottom:12px; }
        .auth-card.register-card .auth-brand__mark { width:42px; height:42px; border-radius:13px; }
        .auth-card.register-card .subtitle { margin-bottom:14px; }
        .auth-card.register-card .form-group { margin-bottom:9px; }
        .auth-card.register-card .form-control { min-height:40px; padding-block:7px; }
        .auth-card.register-card .field-error { margin-top:4px; }
        .auth-card.register-card .divider { margin:13px 0; }
        .auth-brand { display:inline-flex; align-items:center; justify-content:center; color:var(--auth-ink)!important; text-decoration:none!important; margin:0 auto 8px; }
        .auth-brand__logo { display:block; width:118px; height:64px; object-fit:contain; }
        .auth-brand__mark { display:grid; width:48px; height:48px; place-items:center; border-radius:15px; background:#09090b; color:#fff; box-shadow:0 10px 24px rgba(9,9,11,.16); }
        .auth-brand__svg { width:29px; height:29px; }
        .hotel-brand { color:var(--auth-ink); font:700 clamp(1.35rem,4vw,1.7rem)/1 Inter,sans-serif; letter-spacing:.18em; text-align:center; margin:0; padding-left:.18em; }
        .auth-card .subtitle { text-align:center; color:#686c75; margin:0 0 22px; font-size:.86rem; }
        .auth-card .form-label { color:#34363d; font-size:.83rem; font-weight:600; margin-bottom:8px; }.auth-card input[required]+.field-error{}.auth-card .form-label:has(+ input[required])::after,.auth-card .form-label:has(+ .password-wrapper input[required])::after{content:" *";color:#a33131}
        .auth-card .form-control,.auth-card select.form-control { min-height:44px; border:1px solid #e5e8ed; border-radius:11px; padding:9px 13px; background:#fff; box-shadow:none; font:400 .9rem Inter,sans-serif; }
        .auth-card .form-control:focus { border-color:#c9cdd2; outline:0; box-shadow:none; }
        .auth-card .form-control:focus-visible { border-color:#34363d; outline:1px solid rgba(21,21,21,.3); outline-offset:1px; box-shadow:none; }
        .auth-card .btn-auth,.auth-card .btn-primary { min-height:49px; width:100%; border:0!important; border-radius:999px; padding:12px 20px; background:#09090b!important; color:#fff!important; font:600 .94rem Inter,sans-serif; text-transform:none; letter-spacing:0; box-shadow:0 9px 22px rgba(10,17,27,.14); transition:transform .25s ease,box-shadow .25s ease,background .25s ease; }
        .auth-card .btn-auth:hover,.auth-card .btn-primary:hover { background:#24272d; transform:translateY(-2px); box-shadow:0 14px 26px rgba(10,17,27,.2); }
        .auth-card .auth-register-link { margin-top:17px!important; }
        .auth-card a { color:#386b97!important; text-decoration:none; font-weight:600; }
        .auth-card a:hover { color:#17496f; text-decoration:underline; }
        .auth-card .text-center { color:#656b75; }
        .auth-form-status { display:flex; align-items:flex-start; gap:8px; margin:0 0 14px; padding:0; color:#356a55; font-size:.78rem; line-height:1.45; }
        .auth-form-status.is-error { color:#a33131; }
        .auth-card .password-wrapper { position:relative; }
        .auth-card .password-wrapper .form-control { padding-right:48px; }
        .auth-card .toggle-password { position:absolute; top:50%; right:10px; transform:translateY(-50%); border:0; background:transparent; color:#777f8a; padding:8px; }
        .auth-card .divider { display:flex; align-items:center; gap:14px; color:#9297a0; margin:17px 0; font-size:.76rem; }
        .auth-card .divider::before,.auth-card .divider::after { content:""; height:1px; background:#e5e8ed; flex:1; }
        .auth-provider{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:46px;margin-bottom:14px;border:1px solid #dfe3e7;border-radius:999px;background:#fff;color:#25272b!important;font-size:.86rem;font-weight:600!important;text-decoration:none!important;transition:transform .2s,border-color .2s,box-shadow .2s}.auth-provider:hover{border-color:#bfc7ce;box-shadow:0 8px 20px rgba(20,45,70,.08);transform:translateY(-1px)}.auth-provider__mark{display:block;width:18px;height:18px;flex:0 0 18px}.auth-card ::placeholder{color:#9aa0a7;opacity:1}
        .auth-card .field-error { position:static; display:flex; align-items:center; gap:6px; min-height:18px; margin:6px 0 0; color:#a33131; font-size:.76rem; line-height:1.35; }
        .auth-card .field-error:empty { display:none; }
        .auth-card .credential-hint { display:flex; align-items:flex-start; gap:7px; margin:11px 0 0; color:#777d86; font-size:.72rem; line-height:1.45; }
        .auth-card .credential-hint i { color:#386b97; margin-top:1px; }
        .auth-card .credential-hint strong { color:#4e5560; font-weight:600; }
        .auth-card .form-control.is-invalid { border-color:#b84a4a; background-image:none; box-shadow:0 0 0 4px rgba(184,74,74,.08); }
        .auth-card .form-control.is-valid { border-color:#76a38f; background-image:none; }
        .auth-card .auth-intro { margin-bottom:20px; text-align:center; }
        .auth-card .form-group { margin-bottom:14px; }
        .auth-card .mb-4 { margin-bottom:1rem!important; }
        .auth-card .mt-4 { margin-top:1rem!important; }
        .auth-back { display:inline-flex; align-items:center; gap:8px; color:#fff!important; margin:0 0 18px 4px; font-size:.88rem; text-decoration:none!important; text-shadow:0 1px 8px rgba(20,45,70,.18); }
        @keyframes auth-enter { from { opacity:0; transform:translateY(18px) scale(.985); } to { opacity:1; transform:translateY(0) scale(1); } }
        @media(max-width:520px) { .auth-container { padding:18px 14px; } .auth-card { border-radius:20px; padding:24px 20px; } }
    </style>
</head>
<body class="auth-shell">
    @include('client.partials.page-loader')
    <main class="auth-container">
        <div class="auth-wrapper">
            <a class="auth-back" href="{{ route('home') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Royal Hotel</a>
            @yield('content')
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('.toggle-password');
            if (!button) return;
            const input = button.closest('.password-wrapper')?.querySelector('input');
            if (!input) return;
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', reveal ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            button.innerHTML = reveal ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>' : '<i class="bi bi-eye" aria-hidden="true"></i>';
        });

        document.querySelectorAll('form[data-inline-validation]').forEach((form) => {
            const fields = [...form.querySelectorAll('input[data-validate]')];
            const messageFor = (field) => {
                if (field.validity.valueMissing) return field.dataset.requiredMessage || 'Vui lòng điền thông tin này.';
                if (field.validity.typeMismatch) return field.dataset.typeMessage || 'Thông tin chưa đúng định dạng.';
                if (field.validity.tooShort) return field.dataset.minMessage || `Vui lòng nhập ít nhất ${field.minLength} ký tự.`;
                if (field.validity.patternMismatch) return field.dataset.patternMessage || 'Thông tin chưa đúng định dạng.';
                if (field.name === 'password_confirmation' && field.value !== form.querySelector('[name="password"]')?.value) return 'Mật khẩu xác nhận chưa trùng khớp.';
                return '';
            };
            const render = (field) => {
                const error = form.querySelector(`[data-error-for="${field.name}"]`);
                if (!error) return true;
                const message = messageFor(field);
                error.textContent = message;
                field.classList.toggle('is-invalid', Boolean(message));
                field.classList.toggle('is-valid', !message && field.value.length > 0);
                field.setAttribute('aria-invalid', String(Boolean(message)));
                return !message;
            };
            fields.forEach((field) => {
                field.addEventListener('blur', () => render(field));
                field.addEventListener('input', () => {
                    if (field.classList.contains('is-invalid')) render(field);
                    if (field.name === 'password') {
                        const confirmation = form.querySelector('[name="password_confirmation"]');
                        if (confirmation?.value) render(confirmation);
                    }
                });
            });
            form.addEventListener('submit', (event) => {
                const firstInvalid = fields.filter((field) => !render(field))[0];
                if (!firstInvalid) return;
                event.preventDefault();
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior:'smooth', block:'center' });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
