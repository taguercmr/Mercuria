<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'key_prefix',
        'key_hash',
        'last_used_at',
        'requests_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{model:self, plain:string}
     */
    public static function issue(Tenant $tenant, User $user, string $name): array
    {
        $plain = 'mrc_'.Str::lower(Str::random(40));

        $model = static::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => $name,
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
            'is_active' => true,
        ]);

        return ['model' => $model, 'plain' => $plain];
    }

    public static function findByPlainKey(string $plain): ?self
    {
        return static::query()
            ->where('key_hash', hash('sha256', $plain))
            ->where('is_active', true)
            ->first();
    }
}
