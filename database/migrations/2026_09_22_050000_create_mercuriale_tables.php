<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercuriale_rubriques', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->unsignedInteger('articles_count')->default(0);
            $table->timestamps();
        });

        Schema::create('mercuriale_familles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubrique_id')->constrained('mercuriale_rubriques')->cascadeOnDelete();
            $table->string('code', 3);
            $table->string('name')->nullable();
            $table->unsignedInteger('articles_count')->default(0);
            $table->timestamps();

            $table->unique(['rubrique_id', 'code']);
        });

        Schema::create('mercuriale_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubrique_id')->constrained('mercuriale_rubriques')->cascadeOnDelete();
            $table->foreignId('famille_id')->constrained('mercuriale_familles')->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('article_code', 10);
            $table->text('designation');
            $table->string('unit', 64)->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->unsignedBigInteger('unit_price')->default(0); // XAF entier
            $table->unsignedSmallInteger('budget_year')->default(2026);
            $table->string('source', 64)->default('MINCOMMERCE/SYGEP');
            $table->timestamps();

            $table->index(['rubrique_id', 'famille_id']);
            $table->index('unit_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercuriale_articles');
        Schema::dropIfExists('mercuriale_familles');
        Schema::dropIfExists('mercuriale_rubriques');
    }
};
