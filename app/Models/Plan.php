<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price_monthly_xaf',
        'price_yearly_xaf',
        'documents_per_month',
        'clients_limit',
        'team_members',
        'api_access',
        'is_active',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'api_access' => 'boolean',
            'is_active' => 'boolean',
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
