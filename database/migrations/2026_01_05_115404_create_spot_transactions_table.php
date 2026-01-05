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
        Schema::create('spot_transactions', function (Blueprint $table) {
            $table->id();

            // 1. LINK KE PARENT (Wajib)
            // Menghubungkan transaksi ini ke asset induk di tabel spot_trades
            // onDelete('cascade') artinya jika asset induk dihapus, riwayat transaksinya ikut terhapus otomatis.
            $table->foreignId('spot_trade_id')->constrained('spot_trades')->onDelete('cascade');

            // 2. JENIS TRANSAKSI
            // 'BUY' = Untuk DCA / Tambah Muatan / Average Down
            // 'SELL' = Untuk TP Parsial / Jual Sebagian / Exit
            $table->enum('type', ['BUY', 'SELL']);

            // 3. DATA UTAMA
            // Menggunakan decimal(20, 8) agar presisi untuk koin micin (banyak nol di belakang koma)
            $table->decimal('price', 20, 8);      // Harga eksekusi (Entry Price saat DCA atau Sell Price saat Exit)
            $table->decimal('quantity', 20, 8);   // Jumlah koin yang dibeli/dijual
            $table->decimal('fee', 20, 8)->default(0); // Biaya fee exchange

            // 4. WAKTU TRANSAKSI
            $table->date('transaction_date');
            $table->time('transaction_time');

            // 5. PELENGKAP
            $table->text('notes')->nullable();         // Alasan (Misal: "DCA Support", "TP 50%")
            $table->string('chart_image')->nullable(); // Screenshot chart saat aksi

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_transactions');
    }
};