# Thiết Kế Chi Tiết: Modal, Drawer Và Trạng Thái Phụ

**Phạm vi:** 8 giao diện nổi được gọi từ các màn hình chính.  
**Tập tin nguồn:** `src/component/GalleryModal.tsx`, `src/component/BookingSuccessModal.tsx`, `src/component/AppleWalletModal.tsx`, `src/component/CheckInQRModal.tsx`, `src/component/InvoiceModal.tsx`, `src/component/RefundDetailModal.tsx`, `src/component/ConciergeDrawer.tsx`, `src/component/AccountModal.tsx`  
**Ngoài phạm vi:** Global header/navigation bar và footer.

---

## 1. Quy Ước Chung

- Các modal phủ toàn viewport bằng `fixed inset-0 z-50`; lớp nền dùng `slate-950` với độ mờ 70–85% và `backdrop-blur-xl/2xl`.
- Modal sáng dùng nền trắng, viền `slate-200`, bóng `shadow-2xl`; modal media/Wallet dùng nền `slate-900` và viền `slate-700`.
- Chuyển động mở tiêu chuẩn: `fade-in 200ms`; hộp xác nhận có thêm `zoom-in-95 200ms`; drawer trượt từ phải trong `300ms`.
- Nút đóng là nút tròn 32–36px ở góc phải. Màu hành động chính là Apple Blue `#0071e3`; trạng thái thành công dùng Emerald.
- Các hộp dài giới hạn `max-h-[90vh]` và cuộn dọc; mobile dùng `p-4`, chiều rộng `w-full`.

---

## 2. Thư Viện Ảnh Toàn Màn Hình

**Component:** `GalleryModal`  
**Kích thước:** `max-w-5xl`, cao `88vh`, bo góc `rounded-3xl`, nền `slate-900`.

### Bố cục

1. **Thông tin bộ ảnh:** chấm phát sáng xanh, tên “Grand Ocean Panorama Suite • Thư viện ảnh”, chỉ số ảnh hiện tại/tổng số và nút đóng.
2. **Bộ lọc dạng pill cuộn ngang:** Tất cả, Toàn cảnh, Phòng ngủ, Thư giãn, Không gian sống, Ẩm thực. Mục chọn có nền xanh và chữ trắng; đổi danh mục đưa chỉ số ảnh về 0.
3. **Sân khấu ảnh:** nền đen, ảnh `object-contain`, căn giữa và giữ nguyên tỷ lệ. Hai nút tròn trái/phải cho phép duyệt vòng lặp qua danh sách.
4. **Caption nổi:** hộp kính đen ở đáy ảnh, hiển thị tên ảnh và badge danh mục.
5. **Dải thumbnail:** ảnh 64×48px cuộn ngang; ảnh đang chọn có viền xanh, phóng nhẹ và bóng; ảnh khác mờ 60%, rõ lên khi hover.

### Tương tác

- Chọn thumbnail để chuyển trực tiếp đến ảnh tương ứng.
- Nút trước ở ảnh đầu chuyển tới ảnh cuối; nút sau ở ảnh cuối quay lại ảnh đầu.
- Khi lọc, nguồn ảnh và bộ đếm chỉ phản ánh danh mục đang chọn.

---

## 3. Xác Nhận Đặt Phòng Thành Công

**Component:** `BookingSuccessModal`  
**Kích thước:** `max-w-md`, bo góc 32px, nền trắng, căn giữa.

### Thành phần

- Biểu tượng check 64px trên nền Emerald nhạt.
- Nhãn “Đặt Phòng Thành Công”, tên `Grand Ocean Panorama Suite` và thông báo xác nhận đã mã hóa, gửi qua email.
- Biên nhận nền `slate-50` gồm mã `#AETH-8921`, thời gian `28 Th10 — 31 Th10 (3 đêm)`, khách `Nguyễn Hoàng Minh`, xe đón `Mercedes-Maybach S680`, tổng đã thanh toán `16.441.500₫` hoặc giá quy đổi theo tiền tệ đang chọn.
- Nút đen toàn chiều rộng “Thêm thẻ phòng vào Apple Wallet”.
- Hai nút phụ chia đôi: “Mã Check-in” nền xanh nhạt và “Xem Lịch Sử” nền xám.

