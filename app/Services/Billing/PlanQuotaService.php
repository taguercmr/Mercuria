<?php

namespace App\Services\Billing;

use App\Models\Document;
use App\Models\Tenant;
use RuntimeException;

class PlanQuotaService
{
    public function ensureDocumentQuota(Tenant $tenant): void
    {
        $this->resetMonthlyUsageIfNeeded($tenant);

        $limit = data_get(config('mercuria.plans.'.$tenant->plan), 'documents_per_month');

        if ($limit === null) {
            return; // unlimited
        }

        if ($tenant->documents_used_month >= (int) $limit) {
            throw new RuntimeException(
                "Quota Freemium atteint ({$limit} documents / mois). Passez au plan Pro pour continuer."
            );
        }
    }

    public function trackDocumentCreated(Tenant $tenant): void
    {
        $this->resetMonthlyUsageIfNeeded($tenant);
        $tenant->increment('documents_used_month');
    }

    public function resetMonthlyUsageIfNeeded(Tenant $tenant): void
    {
        $month = now()->format('Y-m');
        if ($tenant->usage_month !== $month) {
            $tenant->forceFill([
                'usage_month' => $month,
                'documents_used_month' => 0,
            ])->save();
        }
    }

    public function usageSummary(Tenant $tenant): array
    {
        $this->resetMonthlyUsageIfNeeded($tenant);
        $plan = config('mercuria.plans.'.$tenant->plan, config('mercuria.plans.free'));

        return [
            'plan' => $tenant->plan,
            'plan_name' => $plan['name'] ?? $tenant->plan,
            'documents_used' => (int) $tenant->documents_used_month,
            'documents_limit' => $plan['documents_per_month'] ?? null,
            'clients_limit' => $plan['clients_limit'] ?? null,
            'team_members' => $tenant->users()->count(),
            'team_limit' => $plan['team_members'] ?? 1,
            'api_access' => (bool) ($plan['api_access'] ?? false) && $tenant->isPaid(),
            'is_paid' => $tenant->isPaid(),
        ];
    }

    public function ensureTeamSlot(Tenant $tenant): void
    {
        $limit = data_get(config('mercuria.plans.'.$tenant->plan), 'team_members', 1);
        $pending = $tenant->invitations()->whereNull('accepted_at')->where('expires_at', '>', now())->count();
        $current = $tenant->users()->count() + $pending;

        if ($limit !== null && $current >= (int) $limit) {
            throw new RuntimeException(
                "Limite d'equipe atteinte ({$limit}). Passez au plan Pro pour inviter plus de membres."
            );
        }
    }

    public function ensureApiAccess(Tenant $tenant): void
    {
        if (! $tenant->hasApiAccess()) {
            throw new RuntimeException('API mercuriale réservée aux abonnements Pro.');
        }
    }
}
