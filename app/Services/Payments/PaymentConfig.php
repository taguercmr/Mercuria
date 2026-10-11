<?php

namespace App\Services\Payments;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PaymentConfig
{
    /**
     * @var list<string>
     */
    public const PROVIDERS = ['camerpay', 'fapshi', 'cinetpay'];

    /**
     * @var list<string>
     */
    private const SECRET_KEYS = [
        'api_token',
        'callback_secret',
        'apikey',
        'apiuser',
        'webhook_secret',
        'notify_secret',
    ];

    public function defaultProvider(): string
    {
        $stored = PlatformSetting::getValue('payments.default_provider');

        if (is_string($stored) && in_array($stored, self::PROVIDERS, true)) {
            return $stored;
        }

        $fallback = (string) config('payments.default', 'camerpay');

        return in_array($fallback, self::PROVIDERS, true) ? $fallback : 'camerpay';
    }

    public function setDefaultProvider(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new \InvalidArgumentException("Unknown payment provider [{$provider}].");
        }

        PlatformSetting::setValue('payments.default_provider', $provider);
    }

    public function get(string $provider, string $key, mixed $default = null): mixed
    {
        $stored = PlatformSetting::getValue($this->settingKey($provider, $key));

        if (is_string($stored) && $stored !== '') {
            if ($this->isSecret($key)) {
                return $this->decrypt($stored);
            }

            return $stored;
        }

        return config("payments.{$provider}.{$key}", $default);
    }

    public function set(string $provider, string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $payload = $this->isSecret($key)
            ? Crypt::encryptString($value)
            : $value;

        PlatformSetting::setValue($this->settingKey($provider, $key), $payload);
    }

    public function has(string $provider, string $key): bool
    {
        $value = $this->get($provider, $key);

        return is_string($value) && $value !== '';
    }

    /**
     * Admin UI payload: secrets masked, empty fields for updates.
     *
     * @return array{
     *     default_provider: string,
     *     webhook_urls: array<string, string>,
     *     providers: array<string, array<string, mixed>>
     * }
     */
    public function adminForm(): array
    {
        return [
            'default_provider' => $this->defaultProvider(),
            'webhook_urls' => [
                'camerpay' => route('webhooks.camerpay'),
                'fapshi' => route('webhooks.fapshi'),
                'cinetpay' => route('webhooks.cinetpay'),
            ],
            'providers' => [
                'camerpay' => [
                    'label' => 'CamerPay',
                    'api_token_set' => $this->has('camerpay', 'api_token'),
                    'callback_secret_set' => $this->has('camerpay', 'callback_secret'),
                    'base_url' => (string) $this->get('camerpay', 'base_url', 'https://camerpay.biz/api'),
                ],
                'fapshi' => [
                    'label' => 'Fapshi',
                    'apiuser_set' => $this->has('fapshi', 'apiuser'),
                    'apikey_set' => $this->has('fapshi', 'apikey'),
                    'webhook_secret_set' => $this->has('fapshi', 'webhook_secret'),
                    'base_url' => (string) $this->get('fapshi', 'base_url', 'https://sandbox.fapshi.com'),
                ],
                'cinetpay' => [
                    'label' => 'CinetPay',
                    'apikey_set' => $this->has('cinetpay', 'apikey'),
                    'site_id' => (string) ($this->get('cinetpay', 'site_id') ?? ''),
                    'notify_secret_set' => $this->has('cinetpay', 'notify_secret'),
                    'base_url' => (string) $this->get('cinetpay', 'base_url', 'https://api-checkout.cinetpay.com/v2'),
                ],
            ],
        ];
    }

    private function settingKey(string $provider, string $key): string
    {
        return "payments.{$provider}.{$key}";
    }

    private function isSecret(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    private function decrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return '';
        }
    }
}
