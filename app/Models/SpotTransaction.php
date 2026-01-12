<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotTransaction extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi
    protected $fillable = [
        'spot_trade_id',
        'type', // 'BUY' atau 'SELL'
        'price',
        'quantity',
        'fee',
        'realized_pnl',
        'transaction_date',
        'transaction_time',
        'notes',
        'chart_image'
    ];

    // Relasi ke Parent (SpotTrade)
    public function spotTrade()
    {
        return $this->belongsTo(SpotTrade::class);
    }
}