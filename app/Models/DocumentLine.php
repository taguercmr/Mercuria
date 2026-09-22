<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLine extends Model
{
    protected $fillable = [
        'document_id',
        'mercuriale_article_id',
        'reference',
        'designation',
        'unit',
        'quantity',
        'unit_price',
        'line_total',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function mercurialeArticle(): BelongsTo
    {
        return $this->belongsTo(MercurialeArticle::class);
    }
}
