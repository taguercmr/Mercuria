<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_tenant_id !== null || $user->is_platform_admin;
    }

    public function view(User $user, Client $client): bool
    {
        return $this->sameTenant($user, $client);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $client);
    }

    private function canWrite(User $user): bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return in_array($user->tenantRole(), ['owner', 'editor'], true);
    }

    private function sameTenant(User $user, Client $client): bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return (int) $user->current_tenant_id === (int) $client->tenant_id;
    }
}
