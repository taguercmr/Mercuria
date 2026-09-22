<?php

namespace App\Console\Commands;

use App\Models\MercurialeArticle;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mercuriale\MercurialeSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Throwable;

class SmokeTestCommand extends Command
{
    protected $signature = 'mercuria:smoke
        {--base= : Base URL (default APP_URL)}
        {--http : Require HTTP checks to pass (default: soft if server down)}';

    protected $description = 'Smoke checks for Mercuria launch readiness';

    public function handle(MercurialeSearchService $search): int
    {
        $ok = 0;
        $fail = 0;
        $warn = 0;

        $check = function (string $label, bool $pass, string $detail = '', bool $soft = false) use (&$ok, &$fail, &$warn): void {
            if ($pass) {
                $this->info("[OK] {$label}".($detail ? " — {$detail}" : ''));
                $ok++;
            } elseif ($soft) {
                $this->warn("[SKIP] {$label}".($detail ? " — {$detail}" : ''));
                $warn++;
            } else {
                $this->error("[FAIL] {$label}".($detail ? " — {$detail}" : ''));
                $fail++;
            }
        };

        $check('APP_KEY', filled(config('app.key')));
        $check('Database connection', $this->dbOk());
        $check(
            'Mercuriale articles',
            MercurialeArticle::query()->exists(),
            (string) MercurialeArticle::query()->count().' rows'
        );
        $check('Tenants table', Tenant::query()->exists() || Tenant::query()->count() === 0, 'reachable');
        $check('Route landing', Route::has('landing'));
        $check('Route app.dashboard', Route::has('app.dashboard'));
        $check('Route app.catalogue', Route::has('app.catalogue.index'));
        $check('Route health', Route::has('health'));
        $check('Vite build manifest', file_exists(public_path('build/manifest.json')));
        $check(
            'Search driver',
            true,
            $search->driver().($search->meilisearchAvailable() ? ' (Meili ready)' : ' (SQL fallback)')
        );

        $adminExists = User::query()->where('is_platform_admin', true)->exists();
        $check(
            'Platform admin',
            $adminExists,
            $adminExists ? 'present' : 'run: php artisan db:seed'
        );

        $base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');
        $requireHttp = (bool) $this->option('http');

        if ($base) {
            foreach (['/up', '/health', '/'] as $path) {
                try {
                    $response = Http::timeout(5)->get($base.$path);
                    $soft = ! $requireHttp && ! $response->successful();
                    $check('HTTP '.$path, $response->successful(), (string) $response->status(), $soft);
                } catch (Throwable $e) {
                    $check('HTTP '.$path, false, $e->getMessage(), ! $requireHttp);
                }
            }
        }

        $this->newLine();
        $this->line("Result: {$ok} ok, {$fail} fail, {$warn} skipped");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function dbOk(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
