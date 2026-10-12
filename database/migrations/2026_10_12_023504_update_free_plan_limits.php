<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plans')->where('code', 'free')->update([
            'documents_per_month' => 3,
            'clients_limit' => 2,
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->where('code', 'free')->update([
            'documents_per_month' => 10,
            'clients_limit' => 25,
            'updated_at' => now(),
        ]);
    }
};
