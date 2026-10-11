<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MercurialeArticle;
use App\Services\Mercuriale\MercurialeImporter;
use App\Services\Mercuriale\MercurialeYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MercurialeController extends Controller
{
    public function index(MercurialeYearService $years): Response
    {
        return Inertia::render('Admin/Mercuriale', [
            'activeYear' => $years->active(),
            'years' => $years->catalog(),
            'defaultYear' => (int) config('mercuria.mercuriale_year', now()->year),
        ]);
    }

    public function activate(Request $request, MercurialeYearService $years): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = (int) $data['year'];
        abort_unless(in_array($year, $years->available(), true), 422, 'Année non importée.');

        $years->setActive($year);

        return back()->with('success', "Mercuriale {$year} activée pour toute la plateforme.");
    }

    public function import(
        Request $request,
        MercurialeImporter $importer,
        MercurialeYearService $years,
    ): RedirectResponse {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'source' => ['required', 'file', 'extensions:txt', 'max:51200'],
            'fresh' => ['sometimes', 'boolean'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        $year = (int) $data['year'];
        $path = $request->file('source')->storeAs(
            'mercuriale-imports',
            "mercuriale_{$year}_".now()->format('Ymd_His').'.txt',
        );

        $stats = $importer->importFromText(
            (string) file_get_contents(Storage::path($path)),
            $request->boolean('fresh'),
            $year,
        );

        if ($request->boolean('activate') || MercurialeArticle::query()->where('budget_year', '!=', $year)->doesntExist()) {
            $years->setActive($year);
        }

        return back()->with(
            'success',
            "Import {$year} terminé : {$stats['articles']} articles (ignorés : {$stats['skipped']}).",
        );
    }

    public function destroy(Request $request, MercurialeYearService $years): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = (int) $data['year'];
        MercurialeArticle::query()->where('budget_year', $year)->delete();

        if ($years->active() === $year) {
            $fallback = MercurialeArticle::query()->max('budget_year')
                ?? (int) config('mercuria.mercuriale_year', 2026);
            $years->setActive((int) $fallback);
        }

        return back()->with('success', "Mercuriale {$year} supprimée.");
    }
}
