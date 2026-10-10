# Thiết Kế Chi Tiết: Màn Hình Đặt Phòng - Bước 1 (Thông Tin Khách)
**Trang:** `Đặt Phòng` — Bước 1: Thông Tin Khách Lưu Trú  
**Hệ thống thiết kế:** Cupertino Luxury Hospitality (Apple Human Interface Guidelines)  
**Tập tin nguồn:** `src/component/BookingStep1View.tsx`, `src/component/ConciergeDrawer.tsx`
**Ngoài phạm vi:** Global header/navigation bar và footer.

---

## 1. Bố Cục Tổng Thể & Hệ Thống Lưới (Layout & Grid System)

- **Độ rộng khung chứa:** `max-w-6xl` (1152px), căn giữa cân đối `mx-auto`.
- **Khoảng cách đỉnh trang:** `pt-20 pb-16` (đảm bảo không bị thanh dock đỉnh che khuất).
- **Phân bổ 2 cột lưới bất đối xứng:**
  - Lưới CSS Grid 12 cột: `grid grid-cols-1 lg:grid-cols-12 gap-6 items-start`.
  - **Cột trái (Biểu mẫu thông tin người lưu trú):** `lg:col-span-7` (7/12 cột, ~58.33%).
  - **Cột phải (Thẻ tóm tắt đặt phòng & Quản gia):** `lg:col-span-5` (5/12 cột, ~41.67%), cấu hình dính `lg:sticky lg:top-20`.

---

## 2. Chi Tiết Từng Component & Thông Số Kỹ Thuật

### A. Thanh Tiến Trình Kiểu Viên Thuốc (Apple Stepper Capsule)
Đặt chính giữa phía trên biểu mẫu, dẫn dắt tâm lý người dùng qua 2 bước rõ ràng.

- **Vỏ bọc ngoài:**
  - Lớp nền: `inline-flex items-center gap-2 p-1 rounded-full bg-white border border-slate-200 shadow-sm`.
- **Bước 1 (Đang kích hoạt - Active):**
  - Khối màu: `flex items-center gap-2 pl-1.5 pr-3.5 py-1 rounded-full bg-blue-50 text-[#0071e3]`.
  - Số thứ tự: Vòng tròn đặc `w-5 h-5 rounded-full bg-[#0071e3] text-white flex items-center justify-center text-[11px] font-bold`.
  - Tiêu đề bước: `text-xs font-semibold` ("Thông tin khách").
- **Dấu mũi tên chuyển bước:** `text-slate-300 text-xs px-1 select-none` ("→").
- **Bước 2 (Chưa kích hoạt - Inactive):**
  - Màu sắc: `flex items-center gap-2 pl-1.5 pr-3.5 py-1 rounded-full text-slate-400`.
  - Số thứ tự: Vòng tròn xám nhạt `w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-[11px] font-medium`.
  - Tiêu đề bước: `text-xs font-medium` ("Thanh toán").

---

### B. Cột Trái: Thẻ Biểu Mẫu Nhập Liệu (Input Form Container)
Khối thẻ chính bằng sứ trắng bảo bọc toàn bộ form nhập liệu.

- **Đặc tính thẻ:**
  - Vỏ thẻ: `p-6 md:p-7 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col gap-6`.
  - Tiêu đề phụ bảo mật:
    - Biểu tượng khóa: `material-symbols-outlined text-[16px] text-[#0071e3]`.
    - Chữ nhãn: `text-[11px] uppercase tracking-wider font-semibold text-[#0071e3]` ("BẢO MẬT APPLE PAY & TLS 256-BIT").
  - Tiêu đề chính: `text-xl md:text-2xl font-bold text-slate-900 tracking-tight` ("Thông tin người lưu trú").
  - Đường kẻ ranh giới: `border-b border-slate-100 pb-4`.

#### Các ô nhập liệu thành phần:
1. **Họ và tên (Full Name):**
   - Tiêu đề trường: `flex items-center justify-between text-xs font-semibold text-slate-700`.
   - Ghi chú phụ: `text-[11px] font-normal text-slate-400` ("Theo hộ chiếu / CCCD").
   - Hộp nhập liệu:
     - Kích thước: `w-full h-11 pl-10 pr-4 rounded-xl text-slate-900 text-sm`.
     - Màu nền & viền: `bg-slate-50 border border-slate-200`.
     - Biểu tượng bên trái: `absolute left-3.5 text-slate-400 text-[18px] person`.
     - Trạng thái Focus: `focus:outline-none focus:ring-2 focus:ring-[#0071e3]/30 focus:border-[#0071e3] focus:bg-white transition-all`.
