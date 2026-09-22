<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Billing\PlanQuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(private readonly PlanQuotaService $quotas) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken()
            ?: $request->header('X-Api-Key')
            ?: $request->query('api_key');

        if (! $plain) {
            return response()->json(['message' => 'API key required.'], 401);
        }

        $apiKey = ApiKey::findByPlainKey($plain);
        if (! $apiKey) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        $tenant = $apiKey->tenant;
        if (! $tenant || ! $tenant->is_active) {
            return response()->json(['message' => 'Tenant inactive.'], 403);
        }

        try {
            $this->quotas->ensureApiAccess($tenant);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 402);
        }

        $apiKey->forceFill([
            'last_used_at' => now(),
            'requests_count' => $apiKey->requests_count + 1,
        ])->save();

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
