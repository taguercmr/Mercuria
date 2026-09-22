<?php

namespace App\Console\Commands;

use App\Services\Mercuriale\MercurialeImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportMercurialeCommand extends Command
{
    protected $signature = 'mercuriale:import
        {--source= : Path to extract .txt or PDF}
        {--fresh : Truncate mercuriale tables before import}
        {--index : Index into Meilisearch after import}';

    protected $description = 'Import the MINCOMMERCE Budget 2026 mercuriale into the database';

    public function handle(MercurialeImporter $importer): int
    {
        $source = $this->option('source')
            ?: base_path('docs/_mercuriale_extract.txt');

        if (! File::exists($source)) {
            $pdf = base_path('docs/Mercuriale_BOND_2026.pdf');
            if (File::exists($pdf)) {
                $this->error('Text extract not found. Provide --source=path/to/extract.txt');
                $this->line('PDF is available at: '.$pdf);
                $this->line('Generate text extract first, or place docs/_mercuriale_extract.txt');

                return self::FAILURE;
            }

            $this->error("Source not found: {$source}");

            return self::FAILURE;
        }

        $this->info('Reading '.$source.' ...');
        $text = File::get($source);

        if (str_ends_with(strtolower($source), '.pdf')) {
            $this->warn('PDF binary passed — use a text extract for fidelity. Aborting.');

            return self::FAILURE;
        }

        $this->info('Parsing & importing articles...');
        $stats = $importer->importFromText($text, (bool) $this->option('fresh'));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Articles', $stats['articles']],
                ['Rubriques', $stats['rubriques']],
                ['Familles', $stats['familles']],
                ['Skipped', $stats['skipped']],
            ],
        );

        $this->info('Mercuriale 2026 import completed.');

        if ($this->option('index')) {
            $this->call('mercuriale:index');
        }

        return self::SUCCESS;
    }
}
