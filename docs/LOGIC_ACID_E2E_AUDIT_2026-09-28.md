# Báo cáo kiểm thử logic E2E và tính nhất quán ACID

## 1. Thông tin kiểm tra

| Mục | Giá trị |
|---|---|
| Dự án | Royal Hotel Management System |
| Ngày kiểm tra | 2026-09-28; bổ sung audit Identity/RBAC ngày 2026-09-29, múi giờ Asia/Saigon |
| Commit được đối chiếu | `5e2105286c79ac07dd8bee1aa0edbd4f5ba81512` |
| Phạm vi | Khách hàng, staff/lễ tân, admin; đặt phòng và thanh toán; đăng nhập, đăng ký, Google OAuth, OTP, quên/đổi mật khẩu, hồ sơ, hủy/hoàn tiền, quản lý giá, tài khoản và RBAC |
| Chế độ | Read-only đối với source nghiệp vụ, frontend và database |
| Database test | SQLite `:memory:` theo `phpunit.xml`; không ghi vào MySQL thật |
| Mức kết luận | Có bằng chứng từ test chạy được và/hoặc trace source từ entry point tới database mutation |

## 2. Kết luận điều hành

Hệ thống hiện **chưa đạt điều kiện an toàn để coi luồng đặt phòng và thanh toán là nhất quán ACID trong môi trường có thao tác đồng thời**. Bộ test hiện có chạy xanh, nhưng chưa bao phủ các interleaving quan trọng giữa hold hết hạn, callback thanh toán, hủy phòng, gia hạn và thao tác staff.

Các rủi ro cần chặn trước khi vận hành thật:

1. Hold hết hạn có thể được callback thanh toán muộn xác nhận lại, tạo hai booking hợp lệ trùng một phòng.
2. VietQR có thể trả `paid` cho trình duyệt trong khi booking vẫn `pending`.
3. `payment_status=paid` đang mang cả nghĩa “đã cọc 50%” và “đã thanh toán toàn bộ”, nên có đường checkout hoàn tất khi vẫn còn số dư.
4. Nhiều entry point check-in/checkout áp dụng điều kiện khác nhau; booking pending hoặc chưa thanh toán có thể được check-in.
5. Checkout MoMo/ZaloPay/VNPay dùng lại luồng thu cọc, gửi sai số tiền và callback không thể hoàn tất booking `checked_in`.
6. Dashboard có thể ghi toàn bộ giá trị booking thành doanh thu ngay khi chỉ mới thu cọc; room-night và occupancy cũng đang tính sai.

### Phân loại phát hiện

| Mức | Số lượng | Ý nghĩa |
|---|---:|---|
| Critical | 3 | Có thể double-book, sai trạng thái tiền/phòng hoặc thất thoát doanh thu |
| High | 18 | Có thể thực hiện sai nghiệp vụ, lộ secret, chiếm quyền hoặc phá nhất quán giữa portal |
| Medium | 29 | Sai policy, số liệu, lifecycle danh tính hoặc thiếu audit/realtime |
| Low | 3 | Hợp đồng validation/UI không đồng nhất nhưng backend còn lớp chặn sau |
| **Tổng** | **53** | 34 finding vòng booking/payment và 19 finding bổ sung Identity/RBAC |

## 3. Phương pháp và bằng chứng

### 3.1 Các lớp đã kiểm tra

- Route và middleware của customer/staff/admin.
- Validation tại controller và điều kiện hiển thị/thao tác tại Blade/JavaScript.
- Booking state, payment state, room physical state và quan hệ `booking_rooms`.
- Transaction, `lockForUpdate`, thứ tự khóa, idempotency và unique constraint.
- Callback MoMo, ZaloPay, VNPay; polling VietQR; tiền mặt.
- Cancellation/refund, gia hạn ngày/giờ, late checkout fee.
- KPI admin: revenue, ADR, RevPAR, occupancy, room-night.

### 3.2 Test đã thực thi

| Bộ test | Kết quả |
|---|---|
| `php artisan test --compact` | **39 passed, 170 assertions**, 2.79 giây |
| Characterization suite do agent chính chạy ngoài repository | **7 passed, 16 assertions** |
| Characterization customer/payment của nhánh audit độc lập | **9 passed, 27 assertions** |
| Characterization staff/admin của nhánh audit độc lập | **6 passed, 17 assertions** |
| Characterization Identity/RBAC bổ sung ngày 2026-09-29 | **8 passed, 28 assertions** |

Các characterization test cố ý khẳng định hành vi lỗi hiện tại để chứng minh lỗi có thể tái hiện, ví dụ:

- POST booking chấp nhận 4 người lớn vào loại phòng `max_adults=1` nếu tổng `max_guests` vẫn đủ.
- Booking confirmed nhưng unpaid vẫn check-in được qua route cũ.
- Booking pending/unpaid vẫn check-in được qua endpoint phòng.
- Booking cancelled vẫn đổi phương thức thanh toán và mở payment form.
- Booking pending/unpaid vẫn mở trang payment success và sinh QR check-in.
- Hai phòng nhỏ đủ tổng sức chứa nhưng search không trả kết quả cho nhóm lớn.
- Báo cáo cộng đêm của booking cancelled và pending.
- VietQR trả JSON `paid` trong khi booking còn pending.
- Callback muộn xác nhận lại booking đã hết hold và trùng với booking khác.
- Admin cuối cùng có thể tự đổi role thành customer.

### 3.3 Giới hạn kiểm tra

- Không gọi gateway, SMTP hoặc API ngân hàng thật.
- Không chạy hai connection MySQL thật đồng thời vì yêu cầu không đụng database; các race được chứng minh bằng source interleaving và trạng thái tuần tự tương đương trên SQLite in-memory.
- Không sửa frontend, source nghiệp vụ hoặc schema trong đợt này.
- Vì SQLite không mô phỏng hoàn toàn MySQL enum/locking, mọi fix concurrency phải được retest thêm bằng MySQL test database cô lập.

## 4. Ma trận luồng và kết quả

| Luồng | Kết quả | Ghi chú |
|---|---|---|
| Ngày nhận quá khứ | Đạt | Backend từ chối |
| Ngày trả bằng/trước ngày nhận | Đạt | Backend từ chối |
| Sau 17:00 chọn nhận phòng hôm nay | Đạt ở customer store | Chưa đồng nhất với các đường staff check-in |
| Tổng khách vượt `max_guests` | Đạt ở customer store | Chỉ kiểm tổng, chưa kiểm riêng người lớn/trẻ em |
| Vượt `max_adults`/`max_children` | **Lỗi** | Có thể bypass bằng POST trực tiếp |
| Hai request tạo booking khi hold còn hạn | Có lớp bảo vệ | Store khóa room theo ID rồi recheck overlap |
| Callback sau khi hold hết hạn | **Lỗi Critical** | Có thể hồi sinh booking và double-book |
| Payment callback lặp lại cùng booking | Có guard ứng dụng | Chưa có unique DB toàn hệ thống |
| Cùng transaction gateway cho hai booking | **Lỗi** | Có thể ghi nhận hai lần |
| Check-in pending/unpaid | **Lỗi** | Có nhiều endpoint yếu hơn route chuẩn |
| Checkout chỉ mới trả cọc | **Lỗi Critical** | `paid` bị dùng sai nghĩa |
| Hủy và callback thanh toán giao nhau | **Lỗi** | Tiền có thể không vào ledger/reconciliation |
| Refund submit lặp | Đạt ở mức flag | Không có ledger/chứng từ refund thật |
| Gia hạn đồng thời với booking mới | **Lỗi** | Không dùng cùng locking protocol |
| Staff/admin/customer thấy cùng state | **Chưa đạt** | Không version/outbox/broadcast, room matrix còn dùng scope khác |

