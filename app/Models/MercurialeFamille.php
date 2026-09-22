<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MercurialeFamille extends Model
{
    protected $fillable = ['rubrique_id', 'code', 'name', 'articles_count'];

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(MercurialeRubrique::class, 'rubrique_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(MercurialeArticle::class, 'famille_id');
    }
}
