<?php

namespace App\Services\Payments\Adapters;

use App\Services\Payments\PaymentConfig;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentInitiationResult;
use App\Services\Payments\PaymentRequest;
use App\Services\Payments\PaymentStatusResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FapshiAdapter implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentConfig $config) {}

    public function name(): string
    {
        return 'fapshi';
    }

    public function initiate(PaymentRequest $request): PaymentInitiationResult
    {
        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->withHeaders($this->headers())
            ->acceptJson()
            ->post($this->baseUrl().'/initiate-pay', [
                'amount' => $request->amount,
                'email' => $request->customerEmail,
                'redirectUrl' => $request->returnUrl,
                'userId' => $request->userId,
                'externalId' => $request->externalId,
                'message' => $request->description,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Fapshi initiate-pay failed: '.$response->body());
        }

        $data = $response->json();

        return new PaymentInitiationResult(
            provider: $this->name(),
            providerTransId: (string) ($data['transId'] ?? ''),
            checkoutUrl: (string) ($data['link'] ?? $data['payment_url'] ?? ''),
            raw: is_array($data) ? $data : [],
        );
    }

    public function checkStatus(string $providerTransId): PaymentStatusResult
    {
        $response = Http::connectTimeout(5)
            ->timeout(15)
            ->withHeaders($this->headers())
            ->acceptJson()
            ->get($this->baseUrl().'/payment-status/'.$providerTransId);

        if (! $response->successful()) {
            throw new RuntimeException('Fapshi payment-status failed: '.$response->body());
        }

        $data = $response->json();

        return new PaymentStatusResult(
            provider: $this->name(),
            providerTransId: (string) ($data['transId'] ?? $providerTransId),
            status: (string) ($data['status'] ?? 'UNKNOWN'),
            externalId: $data['externalId'] ?? null,
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
            raw: is_array($data) ? $data : [],
        );
    }

    public function verifyWebhook(Request $request): PaymentStatusResult
    {
        $secret = (string) $this->config->get('fapshi', 'webhook_secret', '');

        if ($secret === '' || ! hash_equals($secret, (string) $request->header('x-wh-secret', ''))) {
            throw new RuntimeException('Invalid Fapshi webhook secret.');
        }

        $data = $request->all();

        return new PaymentStatusResult(
            provider: $this->name(),
            providerTransId: (string) ($data['transId'] ?? ''),
            status: (string) ($data['status'] ?? 'UNKNOWN'),
            externalId: $data['externalId'] ?? null,
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
            raw: $data,
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'apiuser' => (string) $this->config->get('fapshi', 'apiuser', ''),
            'apikey' => (string) $this->config->get('fapshi', 'apikey', ''),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config->get('fapshi', 'base_url', 'https://sandbox.fapshi.com'), '/');
    }
}
