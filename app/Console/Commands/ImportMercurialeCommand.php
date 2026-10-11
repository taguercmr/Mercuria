<?php

namespace App\Console\Commands;

use App\Services\Mercuriale\MercurialeImporter;
use App\Services\Mercuriale\MercurialeYearService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportMercurialeCommand extends Command
{
    protected $signature = 'mercuriale:import
        {--source= : Path to extract .txt}
        {--year= : Budget year (default: config mercuriale_year)}
        {--fresh : Delete articles for that year before import}
        {--activate : Set this year as the active mercuriale}
        {--index : Index into Meilisearch after import}';

    protected $description = 'Import a MINCOMMERCE mercuriale (by budget year) into the database';

    public function handle(MercurialeImporter $importer, MercurialeYearService $years): int
    {
        $year = (int) ($this->option('year') ?: config('mercuria.mercuriale_year', 2026));
        $source = $this->option('source')
            ?: base_path('docs/_mercuriale_extract.txt');

        if (! File::exists($source)) {
            $pdf = base_path("docs/Mercuriale_BOND_{$year}.pdf");
            if (File::exists($pdf) || File::exists(base_path('docs/Mercuriale_BOND_2026.pdf'))) {
                $this->error('Text extract not found. Provide --source=path/to/extract.txt');
                $this->line('Generate a text extract from the official PDF first.');

                return self::FAILURE;
            }

            $this->error("Source not found: {$source}");

            return self::FAILURE;
        }

        if (str_ends_with(strtolower($source), '.pdf')) {
            $this->warn('PDF binary passed — use a text extract for fidelity. Aborting.');

            return self::FAILURE;
        }

        $this->info("Importing mercuriale {$year} from {$source} ...");
        $stats = $importer->importFromText(
            File::get($source),
            (bool) $this->option('fresh'),
            $year,
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Year', $stats['year']],
                ['Articles', $stats['articles']],
                ['Rubriques', $stats['rubriques']],
                ['Familles', $stats['familles']],
                ['Skipped', $stats['skipped']],
            ],
        );

        if ($this->option('activate')) {
            $years->setActive($year);
            $this->info("Active mercuriale year set to {$year}.");
        }

        $this->info("Mercuriale {$year} import completed.");

        if ($this->option('index')) {
            $this->call('mercuriale:index');
        }

        return self::SUCCESS;
    }
}