## 5. Phát hiện chi tiết

### LGC-001 — Critical — Callback muộn hồi sinh hold hết hạn và gây double booking

**Source:** `app/Models/Booking.php:91-103`, `app/Http/Controllers/PaymentController.php:352-376`.

**Tái hiện:** A tạo pending booking; sau hơn 30 phút hold A bị loại khỏi `reservedRoomIds`; B đặt cùng phòng/cùng ngày; callback A đến và `confirmPayment()` chỉ khóa booking A, không khóa room, không kiểm tra hold hoặc overlap; A và B cùng thành confirmed.

**Expected:** tiền đến muộn không được cấp lại inventory; giao dịch phải vào reconciliation/refund.

**Actual:** hai booking active trùng một phòng.

**ACID:** vi phạm Consistency và Isolation.

**Hướng xử lý:** thêm `hold_expires_at`; callback khóa booking rồi khóa room theo ID tăng dần; kiểm tra hold và overlap trong cùng transaction; late payment tạo `payment_exception`, không confirm.

### LGC-002 — Critical — VietQR báo paid nhưng booking vẫn pending

**Source:** `app/Services/Payment/VietQRService.php:172-216`, `app/Http/Controllers/PaymentController.php:145-165,352-376`.

`checkTransaction()` đổi pending PaymentLog thành success trước. Sau đó `confirmPayment()` thấy success log ở dòng 358 và return, không cập nhật booking. Controller vẫn trả `status=paid`.

**ACID:** PaymentLog và Booking không commit/rollback cùng nhau; vi phạm Atomicity và Consistency.

**Hướng xử lý:** payment adapter chỉ trả normalized event; một domain transaction duy nhất khóa attempt + booking + room, ghi log và đổi state; chỉ trả paid sau khi đọc lại booking đã commit.

### LGC-003 — Critical — Tiền cọc bị hiểu là đã thanh toán toàn bộ

**Source:** `PaymentController.php:369-373`; `BookingController.php:240-260`; `Booking.php:123`; route checkout staff tại `routes/web.php:113-115`.

Sau khi nhận cọc 50%, code đặt `payment_status=paid`. `BookingController::checkOut()` chỉ gọi `isPaid()` rồi cho completed. Đây là lỗi mô hình dữ liệu, không chỉ là thiếu một `if`.

**Expected:** completed chỉ khi `total_paid - total_refunded >= final_amount`.

**Hướng xử lý:** ledger là nguồn tiền; tách deposit, checkout balance và refund; chỉ một `CheckoutService` được chuyển booking sang completed.

### LGC-004 — High — Transaction gateway không có unique constraint

**Source:** `database/migrations/2026_01_01_000008_create_remaining_tables.php:42-55`; `PaymentController.php:355-367`.

`transaction_id` và `reference_code` chỉ có index. Hai worker xử lý cùng transaction cho hai booking khác nhau sẽ khóa hai booking khác nhau, cùng pass `exists()`, rồi cùng ghi success.

**Hướng xử lý:** unique `(gateway, transaction_id)`; unique reference có điều kiện; payment attempt có idempotency key; dùng insert/upsert trong transaction.

### LGC-005 — High — Staff checkout MoMo/ZaloPay/VNPay gửi sai số tiền và không hoàn tất state

**Source:** `PaymentController.php:598-646`; `MoMoService.php:30-35`; `ZaloPayService.php:23-28`; `VNPayService.php:22-30`; `PaymentController.php:352-373`.

Staff checkout tái dùng service thu cọc nên gateway nhận `deposit_amount`, không phải `total - already_paid + late_fee`. Callback chuẩn chỉ xử lý booking pending/confirmed, trong khi checkout là checked_in.

**Hướng xử lý:** PaymentAttempt phải có `purpose=deposit|checkout_balance|refund` và amount bất biến; callback dispatch theo purpose; checkout callback gọi `CheckoutService`.

### LGC-006 — High — VietQR checkout không atomic và thiếu state guard

**Source:** `VietQRService.php:124-168`; `PaymentController.php:648-710`.

- Service đánh dấu log success trước khi transaction hoàn tất booking.
- Nếu process chết ở giữa, booking kẹt checked_in nhưng transaction đã bị tiêu thụ.
- Controller tạo thêm success log thứ hai.
- Route tạo QR/poll không bắt buộc booking phải checked_in; poll chỉ bỏ qua completed, nên booking pending/cancelled có thể bị biến thành completed.

**Hướng xử lý:** tạo checkout payment intent có state/amount/purpose; chỉ settle một lần trong transaction; bắt buộc expected booking state checked_in.

### LGC-007 — High — Các entry point check-in không dùng cùng state machine

**Source:** `BookingController.php:200-234,280-386`; `ReceptionController.php:297-344,664-740`.

Các đường hiện tại có rule khác nhau:

- `confirm()` có thể xác nhận unpaid.
- `checkIn()` yêu cầu confirmed nhưng không kiểm payment, ngày, lock hoặc conflict.
- `checkInRoom()` chọn cả pending và confirmed.
- `updateStatus()` chọn cả pending và confirmed.
- Quick QR check-in dùng deny-list thiếu, không whitelist confirmed và không khóa booking/rooms.

Characterization test đã xác nhận pending/unpaid và confirmed/unpaid đều có thể thành checked_in.

**Hướng xử lý:** mọi button, QR và API route gọi chung `BookingTransitionService`; transition dùng compare-and-set `expected_status + version` trong transaction.

### LGC-008 — High — Quick QR check-in chấp nhận cancelled/refunded/pending

**Source:** `ReceptionController.php:674-697,734-738`.

Code chỉ chặn một vài trạng thái đã kết thúc thay vì whitelist trạng thái cho phép; không kiểm payment/refund policy. QR success hiện còn có thể được sinh cho pending unpaid qua LGC-013.

**Hướng xử lý:** QR là token ký số, có purpose, booking version và expiry; server chỉ chấp nhận booking confirmed cùng settlement policy hợp lệ.

### LGC-009 — High — Hủy booking giao nhau với callback có thể làm mất dấu khoản tiền nhận

**Source:** `CancellationController.php:58-71`; `PaymentController.php:172-254,355-358`.

Nếu cancel commit trước, callback hợp lệ đến sau sẽ thấy booking cancelled và return. Endpoint gateway vẫn có thể nhận acknowledgment thành công nhưng không có payment exception hoặc refund task.

