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
            
            // Relasi ke User (Satu User bisa punya banyak akun ini)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('name');          // Nama Akun (misal: "Akun Utama")
            $table->string('exchange');      // BINANCE, BYBIT, TOKOCRYPTO, dll
            $table->string('strategy_type'); // SPOT, FUTURES, MARGIN
            
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency')->default('USD'); // USD, IDR
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_accounts');
    }
};