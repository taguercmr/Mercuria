<?php

namespace App\Services\Payments;

use App\Services\Payments\Adapters\CinetPayAdapter;
use App\Services\Payments\Adapters\FapshiAdapter;
use InvalidArgumentException;

class PaymentManager
{
    public function __construct(
        private readonly FapshiAdapter $fapshi,
        private readonly CinetPayAdapter $cinetpay,
    ) {}

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: (string) config('payments.default', 'fapshi');

        return match ($name) {
            'fapshi' => $this->fapshi,
            'cinetpay' => $this->cinetpay,
            default => throw new InvalidArgumentException("Unknown payment provider [{$name}]."),
        };
    }
}
