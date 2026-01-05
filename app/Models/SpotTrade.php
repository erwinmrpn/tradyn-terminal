<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_account_id',
        'symbol',
        'market_type',
        'status', // OPEN / SOLD
        
        // Buy Data
        'buy_date',
        'buy_time',
        'price', // Entry Price (Akan terupdate otomatis saat DCA/Average Down)
        'quantity', // Total Quantity (Akan bertambah saat DCA, berkurang saat Partial Sell)
        'target_sell_price',
        'target_buy_price',
        'holding_period',
        'buy_screenshot',
        'buy_notes',

        // Sell Data (Data Final saat posisi closed sepenuhnya)
        'sell_date',
        'sell_time',
        'fee',
        'pnl', // Realized PnL
    ];

    /**
     * Relasi ke Akun Trading (Parent)
     */
    public function tradingAccount()
    {
        return $this->belongsTo(TradingAccount::class);
    }

    /**
     * [BARU] Relasi ke History Transaksi (Child)
     * Menghubungkan SpotTrade (Induk) ke tabel spot_transactions.
     * Digunakan untuk melihat riwayat: Kapan DCA? Kapan Jual Sebagian?
     */
    public function transactions()
    {
        // Pastikan Anda sudah membuat model App\Models\SpotTransaction
        return $this->hasMany(SpotTransaction::class, 'spot_trade_id');
    }
}