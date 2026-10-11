<?php

namespace App\Services\Mercuriale;

use App\Models\MercurialeArticle;
use App\Models\PlatformSetting;
use Illuminate\Support\Collection;

class MercurialeYearService
{
    public const SETTING_KEY = 'mercuriale_active_year';

    public function active(): int
    {
        $configured = (int) config('mercuria.mercuriale_year', 2026);
        $stored = PlatformSetting::getValue(self::SETTING_KEY);

        if ($stored !== null && $stored !== '' && is_numeric($stored)) {
            return (int) $stored;
        }

        $latest = MercurialeArticle::query()->max('budget_year');

        return $latest ? (int) $latest : $configured;
    }

    public function setActive(int $year): void
    {
        PlatformSetting::setValue(self::SETTING_KEY, $year);
    }

    /**
     * @return list<int>
     */
    public function available(): array
    {
        $years = MercurialeArticle::query()
            ->select('budget_year')
            ->distinct()
            ->orderByDesc('budget_year')
            ->pluck('budget_year')
            ->map(fn ($year) => (int) $year)
            ->all();

        $active = $this->active();
        if (! in_array($active, $years, true)) {
            array_unshift($years, $active);
        }

        return array_values(array_unique($years));
    }

    /**
     * @return Collection<int, array{year: int, articles: int, is_active: bool}>
     */
    public function catalog(): Collection
    {
        $active = $this->active();

        $rows = MercurialeArticle::query()
            ->selectRaw('budget_year as year, COUNT(*) as articles')
            ->groupBy('budget_year')
            ->orderByDesc('budget_year')
            ->get();

        if ($rows->isEmpty()) {
            return collect([[
                'year' => $active,
                'articles' => 0,
                'is_active' => true,
            ]]);
        }

        return $rows->map(fn ($row) => [
            'year' => (int) $row->year,
            'articles' => (int) $row->articles,
            'is_active' => (int) $row->year === $active,
        ]);
    }

    public function resolve(?int $requested = null): int
    {
        if ($requested && in_array($requested, $this->available(), true)) {
            return $requested;
        }

        return $this->active();
    }
}
