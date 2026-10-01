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
        if (! $this->app->environment('testing') && config('session.driver') !== 'database') {
            throw new \RuntimeException('Posh Boutique requires SESSION_DRIVER=database so password changes can revoke every active session.');
        }

        RateLimiter::for('internal-login', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('username')));

            return [
                Limit::perMinute(20)->by('internal-ip:'.$request->ip()),
                Limit::perMinute(8)->by('internal-id:'.$identity),
            ];
        });

        RateLimiter::for('customer-login', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(20)->by('customer-ip:'.$request->ip()),
                Limit::perMinute(8)->by('customer-id:'.$identity),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email', session('reset_email', ''))));

            return [
                Limit::perMinute(10)->by('reset-ip:'.$request->ip()),
                Limit::perMinute(5)->by('reset-id:'.$identity),
            ];
        });

        RateLimiter::for('chatbot', function (Request $request) {
            return [
                Limit::perMinute(12)->by('chatbot-ip:'.$request->ip()),
                Limit::perMinute(60)->by('chatbot-global'),
            ];
        });
    }
}
