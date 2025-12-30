<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
    Schema::create('futures_trades', function (Blueprint $table) {
        $table->id();
        $table->foreignId('trading_account_id')->constrained()->onDelete('cascade');
        
        // --- 1. Identitas Trade ---
        $table->string('symbol'); // e.g., BTC
        $table->string('market_type')->default('CRYPTO'); // Crypto, Stock, etc
        $table->date('entry_date');
        
        // --- 2. Setup & Risiko ---
        $table->enum('type', ['LONG', 'SHORT']); // Arah Trade
        $table->enum('margin_mode', ['CROSS', 'ISOLATED']); // Margin Mode
        $table->integer('leverage'); // Leverage (1x - 125x)
        
        // --- 3. Eksekusi (Entry) ---
        $table->string('order_type')->default('MARKET'); // Market, Limit
        $table->decimal('entry_price', 20, 8);
        $table->decimal('quantity', 20, 8); // Jumlah Aset (Coin Size)
        $table->decimal('margin', 20, 8);   // Modal Sendiri (Cost)
        
        // --- 4. Rencana (Optional Plan) ---
        $table->decimal('tp_price', 20, 8)->nullable();
        $table->decimal('sl_price', 20, 8)->nullable();
        
        // --- 5. Biaya & Status ---
        $table->decimal('open_fee', 20, 8)->default(0); // Fee saat entry
        $table->text('notes')->nullable();
        
        // Status: OPEN (Sedang jalan), CLOSED (Selesai)
        $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN'); 
        
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('futures_trades');
    }
};
