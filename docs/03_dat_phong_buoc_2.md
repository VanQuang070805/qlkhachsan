# Thiết Kế Chi Tiết: Màn Hình Đặt Phòng - Bước 2 (Thanh Toán Bảo Mật) & Hoàn Tất
**Trang:** `Đặt Phòng` — Bước 2: Thanh Toán Chuẩn Apple Wallet & Xác Nhận Thành Công  
**Hệ thống thiết kế:** Cupertino Luxury Hospitality (Apple Human Interface Guidelines)  
**Tập tin nguồn:** `src/component/BookingStep2View.tsx`, `src/component/BookingSuccessModal.tsx`
**Ngoài phạm vi:** Global header/navigation bar và footer.

---

## 1. Bố Cục Tổng Thể & Hệ Thống Lưới (Layout & Grid System)

- **Độ rộng khung chứa:** `max-w-7xl` (1280px), căn giữa `mx-auto`.
- **Khoảng cách đỉnh trang:** `pt-24 pb-16` (thông thoáng theo chuẩn máy tính bảng & desktop).
- **Phân bổ lưới 12 cột:**
  - `grid grid-cols-1 lg:grid-cols-12 gap-8 items-start`.
  - **Cột trái (Lựa chọn phương thức thanh toán Apple Wallet):** `lg:col-span-7` (chiếm 7/12 cột, ~58.33%).
  - **Cột phải (Hóa đơn tóm tắt bảo chứng TravelShield™):** `lg:col-span-5` (chiếm 5/12 cột, ~41.67%).

---

## 2. Chi Tiết Từng Component & Thông Số Kỹ Thuật

### A. Thanh Tiến Trình & Đồng Hồ Đếm Ngược (Stepper Header & Live Timer)
- **Cụm 3 bước trực quan:**
  1. **Bước 1 (Đã hoàn thành):** Nút tròn xanh ngọc `w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold` kèm chữ "1. Thông tin khách". Người dùng có thể click vào để quay lại chỉnh sửa biểu mẫu.
  2. **Đoạn nối hoàn tất:** Vạch xanh lá `w-8 sm:w-12 h-0.5 bg-emerald-500 rounded-full`.
  3. **Bước 2 (Đang thanh toán - Apple Blue):** Nút tròn `w-7 h-7 rounded-full bg-[#0071e3] text-white text-xs font-bold shadow-md shadow-blue-500/30` kèm chữ "2. Thanh toán bảo mật".
  4. **Đoạn nối chờ:** Vạch xám `w-6 sm:w-10 h-0.5 bg-slate-200 rounded-full`.
  5. **Bước 3 (Chờ hoàn tất):** Nút xám mờ `w-6 h-6 rounded-full bg-slate-200 text-slate-500 opacity-50`.
- **Bộ đếm thời gian giữ phòng thời gian thực (Micro Countdown Timer):**
  - Vỏ: `flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200/60 text-amber-700 shadow-xs`.
  - Biểu tượng đồng hồ: `material-symbols-outlined text-[16px] schedule`.
  - Nhãn chữ: `text-xs uppercase font-medium tracking-wide` ("GIỮ PHÒNG:").
  - Đồng hồ điện tử: `text-xs font-bold font-mono tabular-nums` (đếm ngược từng giây từ `14:59` về `00:00`).

---

### B. Cột Trái: Cụm Phương Thức Thanh Toán (Apple Wallet Payment Stack)
Các tùy chọn thanh toán được thiết kế như những chiếc thẻ xếp chồng trong ứng dụng Apple Wallet.

- **Thẻ 1: Apple Pay (Khuyên dùng - Mặc định):**
  - Thiết kế: Nền trắng sứ với viền xanh Apple nổi bật khi chọn `peer-checked:border-[#0071e3] peer-checked:ring-2 peer-checked:ring-blue-600/20 peer-checked:bg-blue-50/20`.
  - Nút chọn Radio: Vòng tròn đôi tinh xảo đường kính 20px, tâm xanh sáng khi kích hoạt.
  - Huy hiệu "Khuyên dùng": `px-2 py-0.5 rounded-md bg-blue-100 text-[#0071e3] text-[11px] font-semibold tracking-wide`.
  - Biểu trưng Apple Pay chính hãng:
    - Kích thước: `h-10 px-3.5 rounded-xl bg-black text-white flex items-center justify-center shadow-sm`.
    - Logo quả táo Apple Pay vector SVG nguyên bản.
