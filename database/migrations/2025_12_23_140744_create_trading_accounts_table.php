<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Terhubung ke user
            $table->string('name'); // Nama Broker (Bybit, Binance, dll input sendiri)
            $table->string('currency', 10); // USD, IDR, dll
            $table->decimal('balance', 15, 2); // Nominal Saldo Awal
            $table->string('market_type'); // Crypto atau Stock
            $table->string('strategy_type'); // Spot atau Futures
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_accounts');
    }
};