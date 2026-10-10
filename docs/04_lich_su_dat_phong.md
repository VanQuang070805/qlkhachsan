# Thiết Kế Chi Tiết: Màn Hình Lịch Sử Đặt Phòng (Booking History)
**Trang:** `Lịch Sử` — Kỳ Nghỉ Của Quý Khách  
**Hệ thống thiết kế:** Cupertino Luxury Hospitality (Apple Human Interface Guidelines)  
**Tập tin nguồn:** `src/component/BookingHistoryView.tsx`, `src/component/AppleWalletModal.tsx`, `src/component/CheckInQRModal.tsx`, `src/component/InvoiceModal.tsx`, `src/component/RefundDetailModal.tsx`
**Ngoài phạm vi:** Global header/navigation bar và footer.

---

## 1. Bố Cục Tổng Thể & Hệ Thống Lưới (Layout & Grid System)

- **Độ rộng khung chứa:** `max-w-7xl` (1280px), căn giữa `mx-auto`.
- **Khoảng cách đỉnh trang:** `pt-24 pb-16 px-4 md:px-8`.
- **Mô hình giao diện:** Danh sách thẻ ngang phân lớp (*Horizontal Cards Stack*) kết hợp bảng lọc phân đoạn macOS, đem lại trải nghiệm quản lý lưu trú chuẩn máy trạm chuyên nghiệp.

---

## 2. Chi Tiết Từng Component & Thông Số Kỹ Thuật

### A. Tiêu Đề Kho Lưu Trữ & Bộ Điều Khiển Đầu Trang (Top Controls)
- **Breadcrumb vị trí:**
  - Chấm tròn xanh: `w-1.5 h-1.5 rounded-full bg-[#0071e3]`.
  - Nhãn phân cấp: `text-xs font-semibold uppercase tracking-wider text-slate-400` ("KHO LƯU TRỮ / LỊCH SỬ ĐẶT PHÒNG").
- **Tiêu đề trang lớn:** `text-2xl md:text-3xl font-bold text-slate-900 tracking-tight` ("Kỳ Nghỉ Của Quý Khách").
- **Hộp tìm kiếm nhanh (Search Box với phím tắt ⌘K):**
  - Khung nhập: `h-10 pl-9 pr-10 rounded-full bg-white border border-slate-200/90 text-xs shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all w-56 md:w-64`.
  - Biểu tượng kính lúp: `material-symbols-outlined text-[18px] text-slate-400 absolute left-3.5`.
  - Phím tắt macOS: `absolute right-3 text-[10px] text-slate-400 px-1.5 py-0.5 rounded bg-slate-100 font-mono` ("⌘K").
  - Tính năng tìm kiếm: Lọc thời gian thực theo mã phòng (`#AETH-8921`), tên phòng hoặc địa danh khu nghỉ dưỡng.
- **Nút Xuất PDF (Export Action Button):**
  - Kích thước: `h-10 px-4 rounded-full bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 text-xs font-medium flex items-center gap-1.5 shadow-sm transition-all`.
  - Biểu tượng: `download` màu xanh Apple `#0071e3`.

---

### B. Thanh Phân Đoạn Trạng Thái (Segmented Control Bar)
Khay phân đoạn màu xám bạc `bg-slate-200/60 p-1 rounded-2xl flex items-center justify-between`.

- **4 Phân đoạn trạng thái:**
  1. **Tất cả:** Huy hiệu đếm số `4`.
  2. **Sắp tới (Active mặc định):**
     - Đèn tín hiệu xanh lá nhịp thở: `w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse`.
     - Huy hiệu đếm: `px-1.5 py-0.2 rounded-full bg-blue-50 text-[11px] font-semibold text-[#0071e3]` (`1`).
     - Phong cách: `bg-white text-[#0071e3] shadow-sm font-semibold`.
  3. **Hoàn thành:** Huy hiệu đếm số `2`.
  4. **Đã hủy:** Huy hiệu đếm số `1`.
- **Chỉ số tích lũy cá nhân bên phải (Loyalty Metric Ticker):**
  - Tổng đêm đã ở: `14 đêm lưu trú` (Icon `hotel`).
  - Điểm thưởng Aetheria Privilege: `+82.400 PTS` màu xanh ngọc (Icon `stars`).

---

### C. Ngang Xếp Chồng 3 Thẻ Phòng Điển Hình (Horizontal Cards Stack)

