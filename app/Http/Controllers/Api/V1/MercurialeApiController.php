<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MercurialeArticle;
use App\Models\MercurialeRubrique;
use App\Services\Mercuriale\MercurialeSearchService;
use Illuminate\Http\Request;

class MercurialeApiController extends Controller
{
    public function rubriques()
    {
        return response()->json([
            'data' => MercurialeRubrique::query()
                ->orderBy('code')
                ->get(['code', 'name', 'articles_count']),
        ]);
    }

    public function articles(Request $request, MercurialeSearchService $search)
    {
        $q = trim((string) $request->get('q', ''));
        $rubrique = $request->get('rubrique');
        $perPage = min((int) $request->get('per_page', 50), 100);

        $paginator = $search->paginate([
            'q' => $q,
            'rubrique' => $rubrique,
            'per_page' => $perPage,
        ]);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (MercurialeArticle $a) => [
                'reference' => $a->reference,
                'designation' => $a->designation,
                'unit' => $a->unit,
                'quantity' => $a->quantity,
                'unit_price' => $a->unit_price,
                'currency' => 'XAF',
                'budget_year' => $a->budget_year,
                'rubrique' => $a->rubrique?->only(['code', 'name']),
                'famille' => $a->famille?->only(['code', 'name']),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'search_driver' => $search->driver(),
            ],
        ]);
    }

    public function show(string $reference)
    {
        $article = MercurialeArticle::query()
            ->with(['rubrique:id,code,name', 'famille:id,code,name'])
            ->where('reference', $reference)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'reference' => $article->reference,
                'designation' => $article->designation,
                'unit' => $article->unit,
                'quantity' => $article->quantity,
                'unit_price' => $article->unit_price,
                'currency' => 'XAF',
                'budget_year' => $article->budget_year,
                'source' => $article->source,
                'rubrique' => $article->rubrique?->only(['code', 'name']),
                'famille' => $article->famille?->only(['code', 'name']),
            ],
        ]);
    }
}
