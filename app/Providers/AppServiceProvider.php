<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Payment\VietQRService::class);
        $this->app->singleton(\App\Services\Payment\MoMoService::class);
        $this->app->singleton(\App\Services\Payment\ZaloPayService::class);
        $this->app->singleton(\App\Services\Payment\VNPayService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
