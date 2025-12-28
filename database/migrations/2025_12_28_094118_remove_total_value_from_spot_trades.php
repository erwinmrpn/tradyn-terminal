<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            $table->dropColumn('total_value');
        });
    }

    public function down(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            $table->decimal('total_value', 20, 2)->default(0);
        });
    }
};