<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\TradingAccount;

class PortfolioController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Ambil data akun beserta history trade barunya (spot & futures)
        // Kita gunakan 'with' untuk eager loading agar performa cepat
        $accounts = TradingAccount::where('user_id', $userId)
            ->with(['spotTrades', 'futuresTrades']) 
            ->latest()
            ->get();

        // Hitung Total Balance Global
        $totalBalance = $accounts->sum('balance');

        return Inertia::render('Portfolio/Index', [
            'accounts' => $accounts,
            'totalBalance' => $totalBalance
        ]);
    }
}