#### 1. Thẻ Sắp Tới: #AETH-8921 (Grand Ocean Panorama Suite)
- **Vỏ thẻ:** `bg-white border border-slate-200/90 shadow-sm hover:shadow-md rounded-2xl p-5 md:p-6 transition-all flex flex-col lg:flex-row items-stretch lg:items-center gap-5`.
- **Ảnh thu nhỏ (Cột trái):**
  - Kích thước: `w-full lg:w-64 h-44 rounded-xl overflow-hidden shrink-0 relative group`.
  - Nhãn tầng: `absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-md text-[11px] font-semibold text-slate-800 shadow-xs flex items-center gap-1` (Icon `king_bed` + "Tầng 18").
- **Khối thông tin chính (Cột giữa):**
  - Mã đặt phòng: Khối font-mono `px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-mono font-medium` (`#AETH-8921`).
  - Trạng thái sẵn sàng: `px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200/60 text-emerald-700 text-[11px] font-semibold flex items-center gap-1.5` (Đèn xanh nhấp nháy + "Sẵn sàng check-in").
  - Địa điểm: `Aetheria Phú Quốc`.
  - Tiêu đề Suite: `text-lg md:text-xl font-bold text-slate-900 tracking-tight`.
  - Dải thông số phụ:
    - Ngày lưu trú: `28 Th10 — 31 Th10 (3 đêm)`
    - Sức chứa: `2 Người lớn`
    - Dịch vụ đi kèm: Huy hiệu xanh ngọc `Xe đưa đón Maybach`
- **Khối Tài chính & 2 Nút Hành động macOS (Cột phải):**
  - Tình trạng thanh toán: `Đã thanh toán` (`16.441.500₫`).
  - **Nút "Wallet" (Màu đen Apple):**
    - Kích thước: `h-9 px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-all active:scale-95`.
    - Biểu tượng quả táo Apple Wallet Pass vector.
    - Hành động: Mở Modal thẻ phòng Apple Wallet mô phỏng chìa khóa số NFC.
  - **Nút "Check-in" (Màu xanh Apple):**
    - Kích thước: `h-9 px-3.5 rounded-xl bg-[#0071e3] hover:bg-[#0077ed] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-all active:scale-95`.
    - Hành động: Mở Modal mã QR điện tử check-in tự động tại thang máy / sảnh VIP.

---

#### 2. Thẻ Đã Hoàn Thành: #AETH-6430 (Deluxe Ocean Breeze Room)
- **Thông tin:** `Phòng 402` • `Aetheria Cam Ranh`.
- **Huy hiệu hoàn thành:** Icon tích tròn xanh `check_circle` màu ngọc lục bảo.
- **Đặc quyền kèm theo:** Huy hiệu đánh giá `Đánh giá 5/5` (sao vàng) + `Spa & Dinner`.
- **Giá trị chi phí:** `11.890.000₫`.
- **2 Nút Hành động:**
  - **Nút "Hóa đơn":** Nền xám viền mỏng `bg-slate-100 hover:bg-slate-200 text-slate-700` — Mở Modal Hóa đơn điện tử VAT có ký số VNPT-CA Token.
  - **Nút "Đặt lại":** Nền xanh nhạt viền xanh `bg-blue-50 hover:bg-blue-100 text-[#0071e3]` — Tự động nạp cấu hình phòng vào luồng Đặt phòng mới.

---

#### 3. Thẻ Đã Hủy: #AETH-5512 (Penthouse Sky View)
- **Hiệu ứng thị giác:** Lớp ảnh phủ xám điện ảnh `filter grayscale-[40%]`, độ mờ nhẹ toàn thẻ `opacity-90 hover:opacity-100`.
- **Huy hiệu tình trạng:** `bg-rose-50 border border-rose-200/60 text-rose-700 text-[11px] font-semibold` ("Đã hủy • Hoàn tiền 100%").
- **Kênh hoàn tiền:** Thẻ tín dụng `Mastercard •••• 8829`.
- **Số tiền đã hoàn:** `Đã hoàn: 35.000.000₫` có tích xanh.
- **Nút Hành động:** "Chi tiết hoàn tiền" — Mở Modal biểu đồ thời gian hoàn tiền và mã chuẩn chi ngân hàng ARN.

---

### D. Thẻ Hội Viên Tối Thượng (Titanium Elite Ambassador VIP Card)
Thiết kế tối giản sang trọng phong cách Apple Card đặt ở đáy danh sách.

