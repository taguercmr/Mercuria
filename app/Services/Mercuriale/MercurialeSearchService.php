<?php

namespace App\Services\Mercuriale;

use App\Models\MercurialeArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class MercurialeSearchService
{
    public function driver(): string
    {
        $preference = config('mercuria.search.driver', 'auto');

        if ($preference === 'sql') {
            return 'sql';
        }

        if (($preference === 'meilisearch' || $preference === 'auto') && $this->meilisearchAvailable()) {
            return 'meilisearch';
        }

        return 'sql';
    }

    public function meilisearchAvailable(): bool
    {
        return config('scout.driver') === 'meilisearch'
            && filled(config('scout.meilisearch.host'));
    }

    /**
     * @param  array{q?: string, rubrique?: string|null, favorites?: array<int>|null, per_page?: int}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $rubrique = $filters['rubrique'] ?? null;
        $favoriteIds = $filters['favorites'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 30);

        if ($q !== '' && $this->driver() === 'meilisearch') {
            try {
                return $this->paginateViaScout($q, $rubrique, $favoriteIds, $perPage);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $this->paginateViaSql($q, $rubrique, $favoriteIds, $perPage);
    }

    /**
     * @param  array<int>|null  $favoriteIds
     */
    private function paginateViaScout(
        string $q,
        ?string $rubrique,
        ?array $favoriteIds,
        int $perPage,
    ): LengthAwarePaginator {
        $builder = MercurialeArticle::search($q);

        if ($rubrique) {
            $builder->where('rubrique_code', $rubrique);
        }

        if (is_array($favoriteIds)) {
            if ($favoriteIds === []) {
                $builder->where('id', 0);
            } else {
                // Scout whereIn support varies; filter after hydrate for favorites
            }
        }

        $paginator = $builder->paginate($perPage);
        $paginator->getCollection()->load(['rubrique:id,code,name', 'famille:id,code,name']);

        if (is_array($favoriteIds) && $favoriteIds !== []) {
            $allowed = array_flip($favoriteIds);
            $paginator->setCollection(
                $paginator->getCollection()->filter(fn ($a) => isset($allowed[$a->id]))->values()
            );
        }

        return $paginator;
    }

    /**
     * @param  array<int>|null  $favoriteIds
     */
    private function paginateViaSql(
        string $q,
        ?string $rubrique,
        ?array $favoriteIds,
        int $perPage,
    ): LengthAwarePaginator {
        $query = MercurialeArticle::query()
            ->with(['rubrique:id,code,name', 'famille:id,code,name']);

        if (is_array($favoriteIds)) {
            $query->whereIn('id', $favoriteIds ?: [0]);
        }

        if ($q !== '') {
            $this->applySqlSearch($query, $q);
        } else {
            $query->orderBy('reference');
        }

        if ($rubrique) {
            $query->whereHas('rubrique', fn ($inner) => $inner->where('code', $rubrique));
        }

        return $query->paginate($perPage);
    }

    public function applySqlSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search): void {
            $q->where('reference', 'like', "%{$search}%")
                ->orWhere('designation', 'like', "%{$search}%");
        });

        $query->orderByRaw(
            'CASE
                WHEN reference = ? THEN 0
                WHEN reference LIKE ? THEN 1
                WHEN designation LIKE ? THEN 2
                ELSE 3
            END',
            [$search, $search.'%', $search.'%']
        )->orderBy('reference');
    }
}
