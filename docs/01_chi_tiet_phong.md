# Thiết Kế Chi Tiết: Màn Hình Chi Tiết Phòng (Room Details)
**Trang:** `Chi Tiết Phòng` — Grand Ocean Panorama Suite  
**Hệ thống thiết kế:** Cupertino Luxury Hospitality (Apple Human Interface Guidelines)  
**Tập tin nguồn:** `src/component/RoomDetailView.tsx`, `src/component/GalleryModal.tsx`
**Ngoài phạm vi:** Global header/navigation bar và footer.

---

## 1. Bố Cục Tổng Thể & Hệ Thống Grid (Layout & Geometry)

- **Độ rộng khung nhìn chuẩn (Desktop):** `max-w-7xl` (1280px), căn giữa màn hình với `mx-auto`.
- **Khoảng cách đệm biên (Padding):**
  - Mobile (`< 768px`): `px-4 pt-20 pb-12`
  - Tablet/Desktop (`≥ 768px`): `px-8 pt-24 pb-16`
- **Màu nền tổng thể:** Porcelain Canvas `#f8fafc` (Slate 50), độ bão hòa thấp tạo cảm giác trong trẻo, kiến trúc tối giản.
- **Cấu trúc chia cột nội dung chính:**
  - Lưới CSS Grid 12 cột: `grid grid-cols-1 lg:grid-cols-12 gap-8 items-start`.
  - **Cột trái (Nội dung chi tiết & Trải nghiệm):** `lg:col-span-8` (chiếm 8/12 cột, tương đương ~66.67%).
  - **Cột phải (Bảng đặt phòng dính - Sticky Booking Module):** `lg:col-span-4` (chiếm 4/12 cột, tương đương ~33.33%), cấu hình `sticky top-24`.

---

## 2. Chi Tiết Từng Component & Thông Số Kỹ Thuật

### A. Tiêu Đề & Chỉ Báo Trạng Thái (Suite Breadcrumb & Page Title)
- **Breadcrumb:**
  - Chữ: `text-xs font-medium text-slate-500 uppercase tracking-wider`.
  - Dấu phân cách: `·` hoặc `/` màu `text-slate-300`.
  - Điểm nhấn: `text-[#0071e3] font-semibold` ("Tầng 28 • Tháp Apex").
- **Huy hiệu Tình trạng phòng khẩn cấp (Urgency Status Badge):**
  - Lớp vỏ: `inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/60 shadow-xs`.
  - Đèn tín hiệu nhịp thở (Pulsing Dot):
    - Vòng sóng mở rộng: `animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75`.
    - Lõi tròn cố định: `relative inline-flex rounded-full h-2 w-2 bg-emerald-500`.
  - Nhãn trạng thái: `text-xs font-semibold text-emerald-800 tracking-tight` ("Chỉ còn 2 phòng").
- **Huy hiệu Đánh giá sao (Rating Badge):**
  - `bg-white border border-slate-200/80 px-3 py-1 rounded-full shadow-xs text-xs font-medium text-slate-700`.
  - Biểu tượng ngôi sao vàng hổ phách: `text-amber-500 font-variation-settings: 'FILL' 1`.
- **Tiêu đề Suite chính:**
  - Cỡ chữ: `text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight text-slate-900`.
  - Typography: `letter-spacing: -0.025em`, chống tràn từ chữ đơn độc (`text-wrap: balance`).
  - Huy hiệu phân hạng đi kèm: `px-3 py-1 rounded-full bg-[#0071e3]/10 text-[#0071e3] text-xs font-semibold` ("Tổng Thống Phổ Quát").

---

### B. Lưới Thư Viện Ảnh Kiến Trúc (Crisp Architectural Gallery Grid)
- **Cấu trúc Lưới:** `grid grid-cols-1 lg:grid-cols-12 gap-3.5 mb-8`.
- **Khối Hero chính (Cột trái):**
  - Chiều rộng/cao: `lg:col-span-7 h-[360px] md:h-[480px] rounded-2xl overflow-hidden relative group border border-slate-200/80 shadow-sm`.
  - Chuyển động phóng nhẹ: `group-hover:scale-105 transition-transform duration-700 ease-out`.
  - Lớp phủ gradient tương phản (Scrim): `bg-gradient-to-t from-slate-900/60 via-transparent to-transparent`.
  - Nút Panorama 360° góc dưới trái: `px-3 py-1.5 rounded-full bg-white/90 backdrop-blur-md shadow-xs border border-white/40 text-xs font-semibold text-slate-800`.
  - Nút Mở toàn bộ 28 Ảnh góc dưới phải:
    - Kích thước: `px-4 py-2 rounded-full bg-white/90 hover:bg-white text-slate-900 text-xs font-semibold backdrop-blur-md shadow-sm`.
    - Chuyển động nhấn: `active:scale-95 transition-all duration-150`.
