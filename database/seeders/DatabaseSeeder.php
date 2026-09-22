<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->upsert([
            [
                'code' => 'free',
                'name' => 'Gratuit',
                'price_monthly_xaf' => 0,
                'price_yearly_xaf' => 0,
                'documents_per_month' => config('mercuria.plans.free.documents_per_month'),
                'clients_limit' => config('mercuria.plans.free.clients_limit'),
                'team_members' => config('mercuria.plans.free.team_members'),
                'api_access' => false,
                'is_active' => true,
                'features' => json_encode(['documents', 'clients', 'branding_basic']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'price_monthly_xaf' => config('mercuria.plans.pro.price_monthly_xaf'),
                'price_yearly_xaf' => config('mercuria.plans.pro.price_yearly_xaf'),
                'documents_per_month' => null,
                'clients_limit' => null,
                'team_members' => config('mercuria.plans.pro.team_members'),
                'api_access' => true,
                'is_active' => true,
                'features' => json_encode(['documents', 'clients', 'branding_full', 'api', 'team']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code']);

        PlatformSetting::setValue('maintenance_enabled', false);
        PlatformSetting::setValue(
            'maintenance_message',
            config('mercuria.maintenance.default_message'),
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@mercuria.cm'],
            [
                'name' => 'Admin Mercuria',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
