<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel trading_accounts (Wajib ada biar tahu ini transaksi akun mana)
            $table->foreignId('trading_account_id')
                  ->constrained('trading_accounts')
                  ->onDelete('cascade');
            
            $table->enum('type', ['DEPOSIT', 'WITHDRAW']);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};