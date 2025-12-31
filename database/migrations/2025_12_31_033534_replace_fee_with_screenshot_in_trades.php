<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Update Futures: Hapus fee, tambah screenshot
        if (Schema::hasTable('futures_trades')) {
            Schema::table('futures_trades', function (Blueprint $table) {
                // Cek dulu apakah kolom ada sebelum drop (untuk safety)
                if (Schema::hasColumn('futures_trades', 'open_fee')) {
                    $table->dropColumn('open_fee');
                }
                if (!Schema::hasColumn('futures_trades', 'entry_screenshot')) {
                    $table->string('entry_screenshot')->nullable()->after('notes');
                }
            });
        }

        // 2. Update Spot: Hapus fee, tambah screenshot
        if (Schema::hasTable('spot_trades')) {
            Schema::table('spot_trades', function (Blueprint $table) {
                if (Schema::hasColumn('spot_trades', 'fee')) {
                    $table->dropColumn('fee');
                }
                if (!Schema::hasColumn('spot_trades', 'entry_screenshot')) {
                    $table->string('entry_screenshot')->nullable()->after('notes');
                }
            });
        }
    }

    public function down()
    {
        // Kembalikan ke kondisi semula jika rollback
        Schema::table('futures_trades', function (Blueprint $table) {
            $table->decimal('open_fee', 20, 8)->default(0);
            $table->dropColumn('entry_screenshot');
        });

        Schema::table('spot_trades', function (Blueprint $table) {
            $table->decimal('fee', 20, 8)->default(0);
            $table->dropColumn('entry_screenshot');
        });
    }
};