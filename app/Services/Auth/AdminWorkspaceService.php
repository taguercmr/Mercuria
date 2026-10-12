<?php

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminWorkspaceService
{
    public function ensure(User $user): ?Tenant
    {
        if (! $user->is_platform_admin) {
            return $user->currentTenant;
        }

        if ($user->currentTenant) {
            return $user->currentTenant;
        }

        return DB::transaction(function () use ($user): Tenant {
            $tenant = $user->tenants()->first();

            if (! $tenant) {
                $tenant = Tenant::query()->create([
                    'name' => 'Espace administration Mercuria',
                    'email' => $user->email,
                    'plan' => 'free',
                    'currency' => 'XAF',
                    'onboarding_completed' => true,
                    'is_active' => true,
                ]);
                $tenant->users()->attach($user->id, ['role' => 'owner']);
            }

            $user->forceFill(['current_tenant_id' => $tenant->id])->save();

            return $tenant;
        });
    }
}
