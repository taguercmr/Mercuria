<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'plan',
        'currency',
        'logo_path',
        'letterhead',
        'footer_text',
        'signature_path',
        'onboarding_completed',
        'onboarding_step',
        'trial_ends_at',
        'subscribed_at',
        'subscription_ends_at',
        'is_active',
        'documents_used_month',
        'usage_month',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed' => 'boolean',
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (blank($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name).'-'.Str::lower(Str::random(4));
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    public function isPaid(): bool
    {
        return $this->plan !== 'free'
            && $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isFuture();
    }

    public function hasApiAccess(): bool
    {
        return (bool) data_get(config('mercuria.plans.'.$this->plan), 'api_access', false)
            && $this->isPaid();
    }
}
