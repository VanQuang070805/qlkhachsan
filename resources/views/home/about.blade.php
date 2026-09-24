@extends('layouts.main')

@section('content')
<style>
.section-title{
    text-align: center;
    font-size: 2.5rem;
    margin-bottom:1rem ;
    padding-top: 0;

}
.about-section { padding: 2rem 1.5rem 4rem; }
.about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; }
.about-img { border-radius: 14px; overflow: hidden; height: 380px; }
.about-img img { width: 100%; height: 100%; object-fit: cover; }
.about-text h3 { font-size: 1.8rem; color: #1a2e5a; font-weight: 800; margin-bottom: 1rem; font-family: Montserrat;}
.about-text p { color: #555; line-height: 1.7; margin-bottom: 0.75rem; }
.about-stats { display: flex; gap: 2rem; margin-top: 1.5rem; }
.stat { text-align: center; }
.stat span { font-size: 2rem; font-weight: 900; color: #1a2e5a; display: block; }
.stat p { font-size: 0.9rem; color: #666; font-weight: 600; }
/* Container tổng bao quanh bản đồ */
.map-container {
    max-width: 1200px; /* Giới hạn độ rộng bằng với nội dung trang web */
    margin: 60px auto; /* Căn giữa và tạo khoảng cách trên dưới */
    padding: 0 20px;   /* Khoảng cách an toàn khi xem trên điện thoại */
}

/* Tiêu đề "Địa chỉ" */
.map-title {
    font-family: 'Playfair Display', serif; /* Font chữ sang trọng đã dùng cho Nav Brand */
    font-size: 2rem;
    color: #1a2e5a; /* Màu xanh Navy chủ đạo */
    text-align: center;
    margin-bottom: 30px;
    position: relative;
}

/* Thêm một đường gạch chân nhỏ trang trí dưới tiêu đề */
.map-title::after {
    content: '';
    display: block;
    width: 60px;
    height: 3px;
    background: linear-gradient(to right, #f67905, #f15416); /* Màu Gradient đồng bộ với nút bấm */
    margin: 10px auto 0;
    border-radius: 2px;
}

/* Khung chứa Iframe */
.map {
    width: 100%;
    border-radius: 25px; /* Bo góc lớn để đồng bộ với Form liên hệ */
    overflow: hidden;    /* Cắt các góc nhọn của Iframe bên trong */
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Đổ bóng tạo chiều sâu */
    border: 1px solid #eee; /* Viền mỏng tinh tế */
}

/* Tối ưu Iframe bên trong để luôn hiển thị đầy đủ */
.map iframe {
    display: block; /* Loại bỏ khoảng trắng thừa dưới Iframe */
    width: 100% !important; /* Buộc Iframe luôn rộng hết cỡ thay vì cố định 1200px */
    filter: grayscale(0.2) contrast(1.1); /* Một chút hiệu ứng màu sắc cho hiện đại (tùy chọn) */
    transition: 0.5s;
}

.map iframe:hover {
    filter: grayscale(0); /* Hiện màu rõ nét khi di chuột vào */
}

/* --- Responsive: Điều chỉnh cho màn hình điện thoại --- */
@media (max-width: 768px) {
    .map-container {
        margin: 40px auto;
    }
    .map-title {
        font-size: 1.5rem;
    }
    .map iframe {
        height: 300px; /* Giảm chiều cao bản đồ trên điện thoại để đỡ tốn diện tích cuộn */
    }
}
</style>
<section class="about-section">
    <div class="section-container">
        <h2 class="section-title">GIỚI THIỆU </h2>
        <div class="about-grid">
            <div class="about-img">
                <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=600&q=80" alt="PHP Hotel">
            </div>
            <div class="about-text">
                <h3>Chào Mừng Đến Royal Hotel</h3>
                <p>Chào mừng đến Royal Hotel – nơi hội tụ sự tiện nghi, sang trọng và thoải mái. 
                Tọa lạc tại vị trí thuận lợi, khách sạn cung cấp các phòng nghỉ hiện đại, dịch vụ chất lượng cao cùng không gian thư giãn lý tưởng cho cả khách du lịch và công tác. 
</p>
                <p>Với đội ngũ nhân viên tận tâm và phong cách phục vụ chuyên nghiệp, chúng tôi cam kết mang đến cho bạn trải nghiệm lưu trú đáng nhớ.</p>
                <div class="about-stats">
                    <div class="stat"><span>25+</span><p>Phòng nghỉ</p></div>
                    <div class="stat"><span>5</span><p>Loại phòng</p></div>
                    <div class="stat"><span>5★</span><p>Chất lượng</p></div>
                </div>
            </div>
        </div>
    </div>
    <div class="map-container">
        <h2 class="map-title">Địa chỉ </h2>
        <div class="map">
                <!-- Mã nhúng iframe từ Google Maps (Tọa độ Chùa Bộc) -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.6203769615904!2d105.82535447503089!3d21.00784918063632!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ac806cfc0845%3A0x3848505bd3b9490f!2zMTIgUC4gQ2jDuWEgQuG7mWMsIEtpbSBMacOqbiwgSMOgIE7hu5lpIDEwMDAwMCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1777979688214!5m2!1svi!2s" 
                    width="1200" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
</div>
</section>
@endsection
