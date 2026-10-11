<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MercurialeArticle;
use App\Models\MercurialeRubrique;
use App\Services\Mercuriale\MercurialeSearchService;
use App\Services\Mercuriale\MercurialeYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MercurialeApiController extends Controller
{
    public function rubriques(Request $request, MercurialeYearService $years)
    {
        $year = $years->resolve($request->integer('year') ?: null);

        $counts = MercurialeArticle::query()
            ->select('rubrique_id', DB::raw('COUNT(*) as aggregate'))
            ->where('budget_year', $year)
            ->groupBy('rubrique_id')
            ->pluck('aggregate', 'rubrique_id');

        $data = MercurialeRubrique::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (MercurialeRubrique $rubrique) => [
                'code' => $rubrique->code,
                'name' => $rubrique->name,
                'articles_count' => (int) ($counts[$rubrique->id] ?? 0),
            ])
            ->filter(fn (array $row) => $row['articles_count'] > 0)
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => ['budget_year' => $year],
        ]);
    }

    public function articles(Request $request, MercurialeSearchService $search, MercurialeYearService $years)
    {
        $year = $years->resolve($request->integer('year') ?: null);
        $q = trim((string) $request->get('q', ''));
        $rubrique = $request->get('rubrique');
        $perPage = min((int) $request->get('per_page', 50), 100);

        $paginator = $search->paginate([
            'q' => $q,
            'rubrique' => $rubrique,
            'per_page' => $perPage,
            'year' => $year,
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
                'budget_year' => $year,
            ],
        ]);
    }

    public function show(string $reference, Request $request, MercurialeYearService $years)
    {
        $year = $years->resolve($request->integer('year') ?: null);

        $article = MercurialeArticle::query()
            ->with(['rubrique:id,code,name', 'famille:id,code,name'])
            ->where('reference', $reference)
            ->where('budget_year', $year)
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
            'meta' => ['budget_year' => $year],
        ]);
    }
}
