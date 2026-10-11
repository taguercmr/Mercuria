<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Document;
use App\Models\MercurialeArticle;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Services\Payments\PaymentConfig;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $now = now();
        $weekAgo = $now->copy()->subDays(7);
        $twoWeeksAgo = $now->copy()->subDays(14);

        $documents = (int) DB::table('documents')->count();
        $tenants = (int) DB::table('tenants')->count();
        $users = (int) DB::table('users')->count();
        $paidTenants = (int) DB::table('tenants')->where('plan', '!=', 'free')->count();
        $openTickets = (int) DB::table('support_tickets')->where('status', 'open')->count();
        $pendingJobs = (int) DB::table('jobs')->count();
        $failedJobs = (int) DB::table('failed_jobs')->count();
        $revenuePaid = (int) DB::table('payment_transactions')->where('status', 'paid')->sum('amount');
        $revenuePending = (int) DB::table('payment_transactions')->where('status', 'pending')->sum('amount');
        $apiRequests = (int) DB::table('api_keys')->sum('requests_count');
        $activeKeys = (int) DB::table('api_keys')->where('is_active', true)->count();
        $maintenance = PlatformSetting::maintenanceEnabled();
        $paymentsConfigured = app(PaymentConfig::class)
            ->has('camerpay', 'api_token');

        $health = [
            [
                'key' => 'database',
                'label' => 'Base de données',
                'status' => 'ok',
                'detail' => DB::connection()->getDriverName().' · '.$tenants.' organisations',
            ],
            [
                'key' => 'queue',
                'label' => 'File d’attente',
                'status' => $failedJobs > 0 ? 'alert' : ($pendingJobs > 20 ? 'warn' : 'ok'),
                'detail' => $pendingJobs.' en attente · '.$failedJobs.' échec(s)',
            ],
            [
                'key' => 'tickets',
                'label' => 'Support',
                'status' => $openTickets > 0 ? 'warn' : 'ok',
                'detail' => $openTickets.' ticket(s) ouvert(s)',
            ],
            [
                'key' => 'billing',
                'label' => 'Paiements',
                'status' => ! $paymentsConfigured ? 'alert' : ($revenuePending > 0 ? 'warn' : 'ok'),
                'detail' => ! $paymentsConfigured
                    ? 'CamerPay non configuré — Admin > Paiements'
                    : number_format($revenuePending, 0, ',', ' ').' XAF en attente',
            ],
            [
                'key' => 'api',
                'label' => 'API mercuriale',
                'status' => 'ok',
                'detail' => $activeKeys.' clé(s) active(s) · '.number_format($apiRequests, 0, ',', ' ').' requêtes',
            ],
            [
                'key' => 'maintenance',
                'label' => 'Maintenance',
                'status' => $maintenance ? 'alert' : 'ok',
                'detail' => $maintenance ? 'Activée — le public est bloqué' : 'Désactivée',
            ],
        ];

        return Inertia::render('Admin/Dashboard', [
            'generatedAt' => $now->toIso8601String(),
            'stats' => [
                'tenants' => $tenants,
                'tenants_week' => (int) DB::table('tenants')->where('created_at', '>=', $weekAgo)->count(),
                'users' => $users,
                'users_week' => (int) DB::table('users')->where('created_at', '>=', $weekAgo)->count(),
                'paid_tenants' => $paidTenants,
                'free_tenants' => max(0, $tenants - $paidTenants),
                'documents' => $documents,
                'documents_week' => (int) DB::table('documents')->where('created_at', '>=', $weekAgo)->count(),
                'documents_prev_week' => (int) DB::table('documents')
                    ->whereBetween('created_at', [$twoWeeksAgo, $weekAgo])
                    ->count(),
                'articles' => MercurialeArticle::query()->count(),
                'open_tickets' => $openTickets,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'revenue_pending' => $revenuePending,
                'revenue_paid' => $revenuePaid,
                'api_requests' => $apiRequests,
                'active_keys' => $activeKeys,
                'maintenance' => $maintenance,
            ],
            'series' => [
                'documents' => $this->dailyCounts('documents'),
                'tenants' => $this->dailyCounts('tenants'),
                'users' => $this->dailyCounts('users'),
            ],
            'breakdowns' => [
                'plans' => [
                    ['key' => 'free', 'label' => 'Gratuit', 'total' => max(0, $tenants - $paidTenants)],
                    ['key' => 'pro', 'label' => 'Pro', 'total' => $paidTenants],
                ],
                'documents' => $this->documentTypes(),
            ],
            'health' => $health,
            'maintenanceMessage' => PlatformSetting::maintenanceMessage(),
            'recentTenants' => Tenant::query()
                ->withCount('users')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Tenant $tenant) => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'plan' => $tenant->plan,
                    'is_active' => (bool) $tenant->is_active,
                    'users_count' => $tenant->users_count,
                    'created_at' => $tenant->created_at?->toIso8601String(),
                ]),
            'recentTickets' => SupportTicket::withoutGlobalScopes()
                ->with(['user:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (SupportTicket $ticket) => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'author' => $ticket->user?->name,
                    'created_at' => $ticket->created_at?->toIso8601String(),
                ]),
            'recentPayments' => PaymentTransaction::query()
                ->with('tenant:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (PaymentTransaction $payment) => [
                    'id' => $payment->id,
                    'tenant' => $payment->tenant?->name,
                    'amount' => (int) $payment->amount,
                    'status' => $payment->status,
                    'provider' => $payment->provider,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ]),
            'activity' => ActivityLog::query()
                ->with(['user:id,name', 'tenant:id,name'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user?->name,
                    'tenant' => $log->tenant?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
            'api' => [
                'active_keys' => $activeKeys,
                'requests' => $apiRequests,
                'last_used_at' => ApiKey::query()->whereNotNull('last_used_at')->max('last_used_at'),
            ],
        ]);
    }

    /**
     * @return list<array{day: string, label: string, total: int}>
     */
    private function dailyCounts(string $table, int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        $rows = DB::table($table)
            ->where('created_at', '>=', $start)
            ->selectRaw($expression.' as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range($days - 1, 0))
            ->map(function (int $offset) use ($rows) {
                $date = now()->subDays($offset);

                return [
                    'day' => $date->toDateString(),
                    'label' => $date->format('d/m'),
                    'total' => (int) ($rows[$date->toDateString()] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, total: int}>
     */
    private function documentTypes(): array
    {
        $counts = DB::table('documents')
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
}
