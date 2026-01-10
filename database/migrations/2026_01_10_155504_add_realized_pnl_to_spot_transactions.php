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
        Schema::table('spot_transactions', function (Blueprint $table) {
            // Cek dulu apakah kolom sudah ada, agar tidak crash
            if (!Schema::hasColumn('spot_transactions', 'realized_pnl')) {
                // Tambahkan kolom realized_pnl
                $table->decimal('realized_pnl', 20, 2)->nullable()->after('fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spot_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('spot_transactions', 'realized_pnl')) {
                $table->dropColumn('realized_pnl');
            }
        });
    }
};