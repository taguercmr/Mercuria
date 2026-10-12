<?php

namespace App\Services\Billing;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use RuntimeException;

class PlanQuotaService
{
    public function ensureDocumentQuota(Tenant $tenant, ?User $user = null): void
    {
        if ($this->isPlatformAdmin($user)) {
            return;
        }

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

    public function trackDocumentCreated(Tenant $tenant, ?User $user = null): void
    {
        if ($this->isPlatformAdmin($user)) {
            return;
        }

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

    public function usageSummary(Tenant $tenant, ?User $user = null): array
    {
        $this->resetMonthlyUsageIfNeeded($tenant);
        $plan = config('mercuria.plans.'.$tenant->plan, config('mercuria.plans.free'));
        $isPlatformAdmin = $this->isPlatformAdmin($user);

        return [
            'plan' => $tenant->plan,
            'plan_name' => $isPlatformAdmin ? 'Administrateur' : ($plan['name'] ?? $tenant->plan),
            'documents_used' => (int) $tenant->documents_used_month,
            'documents_limit' => $isPlatformAdmin ? null : ($plan['documents_per_month'] ?? null),
            'clients_limit' => $isPlatformAdmin ? null : ($plan['clients_limit'] ?? null),
            'team_members' => $tenant->users()->count(),
            'team_limit' => $isPlatformAdmin ? null : ($plan['team_members'] ?? 1),
            'api_access' => $isPlatformAdmin || ((bool) ($plan['api_access'] ?? false) && $tenant->isPaid()),
            'email_sending' => $this->canSendEmail($tenant, $user),
            'advanced_customization' => $this->hasAdvancedCustomization($tenant, $user),
            'is_paid' => $isPlatformAdmin || $tenant->isPaid(),
            'is_platform_admin' => $isPlatformAdmin,
        ];
    }

    public function ensureClientQuota(Tenant $tenant, ?User $user = null): void
    {
        if ($this->isPlatformAdmin($user)) {
            return;
        }

        $limit = data_get(config('mercuria.plans.'.$tenant->plan), 'clients_limit');

        if ($limit !== null && Client::query()->where('tenant_id', $tenant->id)->count() >= (int) $limit) {
            throw new RuntimeException(
                "Limite du plan Gratuit atteinte ({$limit} clients). Passez au plan Pro pour continuer."
            );
        }
    }

    public function ensureEmailSending(Tenant $tenant, ?User $user = null): void
    {
        if (! $this->canSendEmail($tenant, $user)) {
            throw new RuntimeException("L'envoi par email est réservé au plan Pro. L'export PDF reste disponible.");
        }
    }

    public function canSendEmail(Tenant $tenant, ?User $user = null): bool
    {
        return $this->isPlatformAdmin($user)
            || ((bool) data_get(config('mercuria.plans.'.$tenant->plan), 'email_sending', false)
                && $tenant->isPaid());
    }

    public function hasAdvancedCustomization(Tenant $tenant, ?User $user = null): bool
    {
        return $this->isPlatformAdmin($user)
            || ((bool) data_get(config('mercuria.plans.'.$tenant->plan), 'advanced_customization', false)
                && $tenant->isPaid());
    }

    public function ensureTeamSlot(Tenant $tenant, ?User $user = null): void
    {
        if ($this->isPlatformAdmin($user)) {
            return;
        }

        $limit = data_get(config('mercuria.plans.'.$tenant->plan), 'team_members', 1);
        $pending = $tenant->invitations()->whereNull('accepted_at')->where('expires_at', '>', now())->count();
        $current = $tenant->users()->count() + $pending;

        if ($limit !== null && $current >= (int) $limit) {
            throw new RuntimeException(
                "Limite d'equipe atteinte ({$limit}). Passez au plan Pro pour inviter plus de membres."
            );
        }
    }

    public function ensureApiAccess(Tenant $tenant, ?User $user = null): void
    {
        if (! $this->isPlatformAdmin($user) && ! $tenant->hasApiAccess()) {
            throw new RuntimeException('API mercuriale réservée aux abonnements Pro.');
        }
    }

    private function isPlatformAdmin(?User $user): bool
    {
        return (bool) $user?->is_platform_admin;
    }
}
