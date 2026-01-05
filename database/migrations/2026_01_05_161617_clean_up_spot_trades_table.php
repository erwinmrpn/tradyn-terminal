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
        Schema::table('spot_trades', function (Blueprint $table) {
            // Kita hapus kolom yang datanya sudah dipindahkan ke tabel 'spot_transactions'
            $table->dropColumn([
                'sell_price',
                'sell_screenshot',
                'sell_notes',
                // 'fee' // Opsional: Jika fee mau direkap di transaction saja, uncomment baris ini
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            // Kembalikan kolom jika rollback
            $table->decimal('sell_price', 20, 8)->nullable();
            $table->string('sell_screenshot')->nullable();
            $table->text('sell_notes')->nullable();
        });
    }
};