<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'currency',
        'balance',
        'market_type',
        'strategy_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}