<?php

namespace App\Providers;

use App\Contracts\CheckoutSessionGateway;
use App\Services\StripeCheckoutSessionGateway;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CheckoutSessionGateway::class, StripeCheckoutSessionGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // TLS terminates at the reverse proxy; some proxies omit
        // X-Forwarded-Proto. Force the https scheme in production, and also
        // whenever the configured APP_URL is https:// (in case the platform
        // runs outside the production environment), so asset()/route() URLs
        // are never downgraded to http://.
        if (app()->environment('production')
            || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