**Hướng xử lý:** mọi gateway event hợp lệ phải được ghi immutable trước; cancelled/expired chuyển thành `received_after_cancellation` và tạo reconciliation/refund idempotent.

### LGC-010 — High — Payment form/method cho phép booking không còn payable

**Source:** `PaymentController.php:35-61,80-115`.

Booking cancelled vẫn PATCH payment method và mở form; hold hơn 30 phút vẫn mở form. Guard trạng thái chỉ xuất hiện muộn ở `show()` và không ngăn mutation trước đó.

**Hướng xử lý:** một `assertPayable()` dùng chung cho form, update method, gateway URL, polling và callback: owner, state, unpaid, hold còn hạn, inventory không conflict.

### LGC-011 — High — Trang payment success và QR mở cho booking chưa thanh toán

**Source:** `PaymentController.php:278-317`.

Method chỉ kiểm ownership, không kiểm trạng thái payment/booking. Test đã xác nhận pending unpaid nhận HTTP 200 và QR khác rỗng.

**Hướng xử lý:** chỉ confirmed-paid hoặc confirmed-cash theo policy được xem success; token check-in chỉ phát hành sau state commit.

### LGC-012 — High — Giá hiển thị, giá lưu và giá gateway không thống nhất

**Source:** `room/detail.blade.php:484-497`; `booking/create.blade.php:12-13,220-235`; `BookingController.php:117-133`; `payment/form.blade.php:253-275`.

- Trang chi tiết cộng VAT 8%.
- Trang booking cộng 15% service + VAT.
- Backend lưu base adjusted total, không cộng khoản trên.
- Payment gọi `total_price` là đã gồm thuế/phí; cọc bằng 50% base total.

**Hướng xử lý:** `PriceQuoteService` server-side trả và snapshot subtotal, từng tax/fee, grand total, deposit và nightly line items. Frontend chỉ render quote, không tự tính tiền.

### LGC-013 — High — Policy hủy/hoàn tiền tự mâu thuẫn và hiển thị sai số tiền

**Source:** `Booking.php:128-154`; `room/detail.blade.php:345-348`; `client/pages/home/index.blade.php:537`; `booking/cancel.blade.php:61-71`; `CancellationController.php:57-68`.

Comment model nói cuối tuần/ngày lễ cần 3 ngày nhưng code trả 7; trang công khai nói hủy miễn phí trước 48 giờ; cancellation page hiển thị hoàn 100% `total_price`; controller chỉ ghi `deposit_amount`.

**Hướng xử lý:** một `CancellationPolicy` duy nhất trả deadline, timezone, eligibility và exact refund amount; UI và mutation dùng cùng kết quả; snapshot policy version vào booking.

### LGC-014 — High — Gia hạn và booking mới có race tạo overlap/lost update

**Source:** `BookingController.php:401-457`; `ReceptionController.php:466-578`.

Các flow đọc checkout/price, check conflict rồi update nhưng không khóa booking và toàn bộ room theo protocol giống customer store. Hai staff cũng có thể cùng đọc giá/ngày cũ, cùng báo thành công nhưng chỉ một thay đổi được lưu.

**Hướng xử lý:** transaction khóa booking rồi room ID tăng dần; recheck overlap; update theo version; mọi mutation lịch phòng dùng một AvailabilityService.

### LGC-015 — High — Price policy có race và giá nondeterministic

**Source:** `AdminController.php:117-183`; `PriceSetting.php:28-83`.

Hai admin có thể cùng pass overlap check rồi cùng insert. Calculator load policy không có order và `break` ở match đầu; khi overlap tồn tại, giá phụ thuộc thứ tự DB.

**Hướng xử lý:** serialize write hoặc DB constraint phù hợp; có priority rõ; bound adjustment; snapshot giá theo từng đêm khi booking.

### LGC-016 — Medium — Không kiểm riêng `max_adults` và `max_children` ở authoritative store

**Source:** `BookingController.php:80-115`; `ReceptionController.php:423-460`.

Backend chỉ so tổng khách với tổng `max_guests`. Test đã xác nhận loại phòng `max_adults=1,max_guests=4` vẫn nhận 4 người lớn.

**Hướng xử lý:** kiểm tổng riêng `sum(max_adults)`, `sum(max_children)` và `sum(max_guests)` tại mọi entry point customer/walk-in.

### LGC-017 — Medium — Search không ghép nhiều phòng nhỏ cho nhóm lớn

**Source:** `RoomController.php:54-70`, đặc biệt dòng 61-62.

Query yêu cầu một RoomType đơn lẻ chứa toàn bộ nhóm. Hai phòng capacity 2 không được gợi ý cho 3-4 khách dù store hỗ trợ nhiều room.

**Hướng xử lý:** tính số room cần theo aggregate capacity; nếu cho phép mixed types thì giải allocation rõ ràng và xác thực lại ở backend.

### LGC-018 — Medium — Logic capacity trên trang search bị đảo điều kiện

**Source:** `room/search.blade.php:1409-1460`.

JavaScript cảnh báo khi capacity được chọn **lớn hơn** nhu cầu (`>`), nhưng không chặn khi capacity nhỏ hơn (`<`). Quick book dùng cùng hướng sai.

**Hướng xử lý:** frontend chỉ hỗ trợ UX; điều kiện thiếu capacity phải là `<`; backend vẫn là authoritative và phải kiểm đủ ba capacity.

### LGC-019 — Medium — Nút Quick book dùng sai query contract

**Source:** `room/search.blade.php:1159-1166`; `BookingController.php:24-31`.

Button gửi `room_id`, trong khi controller yêu cầu `room_ids[]`. Nhấn “Đặt nhanh 1 phòng” có thể rơi vào validation error.

**Hướng xử lý:** dùng cùng URL builder/DTO với multi-select và có feature test bấm nút tới form.

### LGC-020 — Medium — “Sửa” ở bước thanh toán không sửa booking hiện tại

**Source:** `payment/form.blade.php:22-26,247-250`; `BookingController.php:75-143`.

Link quay về form create; submit luôn tạo booking mới. Booking cũ vẫn giữ phòng nên request mới bị chính booking cũ chặn, tạo dead-end.

**Hướng xử lý:** PATCH draft booking dưới lock/version, hoặc atomically release-and-replace hold của chính booking.

### LGC-021 — Medium — Arrival time và special requests bị bỏ qua

**Source:** `booking/create.blade.php:123-156`; `BookingController.php:80-90,123-137`; schema/model hiện tại.

Hai field được submit nhưng không validate/persist. UI còn cho chọn `16:00-18:00` và “Sau 18:00” trái policy nhận phòng tối đa 17:00.

**Hướng xử lý:** nếu không dùng thì xóa field; nếu dùng thì có schema, giới hạn, sanitization và hiển thị cho staff. Arrival phải nằm trong policy đã duyệt.

### LGC-022 — Medium — Hold timer 15 phút chỉ là cosmetic, backend giữ 30 phút

**Source:** `payment/form.blade.php:327-340`; `Booking.php:99-103`.

