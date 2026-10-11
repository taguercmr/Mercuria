<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mercuriale_articles', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->unique(['reference', 'budget_year']);
            $table->index('budget_year');
        });
    }

    public function down(): void
    {
        Schema::table('mercuriale_articles', function (Blueprint $table) {
            $table->dropIndex(['budget_year']);
            $table->dropUnique(['reference', 'budget_year']);
            $table->unique('reference');
        });
    }
};
