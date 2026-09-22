<?php

namespace App\Providers;

use App\Services\Payments\Adapters\CinetPayAdapter;
use App\Services\Payments\Adapters\FapshiAdapter;
use App\Services\Payments\PaymentManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FapshiAdapter::class);
        $this->app->singleton(CinetPayAdapter::class);
        $this->app->singleton(PaymentManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('mercuriale-api', function (Request $request) {
            $plain = $request->bearerToken()
                ?: $request->header('X-Api-Key')
                ?: $request->query('api_key');

            $bucket = $plain
                ? 'key:'.substr(hash('sha256', (string) $plain), 0, 24)
                : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by($bucket);
        });
    }
}
