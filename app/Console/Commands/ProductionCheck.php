<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\PaymentConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ProductionCheck extends Command
{
    protected $signature = 'mercuria:production-check {--configuration-only : Check environment before migrating the database}';

    protected $description = 'Read-only production checks; does not initiate payments or send email';

    public function handle(PaymentConfig $payments): int
    {
        $url = (string) config('app.url');
        $host = (string) parse_url($url, PHP_URL_HOST);
        $checks = [
            'APP_ENV=production' => app()->isProduction(),
            'APP_DEBUG=false' => config('app.debug') === false,
            'APP_KEY configured' => filled(config('app.key')),
            'Public HTTPS APP_URL' => parse_url($url, PHP_URL_SCHEME) === 'https'
                && str_contains($host, '.') && ! preg_match('/\.(localhost|local|test|invalid)$/i', $host)
                && (! filter_var($host, FILTER_VALIDATE_IP)
                    || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)),
            'Secure HTTP-only session cookies' => config('session.secure') === true && config('session.http_only') === true,
            'Production mail transport' => ! in_array(config('mail.default'), ['log', 'array', null], true),
            'Vite production build' => is_file(public_path('build/manifest.json')) && ! is_file(public_path('hot')),
        ];

        if (! $this->option('configuration-only')) {
            try {
                $admins = User::query()->where('is_platform_admin', true)->get();
                $checks['Platform administrator exists'] = $admins->isNotEmpty();
                $checks['No default administrator password'] = $admins->every(
                    fn (User $admin): bool => ! Hash::check('password', $admin->password));
                $checks['Pro subscriptions have an expiry'] = ! Tenant::query()->where('plan', 'pro')->whereNull('subscription_ends_at')->exists();
                $checks['Active Pro plan with valid prices'] = Plan::query()->where('code', 'pro')
                    ->where('is_active', true)->where('price_monthly_xaf', '>', 0)->where('price_yearly_xaf', '>', 0)->exists();
                $checks['CamerPay selected'] = $payments->defaultProvider() === 'camerpay';
                $checks['CamerPay API token configured'] = $payments->has('camerpay', 'api_token');
                $checks['CamerPay callback secret configured'] = $payments->has('camerpay', 'callback_secret');
                $checks['Official CamerPay endpoint'] = $payments->get('camerpay', 'base_url') === 'https://camerpay.biz/api';
            } catch (Throwable) {
                $checks['Database and stored settings accessible'] = false;
            }
        }

        foreach ($checks as $label => $passed) {
            $passed ? $this->info('[OK] '.$label) : $this->error('[FAIL] '.$label);
        }
        $this->line('External checks still required: HTTPS, live payment, email delivery, monitoring and backup restoration.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
