<?php

namespace App\Services\Payments;

class PaymentStatusResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerTransId,
        public readonly string $status,
        public readonly ?string $externalId = null,
        public readonly ?int $amount = null,
        public readonly array $raw = [],
        public readonly ?string $currency = null,
        public readonly ?bool $isSandbox = null,
    ) {}

    public function isSuccessful(): bool
    {
        return in_array(strtoupper($this->status), ['SUCCESSFUL', 'ACCEPTED', 'COMPLETED', 'PAID'], true);
    }
}
