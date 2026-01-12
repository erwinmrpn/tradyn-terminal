<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\TradingAccount;
use App\Models\SpotTrade;
use App\Models\SpotTransaction;
use App\Models\FuturesTrade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // 1. GLOBAL STATS
        $totalBalance = TradingAccount::where('user_id', $userId)->sum('balance');
        
        $activeFutures = FuturesTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->where('status', 'OPEN')->count();
        $activeSpot = SpotTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->where('status', 'OPEN')->count();
        $activePositions = $activeFutures + $activeSpot;

        // 2. METRICS (Data Today, Week, Month)
        $metrics = [
            'TODAY' => $this->getMetricsByPeriod($userId, Carbon::today(), Carbon::now(), Carbon::yesterday()),
            'WEEK'  => $this->getMetricsByPeriod($userId, Carbon::now()->startOfWeek(), Carbon::now(), Carbon::now()->subWeek()->startOfWeek()),
            'MONTH' => $this->getMetricsByPeriod($userId, Carbon::now()->startOfMonth(), Carbon::now(), Carbon::now()->subMonth()->startOfMonth()),
        ];

        // 3. CHART DATA (FIX: Menggunakan 3 dataset berbeda untuk switch di frontend)
        $charts = [
            'TODAY' => $this->getHourlyPnLChart($userId), // Grafik Per Jam
            'WEEK'  => $this->getDailyPnLChart($userId, 7), // 7 Hari Terakhir
            'MONTH' => $this->getDailyPnLChart($userId, 30), // 30 Hari Terakhir
        ];

        // 4. RECENT ACTIVITY
        $recentSpot = SpotTrade::with('tradingAccount')
            ->whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->latest('buy_date')->take(5)->get()
            ->map(fn($t) => $this->formatRecentTrade($t, 'SPOT'));

        $recentFutures = FuturesTrade::with('tradingAccount')
            ->whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->latest('entry_date')->take(5)->get()
            ->map(fn($t) => $this->formatRecentTrade($t, 'FUTURES'));

        $recentTrades = $recentSpot->concat($recentFutures)->sortByDesc('display_date_raw')->take(5)->values();

        return Inertia::render('Dashboard', [
            'totalBalance' => $totalBalance,
            'activePositions' => $activePositions,
            'metrics' => $metrics,
            'charts' => $charts, // Kirim variabel charts (bukan chartData)
            'recentTrades' => $recentTrades
        ]);
    }

    // --- HELPER METHODS ---

    private function getMetricsByPeriod($userId, $start, $end, $prevStart)
    {
        $daysDiff = $start->diffInDays($end) + 1;
        $prevEnd = $prevStart->copy()->addDays($daysDiff - 1);
        $current = $this->calculatePnLAndFee($userId, $start, $end);
        $previous = $this->calculatePnLAndFee($userId, $prevStart, $prevEnd);

        return [
            'gross_pnl' => $current['gross_pnl'],
            'fee'       => $current['fee'],
            'net_pnl'   => $current['net_pnl'],
            'roi'       => $current['roi'],
            'prev_gross_pnl' => $previous['gross_pnl'],
            'prev_net_pnl'   => $previous['net_pnl'],
            'prev_roi'       => $previous['roi'],
        ];
    }

    private function calculatePnLAndFee($userId, $start, $end)
    {
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        // FUTURES
        $futuresData = FuturesTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->where('status', 'CLOSED')
            ->whereBetween(DB::raw("CONCAT(exit_date, ' ', exit_time)"), [$startStr, $endStr])
            ->selectRaw('SUM(pnl) as net_pnl, SUM(fee) as fee, SUM(margin) as total_margin')
            ->first();

        $futuresNet  = $futuresData->net_pnl ?? 0;
        $futuresFee  = $futuresData->fee ?? 0;
        $futuresCost = $futuresData->total_margin ?? 0;
        $futuresGross = $futuresNet + $futuresFee;

        // SPOT
        $spotData = SpotTransaction::whereHas('spotTrade.tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->where('type', 'SELL')
            ->whereBetween(DB::raw("CONCAT(transaction_date, ' ', transaction_time)"), [$startStr, $endStr])
            ->selectRaw('SUM(realized_pnl) as gross_pnl, SUM(fee) as fee, SUM(price * quantity) as revenue')
            ->first();

        $spotGross   = $spotData->gross_pnl ?? 0;
        $spotTransFee = $spotData->fee ?? 0;
        $spotRevenue = $spotData->revenue ?? 0;
        $spotCost = $spotRevenue - $spotGross; 

        // SPOT INITIAL FEES
        $spotInitialFee = SpotTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->whereBetween(DB::raw("CONCAT(buy_date, ' ', buy_time)"), [$startStr, $endStr])
            ->sum('fee');

        $totalGross = $futuresGross + $spotGross;
        $totalFee   = $futuresFee + $spotTransFee + $spotInitialFee;
        $netPnl     = $totalGross - $totalFee;
        $totalCost  = $futuresCost + $spotCost;

        $roi = ($totalCost > 0) ? ($netPnl / $totalCost) * 100 : 0;

        return [
            'gross_pnl' => $totalGross,
            'fee'       => $totalFee,
            'net_pnl'   => $netPnl,
            'roi'       => $roi
        ];
    }

    private function getHourlyPnLChart($userId)
    {
        $categories = [];
        $data = [];
        $currentHour = Carbon::now()->hour;
        
        for ($i = 0; $i <= $currentHour; $i++) {
            $start = Carbon::today()->setHour($i)->setMinute(0)->setSecond(0);
            $end   = Carbon::today()->setHour($i)->setMinute(59)->setSecond(59);
            $metrics = $this->calculatePnLAndFee($userId, $start, $end);
            
            // Format ISO agar Chart Frontend bisa baca sebagai datetime
            $categories[] = $start->toIso8601String(); 
            $data[] = $metrics['net_pnl'];
        }

        return ['categories' => $categories, 'series' => [['name' => 'Net PnL', 'data' => $data]]];
    }

    private function getDailyPnLChart($userId, $days)
    {
        $dates = [];
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $start = Carbon::today()->subDays($i)->startOfDay();
            $end   = Carbon::today()->subDays($i)->endOfDay();
            $metrics = $this->calculatePnLAndFee($userId, $start, $end);
            
            // Format Y-m-d agar Chart Frontend bisa baca sebagai datetime
            $dates[] = $start->format('Y-m-d'); 
            $data[] = $metrics['net_pnl'];
        }

        return ['categories' => $dates, 'series' => [['name' => 'Net PnL', 'data' => $data]]];
    }

    private function formatRecentTrade($trade, $category)
    {
        $trade->category = $category;
        if ($category === 'SPOT') {
            $trade->display_date = $trade->buy_date;
            $trade->display_date_raw = $trade->buy_date;
            $trade->margin = $trade->price * $trade->quantity;
        } else {
            $trade->display_date = $trade->entry_date;
            $trade->display_date_raw = $trade->entry_date;
        }
        return $trade;
    }
}