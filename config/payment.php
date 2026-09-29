<?php

/**
 * config/payment.php
 *
 * Tất cả giá trị nhạy cảm đều đọc từ .env
 * Không hardcode key/token ở đây.
 */

return [

    // ── VietQR ────────────────────────────────────────────
    'vietqr' => [
        'bank_bin'     => env('VIETQR_BANK_BIN', '970422'),   // MB Bank mặc định
        'account_no'   => env('VIETQR_ACCOUNT_NO'),
        'account_name' => env('VIETQR_ACCOUNT_NAME'),
        // SePay API — để polling giao dịch tự động
        'sepay_token'   => env('SEPAY_TOKEN'),
        'sepay_api_url' => env('SEPAY_API_URL', 'https://my.sepay.vn/userapi'),
    ],

    // ── MoMo ──────────────────────────────────────────────
    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key'   => env('MOMO_ACCESS_KEY'),
        'secret_key'   => env('MOMO_SECRET_KEY'),
        'endpoint'     => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'return_url'   => env('MOMO_RETURN_URL'),
    ],

    // ── ZaloPay ───────────────────────────────────────────
    'zalopay' => [
        'app_id'   => env('ZALOPAY_APP_ID'),
        'key1'     => env('ZALOPAY_KEY1'),
        'key2'     => env('ZALOPAY_KEY2'),
        'endpoint' => env('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create'),
    ],

    // ── VNPay ─────────────────────────────────────────────
    'vnpay' => [
        'tmn_code'    => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url'         => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    ],

];
