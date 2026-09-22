<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DocumentSequence extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'year',
        'last_number',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function nextNumber(int $tenantId, string $type, int $year): int
    {
        return DB::transaction(function () use ($tenantId, $type, $year) {
            $sequence = static::query()
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = static::query()->create([
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'year' => $year,
                    'last_number' => 0,
                ]);

                $sequence = static::query()
                    ->whereKey($sequence->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $sequence->last_number++;
            $sequence->save();

            return (int) $sequence->last_number;
        });
    }
}