Timer restart khi reload, hết giờ không vô hiệu hóa thao tác, trong khi backend tính 30 phút từ created_at.

**Hướng xử lý:** server trả absolute `hold_expires_at`; UI tính từ server clock, refresh-safe; backend guard là authoritative.

### LGC-023 — Medium — Cash reservation giữ inventory vô thời hạn

**Source:** `PaymentController.php:118-122`; `Booking.php:99-103`.

Cash chuyển booking thành confirmed; confirmed reserve vĩnh viễn, không có payment deadline/no-show expiry.

**Hướng xử lý:** có `confirmation_expires_at` hoặc policy pay-at-property/no-show rõ ràng và job giải phóng.

### LGC-024 — Medium — History hiển thị action trái state machine

**Source:** `booking/my_bookings.blade.php:104-107,170-208`; `PaymentController.php:84`; `CancellationController.php:24-27`.

Checked-in unpaid vẫn có thể thấy pay/cancel dù backend từ chối; QR hiển thị cho pending/confirmed/checked_in kể cả unpaid.

**Hướng xử lý:** backend trả `canPay`, `canCancel`, `canShowCheckinQr`, `canReview`; Blade chỉ render capability đó.

### LGC-025 — Medium — Pending hết hạn không chuyển thành state expired

**Source:** `Booking.php:99-103`.

Availability âm thầm bỏ qua pending cũ nhưng booking vẫn hiện pending ở history, payment form còn mở, callback còn có thể revive.

**Hướng xử lý:** explicit `expired` + `hold_expires_at`; job/CAS chuyển state; mọi command dùng cùng payability predicate.

### LGC-026 — Medium — Không có version/outbox/realtime giữa ba portal

Không tìm thấy entity version, optimistic concurrency, outbox hoặc broadcast cho booking/room/payment. Staff room matrix còn lấy pending cũ khác với customer availability.

**Hướng xử lý:** trước hết dùng `version`/CAS và trả `409 stale_state`; sau commit ghi outbox; client polling delta hoặc WebSocket theo version. Realtime không được thay thế consistency ở transaction.

### LGC-027 — Medium — KPI revenue, ADR, RevPAR và occupancy sai cơ sở tính

**Source:** `Booking.php:168-250`; `AdminController.php:47-54`.

- Revenue cộng toàn bộ `total_price` khi chỉ mới thu cọc.
- `total_nights` gồm pending/cancelled.
- Không nhân số phòng, nên booking 2 đêm x 2 phòng chỉ thành 2 thay vì 4 room-night.
- Date filter chỉ dựa `check_in`, bỏ stay overlap kỳ báo cáo.
- Late fee/refund không đi qua ledger chuẩn.

**Hướng xử lý:** fact booking-room-night cho occupancy; payment ledger cho collected/refunded/recognized revenue; proration theo khoảng report.

### LGC-028 — Medium — Hoàn tiền chỉ đổi flag, không có bằng chứng dòng tiền

**Source:** `CancellationController.php:84-93`.

Không lưu transaction/reference, operator, gateway response, processed_at, failure/retry. `refunded` có thể không tương ứng khoản tiền thực.

**Hướng xử lý:** RefundAttempt/ledger idempotent; chỉ đổi refunded sau bằng chứng gateway hoặc manual reference có actor.

### LGC-029 — Medium — Late fee dùng giá phòng hiện tại thay vì giá đã ký

**Source:** `PaymentController.php:125-132`.

Phụ thu 50% được tính từ `roomType.price` hiện tại. Admin đổi giá sau lúc đặt làm phụ thu thay đổi hồi tố; dynamic price cũng không được snapshot.

**Hướng xử lý:** tính từ contracted nightly rate snapshot của booking, không đọc catalog price lúc checkout.

### LGC-030 — Medium — Gia hạn theo giờ chỉ cộng tiền, không lưu deadline vận hành

**Source:** `ReceptionController.php:499-505`; late fee tại `PaymentController.php:125-131`.

Gia hạn 2 giờ giữ nguyên checkout deadline nhưng cộng 400.000đ. Bấm lặp tiếp tục cộng, không idempotency/audit; late fee vẫn so mốc cũ.

**Hướng xử lý:** lưu `scheduled_checkout_at` hoặc Extension record với idempotency key, actor, duration, amount; late fee dùng deadline mới.

### LGC-031 — Medium — Admin cuối cùng có thể tự hạ quyền

**Source:** `app/Http/Controllers/Admin/UserController.php:90-128`.

Test xác nhận admin duy nhất đổi chính mình thành customer, làm hệ thống còn 0 admin.

**Hướng xử lý:** trong transaction/lock, cấm self-demotion nếu là admin active cuối cùng; audit role changes.

### LGC-032 — Medium — Status vocabulary giữa schema/backend/report không đồng nhất

**Source:** migration `2026_06_06_102740*`, `Booking.php:206-210`, `AdminController.php:28-33`, `Room.php:12-16`, migration `2026_06_06_133315*`.

Một số giá trị như `soon_to_checkout`, `booked`, `overdue`, `soon_to_checkin` không được tất cả layer cùng hiểu; SQLite và MySQL còn khác cách mở rộng enum.

**Hướng xử lý:** canonical enum/value object + transition matrix; cùng validation, DB constraint và display mapping ở ba portal.

### LGC-033 — Low — GET booking form không kiểm overlap giống POST store

**Source:** `BookingController.php:43-51,98-110`.

GET chỉ kiểm physical room status. Back/direct navigation có thể hiển thị form cho room đã bị hold; submit sau mới bị từ chối. Data vẫn được store bảo vệ.

**Hướng xử lý:** dùng cùng availability scope ở GET và POST, nhưng giữ recheck dưới lock ở POST.

### LGC-034 — Low — Validation GET/POST không đồng nhất

**Source:** `BookingController.php:24-31,80-90`.

GET giới hạn tối đa 25 room và dùng `date_format:Y-m-d`; POST thiếu `max:25` và dùng `date` chung. Direct POST có contract rộng hơn UI.

**Hướng xử lý:** shared FormRequest/domain command rules; canonical civil date `Y-m-d`.

## 6. Rule nghiêm ngặt về logic và ACID

Các rule dưới đây phải trở thành invariant server-side; frontend chỉ hỗ trợ trải nghiệm và không được là lớp bảo vệ duy nhất.

### 6.1 Ngày và sức chứa

1. `check_in` và `check_out` là civil date `Y-m-d`, theo `Asia/Ho_Chi_Minh`.
2. `check_in < check_out`; không cho ngày quá khứ.
3. Sau 17:00 không nhận booking check-in hôm đó; chính sách giờ check-in phải áp dụng cả customer, staff và admin.
4. Khoảng phòng dùng half-open interval `[check_in, check_out)` để hai booking nối tiếp được phép.
5. Mọi entry point phải kiểm đồng thời `max_adults`, `max_children`, `max_guests` trên tổng các room đã chọn.
6. Room maintenance/cleaning/occupied không thể được cấp mới bất kể UI đang hiển thị gì.

