<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentSequence;
use App\Models\Tenant;

class DocumentNumberGenerator
{
    public function next(Tenant $tenant, string $type): string
    {
        $prefix = match ($type) {
            Document::TYPE_QUOTE => 'DEV',
            Document::TYPE_PROFORMA => 'PRF',
            Document::TYPE_PURCHASE_ORDER => 'BC',
            Document::TYPE_INVOICE => 'FAC',
            default => 'DOC',
        };

        $year = (int) now()->format('Y');
        $number = DocumentSequence::nextNumber($tenant->id, $type, $year);

        return sprintf('%s-%s-%05d', $prefix, $year, $number);
    }
}
