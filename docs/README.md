# AETHERIA Sanctuary — Tài Liệu Thiết Kế Kỹ Thuật (Design System & Component Specs)

Hệ thống tài liệu thiết kế chi tiết cho nền tảng nghỉ dưỡng siêu sang chuẩn 6 sao **AETHERIA Sanctuary**, xây dựng theo ngôn ngữ thiết kế **Cupertino Luxury Hospitality (Apple Human Interface Guidelines)**.

> Phạm vi tài liệu chỉ gồm nội dung màn hình và các lớp tương tác. Global header/navigation bar và footer đã được loại bỏ theo yêu cầu.

---

## 📑 Danh Sách Tài Liệu Theo Từng Trang

1. [**Trang 1: Chi Tiết Phòng (Room Details)**](01_chi_tiet_phong.md)
   - *Lưới ảnh kiến trúc 360°, thẻ thông số, tiện nghi thượng đỉnh 6 sao, đánh giá thực tế Blockchain, bản đồ vị trí và bảng đặt phòng dính tương tác.*
2. [**Trang 2: Đặt Phòng - Bước 1: Thông Tin Khách Lưu Trú**](02_dat_phong_buoc_1.md)
   - *Thanh tiến trình Capsule Stepper, Biểu mẫu chuẩn CCCD/Hộ chiếu, Phân đoạn chọn giờ đến kèm icon thời tiết, Công tắc chuyển đổi iOS Switches (Đặt hộ & Hóa đơn VAT doanh nghiệp), Thẻ tóm tắt đặt phòng và Quản gia riêng.*
3. [**Trang 3: Đặt Phòng - Bước 2: Thanh Toán Bảo Mật & Xác Nhận Thành Công**](03_dat_phong_buoc_2.md)
   - *Bộ đếm ngược giữ phòng (14:59), 5 phương thức thanh toán kiểu Apple Wallet (Apple Pay, Thẻ quốc tế, Chuyển khoản VietQR 24/7, Ví điện tử, Tại quầy), Lưu Apple Keychain, Mô phỏng Face ID sinh trắc học, Pháo hoa Confetti và Modal thành công.*
4. [**Trang 4: Lịch Sử Đặt Phòng (Kỳ Nghỉ Của Quý Khách)**](04_lich_su_dat_phong.md)
   - *Bộ tìm kiếm phím tắt ⌘K, Xuất PDF, Thanh phân đoạn 4 trạng thái, Thẻ phòng xếp lớp ngang (Sắp tới, Hoàn thành, Đã hủy), Thẻ Apple Wallet Pass NFC, Mã Check-in QR, Hóa đơn VAT điện tử, Chi tiết hoàn tiền 100%, Thẻ hội viên Titanium Elite.*
5. [**Giao Diện Phụ: Modal, Drawer & Trạng Thái Hoàn Tất**](05_modal_drawer_va_trang_thai_phu.md)
   - *Thư viện ảnh, xác nhận đặt phòng, Apple Wallet, QR check-in, hóa đơn VAT, hoàn tiền, Concierge và tài khoản hội viên.*

---

## 🎨 Hệ Thống Bảng Màu & Tokens Chuẩn (Design Tokens)

| Token Tên | Mã Màu Hex | Vai Trò Ứng Dụng |
| :--- | :--- | :--- |
| **Primary (Apple Blue)** | `#0071e3` | Nút hành động chính, viền tiêu điểm, thanh tiến trình, liên kết nổi bật |
| **Primary Hover** | `#0077ed` | Trạng thái rê chuột nút chính |
| **Secondary (Emerald)** | `#10b981` / `#059669` | Trạng thái sẵn sàng, tích xanh bảo chứng, huy hiệu còn phòng |
| **Canvas Background** | `#f8fafc` | Nền canvas sứ nhạt thanh khiết, chống chói mắt |
| **Surface (Thẻ Card)** | `#ffffff` | Nền các thẻ nội dung, thẻ đặt phòng, cửa sổ popover |
| **Hairline Border** | `#e2e8f0` / `#cbd5e1` | Đường viền mảnh 1px phân tách các phân khu |
| **Dark Neutral (Graphite)** | `#0f172a` | Tiêu đề lớn, chữ số định lượng tương phản cao |
| **Text Muted** | `#64748b` | Nhãn phụ, đơn vị tính, ghi chú thời gian |

---

## 📐 Thước Đo Kích Thước & Bo Góc (Geometry & Radii)

- **Bo góc chuẩn:**
  - `rounded-xl`: 12px (Ô nhập form, nút hành động, thẻ tiện nghi).
  - `rounded-2xl`: 16px (Khung thẻ card, khối tóm tắt, ảnh phòng).
  - `rounded-3xl` / `rounded-[32px]`: 24px - 32px (Modal nổi, biên lai hóa đơn).
  - `rounded-full`: 9999px (Thanh Dock nổi, viên thuốc phân đoạn, nút tròn stepper).
- **Hệ lưới khoảng cách (Spacing Math):**
  - Đệm trong thẻ: `p-5` đến `p-7` (20px - 28px).
  - Khoảng cách giữa các phần: `gap-6` đến `gap-8` (24px - 32px).

---

## ⏱️ Tiêu Chuẩn Chuyển Động Xúc Giác (Motion & Timing Curves)

- **Tương tác phản hồi tức thời:** $\le 150\text{ms}$ (`ease-out`).
- **Chuyển đổi trạng thái & mở rộng:** $200\text{ms}$ (`cubic-bezier(0.16, 1, 0.3, 1)`).
- **Phóng ảnh kiến trúc:** $500\text{ms} - 700\text{ms}$ (`ease-out`).
- **Pháo hoa Confetti:** $2500\text{ms}$ vật lý trọng lực hạt màu.
