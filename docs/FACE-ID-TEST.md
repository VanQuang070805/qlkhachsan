# Kiểm thử Face ID

## Đăng ký khuôn mặt

1. Bảo đảm PC service, Laravel và Pi API đều đang chạy.
2. Đăng nhập tài khoản lễ tân hoặc admin.
3. Mở `/staff/iot` và kiểm tra ba trạng thái dịch vụ.
4. Chọn booking đang check-in, bật webcam và xác nhận sự đồng ý của khách.
5. Bấm `Đăng ký Face ID`, giữ một khuôn mặt trong khung đến `15/15`.
6. Bấm `Retry ngay` hoặc chờ scheduler; số yêu cầu chờ phải về `0`.

Không có ảnh nào được lưu. Nếu đăng ký lại cùng booking, profile được cập nhật
và queue cũ được thay thế thay vì tạo vô hạn job trùng.

## Pi offline và retry

1. Tắt `run_api.py` hoặc Pi thật.
2. Đăng ký Face ID; đăng ký trên PC vẫn phải thành công.
3. Kiểm tra `Yêu cầu chờ` lớn hơn `0`.
4. Bật Pi lại và chạy `php artisan face:sync` hoặc chờ scheduler.
5. Kiểm tra queue về `0` và health Pi tăng số face.

## Checkout và DELETE offline

1. Với khách đã đăng ký Face ID, tắt Pi rồi checkout bằng luồng hiện tại.
2. Booking phải checkout bình thường; Face ID trên PC chuyển inactive và DELETE
   vẫn PENDING.
3. Bật Pi, chạy `php artisan face:sync`.
4. Pi phải xóa profile; khách cũ được nhận là UNKNOWN nếu không còn profile hợp lệ.

## Full sync

Sau khi reset `faces.db` trên Pi:

```powershell
php artisan face:sync --full
```

Kết quả trả về gồm `received`, `added`, `updated`, `deleted`. Payload không hợp
lệ bị từ chối trước transaction và không xóa cache hiện tại.

## Hiệu chỉnh threshold

Chạy `test_recognition.py` với cùng người, người khác, nhiều điều kiện ánh sáng,
góc mặt và khoảng cách. Điều chỉnh `RECOGNITION_THRESHOLD` tại một nơi trong
`.env`; không thay magic number trong mã nguồn.
