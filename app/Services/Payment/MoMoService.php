<?php

namespace App\Services\Payment;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;

// ============================================================
// MoMoService
// ============================================================
class MoMoService
{
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $notifyUrl;
    private string $returnUrl;

    public function __construct()
    {
        $this->partnerCode = (string) config('payment.momo.partner_code', '');
        $this->accessKey   = (string) config('payment.momo.access_key', '');
        $this->secretKey   = (string) config('payment.momo.secret_key', '');
        $this->endpoint    = (string) config('payment.momo.endpoint', 'https://payment.momo.vn/v2/gateway/api/create');
        $this->notifyUrl   = route('webhook.momo');
        $this->returnUrl   = (string) config('payment.momo.return_url', route('home'));
    }

    public function createPaymentUrl(Booking $booking, ?int $amount = null, string $purpose = 'deposit'): string
    {
        $orderId    = $booking->id . '_' . $purpose . '_' . time();
        $requestId  = $this->partnerCode . time();
        $amount = $amount ?? (int) ($booking->deposit_amount > 0 ? $booking->deposit_amount : $booking->total_price);
        $orderInfo  = "Thanh toán đặt phòng #" . $booking->id;
        $requestType = 'payWithMethod';
        $extraData  = '';

        $rawHash = "accessKey={$this->accessKey}"
            . "&amount={$amount}"
            . "&extraData={$extraData}"
            . "&ipnUrl={$this->notifyUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$orderInfo}"
            . "&partnerCode={$this->partnerCode}"
            . "&redirectUrl={$this->returnUrl}"
            . "&requestId={$requestId}"
            . "&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);

        $response = Http::timeout(10)->retry(2, 250)->post($this->endpoint, [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'Hotel',
            'storeId'     => 'HotelMain',
            'requestId'   => $requestId,
            'amount'      => $amount,
            'orderId'     => $orderId,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $this->returnUrl,
            'ipnUrl'      => $this->notifyUrl,
            'lang'        => 'vi',
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature,
        ]);
        return $response->json('payUrl') ?? route('payment.error', $booking->id);
    }

    public function verifySignature(array $data): bool
    {
        if ($this->accessKey === '' || $this->secretKey === '') {
            return false;
        }

        $required = ['amount','extraData','message','orderId','orderInfo','orderType','partnerCode','payType','requestId','responseTime','resultCode','transId','signature'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) return false;
        }
        $received = $data['signature'] ?? '';
        $rawHash  = "accessKey={$this->accessKey}"
            . "&amount={$data['amount']}"
            . "&extraData={$data['extraData']}"
            . "&message={$data['message']}"
            . "&orderId={$data['orderId']}"
            . "&orderInfo={$data['orderInfo']}"
            . "&orderType={$data['orderType']}"
            . "&partnerCode={$data['partnerCode']}"
            . "&payType={$data['payType']}"
            . "&requestId={$data['requestId']}"
            . "&responseTime={$data['responseTime']}"
            . "&resultCode={$data['resultCode']}"
            . "&transId={$data['transId']}";

        return hash_equals(hash_hmac('sha256', $rawHash, $this->secretKey), (string) $received);
    }
}