2. **Email nhận xác nhận (Confirmation Email):**
   - Biểu tượng mail: `absolute left-3.5 text-slate-400 text-[18px] mail`.
   - Chiều cao `h-11`, bo góc `rounded-xl`, giá trị mẫu: `hoangminh.arch@icloud.com`.
3. **Số điện thoại (Phone Number với Quốc kỳ Việt Nam):**
   - Khối nhãn mã quốc gia neo cố định:
     - Vị trí: `absolute left-3 flex items-center gap-1 pl-1 pr-1.5 py-0.5 rounded-lg bg-slate-200/80 text-slate-800 text-xs font-medium`.
     - Cờ & Mã: `🇻🇳 +84`.
   - Ô nhập: `pl-20 pr-4 h-11 rounded-xl bg-slate-50 border border-slate-200 text-sm font-mono`.

---

### C. Bộ Điều Khiển Phân Đoạn Giờ Nhận Phòng (Arrival Time Segmented Control)
Thay vì dropdown truyền thống gây mất nhịp, giao diện dùng cụm 3 nút chuyển phân đoạn trực quan.

- **Tiêu đề & Trạng thái chuẩn:**
  - Nhãn trái: `text-xs font-semibold text-slate-700` ("Khung giờ nhận phòng").
  - Nhãn phải: `text-[11px] font-medium text-emerald-600 flex items-center gap-1` (chấm xanh `w-1.5 h-1.5 rounded-full bg-emerald-500` + "Check-in chuẩn: 14:00").
- **Khay phân đoạn:** `grid grid-cols-3 gap-2 p-1 rounded-xl bg-slate-100 border border-slate-200/70`.
- **Cấu trúc 3 nút bấm phân đoạn:**
  - Nút 1: `14:00 - 16:00` (Icon `wb_sunny`) — Khung giờ mặt trời rực rỡ.
  - Nút 2: `16:00 - 18:00` (Icon `wb_twilight`) — Khung giờ hoàng hôn.
  - Nút 3: `Sau 18:00` (Icon `bedtime`) — Khung giờ chiều tối.
- **Trạng thái Active:**
  - `bg-white text-[#0071e3] shadow-sm font-semibold`.
- **Trạng thái Inactive:**
  - `text-slate-600 font-medium hover:text-slate-900 hover:bg-white/60`.
- **Hiệu ứng chuyển đổi:** `transition-all duration-200 ease-out`.

---

### D. Ghi Chú Cho Quản Gia & Công Tắc Chuyển Đổi iOS (Switches)
- **Khu vực Ghi chú (Special Requests):**
  - Ô Textarea: `w-full p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm resize-none focus:bg-white focus:ring-2 focus:ring-[#0071e3]/30 focus:border-[#0071e3]`.
  - Số dòng: `rows={2}`, chiều cao tự nhiên ~76px.
- **Công tắc iOS Switch 1: "Đặt phòng cho người khác"**
  - Thân vỏ công tắc: `relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors duration-200 ease-in-out`.
    - Khi Bật (True): `bg-[#0071e3]`.
    - Khi Tắt (False): `bg-slate-200`.
  - Nút trượt tròn (Thumb): `inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5`.
    - Khi Bật: `translate-x-5`.
    - Khi Tắt: `translate-x-0.5`.
  - Hiệu ứng mở rộng dữ liệu (Expandable Accordion):
    - Khi Bật: Tự động trượt mở trường nhập *Tên người lưu trú chính* và *Số điện thoại người lưu trú* với hiệu ứng `animate-in fade-in duration-200`.
- **Công tắc iOS Switch 2: "Xuất hóa đơn điện tử VAT doanh nghiệp"**
  - Hoạt động tương tự: Khi bật sẽ mở rộng 3 trường thông tin: *Mã số thuế*, *Tên công ty*, *Địa chỉ doanh nghiệp*.

---

### E. Nút Hành Động Chuyển Tiếp (Primary Blue CTA Button)
- **Kích thước & Kiểu dáng:**
  - Chiều cao: `h-12` (48px), chiều rộng toàn phần `w-full`.
  - Bo góc: `rounded-xl` (12px).
  - Màu sắc: `bg-[#0071e3] text-white font-semibold text-sm flex items-center justify-center gap-2`.
  - Tương tác:
    - Hover: `hover:bg-[#0077ed] hover:shadow-md hover:-translate-y-0.5`.
    - Active: `active:bg-[#0062c4] active:translate-y-0`.
    - Chuyển động mượt: `transition-all duration-200 ease-out`.
