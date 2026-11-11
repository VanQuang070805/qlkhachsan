<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\CacheVersionService;
use App\Services\OutboxService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        $this->app->singleton(CacheVersionService::class);
        $this->app->singleton(OutboxService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->environment('testing') && config('session.driver') !== 'database') {
            throw new \RuntimeException('Rosaliza Hotel requires SESSION_DRIVER=database so password changes can revoke every active session.');
        }

        RateLimiter::for('public-availability', fn (Request $request) => Limit::perMinute(20)->by('availability-ip:'.$request->ip()));
        RateLimiter::for('public-quote', fn (Request $request) => Limit::perMinute(10)->by('quote-ip:'.$request->ip()));

        RateLimiter::for('otp-verify', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email', session('reset_email', session('pending_verify_email', '')))));

            return [
                Limit::perMinute(8)->by('otp-ip:'.$request->ip()),
                Limit::perMinute(8)->by('otp-id:'.$identity),
                Limit::perHour(20)->by('otp-hour:'.$identity),
            ];
        });

        RateLimiter::for('otp-send', function (Request $request) {
            $identity = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by('otp-send-ip:'.$request->ip()),
                Limit::perMinute(1)->by('otp-send-id:'.$identity),
                Limit::perHour(5)->by('otp-send-hour:'.$identity),
            ];
        });

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
            $customerId = (int) ($request->hasSession() ? ($request->session()->get('customer_user_id') ?: 0) : 0);
            if ($customerId <= 0) {
                $customerId = (int) (auth('customer')->id() ?: 0);
            }

            if ($customerId > 0) {
                return [
                    Limit::perMinute(12)->by('chatbot-cust:'.$customerId),
                    Limit::perMinute(30)->by('chatbot-cust-ip:'.$request->ip()),
                    Limit::perMinute(60)->by('chatbot-global'),
                ];
            }

            $sessionKey = $request->hasSession()
                ? ($request->session()->get('chatbot.participant_token') ?: $request->session()->getId())
                : $request->ip();

            $guestLimit = (int) config('services.langgraph.guest_rate_limit', 12);

            return [
                Limit::perMinute($guestLimit)->by('chatbot-guest-sess:'.$sessionKey),
                Limit::perMinute($guestLimit)->by('chatbot-ip:'.$request->ip()),
                Limit::perMinute(60)->by('chatbot-global'),
            ];
        });

        RateLimiter::for('chatbot-internal-tools', function (Request $request) {
            $cap = (string) ($request->header('X-Chatbot-Capability') ?: $request->ip());

            return [
                Limit::perMinute(120)->by('chatbot-tool:'.substr(hash('sha256', $cap), 0, 24)),
            ];
        });

        $this->registerCacheAndOutboxObservers();
    }

    private function registerCacheAndOutboxObservers(): void
    {
        RoomType::saved(function (RoomType $roomType) {
            $version = app(CacheVersionService::class)->bumpCatalogVersion();
            app(OutboxService::class)->recordCatalogMutation('room_type_upserted', [
                'actor_type' => 'admin',
                'room_type_id' => (int) $roomType->id,
                'catalog_version' => $version,
            ]);
        });

        PriceSetting::saved(function (PriceSetting $setting) {
            $version = app(CacheVersionService::class)->bumpCatalogVersion();
            app(OutboxService::class)->recordCatalogMutation('price_setting_upserted', [
                'actor_type' => 'admin',
                'price_setting_id' => (int) $setting->id,
                'catalog_version' => $version,
            ]);
        });

        PriceSetting::deleted(function (PriceSetting $setting) {
            $version = app(CacheVersionService::class)->bumpCatalogVersion();
            app(OutboxService::class)->recordCatalogMutation('price_setting_deleted', [
                'actor_type' => 'admin',
                'price_setting_id' => (int) $setting->id,
                'catalog_version' => $version,
            ]);
        });

        Booking::saved(function () {
            app(CacheVersionService::class)->bumpAvailabilityVersion();
        });

        Booking::deleted(function () {
            app(CacheVersionService::class)->bumpAvailabilityVersion();
        });

        Room::saved(function () {
            app(CacheVersionService::class)->bumpAvailabilityVersion();
        });
    }
}
