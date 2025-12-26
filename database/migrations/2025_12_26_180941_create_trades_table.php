<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_account_id')->constrained('trading_accounts')->onDelete('cascade');
            $table->string('pair'); // misal: BTC/USDT
            $table->enum('type', ['LONG', 'SHORT']);
            $table->decimal('entry_price', 15, 8)->nullable();
            $table->decimal('exit_price', 15, 8)->nullable();
            $table->decimal('pnl', 15, 2)->default(0);
            $table->enum('status', ['OPEN', 'CLOSED']);
            $table->date('entry_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};