<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah kolom 'notes' lama menjadi 'entry_notes'
        DB::statement("ALTER TABLE futures_trades CHANGE notes entry_notes TEXT NULL");
        
        // 2. Tambahkan kolom 'exit_notes' setelah 'entry_notes'
        DB::statement("ALTER TABLE futures_trades ADD exit_notes TEXT NULL AFTER entry_notes");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE futures_trades DROP COLUMN exit_notes");
        DB::statement("ALTER TABLE futures_trades CHANGE entry_notes notes TEXT NULL");
    }
};