- **4 Khối ảnh phụ (Cột phải):**
  - Chiều rộng/cao: `lg:col-span-5 grid grid-cols-2 gap-3.5 h-[360px] md:h-[480px]`.
  - Từng ô ảnh: `rounded-2xl overflow-hidden relative group border border-slate-200/80 shadow-xs`.
  - Nhãn mác kính mờ neo góc dưới: `px-2.5 py-1 rounded-full bg-white/90 backdrop-blur-md text-[11px] font-semibold text-slate-900 shadow-xs`.

---

### C. 4 Thẻ Đo Lường Nhanh (Metric Spec Cards)
Lưới 4 cột: `grid grid-cols-2 sm:grid-cols-4 gap-3`.
- **Quy chuẩn thẻ đơn:**
  - Vỏ: `p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3`.
  - Hộp biểu tượng: `w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-[#0071e3] shrink-0`.
  - Chỉ số chính: `text-base font-bold text-slate-900 leading-tight tabular-nums`.
  - Mô tả phụ: `text-[11px] text-slate-400 font-medium`.
- **Nội dung 4 thông số:**
  1. `142 m²` — Diện tích suite
  2. `3 Khách` — Sức chứa tối đa
  3. `King 2.2m` — Đệm mây Savoir
  4. `360° Vịnh` — Tầm nhìn đỉnh tháp

---

### D. Lưới Tiện Nghi Thượng Đỉnh 6 Sao (Summit Amenities)
- **Vỏ khung ngoài:** `rounded-2xl bg-white/90 backdrop-blur-md border border-slate-200/80 p-6 shadow-xs`.
- **Lưới ô chức năng:** `grid grid-cols-2 sm:grid-cols-4 gap-3`.
- **Chi tiết thẻ tiện nghi:**
  - Lớp nền thẻ: `p-3.5 rounded-xl bg-slate-50 border border-slate-200/50 flex flex-col items-center justify-center text-center transition-all duration-200`.
  - Vòng tròn Icon: `w-9 h-9 rounded-full bg-white flex items-center justify-center text-slate-700 shadow-xs transition-colors`.
  - Tương tác Hover:
    - `hover:border-[#0071e3]/30 hover:bg-white hover:shadow-sm`
    - Icon nền chuyển sang `group-hover:bg-[#0071e3]/10 group-hover:text-[#0071e3]`.
  - Tên tiện nghi: `text-xs font-semibold text-slate-800`.
  - Chú thích cao cấp: `text-[10px] text-slate-400 mt-0.5`.

---

### E. Khối Đánh Giá Thực Tế (Blockchain Verified Reviews)
- **Lớp bảo chứng:** Huy hiệu xanh ngọc `text-emerald-600 text-xs font-medium flex items-center gap-1` ("Xác minh Blockchain").
- **Thẻ đánh giá kép:** `grid grid-cols-1 md:grid-cols-2 gap-3.5`.
  - Vỏ: `p-4 rounded-xl bg-slate-50 border border-slate-200/50 flex flex-col justify-between gap-3`.
  - Số sao: 5 sao vàng xếp hàng ngang `text-amber-500 text-[16px]`.
  - Đoạn trích cảm nhận: `text-xs text-slate-700 leading-relaxed font-normal`.
  - Tác giả: Avatar chữ hoa `w-6 h-6 rounded-full bg-[#0071e3]/10 text-[#0071e3] text-[10px] font-bold` + Tên người lưu trú.

---

### F. Khối Vị Trí Địa Lý (Geographical Context Card)
- **Hình ảnh vệ tinh / bản đồ phối cảnh:** Chiều cao cố định `h-44 w-full bg-cover bg-center` với lớp lọc sáng dịu `bg-slate-900/20 backdrop-blur-[0.5px]`.
- **Thanh thông tin bên dưới:**
  - Biểu tượng ghim: `w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[#0071e3]`.
  - Tên địa danh: "Bán đảo Sơn Trà, Đà Nẵng — 25 phút từ sân bay quốc tế".
  - Nút bấm "Chỉ đường": `px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200/80 text-xs font-semibold text-slate-700 transition-colors`.

---

### G. Khối Đặt Phòng Dính Apple (Sticky Booking Module)
Khung tính giá và ra quyết định hành động nhanh cố định khi người dùng cuộn nội dung.

- **Định vị & Kích thước:**
  - Vị trí dính: `sticky top-24`.
  - Vỏ thẻ: `rounded-2xl bg-white/90 backdrop-blur-2xl border border-slate-200/80 p-6 shadow-[0_12px_40px_rgb(0,0,0,0.06)] flex flex-col gap-5`.
