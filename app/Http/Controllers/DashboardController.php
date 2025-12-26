<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\AccountTransaction; // <--- JANGAN LUPA IMPORT INI
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // --- 1. DATA SALDO SAAT INI ---
        $totalBalance = TradingAccount::where('user_id', $userId)->sum('balance');

        // --- 2. HITUNG PERUBAHAN 24 JAM TERAKHIR ---
        // Kita perlu tahu saldo kemarin untuk menghitung % kenaikan/penurunan
        
        // A. Hitung PnL (Profit/Loss) Hari Ini
        $todaysPnL = Trade::whereHas('tradingAccount', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->where('status', 'CLOSED')
        ->whereDate('updated_at', Carbon::today()) // Asumsi trade ditutup hari ini
        ->sum('pnl');

        // B. Hitung Net Flow (Deposit - Withdraw) Hari Ini
        // Kita pakai raw query biar cepat hitung selisihnya
        $todaysNetFlow = AccountTransaction::whereHas('tradingAccount', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereDate('date', Carbon::today())
        ->sum(\DB::raw("CASE WHEN type = 'DEPOSIT' THEN amount ELSE -amount END"));

        // C. Rekonstruksi Saldo Kemarin
        // Rumus: Saldo Sekarang - (Uang Masuk Hari Ini + Profit Hari Ini)
        $yesterdayBalance = $totalBalance - ($todaysNetFlow + $todaysPnL);

        // D. Hitung Persentase Perubahan
        $dailyChangePct = 0;
        if ($yesterdayBalance > 0) {
            $diff = $totalBalance - $yesterdayBalance;
            $dailyChangePct = ($diff / $yesterdayBalance) * 100;
        } elseif ($yesterdayBalance == 0 && $totalBalance > 0) {
            $dailyChangePct = 100; // Kalau kemarin 0 dan sekarang ada isi, naik 100%
        }

        // --- 3. DATA LAINNYA (SAMA SEPERTI SEBELUMNYA) ---
        $trades = Trade::whereHas('tradingAccount', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        $netProfit = $trades->where('status', 'CLOSED')->sum('pnl');

        $closedTrades = $trades->where('status', 'CLOSED');
        $totalClosed = $closedTrades->count();
        $wins = $closedTrades->where('pnl', '>', 0)->count();
        $winRate = $totalClosed > 0 ? round(($wins / $totalClosed) * 100, 1) : 0;
        $activePositions = $trades->where('status', 'OPEN')->count();

        // Data Chart Dummy (Bisa diganti logic real nanti)
        $growthSeries = [$totalBalance * 0.9, $totalBalance * 0.95, $totalBalance]; 
        $performanceSeries = [$wins, $closedTrades->where('pnl', '<=', 0)->count(), 0];

        // Recent Trades
        $recentTrades = Trade::with('tradingAccount')
            ->whereHas('tradingAccount', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->latest('created_at')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_balance' => $totalBalance,
                'daily_change_pct' => round($dailyChangePct, 2), // <--- KITA KIRIM INI
                'net_profit' => $netProfit,
                'win_rate' => $winRate,
                'active_positions' => $activePositions,
            ],
            'charts' => [
                'growth' => $growthSeries,
                'performance' => $performanceSeries
            ],
            'recentTrades' => $recentTrades
        ]);
    }
}