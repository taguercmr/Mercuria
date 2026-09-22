<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TenantInvitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'invited_by',
        'email',
        'role',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->accepted_at !== null;
    }

    public static function createFor(Tenant $tenant, User $inviter, string $email, string $role = 'editor'): self
    {
        return static::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => Str::lower($email),
            ],
            [
                'invited_by' => $inviter->id,
                'role' => $role,
                'token' => Str::random(48),
                'accepted_at' => null,
                'expires_at' => now()->addDays(7),
            ],
        );
    }
}
