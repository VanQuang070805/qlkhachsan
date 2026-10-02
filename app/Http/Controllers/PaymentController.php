<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\Room;
use App\Services\Payment\VietQRService;
use App\Services\Payment\MoMoService;
use App\Services\Payment\ZaloPayService;
use App\Services\Payment\VNPayService;
use App\Services\ExpirePendingBookingHolds;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $this->authorizeCustomerBooking($booking);
        $this->assertDepositPayable($booking);

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
        $this->authorizeCustomerBooking($booking);
        $this->assertDepositPayable($booking);

        if ($booking->isPaid()) {
            return redirect()->route('payment.success', $bookingId);
        }

        return view('payment.form', compact('booking'));
    }

    public function preview(int $bookingId)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);
        $this->authorizeCustomerBooking($booking);

        return view('payment.form', [
            'booking' => $booking,
            'isPreview' => true,
        ]);
    }

    public function show(int $bookingId)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);
        $this->authorizeCustomerBooking($booking);
        $this->assertDepositPayable($booking);

        if ($booking->isPaid()) {
            return redirect()->route('payment.success', $bookingId);
        }

        // Kiểm tra phòng có còn available không
        $bookedRoomIds = Booking::reservedRoomIds($booking->check_in->toDateString(), $booking->check_out->toDateString(), $bookingId);

        $conflictRoom = $booking->rooms->first(fn($r) => $bookedRoomIds->contains($r->id));

        if ($conflictRoom) {
            // Hủy booking này vì phòng đã bị người khác đặt
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => 'Phòng đã được đặt bởi khách khác.',
                'refund_status'       => 'none',
            ]);

            return redirect()->route('booking.mine')
                ->with('error', 'Rất tiếc, phòng ' . $conflictRoom->room_number . ' đã được đặt bởi khách khác. Đặt phòng của bạn đã bị hủy tự động.');
        }

        return match ($booking->payment_method) {
            'cash'    => $this->confirmCashReservation($booking),
            'vietqr'  => $this->showVietQR($booking),
            'momo'    => $this->redirectMomo($booking),
            'zalopay' => $this->redirectZalopay($booking),
            'vnpay'   => $this->redirectVnpay($booking),
            default   => abort(400, 'Phương thức thanh toán không hợp lệ'),
        };
    }

    private function confirmCashReservation(Booking $booking)
    {
        $booking->update(['status' => 'confirmed', 'payment_status' => 'pending', 'payment_method' => 'cash']);
        return redirect()->route('booking.success', $booking->id)
            ->with('success', 'Đặt phòng đã được giữ. Vui lòng thanh toán tiền mặt tại quầy.');
    }

    private function lateCheckoutFee(Booking $booking): float
    {
        if ($booking->waive_late_fee || now('Asia/Ho_Chi_Minh')->lte(
            \Carbon\Carbon::parse($booking->check_out, 'Asia/Ho_Chi_Minh')->setTime(13, 0)
        )) return 0;

        return round($booking->rooms->sum(fn ($room) => (float) ($room->roomType->price ?? 0)) * .5, 2);
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
        $this->authorizeCustomerBooking($booking);

        if (app(ExpirePendingBookingHolds::class)->expireIfDue($booking)) {
            return response()->json(['status' => 'expired'], 410);
        }
        $booking->refresh();

        if ($booking->status === 'cancelled') {
            return response()->json(['status' => 'expired'], 410);
        }

        if ($booking->isPaid()) {
            return response()->json([
                'status'       => 'paid',
                'redirect_url' => route('payment.success', $bookingId),
            ]);
        }

        // Polling SePay/MB Bank API để check giao dịch mới
        $transaction = $this->vietqr->checkTransaction($booking);

        if ($transaction) {
            if ($this->confirmPayment($booking, 'vietqr', (string) $transaction['id'], $transaction)) {
                return response()->json([
                    'status'       => 'paid',
                    'redirect_url' => route('payment.success', $bookingId),
                ]);
            }

            return response()->json(['status' => 'expired'], 409);
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

        $orderParts = explode('_', (string) ($data['orderId'] ?? '0'));
        $bookingId = (int) ($orderParts[0] ?? 0);
        $purpose = ($orderParts[1] ?? 'deposit') === 'checkout' ? 'checkout' : 'deposit';
        $booking   = Booking::find($bookingId);

        if (!$booking || ($purpose === 'deposit' && $booking->isPaid())) {
            return response()->json(['message' => 'OK']);
        }

        $expected = $purpose === 'checkout' ? $this->checkoutAmount($booking) : (float) $booking->deposit_amount;
        if (($data['resultCode'] ?? -1) === 0 && $this->amountMatches($expected, $data['amount'] ?? null)) {
            $confirmed = $purpose === 'checkout'
                ? $this->confirmCheckoutPayment($booking, 'momo', (string) $data['transId'], (float) $data['amount'], $data)
                : $this->confirmPayment($booking, 'momo', (string) $data['transId'], $data);
            if (!$confirmed) {
                $this->recordRejectedPayment($booking, 'momo', (string) $data['transId'], $expected, $purpose, $data);
                return response()->json(['message' => 'Payment state conflict'], 409);
            }
        } else {
            PaymentLog::create([
                'booking_id'   => $booking->id,
                'gateway'      => 'momo',
                'transaction_id'=> $data['transId'] ?? null,
                'amount'       => $expected,
                'purpose'      => $purpose,
                'status'       => 'failed',
                'raw_response' => $this->paymentEvidence($data),
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

        $callbackData = json_decode($data['data'] ?? '{}', true) ?: [];
        $embedData = json_decode($callbackData['embed_data'] ?? '{}', true) ?: [];
        $bookingId = $embedData['booking_id'] ?? null;
        $purpose = ($embedData['purpose'] ?? 'deposit') === 'checkout' ? 'checkout' : 'deposit';
        $booking   = Booking::find($bookingId);

        if (!$booking || ($purpose === 'deposit' && $booking->isPaid())) {
            return response()->json(['return_code' => 1, 'return_message' => 'OK']);
        }

        $expected = $purpose === 'checkout' ? $this->checkoutAmount($booking) : (float) $booking->deposit_amount;
        if ($this->amountMatches($expected, $callbackData['amount'] ?? null)) {
            $confirmed = $purpose === 'checkout'
                ? $this->confirmCheckoutPayment($booking, 'zalopay', (string) ($callbackData['zp_trans_id'] ?? ''), (float) $callbackData['amount'], $callbackData)
                : $this->confirmPayment($booking, 'zalopay', (string) ($callbackData['zp_trans_id'] ?? ''), $callbackData);
            if (!$confirmed) {
                $this->recordRejectedPayment($booking, 'zalopay', (string) ($callbackData['zp_trans_id'] ?? ''), $expected, $purpose, $callbackData);
                return response()->json(['return_code' => -1, 'return_message' => 'Payment state conflict']);
            }
        } else {
            return response()->json(['return_code' => -1, 'return_message' => 'Invalid amount']);
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

        $txnParts = explode('_', (string) ($data['vnp_TxnRef'] ?? ''));
        $bookingId = (int) ($txnParts[0] ?? 0);
        $purpose = ($txnParts[1] ?? 'deposit') === 'checkout' ? 'checkout' : 'deposit';
        $booking   = Booking::find($bookingId);

        if (!$booking) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if ($purpose === 'deposit' && $booking->isPaid()) {
            return response()->json(['RspCode' => '02', 'Message' => 'Already updated']);
        }

        $amount = isset($data['vnp_Amount']) ? ((float) $data['vnp_Amount'] / 100) : null;
        $expected = $purpose === 'checkout' ? $this->checkoutAmount($booking) : (float) $booking->deposit_amount;
        if (($data['vnp_ResponseCode'] ?? '') === '00' && $this->amountMatches($expected, $amount)) {
            $confirmed = $purpose === 'checkout'
                ? $this->confirmCheckoutPayment($booking, 'vnpay', (string) $data['vnp_TransactionNo'], (float) $amount, $data)
                : $this->confirmPayment($booking, 'vnpay', (string) $data['vnp_TransactionNo'], $data);
            if (!$confirmed) {
                $this->recordRejectedPayment($booking, 'vnpay', (string) $data['vnp_TransactionNo'], $expected, $purpose, $data);
                return response()->json(['RspCode' => '99', 'Message' => 'Payment state conflict']);
            }
        } else {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount or status']);
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

        $bookingId = (int) explode('_', (string) ($data['vnp_TxnRef'] ?? '0'))[0];

        if (($data['vnp_ResponseCode'] ?? '') === '00') {
            return redirect()->route('payment.success', $bookingId);
        }

        return redirect()->route('payment.error', $bookingId);
    }

    // ── Success / Error pages ─────────────────────────────
    public function success(int $bookingId)
    {
        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);
        $this->authorizeCustomerBooking($booking);
        abort_unless($booking->isPaid() && in_array($booking->status, ['confirmed', 'checked_in', 'completed'], true), 409, 'Thanh toán chưa được xác nhận.');

        $checkin_token = in_array($booking->status, ['confirmed', 'checked_in'], true)
            ? app(\App\Services\CheckInTokenService::class)->qrPayload($booking)
            : null;
        $qr_base64 = '';
        if ($checkin_token && class_exists('Endroid\QrCode\QrCode')) {
            try {
                $qr = QrCode::create($checkin_token)
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

        return view('payment.success', compact('booking', 'checkin_token', 'qr_base64'));
    }

    public function error(int $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeCustomerBooking($booking);
        return view('payment.error', compact('booking'));
    }

    private function authorizeCustomerBooking(Booking $booking): void
    {
        abort_unless(Auth::check() && (int) $booking->user_id === (int) Auth::id(), 403);
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
    private function confirmPayment(Booking $booking, string $gateway, string $transactionId, array $rawData): bool
    {
        if ($transactionId === '') return false;
        app(ExpirePendingBookingHolds::class)->expireIfDue($booking);

        $lock = Cache::lock('payment:'.$gateway.':'.$transactionId, 15);
        if (!$lock->get()) return false;

        try {
            $confirmed = false;
            \DB::transaction(function () use ($booking, $gateway, $transactionId, $rawData, &$confirmed) {
            $booking = Booking::lockForUpdate()->find($booking->id);
            if (!$booking) return;
            $existing = PaymentLog::where('gateway', $gateway)->where('transaction_id', $transactionId)->lockForUpdate()->first();
            if ($existing) {
                $confirmed = $existing->status === 'success'
                    && (int) $existing->booking_id === (int) $booking->id
                    && $this->amountMatches((float) $booking->deposit_amount, $existing->amount);
                return;
            }
            if ($booking->isPaid() || !in_array($booking->status, ['pending', 'confirmed'], true)) return;
            $createdAt = $booking->{$booking->getCreatedAtColumn()};
            if ($booking->status === 'pending' && $createdAt && $createdAt->lte(now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES))) return;

            $roomIds = $booking->rooms()->orderBy('rooms.id')->pluck('rooms.id');
            Room::whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
            if (Booking::reservedRoomIds(
                $booking->check_in->toDateString(),
                $booking->check_out->toDateString(),
                $booking->id
            )->intersect($roomIds)->isNotEmpty()) return;

            $log = PaymentLog::where('booking_id', $booking->id)
                ->where('gateway', $gateway)
                ->where('status', 'pending')
                ->whereNull('transaction_id')
                ->latest()
                ->lockForUpdate()
                ->first() ?? new PaymentLog(['booking_id' => $booking->id, 'gateway' => $gateway]);
            $log->fill([
                'booking_id'     => $booking->id,
                'gateway'        => $gateway,
                'transaction_id' => $transactionId,
                'amount'         => $booking->deposit_amount,
                'purpose'        => 'deposit',
                'status'         => 'success',
                'raw_response'   => $this->paymentEvidence($rawData),
            ])->save();

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
                    \Illuminate\Support\Facades\Log::warning('Payment receipt delivery failed', ['booking_id' => $booking->id, 'exception' => get_class($e)]);
                }
            }

            return $confirmed;
        } finally {
            $lock->release();
        }
    }

    private function assertDepositPayable(Booking $booking): void
    {
        app(ExpirePendingBookingHolds::class)->expireIfDue($booking);
        $booking->refresh();
        abort_unless(in_array($booking->status, ['pending', 'confirmed'], true), 409, 'Đặt phòng không còn khả dụng để thanh toán.');
        $createdAt = $booking->{$booking->getCreatedAtColumn()};
        abort_if($booking->status === 'pending' && $createdAt && $createdAt->lte(now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES)), 409, 'Thời gian giữ phòng đã hết.');
    }

    private function confirmCheckoutPayment(Booking $booking, string $gateway, string $transactionId, float $amount, array $rawData): bool
    {
        if ($transactionId === '') return false;
        $lock = Cache::lock('payment:'.$gateway.':'.$transactionId, 15);
        if (!$lock->get()) return false;

        try {
            return \DB::transaction(function () use ($booking, $gateway, $transactionId, $amount, $rawData) {
                $booking = Booking::with('rooms.roomType')->lockForUpdate()->find($booking->id);
                if (!$booking) return false;
                $existing = PaymentLog::where('gateway', $gateway)->where('transaction_id', $transactionId)->lockForUpdate()->first();
                if ($existing) {
                    return $existing->status === 'success'
                        && (int) $existing->booking_id === (int) $booking->id
                        && $this->amountMatches($amount, $existing->amount);
                }
                if ($booking->status !== 'checked_in') return false;

                $roomIds = $booking->rooms->pluck('id');
                Room::whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
                $lateFee = $this->lateCheckoutFee($booking);
                $booking->late_checkout_fee = $lateFee;
                $expected = $booking->outstandingAmount();
                if (!$this->amountMatches($expected, $amount)) return false;

                $log = PaymentLog::where('booking_id', $booking->id)
                    ->where('gateway', $gateway)->where('purpose', 'checkout')
                    ->where('status', 'pending')->whereNull('transaction_id')
                    ->latest()->lockForUpdate()->first()
                    ?? new PaymentLog(['booking_id' => $booking->id, 'gateway' => $gateway]);
                $log->fill([
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'purpose' => 'checkout',
                    'status' => 'success',
                    'raw_response' => $this->paymentEvidence($rawData),
                ])->save();

                $booking->update([
                    'status' => 'completed',
                    'actual_check_out' => now(),
                    'payment_status' => 'paid',
                    'payment_method' => $gateway,
                    'late_checkout_fee' => $lateFee,
                ]);
                Room::whereIn('id', $roomIds)->update([
                    'status' => Room::STATUS_CLEANING,
                    'needs_cleaning' => true,
                    'cleaning_requested_at' => now(),
                ]);
                return true;
            });
        } finally {
            $lock->release();
        }
    }

    private function amountMatches(float $expected, mixed $amount): bool
    {
        return is_numeric($amount) && abs((float) $amount - $expected) < 0.01;
    }

    private function paymentEvidence(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'id', 'transId', 'zp_trans_id', 'vnp_TransactionNo',
            'resultCode', 'return_code', 'vnp_ResponseCode', 'amount', 'vnp_Amount',
        ]));
    }

    private function recordRejectedPayment(Booking $booking, string $gateway, string $transactionId, float $amount, string $purpose, array $payload): void
    {
        if ($transactionId === '' || PaymentLog::where('gateway', $gateway)->where('transaction_id', $transactionId)->exists()) {
            return;
        }

        PaymentLog::create([
            'booking_id' => $booking->id,
            'gateway' => $gateway,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'purpose' => $purpose,
            'status' => 'failed',
            'raw_response' => $this->paymentEvidence($payload) + ['reason' => 'state_conflict'],
        ]);
    }

    private function amountMatchesDeposit(Booking $booking, mixed $amount): bool
    {
        return $this->amountMatches((float) $booking->deposit_amount, $amount);
    }

    private function checkoutAmount(Booking $booking): float
    {
        $booking->loadMissing('rooms.roomType');
        $booking->late_checkout_fee = $this->lateCheckoutFee($booking);
        return $booking->outstandingAmount();
    }

    private function sendPaymentConfirmationEmail(Booking $booking, string $method, string $transId): void
    {
        try {
            $to      = $booking->customer_email;
            $name    = $booking->customer_name;
            $hotel   = config('app.name', 'Posh Boutique');
            $subject = "Xác nhận đặt phòng & Mã nhận phòng QR – #{$booking->id} | {$hotel}";

            $booking->loadMissing('rooms.roomType');
            $rooms = $booking->rooms;

            $qrBytes = $this->generateQrCodeBytes($booking);
            $logoPath = public_path('aura-logo-white.png');
            $logoBytes = is_file($logoPath) ? file_get_contents($logoPath) : '';
            $body = $this->buildPaymentEmailHtml($booking, $rooms, $method, $transId, $hotel, $qrBytes !== '', $logoBytes !== '');
            
            $mailCfg = [
                'host'       => config('mail.mailers.smtp.host'),
                'port'       => config('mail.mailers.smtp.port'),
                'username'   => config('mail.mailers.smtp.username'),
                'password'   => config('mail.mailers.smtp.password'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_email' => config('mail.from.address'),
                'from_name'  => config('mail.from.name'),
            ];

            $this->sendMailWithPHPMailer($to, $name, $subject, $body, $mailCfg, $qrBytes, $logoBytes);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Payment receipt delivery failed', ['booking_id' => $booking->id, 'exception' => get_class($e)]);
        }
    }

    private function generateQrCodeBytes(Booking $booking): string
    {
        if (!class_exists('Endroid\QrCode\QrCode')) {
            return '';
        }

        $qr_data = app(\App\Services\CheckInTokenService::class)->qrPayload($booking);

        $qr = QrCode::create($qr_data)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setSize(300)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();
        return $writer->write($qr)->getString();
    }

    private function sendMailWithPHPMailer(string $to, string $name, string $subject, string $body, array $mailCfg, ?string $qrBytes = null, ?string $logoBytes = null): void
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
            
            if ($logoBytes) {
                $mail->addStringEmbeddedImage($logoBytes, 'royal-hotel-logo', 'Royal_Hotel_Logo.png', PHPMailer::ENCODING_BASE64, 'image/png');
            }
            if ($qrBytes) {
                $mail->addStringEmbeddedImage($qrBytes, 'checkin-qr', 'CheckIn_QR.png', PHPMailer::ENCODING_BASE64, 'image/png');
                $mail->addStringAttachment($qrBytes, 'CheckIn_QR.png', PHPMailer::ENCODING_BASE64, 'image/png');
            }
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return;
        }

        // Fallback: Laravel default mailer
        \Illuminate\Support\Facades\Mail::html($body, function ($message) use ($to, $name, $subject, $qrBytes, $logoBytes) {
            $message->to($to, $name)
                    ->subject($subject);
            $replacements = [];
            if ($logoBytes) {
                $replacements['cid:royal-hotel-logo'] = $message->embedData($logoBytes, 'Royal_Hotel_Logo.png', 'image/png');
            }
            if ($qrBytes) {
                $replacements['cid:checkin-qr'] = $message->embedData($qrBytes, 'CheckIn_QR.png', 'image/png');
                $message->attachData($qrBytes, 'CheckIn_QR.png', ['mime' => 'image/png']);
            }
            if ($replacements) {
                $message->getSymfonyMessage()->html(str_replace(array_keys($replacements), array_values($replacements), $body));
            }
        });
    }

    private function buildPaymentEmailHtml(Booking $b, $rooms, string $method, string $transId, string $hotel, bool $hasQr = false, bool $hasLogo = false): string
    {
        $escape = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $customerName = $escape($b->customer_name);
        $hotel = $escape($hotel);
        $transId = $escape($transId);
        $depositPrice = (float)$b->total_price / 2;
        $remainingPrice = (float)$b->total_price - $depositPrice;
        $totalFormatted = number_format((float)$b->total_price, 0, ',', '.') . ' ₫';
        $depositFormatted = number_format($depositPrice, 0, ',', '.') . ' ₫';
        $remainingFormatted = number_format($remainingPrice, 0, ',', '.') . ' ₫';

        $checkInFormatted = !empty($b->check_in) ? \Carbon\Carbon::parse($b->check_in)->format('d/m/Y') : '—';
        $checkOutFormatted = !empty($b->check_out) ? \Carbon\Carbon::parse($b->check_out)->format('d/m/Y') : '—';

        $supportedMethods = [
            'cash'    => 'Tiền mặt tại quầy',
            'vietqr'  => 'Chuyển khoản VietQR',
            'momo'    => 'Ví MoMo',
            'zalopay' => 'Ví ZaloPay',
            'vnpay'   => 'Cổng VNPay',
        ];
        $methodLabel = $escape($supportedMethods[$method] ?? $method);

        $roomRowsHtml = '';
        foreach ($rooms as $r) {
            $typeName = $escape($r->roomType->type_name ?? $r->roomType->name ?? 'Tiêu chuẩn');
            $roomRowsHtml .= '<tr><td style="padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:14px; color:#1e293b;">Phòng <strong>'.$escape($r->room_number).'</strong></td><td style="padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:14px; text-align:right; color:#64748b;">Hạng: <strong style="color:#0f172a;">'.$typeName.'</strong></td></tr>';
        }

        $qrCodeBlock = '';
        if ($hasQr) {
            $qrCodeBlock = <<<QR
            <div style="text-align: center; margin: 16px 0 8px 0;">
                <img src="cid:checkin-qr" width="165" height="165" alt="Mã QR Check-in" style="display:inline-block; border-radius:12px; border:1px solid #cbd5e1; padding:8px; background:#ffffff;">
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b; font-weight: 500;">Mã QR xác thực làm thủ tục nhận phòng nhanh</p>
            </div>
QR;
        }

        $logoImgHtml = $hasLogo ? '<img src="cid:royal-hotel-logo" alt="Posh Boutique Logo" class="header-logo" width="105" style="display:block; margin:0 auto 10px auto; max-width:105px; height:auto;">' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Xác nhận đặt phòng - {$hotel}</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f6f5f2; margin: 0; padding: 24px 12px; color: #1e293b; -webkit-font-smoothing: antialiased; }
    .email-wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); border: 1px solid #e7e5df; }
    .email-header { background: linear-gradient(180deg, #0b0c10 0%, #141722 100%); color: #ffffff; padding: 34px 24px 30px; text-align: center; border-bottom: 2px solid #d4af37; }
    .header-logo { display: block; margin: 0 auto 10px auto; max-width: 105px; height: auto; }
    .hotel-name { margin: 0; font-family: 'Georgia', serif; font-size: 22px; letter-spacing: 3px; text-transform: uppercase; color: #ffffff; font-weight: 600; }
    .header-sub { margin: 8px 0 0; font-size: 11px; letter-spacing: 1.6px; text-transform: uppercase; color: #e2e8f0; font-weight: 500; }
    .email-content { padding: 32px 28px; }
    .greeting { font-size: 16px; margin-bottom: 12px; color: #0f172a; font-weight: 600; }
    .intro-text { color: #475569; font-size: 14px; line-height: 1.65; margin-bottom: 20px; }
    .card-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 18px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02); }
    .card-title { font-size: 12.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.8px; margin: 0 0 12px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
    .info-table td:first-child { color: #64748b; width: 44%; }
    .info-table td:last-child { font-weight: 600; text-align: right; color: #0f172a; }
    .info-table tr:last-child td { border-bottom: none; }
    .qr-card { background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 20px; text-align: center; margin: 20px 0; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03); }
    .qr-badge { display: inline-block; background: #0071e3; color: #ffffff; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .qr-instruction { font-size: 13px; color: #475569; line-height: 1.55; margin: 10px 0 0 0; }
    .total-banner { background: #0c0d12; color: #ffffff; padding: 20px; border-radius: 14px; text-align: center; margin: 22px 0; border: 1px solid rgba(255,255,255,0.08); }
    .total-banner p { margin: 0; font-size: 11.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 600; }
    .total-banner h2 { margin: 6px 0 0; font-size: 28px; font-weight: 700; color: #38bdf8; font-family: -apple-system, sans-serif; }
    .signoff { margin-top: 20px; font-size: 13.5px; color: #334155; line-height: 1.5; }
    .email-footer { background-color: #f8fafc; padding: 22px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; line-height: 1.6; }
    .email-footer p { margin: 3px 0; }
</style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-header">
        {$logoImgHtml}
        <h1 class="hotel-name">POSH BOUTIQUE</h1>
        <p class="header-sub">Xác Nhận Đặt Phòng &amp; Mã Nhận Phòng QR</p>
    </div>
    <div class="email-content">
        <div class="greeting">Kính gửi quý khách <strong>{$customerName}</strong>,</div>
        <p class="intro-text">
            Posh Boutique xin trân trọng thông báo yêu cầu đặt phòng của quý khách đã được ghi nhận thành công trên hệ thống. Khoản tiền cọc 50% đã được xác nhận thanh toán an toàn. Số tiền còn lại quý khách sẽ thanh toán khi làm thủ tục nhận phòng tại khách sạn.
        </p>

        <!-- Fast Check-in QR Section -->
        <div class="qr-card">
            <span class="qr-badge">Dịch Vụ Nhận Phòng Nhanh</span>
            <h3 style="margin: 6px 0 0 0; font-size: 15px; color: #0f172a; font-weight: 700;">MÃ QR NHẬN PHÒNG TỰ ĐỘNG</h3>
            {$qrCodeBlock}
            <p class="qr-instruction">
                Quý khách vui lòng xuất trình <strong>Mã QR ở trên</strong> tại quầy Lễ tân để hoàn tất thủ tục nhận phòng tức thì trong 5 giây mà không cần điền giấy tờ.
            </p>
        </div>

        <!-- Reservation Details -->
        <div class="card-box">
            <div class="card-title">Chi Tiết Kỳ Nghỉ</div>
            <table class="info-table">
                <tr><td>Mã đặt phòng</td><td>#{$b->id}</td></tr>
                <tr><td>Ngày nhận phòng</td><td>{$checkInFormatted} (từ 14:00)</td></tr>
                <tr><td>Ngày trả phòng</td><td>{$checkOutFormatted} (trước 12:00)</td></tr>
                <tr><td>Phương thức thanh toán</td><td>{$methodLabel}</td></tr>
                <tr><td>Trạng thái đặt phòng</td><td style="color: #15803d;">✓ Đã xác nhận</td></tr>
            </table>
        </div>

        <!-- Room List -->
        <div class="card-box">
            <div class="card-title">Danh Sách Phòng Đã Đặt</div>
            <table class="info-table">
                {$roomRowsHtml}
            </table>
        </div>

        <!-- Payment Breakdown -->
        <div class="card-box">
            <div class="card-title">Chi Tiết Thanh Toán</div>
            <table class="info-table">
                <tr><td>Tổng tiền phòng dự kiến</td><td>{$totalFormatted}</td></tr>
                <tr><td>Đã đặt cọc thanh toán (50%)</td><td style="color: #0071e3;">{$depositFormatted}</td></tr>
                <tr><td>Còn lại thanh toán khi nhận phòng</td><td style="color: #0f172a; font-size: 14.5px;">{$remainingFormatted}</td></tr>
            </table>
        </div>

        <div class="total-banner">
            <p>Số Tiền Đã Thanh Toán</p>
            <h2>{$depositFormatted}</h2>
        </div>

        <div class="signoff">
            Trân trọng phục vụ,<br>
            <strong style="color: #0f172a;">Ban Quản lý Posh Boutique</strong>
        </div>
    </div>
    <div class="email-footer">
        <p><strong>Posh Boutique</strong> — Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội</p>
        <p>Hotline: 024 3828 9999 | Email: contact@poshboutique.vn</p>
        <p style="font-size: 11px; color: #94a3b8; margin-top: 8px;">&copy; 2026 Posh Boutique. All rights reserved.</p>
    </div>
</div>
</body>
</html>
HTML;
    }
    public function staffCheckoutPayment(Request $request, int $bookingId)
    {
        $request->validate(['payment_method'=>'required|in:cash,vietqr,momo,zalopay,vnpay', 'waive_late_fee'=>'sometimes|boolean']);
        $booking = Booking::with('rooms')->findOrFail($bookingId);
        abort_unless($booking->status === 'checked_in', 409, 'Chỉ có thể trả phòng cho booking đang lưu trú.');
        $waive = $request->boolean('waive_late_fee', false);
        $method = $request->input('payment_method');
        // Tiền mặt — xác nhận luôn
        if ($method === 'cash') {
            \DB::transaction(function () use ($booking, $waive) {
                $booking = Booking::with('rooms.roomType')->lockForUpdate()->findOrFail($booking->id);
                abort_unless($booking->status === 'checked_in', 409, 'Booking đã được xử lý.');
                $booking->waive_late_fee = $waive;
                $lateFee = $this->lateCheckoutFee($booking);
                $booking->late_checkout_fee = $lateFee;
                $outstanding = $booking->outstandingAmount();

                if ($outstanding > 0) {
                    PaymentLog::create([
                        'booking_id' => $booking->id,
                        'gateway' => 'cash',
                        'transaction_id' => 'cash-'.$booking->id.'-'.\Illuminate\Support\Str::uuid(),
                        'amount' => $outstanding,
                        'purpose' => 'checkout',
                        'status' => 'success',
                        'raw_response' => ['received_by' => Auth::id()],
                    ]);
                }

                $booking->update([
                    'status'            => 'completed',
                    'actual_check_out'  => now(),
                    'payment_status'    => 'paid',
                    'payment_method'    => 'cash',
                    'late_checkout_fee' => $lateFee,
                ]);
                foreach ($booking->rooms as $room) {
                    $room->update([
                        'status' => Room::STATUS_CLEANING,
                        'needs_cleaning' => true,
                        'cleaning_requested_at' => now(),
                    ]);
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

        $booking->loadMissing('rooms.roomType');
        $lateFee = $this->lateCheckoutFee($booking);
        $booking->update(['late_checkout_fee' => $lateFee]);
        $amount = (int) round($booking->fresh()->outstandingAmount());
        abort_if($amount <= 0, 409, 'Booking không còn số dư cần thanh toán.');

        $url = match($method) {
            'momo'    => $this->momo->createPaymentUrl($booking, $amount, 'checkout'),
            'zalopay' => $this->zalopay->createPaymentUrl($booking, $amount, 'checkout'),
            'vnpay'   => $this->vnpay->createPaymentUrl($booking, $amount, 'checkout'),
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
        abort_unless($booking->status === 'checked_in', 409, 'Chỉ có thể thanh toán trả phòng cho booking đang lưu trú.');
        $lateFee = $this->lateCheckoutFee($booking);
        // Lưu fee vào DB luôn để staffCheckStatus dùng lại
        $booking->update(['late_checkout_fee' => $lateFee]);

        $remaining = (int) round($booking->fresh()->outstandingAmount());
        abort_if($remaining <= 0, 409, 'Booking không còn số dư cần thanh toán.');
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
            $pending = PaymentLog::where('booking_id', $booking->id)->where('gateway', 'vietqr')
                ->where('purpose', 'checkout')->where('status', 'pending')->latest()->first();
            if (!$pending || !$this->confirmCheckoutPayment($booking, 'vietqr', (string) $transaction['id'], (float) $pending->amount, $transaction)) {
                return response()->json(['status' => 'conflict'], 409);
            }

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
    $parts = explode('_', (string) $request->input('orderId', '0'));
    $bookingId = (int) ($parts[0] ?? 0);
    $purpose = ($parts[1] ?? 'deposit') === 'checkout' ? 'checkout' : 'deposit';
    $booking = Booking::findOrFail($bookingId);
    if ($purpose === 'checkout') {
        return $booking->status === 'completed'
            ? redirect()->route('staff.bookings.checkout-success', $bookingId)
            : redirect()->route('staff.bookings');
    }
    if ($booking->isPaid()) {
        return redirect()->route('payment.success', $bookingId);
    }
    return redirect()->route('payment.error', $bookingId);
}
}
