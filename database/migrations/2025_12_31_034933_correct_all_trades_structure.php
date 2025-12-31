<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. PERBAIKAN SPOT TRADES
        Schema::table('spot_trades', function (Blueprint $table) {
            // A. Kembalikan kolom 'total' (Wajib ada untuk hitungan)
            if (!Schema::hasColumn('spot_trades', 'total')) {
                $table->decimal('total', 20, 8)->default(0); 
            }
            
            // B. Kembalikan kolom 'fee'
            if (!Schema::hasColumn('spot_trades', 'fee')) {
                $table->decimal('fee', 20, 8)->default(0);
            }

            // C. Hapus 'entry_screenshot' di Spot (Tidak perlu)
            if (Schema::hasColumn('spot_trades', 'entry_screenshot')) {
                $table->dropColumn('entry_screenshot');
            }
        });

        // 2. PERBAIKAN FUTURES TRADES
        Schema::table('futures_trades', function (Blueprint $table) {
            // A. Tambah 'entry_screenshot'
            if (!Schema::hasColumn('futures_trades', 'entry_screenshot')) {
                $table->string('entry_screenshot')->nullable()->after('notes');
            }

            // B. Hapus 'open_fee' (Tidak perlu)
            if (Schema::hasColumn('futures_trades', 'open_fee')) {
                $table->dropColumn('open_fee');
            }
        });
    }

    public function down()
    {
        // Biarkan kosong agar tidak ribet rollback
    }
};