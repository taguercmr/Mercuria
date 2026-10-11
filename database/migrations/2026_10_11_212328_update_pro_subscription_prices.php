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
        DB::table('plans')->where('code', 'pro')->where('price_monthly_xaf', 15000)
            ->update(['price_monthly_xaf' => 9900]);
        DB::table('plans')->where('code', 'pro')->where('price_yearly_xaf', 150000)
            ->update(['price_yearly_xaf' => 99000]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->where('code', 'pro')->where('price_monthly_xaf', 9900)
            ->update(['price_monthly_xaf' => 15000]);
        DB::table('plans')->where('code', 'pro')->where('price_yearly_xaf', 99000)
            ->update(['price_yearly_xaf' => 150000]);
    }
};
