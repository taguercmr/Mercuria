<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('design', 32)->default('signature');
            $table->decimal('ir_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('ir_amount')->default(0);
            $table->string('ir_mode', 16)->default('none');
        });
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->string('design', 32)->default('signature');
            $table->decimal('ir_rate', 5, 2)->default(0);
            $table->string('ir_mode', 16)->default('none');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn(['design', 'ir_rate', 'ir_amount', 'ir_mode']);
        });
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->dropColumn(['design', 'ir_rate', 'ir_mode']);
        });
    }
};
