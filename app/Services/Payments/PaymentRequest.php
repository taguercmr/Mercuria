<?php

namespace App\Services\Payments;

class PaymentRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $externalId,
        public readonly string $description,
        public readonly string $returnUrl,
        public readonly string $notifyUrl,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $userId = null,
    ) {}
}