- **Vỏ thẻ:** `p-5 md:p-6 rounded-2xl bg-white border border-slate-200/90 shadow-sm flex flex-col md:flex-row items-center justify-between gap-5`.
- **Khối Icon Đặc quyền:** Hộp tròn bo cong `w-12 h-12 rounded-2xl bg-[#0071e3] text-white flex items-center justify-center shadow-md shadow-blue-500/20`.
- **Nội dung hạng:**
  - Tên hạng: `text-base font-bold text-slate-900` ("Titanium Elite Ambassador").
  - Huy hiệu danh dự: `px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider` ("TRỌN ĐỜI").
  - Mô tả: "Nâng hạng phòng tự động, bữa sáng độc quyền và Quản gia Concierge 24/7."
- **Cụm 2 nút thao tác bên phải:**
  - Nút **"Concierge VIP":** Mở khay trò chuyện Quản gia Mai Trâm 24/7.
  - Nút **"Đặt Kỳ Nghỉ Mới":** Màu xanh Apple `#0071e3` kèm icon cộng `add` — Điều hướng tức thì sang kho phòng để đặt kỳ nghỉ tiếp theo.

---

### E. Quy Chuẩn Kỹ Thuật 4 Modal Tương Tác Kèm Theo

1. **Apple Wallet Pass Modal (`AppleWalletModal.tsx`):**
   - Thiết kế chuẩn PassKit của hệ điều hành iOS: Gradient đen than chì Obsidian `from-slate-800 via-slate-850 to-slate-900`.
   - Vòng hào quang xanh Apple tỏa sáng góc trên.
   - Nhãn điện tử `NFC KEY ACTIVE` cùng mã vạch quang học và chỉ dẫn *"CHẠM IPHONE VÀO CỬA ĐỂ MỞ KHÓA"*.
   - Nút *"Thêm thẻ phòng vào Apple Wallet"* với phản hồi xúc giác đổi màu xanh hoàn tất.
2. **Check-in QR Modal (`CheckInQRModal.tsx`):**
   - Hộp trắng bo góc `rounded-[32px] max-w-sm`.
   - Mã QR tương phản cao kích thước `w-48 h-48` có mắt nhận diện chuẩn Napas và vi bằng nhận phòng 14:00.
3. **VAT Invoice Modal (`InvoiceModal.tsx`):**
   - Hóa đơn điện tử đầy đủ pháp lý: Ký hiệu `1C25TAE`, Số HĐ `0008492`, thông tin bên bán Aetheria, bên mua Công ty CP Kiến Trúc Skyline (MST 0315894210).
   - Tách dòng chi tiết: Tiền phòng, Spa & Dinner, thuế suất GTGT 8% và chữ ký số Token VNPT-CA. Nút in trực tiếp ra tệp PDF.
4. **Refund Details Modal (`RefundDetailModal.tsx`):**
   - Minh bạch dòng tiền hoàn lại: Khung số tiền lớn `35.000.000₫`, mã giao dịch hoàn `#REF-99214`, mã ARN ngân hàng 23 chữ số chuẩn quốc tế.

---

## 3. Bảng Tổng Hợp Chuyển Động & Hiệu Ứng (Animations & Transitions)

| Tên thành phần | Sự kiện kích hoạt | Thuộc tính CSS biến đổi | Thời lượng & Đường cong | Mô tả hiệu ứng |
| :--- | :--- | :--- | :--- | :--- |
| **Thẻ phòng ngang** | Hover chuột vào thẻ | `box-shadow`, `border-color` | `200ms ease-out` | Bóng đổ nở rộng nhẹ, viền xám đậm nét |
| **Ảnh bìa thẻ** | Hover chuột vào thẻ | `transform: scale(1.05)` | `500ms ease-out` | Cảnh sắc mở rộng nhẹ bên trong khung |
| **Bộ lọc Phân đoạn** | Click chọn trạng thái | `background-color`, `box-shadow`, `color` | `150ms ease-in-out` | Con trỏ chuyển đổi êm mượt chuẩn macOS Tabs |
| **Nút Wallet** | Hover & Click | `scale(1) -> scale(0.95)`, icon scale | `150ms ease-out` | Biểu tượng Apple to lên nhẹ, nút lún khi nhấn |
| **Modal Hóa đơn/Wallet**| Mở thẻ bật | `opacity: 0 -> 1`, `zoom-in-95` | `200ms cubic-bezier(0.16, 1, 0.3, 1)` | Lớp nền mờ kính 70%, hộp modal trồi lên |
