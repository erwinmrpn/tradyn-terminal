<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            // Kolom untuk Close Position
            if (!Schema::hasColumn('futures_trades', 'exit_reason')) {
                $table->string('exit_reason')->nullable()->after('status'); // Hit TP, Hit SL, dll
            }
            if (!Schema::hasColumn('futures_trades', 'fee')) {
                $table->decimal('fee', 20, 8)->default(0)->after('margin'); // Total Fee (karena futures fee dihitung di akhir)
            }
            if (!Schema::hasColumn('futures_trades', 'exit_screenshot')) {
                $table->string('exit_screenshot')->nullable()->after('entry_screenshot'); // Bukti Close
            }
            // Pastikan kolom exit_price dan exit_date sudah ada (biasanya sudah dari migrasi awal, tapi kita double check)
            if (!Schema::hasColumn('futures_trades', 'exit_price')) {
                $table->decimal('exit_price', 20, 8)->nullable()->after('entry_price');
            }
            if (!Schema::hasColumn('futures_trades', 'exit_date')) {
                $table->date('exit_date')->nullable()->after('entry_date');
            }
            if (!Schema::hasColumn('futures_trades', 'pnl')) {
                $table->decimal('pnl', 20, 8)->nullable()->after('fee');
            }
        });
    }

    public function down()
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            $table->dropColumn(['exit_reason', 'fee', 'exit_screenshot', 'exit_price', 'exit_date', 'pnl']);
        });
    }
};