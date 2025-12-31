<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuturesTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_account_id',
        'symbol',
        'market_type',
        'entry_date',     // Pastikan nama kolom di DB 'entry_date'
        'type',           // LONG / SHORT
        'leverage',       // 1x - 125x
        'margin_mode',    // CROSS / ISOLATED
        'order_type',     // MARKET / LIMIT
        'entry_price',
        'quantity',       // Size koin
        'margin',         // Modal (Cost)
        'tp_price',
        'sl_price',
        'entry_screenshot', // <--- WAJIB ADA INI! (Ini yang bikin NULL terus tadi)
        'notes',
        'status'
    ];

    /**
     * Relasi ke Trading Account.
     */
    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id');
    }
}