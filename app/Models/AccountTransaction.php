<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransaction extends Model
{
    protected $fillable = [
        'trading_account_id',
        'type',
        'amount',
        'date',
        'notes'
    ];

    // Relasi balik ke Trading Account
    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }
}