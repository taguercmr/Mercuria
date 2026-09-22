<?php

namespace App\Console\Commands;

use App\Models\MercurialeArticle;
use App\Services\Mercuriale\MercurialeSearchService;
use Illuminate\Console\Command;
use Throwable;

class IndexMercurialeCommand extends Command
{
    protected $signature = 'mercuriale:index
        {--flush : Flush the Scout index before importing}';

    protected $description = 'Index mercuriale articles into Meilisearch (no-op if SCOUT_DRIVER is not meilisearch)';

    public function handle(MercurialeSearchService $search): int
    {
        if (! $search->meilisearchAvailable()) {
            $this->warn('Meilisearch non configure (SCOUT_DRIVER=meilisearch + MEILISEARCH_HOST).');
            $this->line('La recherche reste en SQL (fallback). Rien a indexer.');

            return self::SUCCESS;
        }

        try {
            if ($this->option('flush')) {
                $this->info('Flush index...');
                MercurialeArticle::removeAllFromSearch();
            }

            $this->info('Import Scout...');
            $this->call('scout:import', [
                'model' => MercurialeArticle::class,
            ]);

            $this->info('Index mercuriale pret (driver='.$search->driver().').');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Indexation echouee : '.$e->getMessage());
            $this->line('Fallback SQL toujours disponible.');

            return self::FAILURE;
        }
    }
}
