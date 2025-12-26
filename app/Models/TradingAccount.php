<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'exchange',       // <--- Tambahkan ini
        'strategy_type',  // <--- Tambahkan ini
        'balance',
        'currency',
    ];

    // Relasi: Satu akun punya banyak transaksi deposit/wd
    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    // Relasi: Satu akun punya banyak history trade
    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }
}