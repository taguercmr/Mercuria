<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\MercurialeArticle;
use App\Models\MercurialeFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request, MercurialeArticle $article): RedirectResponse
    {
        MercurialeFavorite::query()->firstOrCreate([
            'tenant_id' => $request->user()->current_tenant_id,
            'user_id' => $request->user()->id,
            'mercuriale_article_id' => $article->id,
        ]);

        return back()->with('success', 'Article ajoute aux favoris.');
    }

    public function destroy(Request $request, MercurialeArticle $article): RedirectResponse
    {
        MercurialeFavorite::query()
            ->where('tenant_id', $request->user()->current_tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('mercuriale_article_id', $article->id)
            ->delete();

        return back()->with('success', 'Favori retire.');
    }
}
