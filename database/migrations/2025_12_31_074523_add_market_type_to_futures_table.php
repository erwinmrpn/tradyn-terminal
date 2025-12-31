<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            // 1. Tambah market_type jika belum ada
            if (!Schema::hasColumn('futures_trades', 'market_type')) {
                $table->string('market_type')->default('CRYPTO')->after('symbol');
            }

            // 2. Pastikan open_fee DIBUANG (agar tidak error karena controller tidak mengirimnya)
            if (Schema::hasColumn('futures_trades', 'open_fee')) {
                $table->dropColumn('open_fee');
            }
        });
    }

    public function down()
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            $table->dropColumn('market_type');
            $table->decimal('open_fee', 20, 8)->default(0);
        });
    }
};