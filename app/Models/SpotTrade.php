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
        'price', // Entry Price
        'quantity',
        'target_sell_price',
        'target_buy_price',
        'holding_period',
        'buy_screenshot',
        'buy_notes',

        // Sell Data
        'sell_date',
        'sell_time',
        'sell_price',
        'fee',
        'pnl',
        'sell_screenshot',
        'sell_notes',
    ];

    public function tradingAccount()
    {
        return $this->belongsTo(TradingAccount::class);
    }
}