- **Thẻ 2: Thẻ quốc tế (Visa, Mastercard, JCB):**
  - Nhãn hiệu thẻ đính kèm: 3 badge nhỏ xếp gọn (`VISA` xanh, `MC` đỏ, `JCB` xanh lá).
  - Vùng nhập thẻ tự động mở rộng khi chọn:
    - Ô Số thẻ: Hiển thị định dạng che phủ `•••• •••• •••• 4242` kèm tích xanh `verified`.
    - Hạn sử dụng: `08 / 28`.
    - Mã CVV/CVC: `888` kèm icon khóa bảo mật.
- **Thẻ 3: Chuyển khoản VietQR 24/7 (Tự động 3s):**
  - Huy hiệu "Tự động 3s": `bg-emerald-100 text-emerald-700 text-[11px] font-semibold`.
  - Khi chọn: Mở rộng khung mã VietQR chuẩn Napas 247 kèm số tài khoản Vietcombank và cú pháp chuyển tiền tiện lợi `AETH 8921 MINH`.
- **Thẻ 4: Ví MoMo / ZaloPay:**
  - Biểu tượng ví điện tử: Hộp hồng pastel `bg-pink-50 text-pink-600` kích thước 40x40px.
- **Thẻ 5: Thanh toán tại quầy lễ tân (Check-in Desk):**
  - Biểu tượng chìa khóa lễ tân Concierge sang trọng.

---

### C. Hộp Lưu Trữ Apple Keychain & Nút Thanh Toán Chính
- **Hộp kiểm Apple Keychain:**
  - Hộp kiểm bo tròn: `w-4 h-4 rounded text-[#0071e3] focus:ring-blue-500 border-slate-300`.
  - Nhãn chữ: Icon chìa khóa vàng + "Lưu an toàn vào Apple Keychain để thanh toán nhanh lần sau".
- **Nút Thanh toán chính (Primary CTA Button):**
  - Kích thước: `w-full py-4 px-6 rounded-2xl font-semibold text-base flex items-center justify-center gap-2`.
  - Màu nền: `bg-[#0071e3] text-white shadow-lg shadow-blue-500/25 hover:bg-[#0077ed] active:scale-[0.99]`.
  - Nhãn nút thay đổi tương ứng:
    - Chọn Apple Pay: *"Xác nhận & Thanh toán 16.441.500₫"*
    - Chọn Thẻ: *"Thanh toán bằng thẻ 16.441.500₫"*
    - Chọn VietQR: *"Tạo mã QR thanh toán 16.441.500₫"*
    - Chọn Tại quầy: *"Xác nhận giữ chỗ (Thanh toán tại quầy)"*
- **3 Huy hiệu bảo chứng chân trang:**
  - Bảo mật FIDO2 (`verified_user` xanh ngọc).
  - Face ID / Touch ID (`fingerprint` xanh Apple).
  - Hoàn 100% trước 48h (`refresh` xám bạc).

---

### D. Cột Phải: Hóa Đơn Chi Tiết Đặt Phòng (Order Summary Receipt)
Thiết kế theo dạng hóa đơn biên lai cao cấp bo góc 24px (`rounded-3xl`).

- **Đầu biên lai:**
  - Tiêu đề: `CHI TIẾT ĐẶT PHÒNG` (uppercase, tracking-wider).
  - Trạng thái: `px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold` ("Đang giữ phòng").
- **Ảnh thu nhỏ Suite & Phân hạng:**
  - Khung ảnh vuông: `w-20 h-20 rounded-2xl overflow-hidden shrink-0 border border-slate-200`.
  - Tiêu đề phụ: `VILLA BIỆT LẬP HƯỚNG ĐỒI`.
  - Tên Suite: `The Grand Aetheria Suite` (145 m² • 3 đêm lưu trú).
- **Khung ngày Nhận / Trả phòng kép:**
  - Nền khay: `grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-xs`.
  - Nhận phòng: **18/06/2026** Thứ Năm (14:00).
  - Trả phòng: **21/06/2026** Chủ Nhật (12:00).
- **Khung thông tin khách có nút Sửa:**
  - Avatar viết tắt `HM` nền xanh.
  - Tên & Email: `Nguyễn Hoàng Minh` (hoangminh.arch@icloud.com).
  - Nút chữ "Sửa": `text-xs font-semibold text-[#0071e3] hover:text-blue-700` — quay lại Bước 1 chỉ với 1 click.
