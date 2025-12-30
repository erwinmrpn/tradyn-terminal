<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_account_id',
        'symbol',
        'market_type',
        'type',
        'price',
        'quantity',
        'total',
        'fee',
        'date',
        'notes',
    ];

    /**
     * Relasi ke TradingAccount.
     * Perhatikan nama fungsinya adalah 'tradingAccount' (camelCase).
     * Maka di Controller wajib dipanggil dengan: with('tradingAccount')
     */
    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }
}