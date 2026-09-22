<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\MercurialeArticle;
use App\Models\MercurialeFavorite;
use App\Models\MercurialeRubrique;
use App\Services\Mercuriale\MercurialeSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(Request $request, MercurialeSearchService $search): Response
    {
        $user = $request->user();
        $favoriteIds = MercurialeFavorite::query()
            ->where('tenant_id', $user->current_tenant_id)
            ->where('user_id', $user->id)
            ->pluck('mercuriale_article_id')
            ->all();

        $q = trim((string) $request->get('q', ''));
        $rubrique = $request->get('rubrique');

        $articles = $search->paginate([
            'q' => $q,
            'rubrique' => $rubrique,
            'favorites' => $request->boolean('favorites') ? $favoriteIds : null,
            'per_page' => 30,
        ])->withQueryString();

        return Inertia::render('App/Catalogue/Index', [
            'filters' => [
                'q' => $q,
                'rubrique' => $rubrique,
                'favorites' => $request->boolean('favorites'),
            ],
            'rubriques' => MercurialeRubrique::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'articles_count']),
            'articles' => $articles,
            'favoriteIds' => $favoriteIds,
            'searchDriver' => $search->driver(),
            'stats' => [
                'total' => MercurialeArticle::query()->count(),
                'rubriques' => MercurialeRubrique::query()->count(),
                'favorites' => count($favoriteIds),
            ],
        ]);
    }
}
