<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <--- Jangan lupa import ini

class Trade extends Model
{
    protected $fillable = [
        'trading_account_id', 'pair', 'type', 'entry_price', 
        'exit_price', 'pnl', 'status', 'entry_date'
    ];

    // --- TAMBAHKAN FUNGSI INI ---
    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }
}