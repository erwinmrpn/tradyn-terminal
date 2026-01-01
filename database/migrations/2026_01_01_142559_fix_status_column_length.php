<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <--- Jangan lupa import ini

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom 'status' menjadi VARCHAR(50) agar muat menampung 'CANCELLED'
        // Kita gunakan Raw SQL agar kompatibel meskipun sebelumnya tipe datanya ENUM
        DB::statement("ALTER TABLE futures_trades MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'OPEN'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke varchar pendek (opsional)
        DB::statement("ALTER TABLE futures_trades MODIFY COLUMN status VARCHAR(10) NOT NULL DEFAULT 'OPEN'");
    }
};