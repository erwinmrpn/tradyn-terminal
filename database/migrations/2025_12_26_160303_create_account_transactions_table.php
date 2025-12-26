<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('account_transactions', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel trading_accounts
        $table->foreignId('trading_account_id')->constrained()->onDelete('cascade');
        
        $table->enum('type', ['DEPOSIT', 'WITHDRAW']);
        $table->decimal('amount', 15, 2); // 15 digit total, 2 desimal
        $table->date('date');
        $table->text('notes')->nullable(); // Catatan opsional
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
