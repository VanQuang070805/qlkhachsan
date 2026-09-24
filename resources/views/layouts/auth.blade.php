<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Khách sạn Royal Hotel') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            background-color: #0f172a; /* Dark fallback */
        }

        .auth-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 80px 20px;
            position: relative;
            background-image: url('https://i.pinimg.com/1200x/a7/cc/2a/a7cc2a6bdcf9ec356b624620785053e7.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Soft overlay to let the bright image show but keep text readable */
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.1));
            z-index: 1;
        }

        .auth-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .auth-card {
            max-width: 480px;
            width: 100%;
            /* Premium White Glassmorphism effect */
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            margin: 0 auto;
        }

        .auth-card.register-card {
            max-width: 500px;
        }

        .auth-card:hover {
            transform: translateY(-5px);
            border-color: rgba(212, 175, 55, 0.5); /* Gold accent on hover */
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }

        .hotel-brand {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: #b08d28; /* Deeper Champagne Gold for white bg */
            text-align: center;
            margin-bottom: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .auth-card p.subtitle {
            color: #475569;
            text-align: center;
            font-size: 0.95rem;
            margin-bottom: 30px;
            font-weight: 400;
        }

        .auth-card .form-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .auth-card .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            color: #1e293b;
            transition: all 0.3s ease;
        }

        .auth-card .form-control::placeholder {
            color: #94a3b8;
        }

        .auth-card .form-control:focus {
            background: #ffffff;
            border-color: #d4af37;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
            outline: none;
        }

        .password-wrapper { position: relative; }
        .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            border: none; background: transparent; color: #64748b; cursor: pointer;
            transition: color 0.3s ease;
        }
        .toggle-password:hover { color: #d4af37; }

        .auth-card .btn-auth, .auth-card .btn-primary {
            background: linear-gradient(135deg, #d4af37, #b08d28);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1rem;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .auth-card .btn-auth:hover, .auth-card .btn-primary:hover {
            background: linear-gradient(135deg, #e5c158, #c49e35);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
            color: #ffffff;
        }

        .auth-card a {
            color: #b08d28;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .auth-card a:hover {
            color: #8c6e1e;
            text-decoration: underline;
        }

        .auth-card .text-center { font-size: 0.95rem; color: #475569; }

        .auth-card .alert {
            backdrop-filter: blur(5px);
            border-radius: 10px;
        }
        .auth-card .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        .auth-card .alert-success {
            background: rgba(25, 135, 84, 0.1);
            border: 1px solid rgba(25, 135, 84, 0.2);
            color: #198754;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #94a3b8;
            margin: 25px 0;
            font-weight: 500;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #cbd5e1;
        }
        .divider:not(:empty)::before { margin-right: 15px; }
        .divider:not(:empty)::after { margin-left: 15px; }

        @media (max-width: 768px) {
            .auth-container { padding: 40px 15px; }
            .auth-card { padding: 30px 20px; max-width: 100%; border-radius: 15px; }
            .hotel-brand { font-size: 1.8rem; }
            .auth-card .form-control { padding: 10px 12px; font-size: 0.95rem; }
            .auth-card .btn-auth, .auth-card .btn-primary { padding: 10px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- FLASH MESSAGE -->
            @if(session('success'))
                <div style="max-width:480px;margin:0 auto 15px auto;">
                    <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div style="max-width:480px;margin:0 auto 15px auto;">
                    <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 100 });
        
        // Toggle show/hide password for all inputs on the page
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.toggle-password');
            toggles.forEach(function(btn) {
                const wrapper = btn.closest('.password-wrapper');
                if (!wrapper) return;
                const pwd = wrapper.querySelector('input');
                if (!pwd) return;
                
                btn.addEventListener('click', function() {
                    const isPwd = pwd.getAttribute('type') === 'password';
                    pwd.setAttribute('type', isPwd ? 'text' : 'password');
                    this.innerHTML = isPwd ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                });
            });
        });
    </script>
</body>
</html>
