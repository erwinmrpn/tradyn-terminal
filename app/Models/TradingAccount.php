<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'exchange',
        'strategy_type',
        'balance',
        'currency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke History Spot
     */
    public function spotTrades(): HasMany
    {
        return $this->hasMany(SpotTrade::class, 'trading_account_id');
    }

    /**
     * Relasi ke History Futures
     */
    public function futuresTrades(): HasMany
    {
        return $this->hasMany(FuturesTrade::class, 'trading_account_id');
    }
    
    // Pastikan tidak ada fungsi "trades()" yang memanggil "Trade::class" lagi di sini
}