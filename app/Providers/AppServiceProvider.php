<?php

namespace App\Providers;

use App\Services\Auth\PasswordResetCodeBrokerManager;
use App\Services\Payments\Adapters\CinetPayAdapter;
use App\Services\Payments\Adapters\FapshiAdapter;
use App\Services\Payments\PaymentManager;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
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
        $this->app->extend('auth.password', fn (PasswordBrokerManager $manager, Application $app): PasswordResetCodeBrokerManager => new PasswordResetCodeBrokerManager($app));
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

        RateLimiter::for('password-code-email', function (Request $request): array {
            $email = $request->input('email');
            $emailKey = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : '');
            $response = fn (Request $request, array $headers): RedirectResponse => back()
                ->withErrors(['email' => 'Veuillez patienter une minute avant de demander un nouveau code.'])
                ->withHeaders($headers);

            return [
                Limit::perMinute(5)->by('ip:'.$request->ip())->response($response),
                Limit::perMinute(1)->by('email:'.$emailKey)->response($response),
            ];
        });

        RateLimiter::for('password-code-reset', function (Request $request): array {
            $email = $request->input('email');
            $emailKey = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : '');
            $response = fn (Request $request, array $headers): RedirectResponse => back()
                ->withErrors(['code' => 'Trop de tentatives. Patientez 15 minutes avant de réessayer, puis demandez un nouveau code.'])
                ->withHeaders($headers);

            return [
                Limit::perMinute(30)->by('ip:'.$request->ip())->response($response),
                Limit::perMinutes(15, 5)->by('email:'.$emailKey)->response($response),
            ];
        });

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
