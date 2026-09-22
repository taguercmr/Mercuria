<?php

namespace App\Services\Payments;

class PaymentInitiationResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerTransId,
        public readonly string $checkoutUrl,
        public readonly array $raw = [],
    ) {}
}
