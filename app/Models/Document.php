<?php

namespace App\Models;

use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use BelongsToTenant;

    public const TYPE_QUOTE = 'quote';

    public const TYPE_PROFORMA = 'proforma';

    public const TYPE_PURCHASE_ORDER = 'purchase_order';

    public const TYPE_INVOICE = 'invoice';

    public const TYPES = [
        self::TYPE_QUOTE => 'Devis',
        self::TYPE_PROFORMA => 'Proforma',
        self::TYPE_PURCHASE_ORDER => 'Bon de commande',
        self::TYPE_INVOICE => 'Facture',
    ];

    protected $fillable = [
        'tenant_id',
        'client_id',
        'created_by',
        'type',
        'number',
        'status',
        'currency',
        'title',
        'notes',
        'terms',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'ir_rate',
        'ir_amount',
        'ir_mode',
        'design',
        'total',
        'converted_from_id',
        'issued_at',
    ];

    protected $appends = ['net_total'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'tax_rate' => 'decimal:2',
            'ir_rate' => 'decimal:2',
            'ir_amount' => 'integer',
            'subtotal' => 'integer',
            'tax_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class)->orderBy('position');
    }

    public function convertedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'converted_from_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    protected function netTotal(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->total - ($this->ir_mode === 'withholding' ? (int) $this->ir_amount : 0));
    }

    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->lines()->sum('line_total');
        $taxAmount = (int) round($subtotal * ((float) $this->tax_rate / 100));

        $irAmount = $this->ir_mode !== 'none'
            ? (int) round($subtotal * ((float) $this->ir_rate / 100))
            : 0;

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'ir_amount' => $irAmount,
            'total' => $subtotal + $taxAmount + ($this->ir_mode === 'addition' ? $irAmount : 0),
        ])->save();
    }
}
