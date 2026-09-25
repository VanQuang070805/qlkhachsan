<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        RateLimiter::for('internal-login', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('username')));

            return Limit::perMinute(8)->by($identity.'|'.$request->ip());
        });
    }
}
