<?php

namespace App\Services\Mercuriale;

use App\Models\MercurialeArticle;
use App\Models\MercurialeFamille;
use App\Models\MercurialeRubrique;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MercurialeImporter
{
    /** @var array<string, string> */
    private array $rubriqueNames = [
        '01' => 'ACCESSOIRES ET CONSOMMABLES INFORMATIQUES',
        '02' => 'ALIMENTATION',
        '03' => 'AMENAGEMENTS DES ESPACES VERTS',
        '04' => 'AMEUBLEMENT1: BUREAUX, ARMOIRES, LIVINGS, BARS D\'ANGLE, SALONS, FAUTEUILS, TABLES, SALLES A MANGER, GUERIDONS',
        '05' => 'AMEUBLEMENT2: MATELAS, LITS, CHAMBRES, OREILLERS ET COUSSINS',
        '06' => 'AMEUBLEMENT3: MATERIELS DE DECORATION',
        '07' => 'APPAREILS, CONSOMMABLES ET DISPOSITIFS MEDICAUX',
        '08' => 'APPAREILS ELECTRIQUES, ELECTRONIQUES, AUDIO-VISUELS ET ACCESSOIRES',
        '09' => 'APPAREILS ET MATERIELS DE CONTROLE D\'ACCES ET DE SECURITE',
        '10' => 'APPAREILS ET MATERIELS DE PHOTOGRAPHIE',
        '11' => 'ARMES, MUNITIONS, ACCESSOIRES ET EFFETS MILITAIRES',
        '12' => 'AUTOMOBILES',
        '13' => 'BATTERIES, PNEUMATIQUES ET PEINTURES AUTOMOBILES',
        '14' => 'COUT DE CONSTRUCTION DES SALLES DE CLASSE DANS LES 10 REGIONS',
        '15' => 'BOISSONS ET SPIRITUEUX',
        '16' => 'BORNAGE, TRAVAUX CADASTRAUX ET LEVES TOPOGRAPHIQUES',
        '17' => 'CLIMATISATION',
        '18' => 'COFFRES-FORTS',
        '19' => 'CONSTRUCTION, ENTRETIEN ET DECORATION DES BATIMENTS',
        '20' => 'CONSTRUCTION, ENTRETIEN ROUTIER, VOIRIE ET RESEAUX DIVERS',
        '21' => 'CONSTRUCTION, MAINTENANCE DES FORAGES, DES PUITS ET SOURCES ET TRAVAUX D\'ADDUCTION D\'EAU',
        '22' => 'TRAVAUX ROUTIERS NEUFS',
        '23' => 'CYCLES, MOTOCYCLES, TRICYCLES ET ACCESSOIRES',
        '24' => 'DROGUERIE, PRODUITS D\'HYGIENE ET D\'ENTRETIEN',
        '25' => 'ELECTRO-MENAGERS, BATTERIES ET USTENSILES DE CUISINE',
        '26' => 'ENGINS ET MATERIELS DES TRAVAUX PUBLICS',
        '27' => 'ENVOIS DES COLIS ET MESSAGERIE',
        '28' => 'ENTRETIEN ET REPARATION DES ARMES',
        '29' => 'FOURNITURES DE BUREAU ET PAPIERS',
        '30' => 'HABILLEMENT ET LINGERIE DE MAISON',
        '31' => 'GARDIENNAGE',
        '32' => 'HONORAIRES DES EXPERTS ET TECHNICIENS',
        '33' => 'HOTELLERIE, RESTAURATION ET LOCATION DES SALLES DE CONFERENCE',
        '34' => 'IMPRESSION, MONTAGE, FLASHAGE ET MATERIELS D\'IMPRIMERIE',
        '35' => 'INFORMATIQUE ET BUREAUTIQUE',
        '36' => 'INSTRUMENTS DE MUSIQUE ET ACCESSOIRES',
        '37' => 'LUBRIFIANTS ET CARBURANT',
        '38' => 'MAINTENANCE, REPARATION ET LOCATION DES AUTOMOBILES ET DES ENGINS',
        '39' => 'MATERIELS AGRICOLES ET PRODUITS PHYTOSANITAIRES',
        '40' => 'MATERIELS DE LUTTE CONTRE LES INCENDIES',
        '41' => 'MATERIELS DE PECHE ET ACCESSOIRES',
        '42' => 'MATERIELS D\'ELECTRICITE, GROUPES ELECTROGENES ET ACCESSOIRES',
        '43' => 'MATERIELS D\'ELEVAGE ET PRODUITS VETERINAIRES',
        '44' => 'MATERIELS DIDACTIQUES',
        '45' => 'MATERIELS ET ARTICLES DE SPORTS',
        '46' => 'MATERIAUX ET MATERIELS DE CONSTRUCTION',
        '47' => 'MOTEURS HORS-BORD, POMPES A EAU ET COQUES AMENAGEES',
        '48' => 'JOURNAUX, PUBLI-REPORTAGE, DOCUMENTAIRES ET INSERTIONS PUBLICITAIRES',
        '49' => 'PIECES DETACHEES CAMI',
        '50' => 'PIECES DETACHEES TRACTAFRIC',
        '51' => 'POMPES FUNEBRES',
        '52' => 'PRODUITS PHARMACEUTIQUES',
    ];

    /**
     * @return array{articles:int, rubriques:int, familles:int, skipped:int}
     */
    public function importFromText(string $text, bool $fresh = false): array
    {
        if ($fresh) {
            DB::table('mercuriale_articles')->delete();
            DB::table('mercuriale_familles')->delete();
            DB::table('mercuriale_rubriques')->delete();
        }

        $this->seedRubriques();

        $familyTitles = $this->extractFamilyTitles($text);
        $parsed = $this->parseArticles($text);

        $rubriqueCache = MercurialeRubrique::query()->get()->keyBy('code');
        $familleCache = [];
        $batch = [];
        $skipped = 0;
        $now = now();

        foreach ($parsed as $row) {
            $rubrique = $rubriqueCache->get($row['rubrique_code']);
            if (! $rubrique) {
                $skipped++;

                continue;
            }

            $famKey = $row['rubrique_code'].'-'.$row['famille_code'];
            if (! isset($familleCache[$famKey])) {
                $familleCache[$famKey] = MercurialeFamille::query()->firstOrCreate(
                    [
                        'rubrique_id' => $rubrique->id,
                        'code' => $row['famille_code'],
                    ],
                    [
                        'name' => $familyTitles[$famKey]
                            ?? $familyTitles[$row['famille_code']]
                            ?? 'Famille '.$row['famille_code'],
                    ],
                );
            }

            $batch[] = [
                'rubrique_id' => $rubrique->id,
                'famille_id' => $familleCache[$famKey]->id,
                'reference' => $row['reference'],
                'article_code' => $row['article_code'],
                'designation' => $row['designation'],
                'unit' => $row['unit'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'budget_year' => 2026,
                'source' => 'MINCOMMERCE/SYGEP',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 500) {
                MercurialeArticle::query()->upsert(
                    $batch,
                    ['reference'],
                    ['rubrique_id', 'famille_id', 'article_code', 'designation', 'unit', 'quantity', 'unit_price', 'budget_year', 'source', 'updated_at'],
                );
                $batch = [];
            }
        }

        if ($batch !== []) {
            MercurialeArticle::query()->upsert(
                $batch,
                ['reference'],
                ['rubrique_id', 'famille_id', 'article_code', 'designation', 'unit', 'quantity', 'unit_price', 'budget_year', 'source', 'updated_at'],
            );
        }

        $this->refreshCounts();

        return [
            'articles' => MercurialeArticle::query()->count(),
            'rubriques' => MercurialeRubrique::query()->count(),
            'familles' => MercurialeFamille::query()->count(),
            'skipped' => $skipped,
        ];
    }

    private function seedRubriques(): void
    {
        $now = now();
        $rows = [];
        foreach ($this->rubriqueNames as $code => $name) {
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'articles_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        MercurialeRubrique::query()->upsert($rows, ['code'], ['name', 'updated_at']);
    }

    /**
     * @return array<int, array{
     *   reference:string,
     *   rubrique_code:string,
     *   famille_code:string,
     *   article_code:string,
     *   designation:string,
     *   unit:?string,
     *   quantity:float,
     *   unit_price:int
     * }>
     */
    private function parseArticles(string $text): array
    {
        $normalized = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        preg_match_all('/\b(\d{2}-\d{3}-\d{6})\b/', $normalized, $matches, PREG_OFFSET_CAPTURE);

        $articles = [];
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $reference = $matches[1][$i][0];
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end = $i + 1 < $count ? $matches[0][$i + 1][1] : strlen($normalized);
            $chunk = substr($normalized, $start, $end - $start);
            $chunk = $this->cleanChunk($chunk);

            [$designation, $quantity, $unit, $price] = $this->splitDesignationAndPrice($chunk);

            if ($designation === '' || $price === null) {
                // Keep article even if price missing (0) to preserve catalogue fidelity of references.
                $price = $price ?? 0;
            }

            [$rubrique, $famille, $articleCode] = explode('-', $reference);

            $articles[] = [
                'reference' => $reference,
                'rubrique_code' => $rubrique,
                'famille_code' => $famille,
                'article_code' => $articleCode,
                'designation' => Str::limit($designation !== '' ? $designation : 'Article '.$reference, 5000, ''),
                'unit' => $unit,
                'quantity' => $quantity,
                'unit_price' => $price,
            ];
        }

        return $articles;
    }

    private function cleanChunk(string $chunk): string
    {
        $chunk = preg_replace('/MINCOMMERCE\/DMQP.*?(?=(\d{2}-\d{3}-\d{6})|$)/si', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/Page\s+\d+\s*\/\s*\d+/i', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/Annexe.*?BUDGET\s*2026/i', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/RUBRIQUE\s*:?\s*\d+.*?(?=(\d{2}-\d{3}-\d{6})|$)/si', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/MERCURIALE DE REFERENCE.*?(?=(\d{2}-\d{3}-\d{6})|$)/si', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/REFERENCE DESIGNATIONS.*?PRIX UNITAIRE/si', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/SYst[eè]me de GEstion des Prix de r[eé]f[eé]rence/iu', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/Imprim[eé] le:.*?(?=(\d{2}-\d{3}-\d{6})|$)/iu', ' ', $chunk) ?? $chunk;
        $chunk = preg_replace('/\s+/', ' ', $chunk) ?? $chunk;

        return trim($chunk);
    }

    /**
     * @return array{0:string,1:float,2:?string,3:?int}
     */
    private function splitDesignationAndPrice(string $chunk): array
    {
        // Pattern: ... [qty] UNIT PRICE (spaces as thousands separators)
        if (preg_match(
            '/^(.*?)\s+(\d+(?:[.,]\d+)?)\s+([A-Za-zÀ-ÿÉéÈèÊêËëÀàÂâÄäÔôÖöÙùÛûÜüÇç\/\-]{2,40})\s+(\d{1,3}(?:\s\d{3})+|\d+)\s*$/u',
            $chunk,
            $m,
        )) {
            return [
                trim($m[1]),
                (float) str_replace(',', '.', $m[2]),
                $this->normalizeUnit($m[3]),
                (int) str_replace(' ', '', $m[4]),
            ];
        }

        // Fallback: trailing price only
        if (preg_match('/^(.*?)\s+(\d{1,3}(?:\s\d{3})+|\d+)\s*$/u', $chunk, $m)) {
            $body = trim($m[1]);
            $price = (int) str_replace(' ', '', $m[2]);

            if (preg_match('/^(.*?)\s+(\d+(?:[.,]\d+)?)\s+([A-Za-zÀ-ÿÉéÈèÊêËëÀàÂâÄäÔôÖöÙùÛûÜüÇç\/\-]{2,40})\s*$/u', $body, $u)) {
                return [
                    trim($u[1]),
                    (float) str_replace(',', '.', $u[2]),
                    $this->normalizeUnit($u[3]),
                    $price,
                ];
            }

            return [$body, 1.0, null, $price];
        }

        return [trim($chunk), 1.0, null, null];
    }

    private function normalizeUnit(string $unit): string
    {
        $unit = Str::upper(trim($unit));
        $map = [
            'PICE' => 'PIECE',
            'PEICE' => 'PIECE',
            'PIÈCE' => 'PIECE',
            'UNITÉ' => 'UNITE',
            'UNITÃ‰' => 'UNITE',
        ];

        return $map[$unit] ?? $unit;
    }

    /**
     * @return array<string, string>
     */
    private function extractFamilyTitles(string $text): array
    {
        $titles = [];

        if (preg_match_all('/(?:^|\n)\s*(\d{3})\s*[-–]\s*([A-ZÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ0-9][^\n]{3,120})/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $code = $m[1];
                $name = trim(preg_replace('/\s+/', ' ', $m[2]) ?? $m[2]);
                if (Str::contains(Str::upper($name), ['PAGE', 'MINCOMMERCE', 'SYGEP', 'PRIX UNITAIRE'])) {
                    continue;
                }
                $titles[$code] = $name;
            }
        }

        return $titles;
    }

    private function refreshCounts(): void
    {
        DB::statement('
            UPDATE mercuriale_familles
            SET articles_count = (
                SELECT COUNT(*) FROM mercuriale_articles
                WHERE mercuriale_articles.famille_id = mercuriale_familles.id
            )
        ');

        DB::statement('
            UPDATE mercuriale_rubriques
            SET articles_count = (
                SELECT COUNT(*) FROM mercuriale_articles
                WHERE mercuriale_articles.rubrique_id = mercuriale_rubriques.id
            )
        ');
    }
}
