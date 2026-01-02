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
        
        // [UPDATE] Kolom Terpisah
        'entry_date', 
        'entry_time',
        
        'type',
        'leverage',
        'margin_mode',
        'order_type',
        'entry_price',
        'quantity',
        'margin',
        'tp_price',
        'sl_price',
        'entry_screenshot',
        'entry_notes',
        'status',

        // [UPDATE] Kolom Terpisah
        'exit_date',
        'exit_time',
        
        'exit_price',
        'fee',
        'exit_reason',
        'exit_screenshot',
        'exit_notes',
        'pnl',
    ];

    /**
     * Relasi ke Trading Account.
     */
    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id');
    }
}