<?php

namespace App\Models;

use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'type',
        'title',
        'notes',
        'terms',
        'tax_rate',
        'ir_rate',
        'ir_mode',
        'design',
        'lines',
    ];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'tax_rate' => 'decimal:2',
            'ir_rate' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