### 6.2 Inventory và hold

1. Một room không có hơn một active allocation trong cùng khoảng thời gian.
2. Pending hold phải có `hold_expires_at`; hết hạn phải thành `expired`, không chỉ bị bỏ khỏi query.
3. Hold hết hạn không được thanh toán hoặc confirm; tiền đến muộn đi reconciliation/refund.
4. Mọi command thay đổi lịch phòng phải khóa room ID theo thứ tự tăng dần rồi recheck availability trong cùng transaction.
5. Cash/no-show reservation cũng phải có deadline hoặc policy bảo lãnh rõ ràng.

### 6.3 State machine booking

State đề xuất tối thiểu:

`draft -> pending_hold -> confirmed -> checked_in -> completed`

Nhánh ngoại lệ:

- `pending_hold -> expired`
- `pending_hold|confirmed -> cancelled`
- `cancelled|expired + late payment -> payment_exception -> refunded|manually_reconciled`

Không controller nào được update status trực tiếp ngoài một transition service. Mỗi command phải có expected status và entity version.

### 6.4 Payment, checkout và refund

1. Mỗi PaymentAttempt có immutable booking, purpose, amount, currency và idempotency key.
2. Purpose chỉ thuộc `deposit`, `checkout_balance`, `refund`.
3. `(gateway, transaction_id)` duy nhất toàn DB.
4. Gateway event hợp lệ luôn được lưu immutable, kể cả booking đã cancel/expire.
5. Ledger là nguồn sự thật cho `total_paid`, `total_refunded`, `balance_due`.
6. Booking chỉ completed khi `total_paid - total_refunded >= final_amount`.
7. Refund chỉ thành công sau khi có gateway/manual reference, actor và processed time.
8. Email/event phát sau commit qua after-commit/outbox; lỗi email không rollback sự thật đã ghi.

### 6.5 Pricing và báo cáo

1. Booking snapshot giá từng đêm, thuế, phí, discount và policy version tại lúc giữ chỗ.
2. Giá catalog thay đổi không làm đổi booking hoặc late fee đã ký.
3. Revenue thực thu lấy từ payment ledger; booked revenue và recognized revenue là metric riêng.
4. Occupancy/ADR/RevPAR dùng room-night hợp lệ, loại cancelled/expired/pending theo định nghĩa đã phê duyệt.
5. Filter report phải tính phần overlap trong kỳ, không chỉ ngày check-in.

### 6.6 Nhất quán ba portal

1. Customer, staff và admin đọc cùng canonical booking/payment/room state.
2. Mọi response mutation trả `version` mới; command stale trả HTTP 409 và state mới nhất.
3. Sau commit ghi outbox event; WebSocket hoặc polling delta chỉ dùng để phân phối state đã commit.
4. Không suy room availability chỉ từ `rooms.status`; physical status và future allocation là hai khái niệm riêng.

## 7. Giao thức transaction bắt buộc cho đặt/thanh toán

### 7.1 Tạo hold

1. Validate command.
2. Begin transaction.
3. Lock room rows theo ID tăng dần.
4. Recheck physical state, date overlap và capacity.
5. Snapshot price quote.
6. Tạo booking `pending_hold`, `hold_expires_at` và allocations.
7. Ghi outbox event.
8. Commit.

### 7.2 Xác nhận thanh toán

1. Normalize và verify gateway event.
2. Begin transaction.
3. Insert/upsert gateway event theo unique transaction ID.
4. Lock payment attempt, booking, rồi room rows theo thứ tự cố định.
5. Kiểm expected state, amount, purpose, hold expiry và overlap.
6. Nếu hợp lệ: ghi ledger, transition booking, ghi outbox.
7. Nếu late/cancelled/conflict: ghi payment exception và refund/reconcile task; không cấp room.
8. Commit rồi mới trả success/send email.

### 7.3 Check-in/check-out/gia hạn

1. Tất cả entry point gọi cùng service.
2. Lock booking + room rows.
3. Kiểm expected state/version, date/time policy, payment requirement và room state.
4. Với gia hạn: recheck toàn bộ khoảng mới khi đang giữ lock.
5. Với checkout: tính final bill từ snapshot + extension + late fee - paid ledger.
6. Commit booking, room, ledger và outbox cùng transaction.

## 8. Kế hoạch xử lý đề xuất

### P0 — Chặn mất phòng/mất tiền

1. Sửa VietQR atomicity và false paid.
2. Chặn callback muộn hồi sinh hold; thêm explicit expiry.
3. Tách deposit khỏi fully-paid; khóa route checkout cũ vào một service.
4. Tạo PaymentAttempt purpose/amount/idempotency và unique gateway transaction.
5. Hợp nhất toàn bộ check-in/checkout transition; chặn pending/unpaid/cancelled/refunded.
6. Sửa checkout online theo remaining balance.

**Điều kiện ra P0:** toàn bộ concurrency/idempotency test tại mục 9 đạt trên MySQL test DB.

### P1 — Nhất quán inventory, pricing và cancellation

1. AvailabilityService + ordered room lock cho create/extend/walk-in/callback.
2. PriceQuote snapshot; bỏ mọi financial calculation độc lập ở Blade/JS.
3. CancellationPolicy duy nhất và Refund ledger.
4. Capacity riêng adults/children/guests.
5. Payability/capability policy dùng chung cho form và action.

### P2 — Báo cáo, realtime và vận hành

1. Sửa room-night/report facts và metric definitions.
2. Version/CAS + outbox; sau đó mới thêm broadcast/polling delta.
3. Canonical enum/state mapping.
4. Admin last-role invariant; email after-commit.
5. Audit log cho role, refund, extension và manual override.

## 9. Test nghiệm thu bắt buộc sau khi sửa

### 9.1 Date/capacity/price

1. Missing, malformed, past, same-day, reversed dates đều trả lỗi đúng field.
2. Boundary 16:59 cho phép hôm nay; từ 17:00 từ chối hôm nay theo timezone cấu hình.
3. Vượt adults, children hoặc total ở mọi entry point đều bị từ chối.
4. Hai phòng nhỏ được gợi ý khi aggregate capacity đủ.
5. UI total, booking snapshot, gateway amount và invoice khớp từng đồng.

### 9.2 Concurrency và ACID

1. Hai transaction đồng thời đặt cùng room/date: đúng một hold thành công.
2. A hết hold, B đặt, callback A: B giữ room; A vào reconciliation.
3. Cùng gateway transaction gửi đồng thời cho hai booking: đúng một ledger success.
4. Callback lặp 10 lần: một ledger, một transition, một email/outbox effect.
5. Cancel và callback chạy theo cả hai thứ tự: tiền luôn xuất hiện trong ledger và có kết quả refund/reconcile.
6. Hai staff gia hạn đồng thời: một success và một stale/conflict, hoặc cộng dồn đúng theo versioned command.
7. Booking mới giao với gia hạn: không thể tạo overlap.

### 9.3 State transition

