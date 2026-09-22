<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('price_monthly_xaf')->default(0);
            $table->unsignedInteger('price_yearly_xaf')->default(0);
            $table->unsignedInteger('documents_per_month')->nullable();
            $table->unsignedInteger('clients_limit')->nullable();
            $table->unsignedInteger('team_members')->default(1);
            $table->boolean('api_access')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('active'); // active, past_due, cancelled, expired
            $table->string('billing_cycle', 16)->default('monthly'); // monthly, yearly
            $table->string('provider', 32)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32);
            $table->string('external_id')->unique();
            $table->string('provider_trans_id')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->string('currency', 8)->default('XAF');
            $table->string('status', 32)->default('pending');
            $table->string('checkout_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('platform_settings');
    }
};
