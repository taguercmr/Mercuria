<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Services\Billing\PlanQuotaService;
use App\Services\Mercuriale\MercurialeYearService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(PlanQuotaService $quotas, MercurialeYearService $years): Response
    {
        $tenant = auth()->user()?->currentTenant;
        $currency = $tenant?->currency ?? 'XAF';
        $monthStart = now()->startOfMonth();

        $onboarding = null;
        if ($tenant && ! $tenant->onboarding_completed) {
            $onboarding = [
                'branding' => filled($tenant->logo_path) || filled($tenant->letterhead),
                'client' => Client::query()->exists(),
                'document' => Document::query()->exists(),
            ];
        }

        $documentsMonth = Document::query()->where('created_at', '>=', $monthStart)->count();
        $revenueMonth = (int) Document::query()
            ->where('created_at', '>=', $monthStart)
            ->whereIn('status', ['issued', 'sent', 'converted'])
            ->sum('total');
        $drafts = Document::query()->where('status', 'draft')->count();
        $clients = Client::query()->count();

        return Inertia::render('App/Dashboard', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan,
                'currency' => $currency,
            ] : null,
            'usage' => $tenant ? $quotas->usageSummary($tenant, auth()->user()) : null,
            'onboarding' => $onboarding,
            'mercurialeYear' => $years->active(),
            'stats' => [
                'documents_month' => $documentsMonth,
                'documents_total' => Document::query()->count(),
                'clients' => $clients,
                'drafts' => $drafts,
                'revenue_month' => $revenueMonth,
                'currency' => $currency,
            ],
            'series' => [
                'documents' => $this->dailyDocumentCounts(14),
                'revenue' => $this->dailyRevenue(14),
            ],
            'breakdowns' => [
                'types' => $this->documentTypeBreakdown(),
                'statuses' => $this->documentStatusBreakdown(),
            ],
            'recentDocuments' => Document::query()
                ->with('client:id,name')
                ->latest()
                ->limit(6)
                ->get(['id', 'type', 'number', 'status', 'total', 'currency', 'client_id', 'created_at']),
            'topClients' => $this->topClientsThisMonth($monthStart),
            'quickActions' => [
                [
                    'label' => 'Mercuriale '.$years->active(),
                    'href' => '/app/catalogue',
                    'hint' => 'Explorer les prix officiels',
                ],
                [
                    'label' => 'Nouveau devis',
                    'href' => '/app/documents/create?type=quote',
                    'hint' => 'À partir de la mercuriale',
                ],
                [
                    'label' => 'Modèles',
                    'href' => '/app/templates',
                    'hint' => 'Réutiliser un document type',
                ],
                [
                    'label' => 'Clients',
                    'href' => '/app/clients',
                    'hint' => 'Carnet & historique',
                ],
            ],
        ]);
    }

    /**
     * @return list<array{day: string, label: string, total: int}>
     */
    private function dailyDocumentCounts(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        $rows = Document::query()
            ->where('created_at', '>=', $start)
            ->selectRaw($expression.' as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range($days - 1, 0))
            ->map(function (int $offset) use ($rows) {
                $date = now()->subDays($offset);

                return [
                    'day' => $date->toDateString(),
                    'label' => $date->locale('fr')->isoFormat('D MMM'),
                    'total' => (int) ($rows[$date->toDateString()] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{day: string, label: string, total: int}>
     */
    private function dailyRevenue(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        $rows = Document::query()
            ->where('created_at', '>=', $start)
            ->selectRaw($expression.' as day, COALESCE(SUM(total), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range($days - 1, 0))
            ->map(function (int $offset) use ($rows) {
                $date = now()->subDays($offset);

                return [
                    'day' => $date->toDateString(),
                    'label' => $date->locale('fr')->isoFormat('D MMM'),
                    'total' => (int) ($rows[$date->toDateString()] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, total: int}>
     */
    private function documentTypeBreakdown(): array
    {
        $counts = Document::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return collect(Document::TYPES)
            ->map(fn (string $label, string $type) => [
                'key' => $type,
                'label' => $label,
                'total' => (int) ($counts[$type] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, total: int}>
     */
    private function documentStatusBreakdown(): array
    {
        $labels = [
            'draft' => 'Brouillon',
            'issued' => 'Émis',
            'sent' => 'Envoyé',
            'converted' => 'Converti',
            'cancelled' => 'Annulé',
        ];

        $counts = Document::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($labels)
            ->map(fn (string $label, string $status) => [
                'key' => $status,
                'label' => $label,
                'total' => (int) ($counts[$status] ?? 0),
            ])
            ->filter(fn (array $row) => $row['total'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, documents_count: int, revenue: int}>
     */
    private function topClientsThisMonth(\DateTimeInterface $monthStart): array
    {
        $rows = Document::query()
            ->select('client_id', DB::raw('COUNT(*) as documents_count'), DB::raw('SUM(total) as revenue'))
            ->whereNotNull('client_id')
            ->where('created_at', '>=', $monthStart)
            ->groupBy('client_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $names = Client::query()
            ->whereIn('id', $rows->pluck('client_id'))
            ->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'id' => (int) $row->client_id,
            'name' => (string) ($names[$row->client_id] ?? 'Client'),
            'documents_count' => (int) $row->documents_count,
            'revenue' => (int) $row->revenue,
        ])->values()->all();
    }
}