1. Pending, expired, cancelled, refunded, completed không check-in được qua bất kỳ route/button/QR nào.
2. QR giả, hết hạn, sai purpose hoặc stale version bị từ chối.
3. Checkout khi chỉ mới cọc bị từ chối.
4. Cash, VietQR, MoMo, ZaloPay, VNPay checkout đều thu cùng exact balance và cùng state result.
5. Gia hạn giờ cập nhật deadline; late fee dùng deadline sau gia hạn.
6. Refund submit lặp hoặc callback lặp chỉ hoàn một lần.

### 9.4 Ba portal và báo cáo

1. Sau mỗi commit, customer/staff/admin đọc cùng booking version/state.
2. Command từ tab stale nhận 409 và state mới nhất.
3. Room matrix không hiển thị expired hold như booking active.
4. Revenue cash collected bằng sum(success payments) trừ sum(success refunds).
5. Booking 2 đêm x 2 phòng tạo 4 room-night.
6. Stay giao một phần report range chỉ đóng góp phần overlap.
7. Admin cuối cùng không thể tự hạ quyền/xóa khóa truy cập.

## 10. Những kiểm soát hiện có nên giữ

- Customer booking store đã dùng transaction, khóa room theo thứ tự và recheck overlap.
- Interval overlap dùng đúng dạng half-open `[check_in, check_out)`.
- Customer ownership đã có ở payment/cancel/success chính.
- MoMo/ZaloPay/VNPay có signature verification và kiểm amount cọc.
- Cancellation/refund flag hiện có booking row lock và double-submit guard.
- Walk-in ở `ReceptionController` có transaction + room lock + conflict check tốt hơn route legacy.
- Review đã kiểm ownership/completed/room-type và có unique `(user_id, room_type_id)`.

## 11. Tiêu chí phê duyệt trước khi triển khai fix

Đề nghị duyệt theo thứ tự P0 -> P1 -> P2. Không triển khai giao diện hoặc realtime trước khi P0 hoàn tất, vì animation/broadcast không giải quyết được double booking hoặc sai ledger. Mỗi nhóm fix phải kèm:

1. Migration có rollback và dữ liệu chuyển đổi được xác định.
2. Feature/integration test fail trước, pass sau.
3. MySQL concurrency test với ít nhất hai connection thật.
4. Gateway fake contract test cho success, duplicate, late, wrong amount, cancelled.
5. State transition matrix test cho mọi route customer/staff/admin.
6. Đối soát report với payment ledger và room-night fixture.

---

## 12. Audit bổ sung: Identity, hồ sơ, giá, hủy/hoàn tiền và RBAC

### 12.1 Ma trận kết quả

| Luồng | Kết quả | Bằng chứng chính |
|---|---|---|
| Customer login đúng/sai mật khẩu | Có hoạt động | Có rate limit và regenerate session; lỗi hiện tiết lộ account existence |
| Internal login | Có hoạt động | Limiter theo username + IP; customer bị từ chối |
| Tách customer/staff portal | **Chưa đạt** | Public `/login` vẫn nhận `role=staff` và tạo staff session |
| Registration validation | Đạt một phần | Email/phone/password confirmation có validate; password policy còn yếu |
| OTP account verification | Đạt một phần | Có expiry và limiter; OTP plaintext, dùng chung mục đích và bị log khi login |
| Google OAuth | Đạt một phần | Chặn internal email; chưa kiểm cờ provider `email_verified` trước khi link/verify local account |
| Forgot/reset password | Đạt một phần | Binding user ID + email và 10 phút đúng; redirect làm lộ email tồn tại; session cũ không bị revoke |
| Customer profile | **Lỗi** | Đổi verified email không cần current password/reverification; session snapshot bị stale |
| Staff profile | **Lỗi** | Form gửi `new_password`, controller yêu cầu `password`; đổi mật khẩu luôn fail |
| Hủy booking | Đạt phần state/ownership | Có ownership, row lock, state whitelist; policy/amount vẫn mâu thuẫn theo LGC-013 |
| Hoàn tiền | Đạt phần double-submit | Có row lock và guard; không có ledger/evidence/actor; receptionist có toàn quyền xác nhận |
| Chỉnh sửa giá | Đạt phần quyền/date | Chỉ admin; có date/order validation; thiếu bounds, audit và concurrency control |
| Thêm/sửa/khóa tài khoản | Đạt phần RBAC | Route admin-only; mutation và email không atomic; gửi plaintext password |
| Phân quyền | Đạt phần route | Receptionist bị chặn admin report; thiếu last-admin invariant, step-up và audit trail |

### IAM-001 — High — Public customer login cho phép tạo staff/admin session

**Source:** `routes/web.php:40-44`; `AuthController.php:24-139`; cổng nội bộ riêng tại `InternalAuthController.php:33-128`.

POST `/login` chấp nhận client-controlled `role=staff`, query theo username, kiểm role admin/receptionist rồi gọi `loginUser()`, tạo `staff_user_id` và redirect vào admin/staff. Characterization test đã xác nhận admin đăng nhập thành công qua route customer.

**Expected:** `/login` chỉ xử lý customer email; staff/admin chỉ đăng nhập qua `/internalauth/login` và policy/telemetry riêng.

**Tác động:** phá ranh giới portal, làm rule rate-limit/audit/incident response của internal login có thể bị bypass khi hai endpoint thay đổi khác nhau.

**Hướng xử lý:** bỏ nhánh staff khỏi `AuthController::login`; server cố định principal type theo route, không tin hidden/client `role`; thêm test public route luôn từ chối internal account.

### IAM-002 — High — OTP được ghi thẳng vào application log

**Source:** `AuthController.php:68-76`.

Mỗi lần password đúng, code log email, role, verified và `otp_code`. OTP là authentication secret và không được xuất hiện trong log tập trung, backup hoặc APM.

**Hướng xử lý:** xóa debug log; redaction rule cho OTP/token/password; rotate/purge log còn chứa OTP theo retention policy.

### IAM-003 — Medium — Login cho phép account enumeration

**Source:** `AuthController.php:45-65`; `InternalAuthController.php:48-68`.

Email/username không tồn tại trả lỗi khác với password sai. Rate limit giảm brute force nhưng không loại enumeration.

**Expected:** response công khai thống nhất “Thông tin đăng nhập không hợp lệ”; chi tiết chỉ ghi vào security telemetry đã redaction.

### IAM-004 — High — Khách đổi verified email không cần mật khẩu hiện tại hoặc xác minh email mới

**Source:** `AuthController.php:156-173`; route `account.update` tại `routes/web.php:78-79`.

Characterization test xác nhận request chỉ cần fullname/email/phone đã đổi được email và vẫn giữ `verified=true`. Nếu session bị chiếm, attacker có thể đổi email rồi sử dụng forgot-password để chiếm hẳn tài khoản. Với account Google, liên kết danh tính cũng trở nên không rõ ràng.

**Hướng xử lý:** sensitive change yêu cầu current password hoặc recent authentication; lưu `pending_email`; gửi link/OTP tới email mới; chỉ swap sau verify; gửi cảnh báo tới email cũ; revoke reset contexts.