- **Phân bổ chi phí & Khuyến mãi (Voucher SUMMER2026):**
  - Giá phòng (3 đêm): `15.750.000₫`
  - Phí dịch vụ & Quản gia VIP: `1.191.500₫`
  - Viên thuốc giảm giá: `p-2.5 rounded-xl bg-blue-50 border border-blue-100 text-[#0071e3] flex justify-between`.
    - Mã voucher: `SUMMER2026`.
    - Mức giảm: `-500.000₫`.
  - Tổng tiền thanh toán cuối cùng:
    - Số tiền VNĐ: `text-2xl font-bold text-slate-900 tracking-tight` (`16.441.500₫`).
    - Quy đổi USD ước tính: `text-xs text-slate-400 font-medium` (`≈ $642.00 USD`).
- **Thẻ Bảo Hiểm TravelShield™:**
  - Khung xanh ngọc: `p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/60 flex items-center gap-3`.
  - Icon khiên bảo vệ `shield`.
  - Nội dung: "Bảo hiểm lưu trú TravelShield™ — Được bảo vệ hủy phòng khẩn cấp miễn phí".
- **Hỗ trợ thanh toán nhanh:**
  - Hotline trực tiếp 24/7: `Hotline 1900 8899` có icon điện thoại bấm gọi ngay.

---

### E. Màn Hình Mô Phỏng Xác Thực Face ID & Modal Thành Công (Step 3: Hoàn Tất)
- **Mô phỏng Face ID:**
  - Khối chữ nhật kính đen bo cong siêu lớn `rounded-[36px] bg-slate-900 border border-slate-700 p-8 text-center text-white`.
  - Vòng tròn quét gương mặt: Icon `face` nhấp nháy phát sáng công nghệ sinh trắc học Apple.
- **Modal Hoàn Tất & Kích Hoạt Pháo Hoa (Confetti Celebration):**
  - Khi thanh toán thành công, thư viện `canvas-confetti` phát nổ chùm pháo hoa sắc màu thương hiệu (`#0071e3`, `#10b981`, `#38bdf8`, `#f59e0b`).
  - Hộp thông báo: `rounded-[32px] bg-white border border-slate-200 p-8 shadow-2xl text-center max-w-md`.
  - Biểu tượng tích xanh lớn: Vòng tròn xanh ngọc kích thước 64x64px.
  - Mã đặt phòng độc nhất: `#AETH-8921`.
  - 3 Nút tiện ích ngay sau khi đặt:
    1. **"Thêm thẻ phòng vào Apple Wallet":** Mở thẻ PassKit có mã vạch và NFC.
    2. **"Mã Check-in":** Mở mã QR điện tử quét tại quầy lễ tân hoặc thang máy.
    3. **"Xem Lịch Sử":** Chuyển hướng người dùng sang trang Lịch Sử Đặt Phòng.

---

## 3. Bảng Tổng Hợp Chuyển Động & Hiệu Ứng (Animations & Transitions)

| Tên thành phần | Sự kiện kích hoạt | Thuộc tính CSS biến đổi | Thời lượng & Đường cong | Mô tả hiệu ứng |
| :--- | :--- | :--- | :--- | :--- |
| **Đồng hồ đếm ngược** | Mỗi 1 giây (1s) | Giá trị số `tabular-nums` | `1000ms linear` | Nhảy số mượt không giật lệch cột |
| **Thẻ Phương thức TT** | Click chọn thẻ | `border-color`, `ring-width`, `background-color` | `200ms ease-out` | Vòng sáng xanh ôm trọn thẻ như Apple Wallet |
| **Ô nhập thẻ Visa/MC** | Khi chọn thẻ quốc tế | `opacity: 0 -> 1`, `height: 0 -> auto` | `200ms ease-in-out` | Trượt nở các ô nhập số thẻ và CVV |
| **Mô phỏng Face ID** | Bấm thanh toán Apple Pay | `opacity: 0 -> 1`, `scale: 0.95 -> 1` | `300ms ease-out` | Hộp đen sinh trắc học hiện lên giữa màn hình |
| **Pháo hoa Confetti** | Xác nhận thanh toán xong | Particle Canvas Physics | `2500ms gravity physics` | 80 hạt confetti tỏa ra ăn mừng đặt phòng |
| **Modal Thành công** | Sau khi xác thực | `zoom-in-95`, `fade-in` | `200ms cubic-bezier(0.16, 1, 0.3, 1)` | Nở nhẹ nhàng từ trung tâm màn hình |
