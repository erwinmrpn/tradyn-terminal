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

        // Ambil data akun.
        // Fungsi 'get()' otomatis mengambil kolom baru 'market_type' dari database.
        // Kita tetap menggunakan 'with' untuk eager loading history trade agar performa cepat.
        $accounts = TradingAccount::where('user_id', $userId)
            ->with(['spotTrades', 'futuresTrades']) 
            ->latest()
            ->get();

        // Hitung Total Balance Global dari semua akun
        $totalBalance = $accounts->sum('balance');

        return Inertia::render('Portfolio/Index', [
            'accounts' => $accounts,
            'totalBalance' => $totalBalance
        ]);
    }
}