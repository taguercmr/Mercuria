<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Services\Billing\PlanQuotaService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(PlanQuotaService $quotas): Response
    {
        $tenant = auth()->user()?->currentTenant;

        $onboarding = null;
        if ($tenant && ! $tenant->onboarding_completed) {
            $onboarding = [
                'branding' => filled($tenant->logo_path) || filled($tenant->letterhead),
                'client' => Client::query()->exists(),
                'document' => Document::query()->exists(),
            ];
        }

        return Inertia::render('App/Dashboard', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan,
                'currency' => $tenant->currency,
            ] : null,
            'usage' => $tenant ? $quotas->usageSummary($tenant) : null,
            'onboarding' => $onboarding,
            'recentDocuments' => Document::query()
                ->with('client:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'type', 'number', 'status', 'total', 'currency', 'client_id', 'created_at']),
            'quickActions' => [
                ['label' => 'Mercuriale 2026', 'href' => '/app/catalogue', 'hint' => 'Explorer les prix officiels'],
                ['label' => 'Nouveau devis', 'href' => '/app/documents/create?type=quote', 'hint' => 'A partir de la mercuriale'],
                ['label' => 'Modeles', 'href' => '/app/templates', 'hint' => 'Reutiliser un document type'],
                ['label' => 'Clients', 'href' => '/app/clients', 'hint' => 'Carnet & historique'],
            ],
        ]);
    }
}
