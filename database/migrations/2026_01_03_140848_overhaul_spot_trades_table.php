<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            // 1. Hapus kolom lama yang tidak relevan/diganti
            $table->dropColumn(['date', 'market_type', 'type', 'total', 'notes']); 
            
            // 2. Tambahkan Kolom Baru (Entry / Buy)
            $table->date('buy_date')->nullable()->after('trading_account_id');
            $table->time('buy_time')->nullable()->after('buy_date');
            
            // Kolom Status (OPEN = Holding, SOLD = Sold)
            $table->string('status')->default('OPEN')->after('symbol'); 
            
            // Harga & Target
            $table->decimal('target_sell_price', 20, 8)->nullable();
            $table->decimal('target_buy_price', 20, 8)->nullable(); // Bisa untuk area DCA / SL
            
            // Info Tambahan
            $table->string('holding_period')->nullable(); // Short, Medium, Long
            $table->string('buy_screenshot')->nullable();
            $table->text('buy_notes')->nullable();

            // 3. Tambahkan Kolom Exit / Sell
            $table->date('sell_date')->nullable();
            $table->time('sell_time')->nullable();
            $table->decimal('sell_price', 20, 8)->nullable();
            $table->decimal('pnl', 20, 8)->nullable();
            $table->string('sell_screenshot')->nullable();
            $table->text('sell_notes')->nullable();
        });
    }

    public function down(): void
    {
        // Rollback logic (simplified)
        Schema::table('spot_trades', function (Blueprint $table) {
            $table->dropColumn([
                'buy_date', 'buy_time', 'status', 'target_sell_price', 'target_buy_price',
                'holding_period', 'buy_screenshot', 'buy_notes',
                'sell_date', 'sell_time', 'sell_price', 'pnl', 'sell_screenshot', 'sell_notes'
            ]);
        });
    }
};