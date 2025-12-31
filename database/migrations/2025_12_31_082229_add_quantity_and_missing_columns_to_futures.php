<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            
            // 1. Tambah Quantity (Wajib)
            if (!Schema::hasColumn('futures_trades', 'quantity')) {
                $table->decimal('quantity', 20, 8)->after('entry_price');
            }

            // 2. Tambah Margin Mode (Cross/Isolated)
            if (!Schema::hasColumn('futures_trades', 'margin_mode')) {
                $table->string('margin_mode')->default('CROSS')->after('leverage');
            }

            // 3. Tambah Order Type (Market/Limit)
            if (!Schema::hasColumn('futures_trades', 'order_type')) {
                $table->string('order_type')->default('MARKET')->after('margin_mode');
            }

            // 4. Tambah TP & SL (Opsional)
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
        Schema::table('futures_trades', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'margin_mode', 'order_type', 'tp_price', 'sl_price']);
        });
    }
};