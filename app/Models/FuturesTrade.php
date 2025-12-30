<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuturesTrade extends Model
{
    use HasFactory;

    // Kita gunakan $fillable agar sama persis formatnya dengan SpotTrade
    protected $fillable = [
        'trading_account_id',
        'symbol',
        'market_type',
        'type',           // LONG / SHORT
        'leverage',       // 1x - 125x
        'margin_mode',    // CROSS / ISOLATED
        'order_type',     // MARKET / LIMIT
        'entry_price',
        'quantity',       // Size koin
        'margin',         // Modal (Cost)
        'tp_price',
        'sl_price',
        'open_fee',
        'entry_date',
        'notes',
        'status'
    ];

    /**
     * Relasi ke Trading Account.
     * Penting: Parameter kedua 'trading_account_id' wajib ada agar tidak error SQL.
     */
    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id');
    }
}