### IAM-005 — Medium — Customer/staff profile snapshot trong session bị stale sau cập nhật

**Source:** `AuthController.php:156-173`; `ReceptionUserController.php:32-62`; `SetContextSessionMiddleware.php:21-65`.

Customer update không cập nhật `customer_user`; staff update chỉ cập nhật generic `user`, không cập nhật `staff_user`. Middleware ưu tiên snapshot context đã tồn tại nên header/sidebar và Verified middleware có thể dùng email/tên/trạng thái cũ. Test xác nhận DB có email mới nhưng `session('customer_user.email')` vẫn là email cũ.

**Hướng xử lý:** không lưu profile mutable thành nhiều bản trong session; chỉ lưu user ID/context, đọc principal hiện tại từ auth/DB/cache versioned. Nếu vẫn snapshot, refresh atomically mọi key sau mutation.

### IAM-006 — High — Form đổi mật khẩu staff không thể hoạt động

**Source:** `resources/views/receptionist/profile.blade.php:281-338`; `ReceptionUserController.php:65-95`.

Form gửi `new_password` và `new_password_confirmation`; controller validate `password` và Laravel chờ `password_confirmation`. Test thực tế nhận validation error `password`, hash không đổi.

**Hướng xử lý:** thống nhất field contract; feature test submit đúng form HTML; sau đổi password regenerate session, revoke remember/session khác và ghi audit event.

### IAM-007 — High — Reset/đổi mật khẩu không thu hồi các session đang hoạt động

**Source:** `AuthController.php:376-403`; `ReceptionUserController.php:65-95`; session config dùng database ở production.

Code đổi password và rotate `remember_token`, nhưng không xóa các session DB khác của user, không tăng auth version và không buộc đăng nhập lại ở thiết bị khác. Một session bị đánh cắp vẫn dùng được sau khi chủ tài khoản reset password.

**Hướng xử lý:** lưu `auth_version`/`password_changed_at`; middleware từ chối session version cũ; xóa session records khác sau commit; giữ hoặc hủy current session theo policy rõ ràng.

### IAM-008 — Medium — Forgot-password vẫn làm lộ email tồn tại qua redirect

**Source:** `AuthController.php:293-325`.

Text response giống nhau nhưng email tồn tại redirect tới `password.verify-otp`, email không tồn tại quay lại `password.forgot`. Characterization test xác nhận hai Location khác nhau.

**Hướng xử lý:** luôn trả cùng status, route và timing envelope; chỉ tạo reset attempt nội bộ khi account hợp lệ.

### IAM-009 — Medium — OTP lưu plaintext và dùng chung cột cho hai mục đích

**Source:** `users.otp_code`, `users.otp_expires_at`; `AuthController.php:196-208,243-275,293-398,493-569`.

Account verification và password reset cùng ghi đè một `otp_code`. OTP không hash và không có purpose/attempt ID/consumed timestamp/attempt counter riêng. Một luồng resend có thể làm mất hiệu lực luồng còn lại; data leak DB/log lập tức lộ mã đang dùng.

**Hướng xử lý:** bảng VerificationChallenge: user, purpose, hashed code, expires_at, attempts, consumed_at, request_id; compare hash constant-time; một challenge chỉ dùng một lần.

### IAM-010 — Medium — Resend OTP dùng GET có side effect và cooldown dùng chung session

**Source:** `routes/web.php:47,55`; `AuthController.php:493-569`.

GET `/verify/resend` và `/forgot-password/resend` thay đổi OTP và gửi email. Link prefetch, crawler hoặc top-level navigation có thể vô tình rotate mã. `last_otp_sent` dùng chung cho verify/reset và không gắn user/purpose.

**Hướng xử lý:** POST + CSRF; limiter theo account/purpose/IP; cooldown server-side theo challenge, không chỉ session browser.

### IAM-011 — Medium — Password policy chỉ kiểm độ dài 8 ký tự

**Source:** `AuthController.php:178-193,376-383`; `ReceptionUserController.php:72-75`; `Admin/UserController.php:40-47,92-99`.

Không có common password rule, breached-password check hoặc policy dùng chung giữa customer/staff/admin. Client strength meter không phải security control.

**Hướng xử lý:** một Password rule server-side dùng chung, tối thiểu length phù hợp và compromised-password check theo chính sách; không ép pattern khó nhớ nếu không cần.

### IAM-012 — High — Admin gửi plaintext password qua email

**Source:** `Admin/UserController.php:51-78,105-124`; `ReceptionistAccountMail`; `AccountUpdatedMail`.

Khi tạo receptionist hoặc đổi password, controller giữ plaintext và truyền vào email. Email không phải kênh bí mật; password tồn tại trong inbox, sent mail, SMTP relay và backup.

**Hướng xử lý:** không tạo/gửi password dùng được; gửi invitation token một lần có expiry, bắt buộc người dùng tự đặt password; admin không được đọc password mới.

### IAM-013 — Medium — Tạo/sửa tài khoản không atomic với gửi email

**Source:** `Admin/UserController.php:37-82,90-131`.

DB mutation xảy ra trước synchronous Mail. Test mô phỏng SMTP failure nhận HTTP 500 nhưng user đã tồn tại; retry gặp unique conflict. Update cũng có thể đã đổi role/email/password dù UI báo lỗi.

**Hướng xử lý:** commit dữ liệu và outbox cùng transaction; mail chạy after-commit qua queue, retry idempotent; UI báo “đã tạo, thông báo đang chờ gửi”.

### IAM-014 — Medium — Khóa/mở tài khoản vẫn đổi state khi email thông báo lỗi

**Source:** `Admin/UserController.php:159-184`.

Characterization test mô phỏng SMTP failure: endpoint 500 nhưng `verified` đã đổi. Admin retry có thể vô tình toggle ngược lại.

**Hướng xử lý:** endpoint nhận desired state (`locked=true/false`) thay vì toggle; mutation idempotent; mail after-commit/outbox; response luôn phản ánh state đã commit.

### IAM-015 — Medium — Email/username chưa được canonicalize nhất quán

**Source:** `AuthController.php:41-42,178-208`; `GoogleAuthController.php:35-38`; profile/admin user validation.

Google lower-case email nhưng register/login/profile không chuẩn hóa cùng cách. Hành vi duplicate và login phụ thuộc collation DB; khoảng trắng/case có thể tạo identity khó đối soát.

**Hướng xử lý:** trim + Unicode-safe lowercase email trước validation/query/write; username có canonical form riêng; unique constraint trên canonical column.

### IAM-016 — Low — Contract phone staff giữa UI và backend không đồng nhất

**Source:** `profile.blade.php:405-411`; `ReceptionUserController.php:38-42`.

UI đánh dấu phone required, backend cho nullable và chỉ `max:30`, khác rule customer `^0[0-9]{9}$`. Direct request có thể xóa hoặc lưu format staff mà UI không dự kiến.

**Hướng xử lý:** chốt business rule rồi dùng FormRequest chung cho UI và backend.

### IAM-017 — Medium — Google linking chưa kiểm explicit provider email verification

