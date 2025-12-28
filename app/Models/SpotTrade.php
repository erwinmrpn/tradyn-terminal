<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotTrade extends Model
{
    protected $fillable = [
        'trading_account_id', 'symbol', 'market_type', 'type', 
        'price', 'quantity', 'fee', 'date', 'notes' 
    ];

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }
}