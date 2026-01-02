<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename entry_date -> entry_datetime
        DB::statement("ALTER TABLE futures_trades CHANGE entry_date entry_datetime DATETIME NULL");
        
        // Rename exit_date -> exit_datetime
        DB::statement("ALTER TABLE futures_trades CHANGE exit_date exit_datetime DATETIME NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE futures_trades CHANGE entry_datetime entry_date DATETIME NULL");
        DB::statement("ALTER TABLE futures_trades CHANGE exit_datetime exit_date DATETIME NULL");
    }
};