<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus tabel 'trades' lama (agar tidak bentrok)
        Schema::dropIfExists('trades');

        // 2. Buat Tabel Khusus SPOT
        Schema::create('spot_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_account_id')->constrained('trading_accounts')->onDelete('cascade');
            $table->string('symbol');           // BTC/USDT, BBCA
            $table->string('market_type');      // CRYPTO, STOCK, COMMODITY
            $table->enum('type', ['BUY', 'SELL']);
            $table->decimal('price', 20, 8);    // Harga saat transaksi
            $table->decimal('quantity', 20, 8); // Jumlah aset
            $table->decimal('total_value', 20, 2); // Total USD (Price x Qty)
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Buat Tabel Khusus FUTURES
        Schema::create('futures_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_account_id')->constrained('trading_accounts')->onDelete('cascade');
            $table->string('symbol');
            $table->enum('type', ['LONG', 'SHORT']);
            $table->integer('leverage')->default(1);
            $table->decimal('margin', 20, 2);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8)->nullable();
            $table->decimal('pnl', 20, 2)->default(0);
            $table->enum('status', ['OPEN', 'CLOSED', 'LIQUIDATED'])->default('OPEN');
            $table->date('entry_date');
            $table->date('exit_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spot_trades');
        Schema::dropIfExists('futures_trades');
    }
};