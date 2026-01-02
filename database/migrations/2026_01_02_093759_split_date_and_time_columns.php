<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('futures_trades', function (Blueprint $table) {
            // 1. Hapus kolom datetime lama
            $table->dropColumn(['entry_datetime', 'exit_datetime']);

            // 2. Buat kolom terpisah (Date & Time)
            // Entry
            $table->date('entry_date')->nullable()->after('market_type');
            $table->time('entry_time')->nullable()->after('entry_date');
            
            // Exit
            $table->date('exit_date')->nullable()->after('status');
            $table->time('exit_time')->nullable()->after('exit_date');
        });
    }

    public function down(): void
    {
        // Rollback logic (jika perlu)
        Schema::table('futures_trades', function (Blueprint $table) {
            $table->dropColumn(['entry_date', 'entry_time', 'exit_date', 'exit_time']);
            $table->dateTime('entry_datetime')->nullable();
            $table->dateTime('exit_datetime')->nullable();
        });
    }
};