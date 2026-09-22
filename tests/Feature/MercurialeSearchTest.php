<?php

namespace Tests\Feature;

use App\Models\MercurialeArticle;
use App\Models\MercurialeFamille;
use App\Models\MercurialeRubrique;
use App\Services\Mercuriale\MercurialeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercurialeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_service_defaults_to_sql_without_meilisearch(): void
    {
        config([
            'scout.driver' => 'collection',
            'scout.meilisearch.host' => null,
            'mercuria.search.driver' => 'auto',
        ]);

        $service = app(MercurialeSearchService::class);

        $this->assertSame('sql', $service->driver());
        $this->assertFalse($service->meilisearchAvailable());
    }

    public function test_sql_search_finds_by_reference(): void
    {
        $rubrique = MercurialeRubrique::query()->create([
            'code' => '03',
            'name' => 'Test',
            'articles_count' => 1,
        ]);
        $famille = MercurialeFamille::query()->create([
            'rubrique_id' => $rubrique->id,
            'code' => '010',
            'name' => 'F',
            'articles_count' => 1,
        ]);
        MercurialeArticle::query()->create([
            'rubrique_id' => $rubrique->id,
            'famille_id' => $famille->id,
            'reference' => '03-010-000042',
            'article_code' => '000042',
            'designation' => 'Cahier 200 pages',
            'unit' => 'U',
            'quantity' => 1,
            'unit_price' => 500,
            'budget_year' => 2026,
        ]);

        $paginator = app(MercurialeSearchService::class)->paginate([
            'q' => '03-010-000042',
            'per_page' => 10,
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('03-010-000042', $paginator->items()[0]->reference);
    }
}