- **Dòng ghi chú pháp lý:**
  - `text-center text-[11px] text-slate-400 mt-2.5` ("Bảo mật giao dịch theo chuẩn Apple Pay & PCI-DSS Level 1.").

---

### F. Cột Phải: Thẻ Tóm Tắt Đặt Phòng (Compact Summary Card)
Cố định khi cuộn màn hình (`lg:sticky lg:top-20`).

- **Thẻ media phòng:**
  - Khung ảnh: `relative w-full h-44 rounded-xl overflow-hidden group border border-slate-100`.
  - Ảnh phòng: Tỉ lệ chữ nhật bo góc, `group-hover:scale-105 transition-transform duration-500`.
  - Huy hiệu góc trên trái: `px-2.5 py-1 rounded-full bg-white/90 backdrop-blur-md text-[#0071e3] text-[11px] font-bold shadow-xs` ("HẠNG THƯỢNG HẠNG").
  - Huy hiệu góc dưới phải: `px-2 py-0.5 rounded-md bg-slate-900/75 backdrop-blur-md text-white text-[11px] font-medium` ("145 m²").
- **Dòng thông số định lượng:**
  - Dải huy hiệu xám: `28 - 31 Th10`, `3 đêm`, `2 Khách` kèm icon Material màu xanh Apple.
- **Danh sách đặc quyền đính kèm (Included Highlights):**
  - Khay viền xám nhạt: `p-3 rounded-xl bg-slate-50 border border-slate-100 flex flex-col gap-1.5 text-xs text-slate-600`.
  - Biểu tượng tích xanh: `material-symbols-outlined text-emerald-600 text-[16px] check_circle`.
  - 3 đặc quyền độc bản:
    1. Buffet sáng cao cấp tại Terrace Horizon
    2. Đưa đón sân bay VIP chuyên cơ/Mercedes
    3. 60 phút trị liệu Signature Spa đôi
- **Bảng tính tiền thu nhỏ (Price Breakdown):**
  - Tiền phòng (3 đêm): `13.950.000₫`
  - Phí dịch vụ & VAT (15%): `2.491.500₫`
  - Đường phân cách tóc mảnh: `w-full h-px bg-slate-100 my-1`.
  - Tổng thanh toán: `text-xl font-bold text-[#0071e3] tracking-tight` (`16.441.500₫`).
- **Thẻ Quản gia trực tuyến (Concierge Assistance Mini-Card):**
  - Vỏ: `p-2.5 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between`.
  - Vòng tròn hỗ trợ: `w-8 h-8 rounded-full bg-blue-100 text-[#0071e3] flex items-center justify-center`.
  - Đèn tín hiệu: `text-[10px] text-emerald-600 font-medium` ("Đang trực tuyến").
  - Nút "Nhắn tin": `px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-[#0071e3] text-xs font-medium shadow-xs transition-colors`. Khi click sẽ mở khay trò chuyện Quản gia riêng tức thì.

---

## 3. Bảng Tổng Hợp Chuyển Động & Hiệu Ứng (Animations & Transitions)

| Tên thành phần | Sự kiện kích hoạt | Thuộc tính CSS biến đổi | Thời lượng & Đường cong | Mô tả hiệu ứng |
| :--- | :--- | :--- | :--- | :--- |
| **Công tắc iOS Switch** | Click bật/tắt | `background-color`, `transform: translateX` | `200ms ease-in-out` | Nút tròn gạt mượt mà chuẩn Apple Settings |
| **Phân đoạn Giờ đến** | Click chọn tab | `background-color`, `box-shadow`, `color` | `150ms ease-out` | Nền trắng trồi lên đổ bóng nhẹ nhàng |
| **Nút "Tiếp tục"** | Rê chuột (Hover) | `transform: translateY(-2px)`, `box-shadow` | `200ms cubic-bezier(0.16, 1, 0.3, 1)` | Nút nâng bổng nhẹ tạo lực hút bấm |
| **Vùng nhập mở rộng** | Khi bật switch | `opacity: 0 -> 1`, `height / max-height` | `200ms ease-in-out` | Trượt xuất hiện êm ả không làm giật trang |
| **Ô Input Form** | Con trỏ vào (Focus) | `ring-width`, `border-color`, `background-color` | `150ms ease-out` | Viền xanh Apple xuất hiện kèm nền trắng sáng |
