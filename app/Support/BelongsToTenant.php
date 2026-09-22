<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if (! $model->getAttribute('tenant_id') && auth()->user()?->current_tenant_id) {
                $model->setAttribute('tenant_id', auth()->user()->current_tenant_id);
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = auth()->user()?->current_tenant_id;
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
