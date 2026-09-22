<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 32)->default('editor'); // owner, editor, viewer
            $table->string('token', 64)->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });

        Schema::table('mercuriale_articles', function (Blueprint $table) {
            $table->index(['budget_year', 'rubrique_id'], 'mercuriale_articles_year_rubrique_index');
            $table->index(['rubrique_id', 'reference'], 'mercuriale_articles_rubrique_ref_index');
        });
    }

    public function down(): void
    {
        Schema::table('mercuriale_articles', function (Blueprint $table) {
            $table->dropIndex('mercuriale_articles_year_rubrique_index');
            $table->dropIndex('mercuriale_articles_rubrique_ref_index');
        });

        Schema::dropIfExists('tenant_invitations');
    }
};
