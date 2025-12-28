<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradingAccount; // <--- Jangan lupa import ini
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradeLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        // 1. Ambil Parameter Filter
        $type = $request->input('type', 'SPOT'); // Default SPOT
        $accountId = $request->input('account_id', 'all'); // Default All Accounts

        // 2. Ambil Daftar Akun untuk Dropdown (Sesuai Type)
        // Jika Tab SPOT, hanya tampilkan akun Spot.
        $accounts = TradingAccount::where('user_id', $userId)
            ->where('strategy_type', $type)
            ->select('id', 'name', 'exchange', 'balance', 'currency')
            ->get();

        // 3. Hitung Total Balance (Sesuai Filter)
        if ($accountId !== 'all') {
            // Jika memilih satu akun spesifik
            $totalBalance = $accounts->where('id', $accountId)->sum('balance');
        } else {
            // Jika memilih 'All Exchange', total semua akun di tipe ini
            $totalBalance = $accounts->sum('balance');
        }

        // 4. Query Trades (Sesuai Filter)
        $query = Trade::with('tradingAccount')
            ->whereHas('tradingAccount', function($q) use ($userId, $type) {
                $q->where('user_id', $userId)
                  ->where('strategy_type', $type);
            });

        // Filter by Account ID jika user memilih spesifik
        if ($accountId !== 'all') {
            $query->where('trading_account_id', $accountId);
        }

        $trades = $query->latest('entry_date')->get();

        return Inertia::render('TradeLog/Index', [
            'trades' => $trades,
            'activeType' => $type,
            'accounts' => $accounts,          // Data untuk Dropdown
            'totalBalance' => $totalBalance,  // Data Saldo
            'selectedAccountId' => $accountId // State Dropdown aktif
        ]);
    }
}