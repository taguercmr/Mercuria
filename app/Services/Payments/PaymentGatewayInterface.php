<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function initiate(PaymentRequest $request): PaymentInitiationResult;

    public function checkStatus(string $providerTransId): PaymentStatusResult;

    public function verifyWebhook(Request $request): PaymentStatusResult;
}
