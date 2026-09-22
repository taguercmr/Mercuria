<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MercurialeRubrique extends Model
{
    protected $fillable = ['code', 'name', 'articles_count'];

    public function familles(): HasMany
    {
        return $this->hasMany(MercurialeFamille::class, 'rubrique_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(MercurialeArticle::class, 'rubrique_id');
    }
}