**Source:** `GoogleAuthController.php:23-75`.

Callback match local account theo lowercase email rồi đặt `verified=true` và link `google_id`; code không kiểm explicit claim `email_verified` từ provider payload. Google thường trả email đã xác minh, nhưng invariant quan trọng này chưa được enforce trong application.

**Hướng xử lý:** yêu cầu provider `email_verified=true`, issuer/audience/state hợp lệ qua Socialite; nếu link vào local account nhạy cảm thì thông báo và hỗ trợ unlink/recovery.

### IAM-018 — Medium — Quyền xác nhận hoàn tiền quá rộng và không có actor/evidence

**Source:** route `staff.bookings.refund` trong group `role:receptionist,admin` tại `routes/web.php:111-117`; `CancellationController.php:84-93`.

Mọi receptionist có thể chuyển refund sang refunded; không có permission riêng, hạn mức, maker-checker, current-password confirmation, actor hoặc reference. Đây là control gap tài chính dù double-submit flag hiện hoạt động.

**Hướng xử lý:** permission `refund.approve`; threshold/dual approval theo số tiền; lưu actor, reason, evidence và immutable ledger; UI không quyết định quyền.

### IAM-019 — Medium — Thao tác đặc quyền thiếu step-up và audit trail

**Source:** admin user CRUD/toggle, admin price setting CRUD, refund processing.

Tạo admin, đổi role/password/email, khóa user, xóa price policy và xác nhận refund chỉ dựa session hiện tại. Không thấy recent-auth/MFA challenge hoặc audit record trước-sau.

**Hướng xử lý:** step-up authentication cho privilege/financial mutations; audit append-only gồm actor, target, before/after, request ID, IP, timestamp; không ghi password/OTP/secret.

### 12.2 Bổ sung cho các finding đã có

- **LGC-013 / LGC-028:** cancellation ownership, state whitelist và booking row lock đang đúng; policy refund và bằng chứng dòng tiền vẫn chưa đạt.
- **LGC-015:** validation hiện cho `adjustment_value` không có upper business bound. Characterization test đã lưu thành công mức tăng `999999999%`; phải có bound riêng cho percent/fixed và approval khi vượt ngưỡng.
- **LGC-031:** test bổ sung tiếp tục xác nhận admin duy nhất tự demote thành customer và hệ thống còn 0 admin.
- Admin price routes và user management routes đã nằm trong middleware `role:admin`; receptionist bị chặn admin reports trong test hiện có.

## 13. Rule bắt buộc cho Identity, Profile và RBAC

1. **Route xác định principal type:** customer route không nhận role từ client; internal route chỉ nhận staff/admin.
2. **Không log secret:** password, OTP, session ID, OAuth token, reset token và payment secret phải redacted.
3. **Generic auth failure:** login/forgot/reset không tiết lộ account existence qua text, status, redirect hoặc timing rõ rệt.
4. **Session fixation:** login/privilege elevation phải regenerate session; logout invalidate + regenerate CSRF.
5. **Password reset:** challenge one-time, hashed, purpose-bound, expire 10 phút, rate-limited; success revoke mọi session/remember token cũ.
6. **Email change:** recent authentication + verify email mới + notify email cũ; chưa verify thì email đăng nhập hiện tại không đổi.
7. **OAuth linking:** chỉ link provider email verified; identity `(provider, subject)` unique; link/unlink có recovery và audit.
8. **Password delivery:** không gửi plaintext; dùng invitation/set-password token one-time.
9. **RBAC deny by default:** route và domain service cùng kiểm permission; role UI không phải control.
10. **Last-admin invariant:** luôn còn ít nhất một admin active; self-demote/delete/lock vi phạm phải bị từ chối trong transaction.
11. **Sensitive action step-up:** role change, password reset by admin, account lock, price override và refund approval cần recent-auth/MFA theo risk.
12. **Audit append-only:** mọi role/account/price/refund mutation lưu actor, target, before/after, request ID và timestamp.
13. **Mutation + notification:** domain state commit độc lập SMTP; notification qua outbox/queue và retry idempotent.
14. **Canonical identity:** email trim/lowercase thống nhất; unique constraint áp dụng canonical value.
15. **Session source of truth:** session chỉ giữ ID/context/version; profile và role đọc từ principal hiện tại, không duy trì nhiều snapshot lệch nhau.

## 14. Test nghiệm thu bổ sung

### 14.1 Login/registration/OAuth

1. `/login` từ chối mọi staff/admin dù client gửi `role=staff`; `/internalauth/login` từ chối customer.
2. Unknown user và wrong password có cùng response contract; limiter áp dụng theo identity + IP.
3. Không log OTP/password/token trong mọi success/failure path.
4. Registration normalize email; case/space variants không tạo hai identity.
5. OAuth callback thiếu `email_verified` không link hoặc verify local account.
6. Hai OAuth callback đồng thời cho cùng email/google subject chỉ tạo một user.

### 14.2 OTP và password

1. OTP sai bị giới hạn attempts; OTP hết hạn, consumed, sai purpose đều bị từ chối.
2. Resend là POST/CSRF, invalidate challenge cũ và không bị link prefetch kích hoạt.
3. Existing và unknown forgot-password trả cùng status/redirect/body shape.
4. Reset token của user A không dùng cho B; test hiện có này phải tiếp tục pass.
5. Reset password thành công xóa/revoke tất cả session cũ và remember cookie cũ.
6. Staff form HTML thực tế đổi password thành công; current password sai không đổi hash.

### 14.3 Profile/account/RBAC

1. Đổi email không có current-password/recent-auth bị từ chối.
2. Email chỉ đổi sau khi email mới xác minh; email cũ nhận cảnh báo.
3. Header/sidebar phản ánh profile mới ở request kế tiếp, không còn snapshot stale.
4. Tạo staff không gửi plaintext password; invitation chỉ dùng một lần và hết hạn.
5. SMTP fail không làm API báo rollback giả; outbox giữ notification pending.
6. Retry lock/unlock cùng desired state không toggle ngược.
7. Admin cuối cùng không thể demote, delete hoặc lock chính mình.
8. Receptionist không gọi được admin user/price routes và không approve refund nếu thiếu permission.
9. Role change từ tab stale nhận 409 theo entity version.
10. Audit log có đúng actor/before/after và tuyệt đối không có secret.

### 14.4 Cancellation/refund/price

1. Customer chỉ hủy booking của mình ở state được phép; hai submit đồng thời chỉ một thành công.
2. Refund approval lặp chỉ tạo một ledger entry; amount/reference/actor khớp gateway/manual evidence.
3. Receptionist vượt refund threshold cần admin thứ hai duyệt.
4. Percent/fixed adjustment ngoài bound bị từ chối server-side.
5. Hai admin tạo policy overlap đồng thời: đúng một policy active.
6. Update/delete policy không thay đổi price snapshot của booking đã tạo.

---

**Trạng thái:** Audit booking/payment và audit bổ sung Identity/RBAC đã hoàn tất; chưa thực hiện fix. Chờ phê duyệt báo cáo và thứ tự remediation.
