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
        
        // Buy Data (Initial Entry)
        'buy_date',
        'buy_time',
        'price',    // Avg Entry Price (Berubah saat DCA, Tetap saat Partial Sell)
        'quantity', // Sisa Quantity saat ini
        'target_sell_price',
        'target_buy_price',
        'holding_period',
        'buy_screenshot',
        'buy_notes',

        // Summary Sell Data (Rangkuman)
        // Kolom detail seperti sell_price/notes/screenshot dihapus dari sini 
        // karena sudah dicatat detailnya di tabel spot_transactions.
        'sell_date', // Untuk sorting "Last Activity"
        'sell_time',
        'fee',       // Akumulasi Fee (jika diperlukan)
        'pnl',       // Realized PnL (Akumulasi Profit/Loss yang sudah diamankan)
    ];

    /**
     * Relasi ke Akun Trading (Parent)
     */
    public function tradingAccount()
    {
        return $this->belongsTo(TradingAccount::class);
    }

    /**
     * Relasi ke History Transaksi (Child)
     * Menampung riwayat DCA (Buy) dan Partial Exit (Sell)
     */
    public function transactions()
    {
        return $this->hasMany(SpotTransaction::class, 'spot_trade_id');
    }

    /**
     * Alias untuk transactions() agar sesuai dengan controller 'with(spotTransactions)'
     * Ini mencegah error 500 saat controller memanggil relasi ini.
     */
    public function spotTransactions()
    {
        return $this->hasMany(SpotTransaction::class, 'spot_trade_id');
    }
}