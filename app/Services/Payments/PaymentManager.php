<?php

namespace App\Services\Payments;

use App\Services\Payments\Adapters\CamerPayAdapter;
use App\Services\Payments\Adapters\CinetPayAdapter;
use App\Services\Payments\Adapters\FapshiAdapter;
use InvalidArgumentException;

class PaymentManager
{
    public function __construct(
        private readonly PaymentConfig $config,
        private readonly CamerPayAdapter $camerpay,
        private readonly FapshiAdapter $fapshi,
        private readonly CinetPayAdapter $cinetpay,
    ) {}

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: $this->config->defaultProvider();

        return match ($name) {
            'camerpay' => $this->camerpay,
            'fapshi' => $this->fapshi,
            'cinetpay' => $this->cinetpay,
            default => throw new InvalidArgumentException("Unknown payment provider [{$name}]."),
        };
    }
}
