@extends('layouts.main')

@section('content')
<head>
    <meta charset="UTF-8">
    <title>...</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<style>/* Container tổng của phần liên hệ */
.contact-page-section {
    padding: 80px 20px;
    background-color: #fcfcfc;
    display: flex;
    justify-content: center;
}

/* Khung lớn bao bọc 2 cột */
.contact-main-container {
    display: grid;
    grid-template-columns: 40% 60%; /* Tỷ lệ cột xanh 40%, trắng 60% */
    max-width: 1100px;
    width: 100%;
    background-color: #fff;
    border-radius: 30px; /* Bo góc toàn bộ khung */
    overflow: hidden; /* Cắt phần thừa của màu nền tại góc bo */
    box-shadow: 0 15px 50px rgba(0,0,0,0.08);
}

/* CỘT XANH */
.contact-info-column {
    background-color: #000000; 
    color: #fff;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.column-title {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    margin-bottom: 10px;
}

.column-subtitle {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-bottom: 40px;
    line-height: 1.6;
}

.info-item-box {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.info-icon {
    width: 45px;
    height: 45px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.info-content span {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    opacity: 0.7;
    letter-spacing: 1px;
}

.info-content strong {
    font-size: 1rem;
}

.social-links {
    margin-top: 30px;
    display: flex;
    gap: 15px;
}

.social-links a {
    color: #fff;
    font-size: 1.2rem;
    opacity: 0.7;
    transition: 0.3s;
}

.social-links a:hover { opacity: 1; }

/* CỘT TRẮNG */
.contact-form-column {
    padding: 50px;
    background-color: #fff;
}

.form-header {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-split {
    display: flex;
    gap: 20px;
}

.form-group {
    flex: 1;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
}

.form-group input, 
.form-group textarea {
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 12px;
    outline: none;
    font-family: 'Montserrat', sans-serif;
    transition: 0.3s;
}

.form-group input:focus, 
.form-group textarea:focus {
    border-color: #090765;
    background-color: #fff;
}


.submit-gradient-btn {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 50px;
    background: linear-gradient(to right, #C9A84C, #f2f675);
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(255, 155, 155, 0.2);
    transition: 0.3s;
}

.submit-gradient-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(255, 155, 155, 0.3);
}
/* Responsive cho di động */
@media (max-width: 850px) {
    .contact-main-container {
        grid-template-columns: 1fr;
    }
    .form-split {
        flex-direction: column;
        gap: 0;
    }
}

</style>
<section class="contact-page-section">
    <div class="contact-main-container">
        <!-- CỘT TRÁI: MÀU XANH (Thông tin) -->
        <div class="contact-info-column">
            <h2 class="column-title">LIÊN HỆ</h2>
            <p class="column-subtitle">Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn 24/7.</p>
            
            <div class="info-list">
                <div class="info-item-box">
                    <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="info-content">
                        <span>HOTLINE</span>
                        <strong>0123456789</strong>
                    </div>
                </div>

                <div class="info-item-box">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-content">
                        <span>ĐỊA CHỈ</span>
                        <strong>12 Chùa Bộc, Đống Đa, Hà Nội</strong>
                    </div>
                </div>

                <div class="info-item-box">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="info-content">
                        <span>EMAIL</span>
                        <strong>royalhotel@gmail.com</strong>
                    </div>
                </div>
            </div>

            <!-- Bạn có thể thêm MXH ở đây -->
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

        <!-- CỘT PHẢI: MÀU TRẮNG (Form gửi tin nhắn) -->
        <div class="contact-form-column">
            <h3 class="form-header">
                <i class="far fa-paper-plane"></i> Gửi tin nhắn
            </h3>
            
            <form action="" method="POST" class="modern-form">
                <div class="form-split">
                    <div class="form-group">
                        <label>Tên của bạn</label>
                        <input type="text" name="name" placeholder="Họ và tên" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nội dung</label>
                    <textarea name="message" rows="5" placeholder="Bạn cần hỗ trợ điều gì..." required></textarea>
                </div>

                <button type="submit" class="submit-gradient-btn">
                    Gửi  </i>
                </button>
            </form>
        </div>
    </div>
</section>
@endsection
