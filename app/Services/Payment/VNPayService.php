<?php

namespace App\Services\Payment;

use App\Models\Booking;

class VNPayService
{
    private string $tmnCode;
    private string $hashSecret;
    private string $url;
    private string $returnUrl;

    public function __construct()
    {
        $this->tmnCode    = config('payment.vnpay.tmn_code');
        $this->hashSecret = config('payment.vnpay.hash_secret');
        $this->url        = config('payment.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $this->returnUrl  = route('webhook.vnpay.return');
    }

    public function createPaymentUrl(Booking $booking): string
    {
        $params = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $this->tmnCode,
            'vnp_Amount' => (int) ($booking->deposit_amount * 100),
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => request()->ip(),
            'vnp_Locale'     => 'vn',
            'vnp_OrderInfo'  => "Thanh toan dat phong {$booking->id}",
            'vnp_OrderType'  => 'other',
            'vnp_ReturnUrl'  => $this->returnUrl,
            'vnp_TxnRef' => (string) $booking->id,
        ];

        ksort($params);
        $query     = http_build_query($params);
        $signature = hash_hmac('sha512', $query, $this->hashSecret);

        return $this->url . '?' . $query . '&vnp_SecureHash=' . $signature;
    }

    public function verifySecureHash(array $data): bool
    {
        $received = $data['vnp_SecureHash'] ?? '';
        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']);
        ksort($data);
        $query = http_build_query($data);
        return hash_hmac('sha512', $query, $this->hashSecret) === $received;
    }
}
