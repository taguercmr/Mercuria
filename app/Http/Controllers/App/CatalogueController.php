<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\MercurialeArticle;
use App\Models\MercurialeFavorite;
use App\Models\MercurialeRubrique;
use App\Services\Mercuriale\MercurialeSearchService;
use App\Services\Mercuriale\MercurialeYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(
        Request $request,
        MercurialeSearchService $search,
        MercurialeYearService $years,
    ): Response {
        $user = $request->user();
        $year = $years->resolve($request->integer('year') ?: null);

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
            'year' => $year,
        ])->withQueryString();

        $rubriqueCounts = MercurialeArticle::query()
            ->select('rubrique_id', DB::raw('COUNT(*) as aggregate'))
            ->where('budget_year', $year)
            ->groupBy('rubrique_id')
            ->pluck('aggregate', 'rubrique_id');

        $rubriques = MercurialeRubrique::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (MercurialeRubrique $item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'articles_count' => (int) ($rubriqueCounts[$item->id] ?? 0),
            ])
            ->filter(fn (array $item) => $item['articles_count'] > 0)
            ->values();

        return Inertia::render('App/Catalogue/Index', [
            'filters' => [
                'q' => $q,
                'rubrique' => $rubrique,
                'favorites' => $request->boolean('favorites'),
                'year' => $year,
            ],
            'years' => $years->available(),
            'activeYear' => $years->active(),
            'rubriques' => $rubriques,
            'articles' => $articles,
            'favoriteIds' => $favoriteIds,
            'searchDriver' => $search->driver(),
            'stats' => [
                'total' => MercurialeArticle::query()->where('budget_year', $year)->count(),
                'rubriques' => $rubriques->count(),
                'favorites' => count($favoriteIds),
            ],
        ]);
    }
}
