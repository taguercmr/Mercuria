<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercurialeFavorite extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'mercuriale_article_id',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(MercurialeArticle::class, 'mercuriale_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
