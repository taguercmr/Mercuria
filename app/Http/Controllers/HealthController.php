<?php

namespace App\Http\Controllers;

use App\Models\MercurialeArticle;
use App\Services\Mercuriale\MercurialeSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(MercurialeSearchService $search): JsonResponse
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = ['ok' => true, 'driver' => config('database.default')];
        } catch (Throwable $e) {
            $checks['database'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        try {
            $checks['mercuriale'] = [
                'ok' => true,
                'articles' => MercurialeArticle::query()->count(),
            ];
        } catch (Throwable $e) {
            $checks['mercuriale'] = ['ok' => false, 'error' => $e->getMessage()];
        }

        $checks['search'] = [
            'ok' => true,
            'driver' => $search->driver(),
            'meilisearch' => $search->meilisearchAvailable(),
        ];

        $checks['queue'] = [
            'ok' => true,
            'connection' => config('queue.default'),
            'failed_jobs_table' => Schema::hasTable('failed_jobs'),
        ];

        $checks['cache'] = [
            'ok' => true,
            'store' => config('cache.default'),
        ];

        $checks['vite'] = [
            'ok' => file_exists(public_path('build/manifest.json')),
        ];

        $healthy = collect($checks)->every(fn ($c) => ($c['ok'] ?? false) === true);

        if (app()->isProduction()) {
            return response()->json(['status' => $healthy ? 'ok' : 'degraded'], $healthy ? 200 : 503);
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'env' => config('app.env'),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