### Trạng thái và chuyển tiếp

- Khi modal mở, phát 80 hạt confetti với các màu `#0071e3`, `#10b981`, `#38bdf8`, `#f59e0b`.
- Mỗi CTA đóng modal trước rồi mở giao diện đích tương ứng.

---

## 4. Thẻ Phòng Apple Wallet

**Component:** `AppleWalletModal`  
**Kích thước:** `max-w-sm`, bo góc 32px, nền `slate-900`.

### Thành phần

- Nhãn Apple Wallet Pass và nút đóng nền than chì.
- Pass dùng gradient đen, viền mờ và quầng xanh ở góc phải.
- Hàng thương hiệu có logo AETHERIA và trạng thái `NFC KEY ACTIVE` màu Emerald.
- Thông tin phòng: tên phòng, ngày lưu trú, tầng 18/phòng 1801.
- Lưới 2×2: chủ sở hữu, mã đặt phòng, giờ check-in/Maybach đón, hạng Titanium Elite.
- Barcode mô phỏng nền trắng và chỉ dẫn “CHẠM IPHONE VÀO CỬA ĐỂ MỞ KHÓA”.
- Nút thêm Wallet nền trắng; sau khi bấm đổi sang Emerald với nhãn thành công trong 3,5 giây rồi trở về trạng thái ban đầu.

---

## 5. Mã Check-in Điện Tử

**Component:** `CheckInQRModal`  
**Kích thước:** `max-w-sm`, bo góc 32px, nền trắng, căn giữa.

### Thành phần

- Icon QR scanner trong ô 48px nền xanh nhạt.
- Nhãn “Thẻ Check-in Điện Tử”, tên phòng và mã đặt phòng font monospace màu xanh.
- Khay QR nền `slate-50`; mã QR SVG 192×192px có ba marker góc màu than và tâm xanh.
- Badge hiệu lực màu Emerald: “Check-in tự động 14:00 (28/10)” với chấm pulse.
- Hướng dẫn quét tại Quầy Lễ Tân VIP hoặc máy quét thang máy Tháp Apex.
- Nút “Xong” màu xanh đóng modal.

---

## 6. Hóa Đơn Điện Tử VAT

**Component:** `InvoiceModal`  
**Kích thước:** `max-w-lg`, `max-h-[90vh]`, cuộn dọc, bo góc 24px.

### Thành phần

- Tiêu đề “HÓA ĐƠN GIÁ TRỊ GIA TĂNG”, ký hiệu `1C25TAE`, số `0008492` và badge `ĐÃ KÝ SỐ`.
- Khối hai cột thông tin:
  - Bên bán: Công ty Cổ phần Nghỉ dưỡng Aetheria, MST `0109923847`, Bãi Dài, Cam Ranh.
  - Bên mua: Công ty CP Kiến Trúc Skyline, MST `0315894210`, khách lưu trú Nguyễn Hoàng Minh.
- Bảng dịch vụ bốn cột `Khoản mục / SL / Đơn giá / Thành tiền`:
  - Deluxe Ocean Breeze Room, 3 đêm, thành tiền `10.076.271₫`.
  - Signature Spa & Dinner, số lượng 1, thành tiền `1.000.000₫`.
- Tổng dịch vụ `11.076.271₫`, thuế `813.729₫`, tổng đã gồm thuế `11.890.000₫`.
- Khối chữ ký số nền xanh nhạt: `VNPT-CA Token`, thời gian ký `15/08/2025 10:14:22 GMT+7`.
- Nút “In / Lưu PDF” gọi chức năng in của trình duyệt.

---

## 7. Chi Tiết Hoàn Tiền

**Component:** `RefundDetailModal`  
**Kích thước:** `max-w-md`, bo góc 24px, nền trắng.

