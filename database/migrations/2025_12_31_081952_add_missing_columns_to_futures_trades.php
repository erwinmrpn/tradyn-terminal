<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            // Cek satu per satu, kalau belum ada baru tambahkan
            if (!Schema::hasColumn('futures_trades', 'margin_mode')) {
                $table->string('margin_mode')->default('CROSS')->after('leverage');
            }
            if (!Schema::hasColumn('futures_trades', 'order_type')) {
                $table->string('order_type')->default('MARKET')->after('margin_mode');
            }
            // Pastikan tp_price dan sl_price ada (jika belum)
            if (!Schema::hasColumn('futures_trades', 'tp_price')) {
                $table->decimal('tp_price', 20, 8)->nullable()->after('margin');
            }
            if (!Schema::hasColumn('futures_trades', 'sl_price')) {
                $table->decimal('sl_price', 20, 8)->nullable()->after('tp_price');
            }
        });
    }

    public function down()
    {
        // Biarkan kosong untuk keamanan data
    }
};