- **Khu vực Giá niêm yết:**
  - Giá thực: `text-2xl font-bold tracking-tight text-slate-900 tabular-nums` (`4.850.000₫`).
  - Đơn vị: `text-xs text-slate-500 font-medium` (`/ đêm`).
  - Giá gốc bị gạch ngang: `text-xs text-slate-400 line-through tabular-nums` (`5.700.000₫`).
- **Thanh chọn ngày kiểu Phân đoạn macOS (Segmented Date Picker Pill):**
  - Khay ngoài: `rounded-xl bg-slate-50 border border-slate-200/70 p-1 flex flex-col gap-1`.
  - 2 Ô ngày Nhận/Trả: `p-2.5 rounded-lg bg-white shadow-xs flex flex-col`.
  - Hàng tổng thời gian: `flex items-center justify-between px-3 py-1.5 text-xs text-slate-500` ("3 đêm liên tiếp").
- **Bộ tăng giảm số lượng khách (Interactive Guest Steppers):**
  - Vỏ khay: `rounded-xl bg-slate-50 border border-slate-200/70 p-3.5 flex flex-col gap-3`.
  - Cụm điều khiển hình viên thuốc: `bg-white rounded-full p-1 border border-slate-200/80 shadow-xs flex items-center gap-2`.
  - Nút tròn `+` và `-`:
    - Kích thước: `w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-colors active:scale-95`.
    - Trạng thái vô hiệu hóa: `disabled:opacity-40 cursor-not-allowed`.
  - Hiển thị số lượng: `text-xs font-bold text-slate-900 w-4 text-center tabular-nums`.
- **Bảng phân tích chi phí minh bạch (Itemized Breakdown):**
  - Giá 3 đêm: `14.550.000₫`
  - Phí dịch vụ & Bảo chứng (5%): `727.500₫`
  - Thuế GTGT (8%): `1.164.000₫`
  - Hộp tổng cộng: `p-3 rounded-xl bg-[#0071e3]/5 border border-[#0071e3]/10 flex justify-between items-center`.
  - Số tiền tổng thanh toán: `text-base font-bold text-[#0071e3] tabular-nums` (`16.441.500₫`).
- **Nút hành động chính (Primary Apple Blue Action Button):**
  - Màu nền: `#0071e3` (Apple Vibrant Blue), hover lên `#0077ed`, khi bấm giảm nhẹ `active:scale-[0.99]`.
  - Kích thước: `w-full py-3.5 px-6 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 text-white`.
  - Bóng đổ phát sáng: `shadow-[0_4px_16px_rgba(0,113,227,0.3)]`.
  - Chuyển tiếp mượt: `transition-all duration-200 ease-out`.
- **Huy hiệu bảo chứng phía dưới:**
  - Miễn phí hủy trong 48 giờ (`text-emerald-700 font-semibold text-xs`).
  - Bảo mật 256-bit • Apple Pay sẵn sàng (`text-slate-400 text-[11px]`).

---

## 3. Bảng Tổng Hợp Chuyển Động & Hiệu Ứng (Animations & Transitions)

| Tên thành phần | Sự kiện kích hoạt | Thuộc tính CSS biến đổi | Thời lượng & Đường cong | Mô tả hiệu ứng |
| :--- | :--- | :--- | :--- | :--- |
| **Ảnh Hero Suite** | Hover chuột vào ảnh | `transform: scale(1.05)` | `700ms cubic-bezier(0.16, 1, 0.3, 1)` | Phóng to chiều sâu kiến trúc mượt mà |
| **Đèn LED khẩn cấp** | Luôn chạy | `opacity: 0.75 -> 0`, `transform: scale(2)` | `animate-ping 1000ms cubic-bezier(0, 0, 0.2, 1) infinite` | Nhịp thở nhắc nhở giữ chỗ tức thì |
| **Nút bấm Đặt ngay** | Bấm chuột (Active) | `transform: scale(0.99)` | `150ms ease-out` | Cảm giác lún phím xúc giác Haptic |
| **Nút Stepper (+/-)** | Bấm chuột (Active) | `transform: scale(0.95)` | `100ms ease-out` | Nhấn lún tròn vi mô chuẩn iOS |
| **Thẻ Tiện nghi** | Rê chuột (Hover) | `border-color`, `background-color`, `box-shadow` | `200ms ease-in-out` | Đổi nền sáng trắng, viền xanh nhạt, nâng shadow |
| **Modal Thư viện 28 ảnh**| Click "28 Ảnh" | `opacity: 0 -> 1`, `transform: scale(0.95 -> 1)` | `200ms cubic-bezier(0.16, 1, 0.3, 1)` | Phóng nở trung tâm mờ nền phim điện ảnh |
