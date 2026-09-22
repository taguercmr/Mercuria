<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type', 32)->default('quote');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->json('lines'); // [{designation, reference, unit, quantity, unit_price, mercuriale_article_id?}]
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
        });

        Schema::create('mercuriale_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mercuriale_article_id')->constrained('mercuriale_articles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'mercuriale_article_id'], 'merc_fav_unique');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('onboarding_step')->default(0)->after('onboarding_completed');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('onboarding_step');
        });
        Schema::dropIfExists('mercuriale_favorites');
        Schema::dropIfExists('document_templates');
    }
};
