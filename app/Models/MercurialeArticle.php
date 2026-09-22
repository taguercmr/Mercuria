<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class MercurialeArticle extends Model
{
    use Searchable;

    protected $fillable = [
        'rubrique_id',
        'famille_id',
        'reference',
        'article_code',
        'designation',
        'unit',
        'quantity',
        'unit_price',
        'budget_year',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'integer',
            'budget_year' => 'integer',
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return config('scout.driver') === 'meilisearch'
            && filled(config('scout.meilisearch.host'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['rubrique:id,code,name', 'famille:id,code,name']);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'article_code' => $this->article_code,
            'designation' => $this->designation,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'budget_year' => $this->budget_year,
            'rubrique_code' => $this->rubrique?->code,
            'rubrique_name' => $this->rubrique?->name,
            'famille_code' => $this->famille?->code,
            'famille_name' => $this->famille?->name,
        ];
    }

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(MercurialeRubrique::class, 'rubrique_id');
    }

    public function famille(): BelongsTo
    {
        return $this->belongsTo(MercurialeFamille::class, 'famille_id');
    }

    public function formattedPrice(): string
    {
        return number_format($this->unit_price, 0, ',', ' ').' XAF';
    }
}
