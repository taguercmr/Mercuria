<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'mercuria:admin {email} {--name=Admin Mercuria} {--reset-password : Reset an existing platform administrator password}';

    protected $description = 'Create a platform administrator with a password entered securely';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Interactive password entry is required.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $identity = Validator::make(compact('email', 'name'), [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        if ($identity->fails()) {
            $this->error('Invalid administrator name or email.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if (($user && (! $user->is_platform_admin || ! $this->option('reset-password')))
            || (! $user && $this->option('reset-password'))) {
            $this->error('Existing accounts are never promoted. Use --reset-password only for an existing administrator.');

            return self::FAILURE;
        }

        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], ['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($validator->fails()) {
            $this->error('Use matching passwords with at least 12 characters, uppercase, lowercase, a number and a symbol.');

            return self::FAILURE;
        }

        if ($user) {
            $user->forceFill(['password' => $password, 'remember_token' => null])->save();
        } else {
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password, 'is_platform_admin' => true]);
            $user->email_verified_at = now();
            $user->save();
        }

        $this->info('Administrator saved.');

        return self::SUCCESS;
    }
}