### Thành phần

- Icon đổi trả màu Emerald, tiêu đề “Chi Tiết Hoàn Tiền 100%”, mã `#REF-99214`.
- Thẻ số tiền nổi bật: `35.000.000₫`, badge `Hoàn tất`.
- Timeline ba bước bằng chấm Emerald:
  1. Yêu cầu hủy được duyệt theo chính sách miễn phí trong 48 giờ.
  2. Hoàn về Mastercard kết thúc `•••• 8829`.
  3. Mã ARN `74582910482910398471029`.
- Khung lưu ý cho biết tiền hoàn xuất hiện trong sao kê tháng tiếp theo.
- Nút đen “Đóng”.

---

## 8. Concierge Quản Gia Riêng

**Component:** `ConciergeDrawer`  
**Kiểu hiển thị:** drawer phải toàn chiều cao, `max-w-md`, nền phủ đen 40%.

### Bố cục

1. **Thông tin quản gia:** avatar `MT`, chấm online, tên “Quản gia riêng Mai Trâm”, badge “Trực tuyến”, dòng “Concierge Hoàng Gia 24/7 • Aetheria Sanctuary”.
2. **Lịch sử chat:** nền `#f8fafc`; tin khách căn phải, nền xanh/chữ trắng; tin quản gia căn trái, nền trắng/viền xám; mỗi tin có thời gian.
3. **Gợi ý nhanh cuộn ngang:** xe Maybach, hoa & Champagne, bữa sáng nổi 8:00, bữa tối hoàng hôn.
4. **Thanh nhập:** ô nhập nền xám, focus đổi nền trắng và ring xanh; nút gửi vuông 44px màu xanh.

### Tương tác

- Không gửi chuỗi rỗng.
- Tin người dùng xuất hiện ngay; quản gia phản hồi sau 800ms.
- Có phản hồi riêng khi nội dung liên quan xe/Maybach/sân bay hoặc bữa sáng/hồ bơi; trường hợp khác dùng phản hồi xác nhận chung.
- Drawer trượt vào từ phải trong 300ms.

---

## 9. Tài Khoản Hội Viên

**Component:** `AccountModal`  
**Kích thước:** `max-w-lg`, `max-h-[90vh]`, cuộn dọc, bo góc 24px.

### Thành phần

- Avatar gradient xanh chữ `HM`, tên Nguyễn Hoàng Minh, badge `Titanium Elite`, email và số điện thoại.
- Thẻ điểm nền `slate-900`: `82.400 PTS`, mô tả đủ điều kiện đổi hai đêm Suite, icon huy chương màu vàng.
- Lưới đặc quyền 1 cột trên mobile, 2 cột từ `sm`:
  - Nâng hạng Suite tự động.
  - Buffet sáng & Champagne đôi.
  - Quản gia Concierge 24/7.
  - Đưa đón phi cơ/Maybach VIP.
- Hai CTA ngang: “Xem Apple Wallet” nền xám và “Lịch sử đặt phòng” nền xanh. Mỗi CTA đóng modal rồi điều hướng/mở giao diện tương ứng.

---

## 10. Ma Trận Mở Giao Diện

| Giao diện | Điểm mở chính | Hành động kế tiếp |
| :--- | :--- | :--- |
| Gallery | Lưới ảnh trang chi tiết phòng | Lọc, duyệt, chọn ảnh |
| Booking Success | Hoàn tất thanh toán | Wallet, QR, lịch sử |
| Apple Wallet | Thành công, lịch sử, tài khoản | Thêm pass |
| Check-in QR | Thành công, booking sắp tới | Quét/check-in |
| Invoice | Booking đã hoàn thành | In hoặc lưu PDF |
| Refund Detail | Booking đã hủy | Đối soát ARN |
| Concierge | Thẻ quản gia và CTA VIP | Chat/gửi yêu cầu |
| Account | Avatar tài khoản | Wallet hoặc lịch sử |

