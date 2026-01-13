<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            // Menambahkan kolom market_type setelah exchange dengan default 'Crypto'
            $table->string('market_type')->default('Crypto')->after('exchange');
        });
    }

    public function down(): void
    {
        Schema::table('trading_accounts', function (Blueprint $table) {
            $table->dropColumn('market_type');
        });
    }
};