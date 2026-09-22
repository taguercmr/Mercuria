<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->current_tenant_id !== null || $user->is_platform_admin;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $document);
    }

    public function send(User $user, Document $document): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $document);
    }

    public function convert(User $user, Document $document): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $document);
    }

    public function duplicate(User $user, Document $document): bool
    {
        return $this->canWrite($user) && $this->sameTenant($user, $document);
    }

    private function canWrite(User $user): bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return in_array($user->tenantRole(), ['owner', 'editor'], true);
    }

    private function sameTenant(User $user, Document $document): bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        return (int) $user->current_tenant_id === (int) $document->tenant_id;
    }
}
