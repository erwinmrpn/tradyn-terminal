<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradingAccountController extends Controller
{
    // Tampilkan Halaman Setup
    public function create()
    {
        return Inertia::render('TradingAccount/Setup');
    }

    // Simpan Data Setup
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string',
            'balance' => 'required|numeric|min:0',
            'market_type' => 'required|string',
            'strategy_type' => 'required|string',
        ]);

        $request->user()->tradingAccounts()->create([
            'name' => $request->name,
            'currency' => $request->currency,
            'balance' => $request->balance,
            'market_type' => $request->market_type,
            'strategy_type' => $request->strategy_type,
        ]);

        // Setelah selesai, arahkan ke Dashboard
        return redirect()->route('dashboard');
    }
}