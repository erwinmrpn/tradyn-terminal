<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortfolioController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // 1. Ambil Semua Akun User
        $accounts = TradingAccount::where('user_id', $userId)
            ->withCount(['trades as total_trades'])
            ->withSum(['trades as total_pnl' => function($q) {
                $q->where('status', 'CLOSED');
            }], 'pnl')
            ->get();

        // 2. Hitung Total Net Worth
        $totalBalance = $accounts->sum('balance');

        // 3. Siapkan Data Chart Alokasi (Berdasarkan Strategy Type)
        // Contoh: Berapa persen di SPOT, berapa di FUTURES
        $allocation = $accounts->groupBy('strategy_type')
            ->map(function ($group) use ($totalBalance) {
                $groupBalance = $group->sum('balance');
                return $totalBalance > 0 ? round(($groupBalance / $totalBalance) * 100, 1) : 0;
            });

        return Inertia::render('Portfolio/Index', [
            'accounts' => $accounts,
            'stats' => [
                'total_net_worth' => $totalBalance,
                'total_accounts' => $accounts->count(),
                'allocation_series' => $allocation->values()->toArray(),
                'allocation_labels' => $allocation->keys()->toArray(),
            ]
        ]);
    }
}