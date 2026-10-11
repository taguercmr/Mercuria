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

class CinetPayAdapter implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentConfig $config) {}

    public function name(): string
    {
        return 'cinetpay';
    }

    public function initiate(PaymentRequest $request): PaymentInitiationResult
    {
        $payload = [
            'apikey' => $this->config->get('cinetpay', 'apikey'),
            'site_id' => $this->config->get('cinetpay', 'site_id'),
            'transaction_id' => $request->externalId,
            'amount' => $this->normalizeAmount($request->amount),
            'currency' => $request->currency,
            'description' => preg_replace('/[#\/\$_&]/', '-', $request->description) ?? $request->description,
            'notify_url' => $request->notifyUrl,
            'return_url' => $request->returnUrl,
            'channels' => 'ALL',
            'customer_name' => $request->customerName ?? 'Client',
            'customer_surname' => $request->customerName ?? 'Mercuria',
            'customer_email' => $request->customerEmail,
            'customer_phone_number' => $request->customerPhone,
        ];

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->post($this->baseUrl().'/payment', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('CinetPay payment init failed: '.$response->body());
        }

        $data = $response->json();
        $paymentData = $data['data'] ?? $data;

        return new PaymentInitiationResult(
            provider: $this->name(),
            providerTransId: $request->externalId,
            checkoutUrl: (string) ($paymentData['payment_url'] ?? ''),
            raw: is_array($data) ? $data : [],
        );
    }

    public function checkStatus(string $providerTransId): PaymentStatusResult
    {
        $response = Http::connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->post($this->baseUrl().'/payment/check', [
                'apikey' => $this->config->get('cinetpay', 'apikey'),
                'site_id' => $this->config->get('cinetpay', 'site_id'),
                'transaction_id' => $providerTransId,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('CinetPay payment check failed: '.$response->body());
        }

        $data = $response->json();
        $paymentData = $data['data'] ?? $data;

        return new PaymentStatusResult(
            provider: $this->name(),
            providerTransId: (string) ($paymentData['transaction_id'] ?? $providerTransId),
            status: (string) ($paymentData['status'] ?? 'UNKNOWN'),
            externalId: $paymentData['transaction_id'] ?? $providerTransId,
            amount: isset($paymentData['amount']) ? (int) $paymentData['amount'] : null,
            raw: is_array($data) ? $data : [],
        );
    }

    public function verifyWebhook(Request $request): PaymentStatusResult
    {
        // CinetPay: always re-check server-side; do not trust notify payload alone.
        $transactionId = (string) ($request->input('cpm_trans_id')
            ?? $request->input('transaction_id')
            ?? '');

        if ($transactionId === '') {
            throw new RuntimeException('Missing CinetPay transaction id.');
        }

        return $this->checkStatus($transactionId);
    }

    private function normalizeAmount(int $amount): int
    {
        // CinetPay requires multiples of 5 for XAF.
        return (int) (round($amount / 5) * 5);
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config->get('cinetpay', 'base_url', 'https://api-checkout.cinetpay.com/v2'), '/');
    }
}
