@extends('layouts.auth')

@section('content')
<div class="auth-card" data-aos="fade-up">
    @if(session('error'))
        <div class="alert alert-danger text-center mb-4" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: #dc3545; border-radius: 10px; padding: 12px; font-size: 0.95rem;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success text-center mb-4" style="background: rgba(25, 135, 84, 0.1); border: 1px solid rgba(25, 135, 84, 0.2); color: #198754; border-radius: 10px; padding: 12px; font-size: 0.95rem;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="auth-header">
        <h2>Xác thực tài khoản</h2>
        <p class="mb-0">Mã OTP đã được gửi đến email: <strong>{{ $email }}</strong></p>
    </div>

    <div class="auth-body">
        <form action="{{ route('verify') }}" method="POST" class="auth-form">
        @csrf
            <div class="form-group mb-4">
                <label for="otp" class="form-label">Nhập mã OTP (6 chữ số)</label>
                <input type="text" 
                       name="otp" 
                       id="otp" 
                       class="form-control text-center" 
                       placeholder="______" 
                       maxlength="6" 
                       required 
                       style="font-size: 24px; letter-spacing: 8px; font-weight: bold;">
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3">XÁC THỰC NGAY</button>
        </form>

        <div class="text-center mt-4">
            <p class="mb-0">Không nhận được mã?</p>
            <div id="resend-container">
                <a href="{{ route('verify.resend') }}" id="resend-link" class="resend-link">Gửi lại mã OTP</a>
                <p id="countdown-text" class="text-muted small mt-2" style="display: none;">Bạn có thể gửi lại mã sau <span id="timer">60</span> giây</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const resendLink = document.getElementById('resend-link');
        const countdownText = document.getElementById('countdown-text');
        const timerSpan = document.getElementById('timer');
        let timeLeft = 0;

        // Kiểm tra xem có đang trong thời gian chờ không (sử dụng localStorage để giữ trạng thái khi reload)
        const lastSent = localStorage.getItem('last_otp_sent_time');
        const now = Date.now();
        
        if (lastSent && (now - lastSent) < 60000) {
            timeLeft = Math.ceil((60000 - (now - lastSent)) / 1000);
            startTimer();
        }

        resendLink.addEventListener('click', function(e) {
            if (timeLeft > 0) {
                e.preventDefault();
                return;
            }
            // Lưu thời điểm gửi
            localStorage.setItem('last_otp_sent_time', Date.now());
        });

        function startTimer() {
            resendLink.style.pointerEvents = 'none';
            resendLink.style.opacity = '0.5';
            countdownText.style.display = 'block';
            
            const interval = setInterval(function() {
                timeLeft--;
                timerSpan.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                    countdownText.style.display = 'none';
                }
            }, 1000);
        }
    });
</script>

<style>
    .auth-card {
        max-width: 450px;
        margin: 60px auto;
        background: #fff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .auth-header h2 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 10px;
    }
    .auth-header p {
        color: #6b7280;
    }
    .form-label {
        font-weight: 600;
        color: #374151;
    }
    .form-control:focus {
        border-color: #1e3a8a;
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    .btn-primary {
        background-color: #1e3a8a;
        border: none;
        font-weight: bold;
        letter-spacing: 1px;
        transition: all 0.3s;
    }
    .btn-primary:hover {
        background-color: #1e40af;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(30, 58, 138, 0.3);
    }
    .resend-link {
        color: #1e3a8a;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }
    .resend-link:hover {
        border-bottom-color: #1e3a8a;
    }
</style>

@endsection
