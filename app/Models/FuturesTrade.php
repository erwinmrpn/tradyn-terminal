<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuturesTrade extends Model
{
    protected $fillable = [
        'trading_account_id', 'symbol', 'type', 'leverage', 
        'margin', 'entry_price', 'exit_price', 'pnl', 
        'status', 'entry_date', 'exit_date', 'notes'
    ];

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }
}