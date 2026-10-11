<?php

namespace App\Services\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;

class PasswordResetCodeRepository extends DatabaseTokenRepository
{
    public function createNewToken(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($position = 0; $position < 7; $position++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
}
