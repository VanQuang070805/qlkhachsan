<?php

namespace Tests\Unit;

use App\Services\Payment\MoMoService;
use App\Services\Payment\VietQRService;
use App\Services\Payment\VNPayService;
use App\Services\Payment\ZaloPayService;
use Tests\TestCase;

class PaymentServiceConfigurationTest extends TestCase
{
    public function test_payment_services_can_boot_when_optional_gateway_credentials_are_missing(): void
    {
        config([
            'payment.vietqr.account_no' => null,
            'payment.vietqr.account_name' => null,
            'payment.momo.partner_code' => null,
            'payment.momo.access_key' => null,
            'payment.momo.secret_key' => null,
            'payment.momo.return_url' => null,
            'payment.zalopay.app_id' => null,
            'payment.zalopay.key1' => null,
            'payment.zalopay.key2' => null,
            'payment.vnpay.tmn_code' => null,
            'payment.vnpay.hash_secret' => null,
        ]);

        $this->assertInstanceOf(VietQRService::class, app(VietQRService::class));
        $this->assertInstanceOf(MoMoService::class, app(MoMoService::class));
        $this->assertInstanceOf(ZaloPayService::class, app(ZaloPayService::class));
        $this->assertInstanceOf(VNPayService::class, app(VNPayService::class));
    }
}
