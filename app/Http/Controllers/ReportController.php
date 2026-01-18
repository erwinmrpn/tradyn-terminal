<?php

namespace App\Http\Controllers;

use App\Models\FuturesTrade;
use App\Models\SpotTrade;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // --- 1. FILTER INPUTS ---
        $selectedYear = $request->input('year', Carbon::now()->year);
        $accountId = $request->input('account_id', 'all');
        $strategyType = $request->input('strategy_type', 'all'); // Spot / Futures
        $marketCategory = $request->input('market_category', 'all'); // Crypto / Stock

        // --- 2. QUERY DATA (Hanya ambil Trade yang sudah SELESAI) ---
        
        $trades = collect();

        // A. FUTURES DATA (Status = CLOSED)
        if ($strategyType === 'all' || $strategyType === 'Futures') {
            $q = FuturesTrade::with('tradingAccount')
                ->where('status', 'CLOSED') // Hanya yang sudah ada hasil PnL
                ->whereYear('exit_date', $selectedYear)
                ->whereHas('tradingAccount', function($sq) use ($userId, $marketCategory) {
                    $sq->where('user_id', $userId);
                    if ($marketCategory !== 'all') {
                        $sq->where('market_type', $marketCategory);
                    }
                });

            if ($accountId !== 'all') $q->where('trading_account_id', $accountId);

            $futures = $q->get()->map(function($t) {
                return [
                    'date' => $t->exit_date, // Tanggal realisasi profit
                    'symbol' => $t->symbol,
                    'pnl' => (float) $t->pnl,
                    'type' => 'FUTURES',
                    'status' => 'CLOSED'
                ];
            });
            $trades = $trades->merge($futures);
        }

        // B. SPOT DATA (Status = SOLD)
        // Kita gunakan SpotTrade Induk yg sudah SOLD (pnl != null)
        if ($strategyType === 'all' || $strategyType === 'Spot') {
            $q = SpotTrade::with('tradingAccount')
                ->whereNotNull('sell_date') // Sudah terjual
                ->whereYear('sell_date', $selectedYear)
                ->whereHas('tradingAccount', function($sq) use ($userId, $marketCategory) {
                    $sq->where('user_id', $userId);
                    if ($marketCategory !== 'all') {
                        $sq->where('market_type', $marketCategory);
                    }
                });

            if ($accountId !== 'all') $q->where('trading_account_id', $accountId);

            $spots = $q->get()->map(function($t) {
                return [
                    'date' => $t->sell_date, // Tanggal realisasi profit
                    'symbol' => $t->symbol,
                    'pnl' => (float) $t->pnl,
                    'type' => 'SPOT',
                    'status' => 'SOLD'
                ];
            });
            $trades = $trades->merge($spots);
        }

        // Urutkan berdasarkan tanggal (Ascending untuk kalkulasi kurva)
        $sortedTrades = $trades->sortBy('date')->values();

        // --- 3. CALCULATE METRICS (KPI) ---
        
        $totalTrades = $sortedTrades->count();
        $netPnl = $sortedTrades->sum('pnl');
        
        $winningTrades = $sortedTrades->where('pnl', '>', 0);
        $losingTrades = $sortedTrades->where('pnl', '<', 0);
        $beTrades = $sortedTrades->where('pnl', '=', 0);

        $winCount = $winningTrades->count();
        $lossCount = $losingTrades->count();
        $beCount = $beTrades->count();

        // Win Rate Calculation
        $winRate = $totalTrades > 0 ? ($winCount / $totalTrades) * 100 : 0;

        // Profit Factor Calculation (Gross Profit / Gross Loss)
        $grossProfit = $winningTrades->sum('pnl');
        $grossLoss = abs($losingTrades->sum('pnl'));
        $profitFactor = $grossLoss > 0 ? ($grossProfit / $grossLoss) : ($grossProfit > 0 ? 99.99 : 0); // Cap at 99.99 if no loss

        // --- 4. PREPARE EQUITY CURVE DATA ---
        // Kita buat akumulasi PnL per hari
        $equityCurve = [];
        $runningBalance = 0;
        
        // Group by Date first
        $groupedByDate = $sortedTrades->groupBy('date');
        
        foreach ($groupedByDate as $date => $dayTrades) {
            $dayPnl = $dayTrades->sum('pnl');
            $runningBalance += $dayPnl;
            
            $equityCurve[] = [
                'date' => $date,
                'value' => $runningBalance
            ];
        }

        // --- 5. PREPARE ASSET PERFORMANCE ---
        $assetPerformance = $sortedTrades->groupBy('symbol')->map(function($rows, $symbol) {
            return [
                'symbol' => $symbol,
                'pnl' => $rows->sum('pnl'),
                'count' => $rows->count(),
                'win_rate' => $rows->count() > 0 ? ($rows->where('pnl', '>', 0)->count() / $rows->count()) * 100 : 0
            ];
        })->sortByDesc('pnl')->values()->all(); // Sort dari profit terbesar

        // --- 6. DATA PENDUKUNG VIEW ---
        $accounts = TradingAccount::where('user_id', $userId)->get();
        
        // Filter options data
        $availableYears = array_unique(array_merge(
            FuturesTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))->pluck('exit_date')->map(fn($d) => substr($d, 0, 4))->toArray(),
            SpotTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))->whereNotNull('sell_date')->pluck('sell_date')->map(fn($d) => substr($d, 0, 4))->toArray(),
            [Carbon::now()->year]
        ));
        rsort($availableYears);

        return Inertia::render('Report/Index', [
            'filters' => [
                'year' => (int)$selectedYear,
                'account_id' => $accountId,
                'strategy_type' => $strategyType,
                'market_category' => $marketCategory
            ],
            'accounts' => $accounts,
            'availableYears' => array_values($availableYears),
            'report' => [
                'kpi' => [
                    'net_pnl' => $netPnl,
                    'win_rate' => round($winRate, 2),
                    'profit_factor' => round($profitFactor, 2),
                    'total_trades' => $totalTrades,
                ],
                'win_loss_distribution' => [
                    'win' => $winCount,
                    'loss' => $lossCount,
                    'be' => $beCount
                ],
                'equity_curve' => $equityCurve,
                'asset_performance' => $assetPerformance
            ]
        ]);
    }
}