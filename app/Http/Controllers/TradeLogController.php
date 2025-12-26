<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradeLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        // 1. Ambil Filter Type (Default: SPOT)
        // Ini yang akan diubah oleh tombol toggle nanti
        $type = $request->input('type', 'SPOT'); // Nilai bisa 'SPOT' atau 'FUTURES'

        // 2. Query Trades Berdasarkan Tipe Akun
        $trades = Trade::with('tradingAccount')
            ->whereHas('tradingAccount', function($q) use ($userId, $type) {
                $q->where('user_id', $userId)
                  ->where('strategy_type', $type); // Filter Magic terjadi disini
            })
            ->latest('entry_date')
            ->get();

        return Inertia::render('TradeLog/Index', [
            'trades' => $trades,
            'activeType' => $type // Kirim balik status tombol aktif ke Vue
        ]);
    }
}