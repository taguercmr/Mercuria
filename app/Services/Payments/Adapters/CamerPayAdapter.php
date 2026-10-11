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

class CamerPayAdapter implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentConfig $config) {}

    public function name(): string
    {
        return 'camerpay';
    }

    public function initiate(PaymentRequest $request): PaymentInitiationResult
    {
        $token = (string) $this->config->get('camerpay', 'api_token', '');

        if ($token === '' || ! $this->config->has('camerpay', 'callback_secret')) {
            throw new RuntimeException('CamerPay n\'est pas configuré. Ajoutez le token API dans l\'admin.');
        }

        if ($request->currency !== 'XAF' || $request->amount <= 0) {
            throw new RuntimeException('Le paiement doit être un montant positif en XAF.');
        }

        $payload = array_filter([
            'amount' => $request->amount,
            'currency' => $request->currency ?: 'XAF',
            'customer_phone' => $request->customerPhone,
            'customer_email' => $request->customerEmail,
            'customer_name' => $request->customerName,
            'merchant_invoice_id' => $request->externalId,
            'merchant_callback_url' => $request->notifyUrl,
            'merchant_return_url' => $request->returnUrl,
            'source' => 'mercuria',
            'idempotency_key' => $request->externalId,
        ], fn ($value) => $value !== null && $value !== '');

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->withToken($token)
            ->post($this->baseUrl().'/payment/initiate', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('CamerPay est indisponible. Réessayez plus tard.');
        }

        $data = $response->json() ?? [];

        if (! is_array($data) || ($data['success'] ?? false) !== true
            || ! is_string($data['transaction_uuid'] ?? null) || $data['transaction_uuid'] === '') {
            throw new RuntimeException('Réponse de paiement CamerPay invalide.');
        }

        $checkoutUrl = $data['pay_url'] ?? $data['redirect_url'] ?? '';
        if (! is_string($checkoutUrl) || parse_url($checkoutUrl, PHP_URL_SCHEME) !== 'https'
            || parse_url($checkoutUrl, PHP_URL_HOST) !== 'camerpay.biz'
            || parse_url($checkoutUrl, PHP_URL_USER) !== null) {
            throw new RuntimeException('Adresse de paiement CamerPay invalide.');
        }
        if (app()->isProduction() && str_starts_with((string) parse_url($checkoutUrl, PHP_URL_PATH), '/sandbox/')) {
            throw new RuntimeException('Activez le mode Live dans CamerPay avant de recevoir des paiements.');
        }

        return new PaymentInitiationResult(
            provider: $this->name(),
            providerTransId: (string) ($data['transaction_uuid'] ?? ''),
            checkoutUrl: $checkoutUrl,
            raw: is_array($data) ? $data : [],
        );
    }

    public function checkStatus(string $providerTransId): PaymentStatusResult
    {
        $token = (string) $this->config->get('camerpay', 'api_token', '');

        if ($token === '') {
            throw new RuntimeException('CamerPay n\'est pas configuré.');
        }

        $response = Http::connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->get($this->baseUrl().'/payment/'.rawurlencode($providerTransId).'/status');

        if (! $response->successful()) {
            throw new RuntimeException('Vérification CamerPay indisponible.');
        }

        $data = $response->json() ?? [];
        $transaction = $data['transaction'] ?? $data;
        if (! is_array($transaction) || ($data['success'] ?? true) === false) {
            throw new RuntimeException('Réponse de statut CamerPay invalide.');
        }
        $isSandbox = isset($transaction['is_sandbox'])
            ? filter_var($transaction['is_sandbox'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : null;
        $payUrl = $transaction['pay_url'] ?? null;
        if ($isSandbox === null && is_string($payUrl)
            && parse_url($payUrl, PHP_URL_SCHEME) === 'https'
            && parse_url($payUrl, PHP_URL_HOST) === 'camerpay.biz') {
            $path = (string) parse_url($payUrl, PHP_URL_PATH);
            if (str_starts_with($path, '/sandbox/')) {
                $isSandbox = true;
            } elseif ($path === '/pay/'.$providerTransId) {
                $isSandbox = false;
            }
        }

        return new PaymentStatusResult(
            provider: $this->name(),
            providerTransId: (string) ($transaction['transaction_uuid'] ?? $transaction['uuid'] ?? ''),
            status: (string) ($transaction['status'] ?? 'UNKNOWN'),
            externalId: isset($transaction['merchant_invoice_id'])
                ? (string) $transaction['merchant_invoice_id']
                : null,
            amount: isset($transaction['amount']) ? $this->xafAmount((string) $transaction['amount']) : null,
            raw: is_array($data) ? $data : [],
            currency: isset($transaction['currency']) ? (string) $transaction['currency'] : null,
            isSandbox: $isSandbox,
        );
    }

    public function verifyWebhook(Request $request): PaymentStatusResult
    {
        $request->validate([
            'uuid' => ['required', 'string', 'max:255'],
            'invoice_id' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:completed,failed,pending,processing,refunded,expired,cancelled'],
            'amount' => ['required', 'regex:/^[0-9]{1,10}(?:\.00)?$/'],
            'signature' => ['sometimes', 'string', 'size:64'],
        ]);
        $uuid = (string) $request->input('uuid', '');
        $invoiceId = (string) $request->input('invoice_id', '');
        $status = (string) $request->input('status', '');
        $amount = (string) $request->input('amount', '');
        $signature = (string) (
            $request->header('X-CamerPay-Signature')
            ?: $request->input('signature', '')
        );

        $secret = (string) $this->config->get('camerpay', 'callback_secret', '');

        if ($secret === '') {
            throw new RuntimeException('CamerPay callback_secret manquant.');
        }

        if ($uuid === '' || $invoiceId === '' || $status === '' || $amount === '' || $signature === '') {
            throw new RuntimeException('CamerPay webhook payload incomplet.');
        }

        $expected = hash_hmac('sha256', "{$uuid}|{$invoiceId}|{$status}|{$amount}", $secret);

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Signature CamerPay invalide.');
        }

        $paidAmount = $this->xafAmount($amount);
        if (app()->isProduction() && $status === 'completed') {
            $confirmed = $this->checkStatus($uuid);
            if (! $confirmed->isSuccessful() || $confirmed->providerTransId !== $uuid
                || ($confirmed->externalId !== null && $confirmed->externalId !== $invoiceId) || $confirmed->amount !== $paidAmount
                || $confirmed->currency !== 'XAF' || $confirmed->isSandbox !== false) {
                throw new RuntimeException('Le paiement réel CamerPay ne peut pas être confirmé.');
            }

            return new PaymentStatusResult(
                provider: $this->name(), providerTransId: $uuid, status: $confirmed->status,
                externalId: $invoiceId, amount: $confirmed->amount,
                currency: $confirmed->currency, isSandbox: $confirmed->isSandbox,
            );
        }

        return new PaymentStatusResult(
            provider: $this->name(),
            providerTransId: $uuid,
            status: $status,
            externalId: $invoiceId,
            amount: $paidAmount,
            currency: 'XAF',
        );
    }

    private function baseUrl(): string
    {
        $url = rtrim((string) $this->config->get('camerpay', 'base_url', 'https://camerpay.biz/api'), '/');
        if ($url !== 'https://camerpay.biz/api') {
            throw new RuntimeException('Adresse API CamerPay invalide.');
        }

        return $url;
    }

    private function xafAmount(string $amount): int
    {
        if (! preg_match('/^[0-9]{1,10}(?:\.00)?$/D', $amount) || (int) $amount <= 0) {
            throw new RuntimeException('Montant CamerPay invalide.');
        }

        return (int) $amount;
    }
}
