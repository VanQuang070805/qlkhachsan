<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\Room;
use App\Services\Payment\VietQRService;
use App\Services\Payment\MoMoService;
use App\Services\Payment\ZaloPayService;
use App\Services\Payment\VNPayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Color\Color;

class PaymentController extends Controller
{
    public function __construct(
        protected VietQRService  $vietqr,
        protected MoMoService    $momo,
        protected ZaloPayService $zalopay,
        protected VNPayService   $vnpay,
    ) {}

    /**
     * Hiển thị trang thanh toán theo phương thức đã chọn.
     */
    public function updateMethod(Request $request, int $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->isPaid()) {
            return redirect()->route('payment.success', $bookingId);
        }

        $request->validate([
            'payment_method' => 'required|in:vietqr,momo,zalopay,vnpay',
        ]);

        $booking->update(['payment_method' => $request->payment_method]);

        return redirect()->route('payment.show', $bookingId);
    }
    public function form(int $bookingId)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);

        if ($booking->isPaid()) {
            return redirect()->route('payment.success', $bookingId);
        }

        return view('payment.form', compact('booking'));
    }

    public function show(int $bookingId)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);

        if ($booking->isPaid()) {
            return redirect()->route('payment.success', $bookingId);
        }

        // Kiểm tra phòng có còn available không
        $bookedRoomIds = \DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->where('bookings.payment_status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->where('bookings.id', '!=', $bookingId)
            ->where('bookings.check_in', '<', $booking->check_out)
            ->where('bookings.check_out', '>', $booking->check_in)
            ->pluck('booking_rooms.room_id');

        $conflictRoom = $booking->rooms->first(fn($r) => $bookedRoomIds->contains($r->id));

        if ($conflictRoom) {
            // Hủy booking này vì phòng đã bị người khác đặt
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => 'Phòng đã được đặt bởi khách khác.',
                'refund_status'       => 'none',
            ]);
            foreach ($booking->rooms as $room) {
                $room->update(['status' => \App\Models\Room::STATUS_AVAILABLE]);
            }
            return redirect()->route('booking.mine')
                ->with('error', 'Rất tiếc, phòng ' . $conflictRoom->room_number . ' đã được đặt bởi khách khác. Đặt phòng của bạn đã bị hủy tự động.');
        }

        return match ($booking->payment_method) {
            'vietqr'  => $this->showVietQR($booking),
            'momo'    => $this->redirectMomo($booking),
            'zalopay' => $this->redirectZalopay($booking),
            'vnpay'   => $this->redirectVnpay($booking),
            default   => abort(400, 'Phương thức thanh toán không hợp lệ'),
        };
    }

    // ── VietQR ────────────────────────────────────────────
    private function showVietQR(Booking $booking)
    {
        $qrData = $this->vietqr->generateQR($booking);
        return view('payment.vietqr', compact('booking', 'qrData'));
    }

    /**
     * AJAX polling — frontend gọi mỗi 5 giây để check trạng thái.
     * Khi paid → trả về redirect URL → frontend tự chuyển trang.
     */
    public function checkStatus(int $bookingId): JsonResponse
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->isPaid()) {
            return response()->json([
                'status'       => 'paid',
                'redirect_url' => route('payment.success', $bookingId),
            ]);
        }

        // Polling SePay/MB Bank API để check giao dịch mới
        $transaction = $this->vietqr->checkTransaction($booking);

        if ($transaction) {
            $this->confirmPayment($booking, 'vietqr', $transaction['id'], $transaction);
            return response()->json([
                'status'       => 'paid',
                'redirect_url' => route('payment.success', $bookingId),
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    // ── MoMo Webhook ──────────────────────────────────────
    public function webhookMomo(Request $request): JsonResponse
    {
        $data = $request->all();

        if (!$this->momo->verifySignature($data)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $bookingId = $data['orderId'] ?? null;
        $booking   = Booking::find($bookingId);

        if (!$booking || $booking->isPaid()) {
            return response()->json(['message' => 'OK']);
        }

        if (($data['resultCode'] ?? -1) === 0) { // 0 = success
            $this->confirmPayment($booking, 'momo', $data['transId'], $data);
        } else {
            PaymentLog::create([
                'booking_id'   => $booking->id,
                'gateway'      => 'momo',
                'transaction_id'=> $data['transId'] ?? null,
                'amount'       => $booking->total_price,
                'status'       => 'failed',
                'raw_response' => $data,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    // ── ZaloPay Webhook ───────────────────────────────────
    public function webhookZalopay(Request $request): JsonResponse
    {
        $data = $request->all();

        if (!$this->zalopay->verifyCallback($data)) {
            return response()->json(['return_code' => -1, 'return_message' => 'Invalid MAC']);
        }

        $embedData = json_decode($data['data'] ?? '{}', true);
        $bookingId = $embedData['booking_id'] ?? null;
        $booking   = Booking::find($bookingId);

        if (!$booking || $booking->isPaid()) {
            return response()->json(['return_code' => 1, 'return_message' => 'OK']);
        }

        if (($data['type'] ?? 0) === 1) { // 1 = payment success
            $parsed = json_decode($data['data'], true);
            $this->confirmPayment($booking, 'zalopay', $parsed['zp_trans_id'], $parsed);
        }

        return response()->json(['return_code' => 1, 'return_message' => 'OK']);
    }

    // ── VNPay Webhook (IPN) ───────────────────────────────
    public function webhookVnpay(Request $request): JsonResponse
    {
        $data = $request->all();

        if (!$this->vnpay->verifySecureHash($data)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $bookingId = $data['vnp_TxnRef'] ?? null;
        $booking   = Booking::find($bookingId);

        if (!$booking) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if ($booking->isPaid()) {
            return response()->json(['RspCode' => '02', 'Message' => 'Already updated']);
        }

        if (($data['vnp_ResponseCode'] ?? '') === '00') {
            $this->confirmPayment($booking, 'vnpay', $data['vnp_TransactionNo'], $data);
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm success']);
    }

    /**
     * VNPay redirect người dùng về sau khi thanh toán.
     */
    public function vnpayReturn(Request $request)
    {
        $data = $request->all();

        if (!$this->vnpay->verifySecureHash($data)) {
            return redirect()->route('payment.error', $data['vnp_TxnRef'] ?? 0);
        }

        $bookingId = $data['vnp_TxnRef'] ?? null;

        if (($data['vnp_ResponseCode'] ?? '') === '00') {
            return redirect()->route('payment.success', $bookingId);
        }

        return redirect()->route('payment.error', $bookingId);
    }

    // ── Success / Error pages ─────────────────────────────
    public function success(int $bookingId)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);

        $qr_base64 = '';
        if (class_exists('Endroid\QrCode\QrCode')) {
            try {
                $checkInDate  = \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y');
                $checkOutDate = \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y');

                $qr_data  = "Ma don: #{$booking->id}\n";
                $qr_data .= "Khách hàng: {$booking->customer_name}\n";
                $qr_data .= "Ngày nhận: {$checkInDate}\n";
                $qr_data .= "Ngày trả: {$checkOutDate}\n";
               $depositPrice = $booking->total_price / 2;
$qr_data .= "Tổng tiền phòng: " . number_format($booking->total_price) . " VNĐ\n";
$qr_data .= "Tiền cọc (50%): " . number_format($depositPrice) . " VNĐ\n";
                $qr_data .= "Danh sách phòng:\n";
                foreach ($booking->rooms as $r) {
                    $qr_data .= "- Phòng {$r->room_number} (Loại: " . ($r->roomType->name ?? '') . ")\n";
                }

                $qr = QrCode::create($qr_data)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                    ->setSize(300)
                    ->setMargin(10)
                    ->setForegroundColor(new Color(0, 0, 0))
                    ->setBackgroundColor(new Color(255, 255, 255));

                $writer = new PngWriter();
                $result = $writer->write($qr);
                $qr_base64 = base64_encode($result->getString());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Không thể sinh mã QR base64 cho booking #{$bookingId}: " . $e->getMessage());
            }
        }

        return view('payment.success', compact('booking', 'qr_base64'));
    }

    public function error(int $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        return view('payment.error', compact('booking'));
    }

    // ── Redirect helpers ──────────────────────────────────
    private function redirectMomo(Booking $booking)
    {
        $payUrl = $this->momo->createPaymentUrl($booking);
        return redirect()->away($payUrl);
    }

    private function redirectZalopay(Booking $booking)
    {
        $payUrl = $this->zalopay->createPaymentUrl($booking);
        return redirect()->away($payUrl);
    }

    private function redirectVnpay(Booking $booking)
    {
        $payUrl = $this->vnpay->createPaymentUrl($booking);
        return redirect()->away($payUrl);
    }

    // ── Core: xác nhận thanh toán thành công ─────────────
    private function confirmPayment(Booking $booking, string $gateway, string $transactionId, array $rawData): void
    {
        $confirmed = false;
        \DB::transaction(function () use ($booking, $gateway, $transactionId, $rawData, &$confirmed) {
            $booking = Booking::lockForUpdate()->find($booking->id);
            if ($booking->isPaid()) return;

            PaymentLog::create([
                'booking_id'     => $booking->id,
                'gateway'        => $gateway,
                'transaction_id' => $transactionId,
                'amount'         => $booking->total_price,
                'status'         => 'success',
                'raw_response'   => $rawData,
            ]);

            $booking->update([
                'payment_status' => 'paid',
                'status'         => 'confirmed',
                'deposit_amount' => $booking->total_price / 2,
            ]);

            $confirmed = true;
        });

        if ($confirmed) {
            try {
                $this->sendPaymentConfirmationEmail($booking, $gateway, $transactionId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Gửi email thanh toán thất bại cho booking #{$booking->id}: " . $e->getMessage());
            }
        }
    }

    private function sendPaymentConfirmationEmail(Booking $booking, string $method, string $transId): void
    {
        try {
            $to      = $booking->customer_email;
            $name    = $booking->customer_name;
            $hotel   = config('app.name', 'Royal Hotel');
            $subject = "💳 Thanh toán thành công – Đặt phòng #{$booking->id}";

            $booking->loadMissing('rooms.roomType');
            $rooms = $booking->rooms;

            $qr_file = $this->generateQrCodeFile($booking, $rooms);
            $body = $this->buildPaymentEmailHtml($booking, $rooms, $method, $transId, $hotel);
            
            $mailCfg = [
                'host'       => config('mail.mailers.smtp.host'),
                'port'       => config('mail.mailers.smtp.port'),
                'username'   => config('mail.mailers.smtp.username'),
                'password'   => config('mail.mailers.smtp.password'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_email' => config('mail.from.address'),
                'from_name'  => config('mail.from.name'),
            ];

            $this->sendMailWithPHPMailer($to, $name, $subject, $body, $mailCfg, $qr_file);
            
            if ($qr_file && file_exists($qr_file)) {
                unlink($qr_file);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gửi email thanh toán thất bại cho booking #{$booking->id}: " . $e->getMessage());
        }
    }

    private function generateQrCodeFile(Booking $booking, $rooms): string
    {
        if (!class_exists('Endroid\QrCode\QrCode')) {
            return '';
        }

        $checkInDate  = \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y');
        $checkOutDate = \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y');

        $qr_data  = "Ma don: #{$booking->id}\n";
        $qr_data .= "Khách hàng: {$booking->customer_name}\n";
        $qr_data .= "Ngày nhận: {$checkInDate}\n";
        $qr_data .= "Ngày trả: {$checkOutDate}\n";
        $qr_data .= "Tổng tiền: " . number_format($booking->total_price) . " VNĐ\n";
        $qr_data .= "Danh sách phòng:\n";
        foreach ($rooms as $r) {
            $qr_data .= "- Phòng {$r->room_number} (Loại: " . ($r->roomType->name ?? '') . ")\n";
        }

        $qr = QrCode::create($qr_data)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setSize(300)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();
        $result = $writer->write($qr);

        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $qr_file = $tempDir . "/qr_{$booking->id}.png";
        file_put_contents($qr_file, $result->getString());

        return $qr_file;
    }

    private function sendMailWithPHPMailer(string $to, string $name, string $subject, string $body, array $mailCfg, ?string $attachment = null): void
    {
        if (empty($to)) return;

        if (class_exists('PHPMailer\PHPMailer\PHPMailer') && !empty($mailCfg['host'])) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $mailCfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailCfg['username'];
            $mail->Password   = $mailCfg['password'];
            $mail->SMTPSecure = $mailCfg['encryption'] ?? 'tls';
            $mail->Port       = $mailCfg['port'] ?? 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($mailCfg['from_email'] ?? $mailCfg['username'], $mailCfg['from_name'] ?? 'Khách Sạn');
            $mail->addAddress($to, $name);
            
            if ($attachment && file_exists($attachment)) {
                $mail->addAttachment($attachment, 'CheckIn_QR.png');
            }
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return;
        }

        // Fallback: Laravel default mailer
        \Illuminate\Support\Facades\Mail::html($body, function ($message) use ($to, $name, $subject, $attachment) {
            $message->to($to, $name)
                    ->subject($subject);
            if ($attachment && file_exists($attachment)) {
                $message->attach($attachment, ['as' => 'CheckIn_QR.png']);
            }
        });
    }

    private function buildPaymentEmailHtml(Booking $b, $rooms, string $method, string $transId, string $hotel): string
    {
      $depositPrice = (float)$b->total_price / 2;   
$price = number_format($depositPrice, 0, ',', '.') . ' ₫';
        $supportedMethods = [
            'cash'    => 'Tiền mặt tại quầy',
            'vietqr'  => 'Chuyển khoản VietQR',
            'momo'    => 'Ví MoMo',
            'zalopay' => 'Ví ZaloPay',
            'vnpay'   => 'Cổng VNPay',
        ];
        $methodLabel = $supportedMethods[$method] ?? $method;
        
        $roomListHtml = '';
        foreach ($rooms as $r) {
            $roomListHtml .= "<li>Phòng <strong>{$r->room_number}</strong> - Hạng phòng: " . ($r->roomType->name ?? '') . "</li>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; color: #333333; }
    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header { background-color: #059669; color: #ffffff; padding: 35px 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 26px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    .header p { margin: 10px 0 0; font-size: 15px; opacity: 0.9; }
    .content { padding: 30px; }
    .greeting { font-size: 18px; margin-bottom: 20px; color: #111827; }
    .payment-badge { display: inline-block; background-color: #d1fae5; color: #047857; padding: 10px 20px; border-radius: 30px; font-weight: bold; font-size: 16px; margin-bottom: 25px; border: 1px solid #a7f3d0; letter-spacing: 0.5px; }
    .section-title { font-size: 16px; font-weight: bold; color: #059669; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 15px; margin-top: 25px; text-transform: uppercase; letter-spacing: 0.5px; }
    .details-table { width: 100%; border-collapse: collapse; }
    .details-table td { padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-size: 15px; }
    .details-table td:first-child { color: #6b7280; width: 45%; }
    .details-table td:last-child { font-weight: 600; text-align: right; color: #111827; }
    .room-list { background-color: #f9fafb; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #f3f4f6; }
    .room-list ul { margin: 0; padding-left: 20px; color: #4b5563; }
    .room-list li { margin-bottom: 8px; font-size: 15px; }
    .room-list li:last-child { margin-bottom: 0; }
    .total-box { background-color: #059669; color: #ffffff; padding: 20px; border-radius: 8px; text-align: center; margin-top: 30px; }
    .total-box p { margin: 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
    .total-box h2 { margin: 8px 0 0; font-size: 32px; }
    .qr-notice { text-align: center; background-color: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; margin-top: 25px; font-size: 14px; border: 1px solid #fde68a; line-height: 1.5; }
    .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{$hotel}</h1>
        <p>Giao Dịch Thành Công</p>
    </div>
    <div class="content">
        <div style="text-align: center;">
            <div class="payment-badge">✓ ĐÃ ĐẶT CỌC</div>
        </div>

        <div class="greeting">Kính gửi <strong>{$b->customer_name}</strong>,</div>
       <p style="color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">Cảm ơn quý khách. Khoản đặt cọc 50% tiền phòng của quý khách đã được ghi nhận thành công trên hệ thống. Số tiền còn lại quý khách vui lòng thanh toán khi nhận phòng.</p>
<p>Đã Đặt Cọc (50%)</p>
        
        <div class="section-title">Chi Tiết Giao Dịch</div>
        <table class="details-table">
            <tr><td>Mã đặt phòng</td><td>#{$b->id}</td></tr>
            <tr><td>Mã giao dịch (TransID)</td><td>{$transId}</td></tr>
            <tr><td>Phương thức TT</td><td>{$methodLabel}</td></tr>
            <tr><td>Trạng thái</td><td style="color: #059669;">Thành công</td></tr>
        </table>

        <div class="section-title">Danh Sách Phòng Đã Đặt</div>
        <div class="room-list">
            <ul>{$roomListHtml}</ul>
        </div>

        <div class="total-box">
            <p>Đã Thanh Toán</p>
            <h2>{$price}</h2>
        </div>

        <div class="qr-notice">
            <strong>Lưu ý quan trọng:</strong> Vui lòng lưu lại email này và xuất trình <strong>Mã QR đính kèm</strong> khi đến làm thủ tục nhận phòng tại quầy Lễ tân.
        </div>
    </div>
    <div class="footer">
        <p>&copy; 2026 {$hotel}. All rights reserved.</p>
        <p>Email này được tạo tự động, vui lòng không trả lời.</p>
    </div>
</div>
</body>
</html>
HTML;
    }
    public function staffCheckoutPayment(Request $request, int $bookingId)
    {
        $booking = Booking::with('rooms')->findOrFail($bookingId);
        $waive = $request->boolean('waive_late_fee', false);
        $method = $request->input('payment_method');
        // Tiền mặt — xác nhận luôn
        if ($method === 'cash') {
            \DB::transaction(function () use ($booking, $waive) {
                $now = now();
                $checkoutHour = $now->hour + $now->minute / 60;
                $lateFee = 0;
                $expectedCheckout = \Carbon\Carbon::parse($booking->check_out)->startOfDay();
                $today = now('Asia/Ho_Chi_Minh')->startOfDay();
                $isLateDay = $today->greaterThanOrEqualTo($expectedCheckout); // đúng ngày hoặc muộn hơn

                if ($checkoutHour > 12.5 && $isLateDay && !$waive) {
                    $nightRate = $booking->rooms->sum(fn($r) => (float)($r->roomType->price ?? 0));
                    $lateFee = $checkoutHour <= 18 ? round($nightRate * 0.5, 2) : $nightRate;
                }

                $booking->update([
                    'status'            => 'completed',
                    'actual_check_out'  => now(),
                    'payment_status'    => 'paid',
                    'payment_method'    => 'cash',
                    'late_checkout_fee' => $lateFee,
                ]);
                foreach ($booking->rooms as $room) {
                    $room->update(['status' => Room::STATUS_CLEANING]);
                }
            });
            return response()->json(['success' => true, 'message' => "Checkout booking #{$booking->id} thành công."]);
        }

        // Cổng thanh toán — chỉ lưu phương thức, CHƯA đổi trạng thái
        // Trạng thái sẽ được cập nhật sau khi thanh toán thực sự thành công
        $booking->update([
            'payment_method' => $method,
            'waive_late_fee' => $waive,
        ]);

        $url = match($method) {
            'momo'    => $this->momo->createPaymentUrl($booking),
            'zalopay' => $this->zalopay->createPaymentUrl($booking),
            'vnpay'   => $this->vnpay->createPaymentUrl($booking),
            'vietqr'  => route('staff.bookings.vietqr', $booking->id),
            default   => null,
        };

        if (!$url) {
            return response()->json(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
        }

        return response()->json(['success' => true, 'redirect' => $url]);
    }
    public function staffVietQR(int $bookingId)
    {
        $booking  = Booking::with('rooms.roomType')->findOrFail($bookingId);
        $now      = now();
        $checkoutHour = $now->hour + $now->minute / 60;
        
            
        $lateFee = 0;
        $expectedCheckout = \Carbon\Carbon::parse($booking->check_out)->startOfDay();
        $today = now('Asia/Ho_Chi_Minh')->startOfDay();
        $isLateDay = $today->greaterThanOrEqualTo($expectedCheckout);

        if ($checkoutHour > 12.5 && $isLateDay && !$booking->waive_late_fee) {
            $nightRate = $booking->rooms->sum(fn($r) => (float)($r->roomType->price ?? 0));
            $lateFee   = $checkoutHour <= 18 ? round($nightRate * 0.5, 2) : $nightRate;
        }
        // Lưu fee vào DB luôn để staffCheckStatus dùng lại
        $booking->update(['late_checkout_fee' => $lateFee]);

        $remaining = (int) ($booking->total_price - $booking->deposit_amount + $lateFee);
        $qrData    = $this->vietqr->generateCheckoutQR($booking, $remaining);
        return view('payment.vietqr_checkout', compact('booking', 'qrData', 'remaining'));
    }

    /**
     * AJAX polling cho staff checkout VietQR — gọi mỗi 5 giây.
     * Khi paid → hoàn tất checkout, đổi trạng thái phòng, trả redirect URL.
     */
    public function staffCheckStatus(int $bookingId): JsonResponse
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);

        if ($booking->status === 'completed' && $booking->payment_status === 'paid') {
            return response()->json([
                'status'       => 'paid',
                'redirect_url' => route('staff.bookings.checkout-success', $bookingId),
            ]);
        }

        $transaction = $this->vietqr->checkCheckoutTransaction($booking);

        if ($transaction) {
            \DB::transaction(function () use ($booking, $transaction) {
                $booking = Booking::lockForUpdate()->find($booking->id);
                if ($booking->status === 'completed') return;
                $booking->loadMissing('rooms.roomType');
                $now = now();
                $checkoutHour = $now->hour + $now->minute / 60;
                $lateFee = 0;
                $expectedCheckout = \Carbon\Carbon::parse($booking->check_out)->startOfDay();
                $today = now('Asia/Ho_Chi_Minh')->startOfDay();
                $isLateDay = $today->greaterThanOrEqualTo($expectedCheckout);

                if ($checkoutHour > 12.5 && $isLateDay && !$booking->waive_late_fee) {
                    $nightRate = $booking->rooms->sum(fn($r) => (float)($r->roomType->price ?? 0));
                    $lateFee = $checkoutHour <= 18 ? round($nightRate * 0.5, 2) : $nightRate;
                }
                \App\Models\PaymentLog::create([
                    'booking_id'     => $booking->id,
                    'gateway'        => 'vietqr',
                    'transaction_id' => $transaction['id'],
                    'amount'         => $booking->total_price - $booking->deposit_amount,
                    'status'         => 'success',
                    'raw_response'   => $transaction,
                ]);

                $booking->update([
                    'status'           => 'completed',
                    'actual_check_out' => now(),
                    'payment_status'   => 'paid',
                    'late_checkout_fee' => $lateFee,
                ]);

                foreach ($booking->rooms as $room) {
                    $room->update(['status' => Room::STATUS_CLEANING]);
                }
            });

            return response()->json([
                'status'       => 'paid',
                'redirect_url' => route('staff.bookings.checkout-success', $bookingId),
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Trang xác nhận checkout thành công (dành cho staff).
     */
    public function checkoutSuccess(int $bookingId)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);
        return view('payment.checkout_success', compact('booking'));
    }
    public function momoReturn(Request $request)
{
    $bookingId = $request->input('orderId');
    if ($request->input('resultCode') == 0) {
        return redirect()->route('payment.success', $bookingId);
    }
    return redirect()->route('payment.error', $bookingId);
}
}
