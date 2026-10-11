<?php

namespace App\Services\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;

class PasswordResetCodeBrokerManager extends PasswordBrokerManager
{
    /**
     * @param  array{provider: string, table: string, expire: int, throttle: int, connection?: string}  $config
     */
    protected function createTokenRepository(array $config): TokenRepositoryInterface
    {
        return new PasswordResetCodeRepository(
            $this->app['db']->connection($config['connection'] ?? null),
            $this->app['hash'],
            $config['table'],
            $this->app['config']['app.key'],
            $config['expire'] * 60,
            $config['throttle'],
        );
    }
}
