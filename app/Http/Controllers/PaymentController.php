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
            'vietqr'  => $this->showVietQR($booking),
            'momo'    => $this->redirectMomo($booking),
            'zalopay' => $this->redirectZalopay($booking),
            'vnpay'   => $this->redirectVnpay($booking),
            default   => redirect()->route('payment.form', $booking->id)
                ->with('error', 'Rosaliza Hotel chỉ nhận đặt cọc trực tuyến (50% giá trị đơn phòng). Vui lòng chọn cổng thanh toán trực tuyến.'),
        };
    }

    private function lateCheckoutFee(Booking $booking): float
    {
        $deadline = $booking->scheduledCheckoutAt()->addHour();
        if ($booking->waive_late_fee || now('Asia/Ho_Chi_Minh')->lte($deadline)) return 0;

        $inHouseRooms = $booking->rooms->whereIn('status', [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE]);
        $billableRooms = $inHouseRooms->isNotEmpty() ? $inHouseRooms : $booking->rooms;

        return round($billableRooms->sum(fn ($room) => (float) ($room->roomType->price ?? 0)) * .5);
    }

    // ── VietQR ────────────────────────────────────────────
    private function showVietQR(Booking $booking)
    {
        $booking->touch();
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

    public function keepVietQrAlive(Request $request, int $bookingId): JsonResponse
    {
        $booking = Booking::with('rooms')->findOrFail($bookingId);
        $this->authorizeCustomerBooking($booking);
        if (app(ExpirePendingBookingHolds::class)->expireIfDue($booking)) {
            return response()->json(['status' => 'expired'], 410);
        }

        $result = \DB::transaction(function () use ($bookingId): array {
            $locked = Booking::with('rooms')->lockForUpdate()->findOrFail($bookingId);
            if ($locked->isPaid()) {
                return ['status' => 'paid'];
            }
            if ($locked->status !== 'pending' || $locked->payment_status !== 'pending' || $locked->payment_method !== 'vietqr') {
                return ['status' => 'expired'];
            }
            $createdColumn = $locked->getCreatedAtColumn();
            $createdAt = $createdColumn && $locked->getAttribute($createdColumn)
                ? \Carbon\Carbon::parse($locked->getAttribute($createdColumn))
                : null;
            if ($createdAt && $createdAt->lte(now()->subMinutes(30))) {
                $locked->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Đã vượt quá thời gian giữ mã thanh toán tối đa.',
                    'refund_status' => 'none',
                ]);
                PaymentLog::where('booking_id', $locked->id)->where('purpose', 'deposit')->where('status', 'pending')->update(['status' => 'failed']);
                return ['status' => 'expired'];
            }

            $roomIds = $locked->rooms->pluck('id');
            Room::whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
            if (Booking::reservedRoomIds(
                $locked->check_in->toDateString(),
                $locked->check_out->toDateString(),
                $locked->id
            )->intersect($roomIds)->isNotEmpty()) {
                $locked->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Phòng không còn khả dụng khi gia hạn thời gian thanh toán.',
                    'refund_status' => 'none',
                ]);
                PaymentLog::where('booking_id', $locked->id)->where('purpose', 'deposit')->where('status', 'pending')->update(['status' => 'failed']);
                return ['status' => 'expired'];
            }

            $locked->touch();
            $log = PaymentLog::where('booking_id', $locked->id)
                ->where('gateway', 'vietqr')->where('purpose', 'deposit')->where('status', 'pending')
                ->where('reference_code', 'like', 'KS%')->latest()->lockForUpdate()->first();
            $log?->touch();

            return ['status' => 'pending'];
        });

        if ($result['status'] === 'paid') {
            return response()->json(['status' => 'paid', 'redirect_url' => route('payment.success', $bookingId)]);
        }
        if ($result['status'] !== 'pending') {
            return response()->json(['status' => 'expired'], 410);
        }

        $booking->refresh();
        $qrData = $this->vietqr->generateQR($booking);

        return response()->json(['status' => 'pending', 'qr' => $qrData]);
    }

    public function cancelPendingPayment(Request $request, int $bookingId): JsonResponse
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeCustomerBooking($booking);

        $result = \DB::transaction(function () use ($bookingId): string {
            $locked = Booking::with('rooms')->lockForUpdate()->findOrFail($bookingId);
            if ($locked->isPaid() || in_array($locked->status, ['confirmed', 'checked_in', 'completed'], true)) {
                return 'paid';
            }
            if ($locked->status === 'cancelled') {
                return 'cancelled';
            }
            if ($locked->status !== 'pending' || $locked->payment_status !== 'pending' || $locked->payment_method !== 'vietqr') {
                return 'unchanged';
            }

            Room::whereIn('id', $locked->rooms->pluck('id'))->orderBy('id')->lockForUpdate()->get();

            $locked->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Khách rời màn hình thanh toán.',
                'refund_status' => 'none',
            ]);
            PaymentLog::where('booking_id', $locked->id)
                ->where('purpose', 'deposit')->where('status', 'pending')
                ->update(['status' => 'failed']);

            return 'cancelled';
        });

        if ($result === 'paid') {
            return response()->json(['status' => 'paid', 'redirect_url' => route('payment.success', $bookingId)]);
        }

        return response()->json(['status' => $result]);
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
            if ($booking->status === 'pending' && $booking->paymentHoldExpired()) return;

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
                'deposit_amount' => round((float) $booking->total_price / 2),
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
        if (!in_array($booking->status, ['pending', 'confirmed'], true)
            || ($booking->status === 'pending' && $booking->paymentHoldExpired())) {
            $message = 'Đặt phòng đã kết thúc, bị hủy hoặc hết thời gian giữ phòng. Vui lòng kiểm tra lịch sử đặt phòng.';
            if (request()->expectsJson()) {
                abort(409, $message);
            }

            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('booking.mine')->with('error', $message)
            );
        }
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
                Room::whereIn('id', $roomIds)
                    ->whereIn('status', [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE])
                    ->update([
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
            $hotel   = config('app.name', 'Rosaliza Hotel');
            $subject = "Xác nhận đặt phòng & Mã nhận phòng QR - #{$booking->id} | {$hotel}";

            $booking->loadMissing('rooms.roomType');
            $rooms = $booking->rooms;

            $qrBytes = $this->generateQrCodeBytes($booking);
            $logoBytes = '';
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
            $mail->Password   = str_replace(' ', '', (string) ($mailCfg['password'] ?? ''));
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
            $mail->AltBody = preg_replace('/\s+/u', ' ', trim(strip_tags($body))) ?: $subject;
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
        $paid = $b->successfulPaymentAmount();
        $depositPrice = $paid > 0
            ? $paid
            : ((float) $b->deposit_amount > 0 ? (float) $b->deposit_amount : round((float) $b->total_price / 2));
        $remainingPrice = max(0, round((float) $b->total_price - $depositPrice));
        $totalFormatted = number_format((float) $b->total_price, 0, ',', '.').' ₫';
        $depositFormatted = number_format($depositPrice, 0, ',', '.').' ₫';
        $remainingFormatted = number_format($remainingPrice, 0, ',', '.').' ₫';

        $checkInFormatted = ! empty($b->check_in) ? \Carbon\Carbon::parse($b->check_in)->format('d/m/Y') : '-';
        $checkOutFormatted = ! empty($b->check_out) ? \Carbon\Carbon::parse($b->check_out)->format('d/m/Y') : '-';
        $nights = (! empty($b->check_in) && ! empty($b->check_out))
            ? max(1, (int) \Carbon\Carbon::parse($b->check_in)->diffInDays(\Carbon\Carbon::parse($b->check_out)))
            : 1;

        $supportedMethods = [
            'vietqr'  => 'Chuyển khoản VietQR',
            'momo'    => 'Ví MoMo',
            'zalopay' => 'Ví ZaloPay',
            'vnpay'   => 'Cổng VNPay',
            'cash'    => 'Tại quầy lễ tân',
        ];
        $methodLabel = $supportedMethods[$method] ?? $method;

        $refundDeadlineDays = 5;
        try {
            $refundDeadlineDays = $b->refundDeadlineDays();
        } catch (\Throwable) {
            $refundDeadlineDays = 5;
        }

        return view('emails.booking_confirmation', [
            'booking' => $b,
            'rooms' => $rooms,
            'methodLabel' => $methodLabel,
            'transId' => $transId,
            'hotel' => $hotel ?: 'Rosaliza Hotel',
            'hasQr' => $hasQr,
            'hasLogo' => $hasLogo,
            'nights' => $nights,
            'checkInFormatted' => $checkInFormatted,
            'checkOutFormatted' => $checkOutFormatted,
            'totalFormatted' => $totalFormatted,
            'depositFormatted' => $depositFormatted,
            'remainingFormatted' => $remainingFormatted,
            'refundDeadlineDays' => $refundDeadlineDays,
        ])->render();
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
                    if (in_array($room->status, [Room::STATUS_OCCUPIED, Room::STATUS_OVERDUE], true)) {
                        $room->update([
                            'status' => Room::STATUS_CLEANING,
                            'needs_cleaning' => true,
                            'cleaning_requested_at' => now(),
                        ]);
                    }
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
