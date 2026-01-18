<?php

namespace App\Http\Controllers;

use App\Models\FuturesTrade;
use App\Models\SpotTransaction;
use App\Models\SpotTrade;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TradeCalendarController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        // Filter Inputs
        $selectedYear = $request->input('year', Carbon::now()->year);
        $accountId = $request->input('account_id', 'all');
        
        // Strategy Type = Spot / Futures
        $strategyType = $request->input('strategy_type', 'all'); 
        
        // Market Category = Crypto / Stock / Commodity (sesuai kolom market_type di DB trading_accounts)
        $marketCategory = $request->input('market_category', 'all'); 

        // 1. GET AVAILABLE YEARS
        // Kita ambil tahun dari semua kemungkinan tanggal (Exit Futures, Buy Spot, Sell Spot)
        $futuresYears = FuturesTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->selectRaw('YEAR(exit_date) as year')->distinct();
            
        $spotBuyYears = SpotTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->selectRaw('YEAR(buy_date) as year')->distinct();
            
        $spotSellYears = SpotTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->selectRaw('YEAR(sell_date) as year')->distinct();
            
        $availableYears = $futuresYears
            ->union($spotBuyYears)
            ->union($spotSellYears)
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values()
            ->all();

        if (empty($availableYears)) $availableYears = [Carbon::now()->year];
        if (!in_array($selectedYear, $availableYears) && count($availableYears) > 0) $selectedYear = $availableYears[0];

        // 2. GET AVAILABLE MARKET TYPES (Untuk Dropdown Filter: CRYPTO, STOCK, dll)
        $availableMarketTypes = TradingAccount::where('user_id', $userId)
            ->select('market_type')
            ->distinct()
            ->pluck('market_type')
            ->all();

        // 3. PREPARE DAILY DATA QUERY
        // Kita gunakan logika Looping PHP agar perhitungan konsisten dengan Detail View (Parent Trade Count)
        $dailyData = [];

        // Helper function untuk mengisi array data harian
        $addToDaily = function($date, $pnl) use (&$dailyData) {
            if (!$date) return;
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = ['trades' => 0, 'pnl' => 0];
            }
            $dailyData[$date]['trades'] += 1;
            $dailyData[$date]['pnl'] += $pnl;
        };

        // --- A. FUTURES DATA ---
        if ($strategyType === 'all' || $strategyType === 'Futures') {
            $futuresQuery = FuturesTrade::whereHas('tradingAccount', function($sq) use ($userId, $marketCategory) {
                $sq->where('user_id', $userId);
                // Filter Market Category (Crypto/Stock)
                if ($marketCategory !== 'all') {
                    $sq->where('market_type', $marketCategory);
                }
            })
            ->where(function($q) use ($selectedYear) {
                $q->whereYear('entry_date', $selectedYear)
                  ->orWhereYear('exit_date', $selectedYear);
            });

            if ($accountId !== 'all') $futuresQuery->where('trading_account_id', $accountId);
            
            // Ambil data yang diperlukan saja
            $futuresTrades = $futuresQuery->get(['entry_date', 'exit_date', 'status', 'pnl']);

            foreach ($futuresTrades as $trade) {
                $entry = $trade->entry_date;
                $exit = $trade->exit_date;
                $isClosed = $trade->status === 'CLOSED';

                if ($isClosed && $entry == $exit) {
                    // Kasus 1: Open & Close di hari yang sama -> Hitung 1x (di hari tersebut) + PnL
                    $addToDaily($exit, $trade->pnl);
                } else {
                    // Kasus 2: Beda Hari
                    // Entry Event (Open Position) -> PnL 0
                    if ($entry && str_starts_with($entry, $selectedYear)) {
                        $addToDaily($entry, 0); 
                    }
                    // Exit Event (Close Position) -> Real PnL
                    if ($isClosed && $exit && str_starts_with($exit, $selectedYear)) {
                        $addToDaily($exit, $trade->pnl);
                    }
                }
            }
        }

        // --- B. SPOT DATA ---
        if ($strategyType === 'all' || $strategyType === 'Spot') {
            $spotQuery = SpotTrade::whereHas('tradingAccount', function($sq) use ($userId, $marketCategory) {
                $sq->where('user_id', $userId);
                if ($marketCategory !== 'all') {
                    $sq->where('market_type', $marketCategory);
                }
            })
            ->where(function($q) use ($selectedYear) {
                $q->whereYear('buy_date', $selectedYear)
                  ->orWhereYear('sell_date', $selectedYear);
            });

            if ($accountId !== 'all') $spotQuery->where('trading_account_id', $accountId);

            $spotTrades = $spotQuery->get(['buy_date', 'sell_date', 'status', 'pnl']);

            foreach ($spotTrades as $trade) {
                $buy = $trade->buy_date;
                $sell = $trade->sell_date;
                // Spot dianggap "Sold/Closed" jika sell_date terisi
                $isSold = !empty($sell);

                if ($isSold && $buy == $sell) {
                    // Kasus 1: Beli & Jual Habis di hari yang sama (Scalping Spot) -> Hitung 1x
                    $addToDaily($sell, $trade->pnl);
                } else {
                    // Kasus 2: Beda Hari
                    // Entry Event (Buy/Holding) -> PnL 0
                    if ($buy && str_starts_with($buy, $selectedYear)) {
                        $addToDaily($buy, 0);
                    }
                    // Exit Event (Sold) -> Real PnL
                    if ($isSold && $sell && str_starts_with($sell, $selectedYear)) {
                        $addToDaily($sell, $trade->pnl);
                    }
                }
            }
        }

        // 4. GENERATE MONTHLY OVERVIEW
        $monthlyOverview = [];
        for ($month = 1; $month <= 12; $month++) {
            $datePrefix = $selectedYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            $monthTrades = 0;
            $monthPnl = 0;

            foreach ($dailyData as $date => $data) {
                if (str_starts_with($date, $datePrefix)) {
                    $monthTrades += $data['trades'];
                    $monthPnl += $data['pnl'];
                }
            }

            $status = ($monthTrades > 0) ? ($monthPnl >= 0 ? 'PROFIT' : 'LOSS') : 'NO_TRADE';
            
            $monthlyOverview[] = [
                'month_name' => Carbon::create()->month($month)->format('F'),
                'month_index' => $month,
                'year' => $selectedYear,
                'total_trades' => $monthTrades,
                'total_pnl' => $monthPnl,
                'status' => $status
            ];
        }

        $accounts = TradingAccount::where('user_id', $userId)->select('id', 'name', 'exchange', 'market_type')->get();

        return Inertia::render('TradeCalendar/Index', [
            'availableYears' => $availableYears,
            'availableMarketTypes' => $availableMarketTypes, 
            'selectedYear' => (int)$selectedYear,
            'monthlyOverview' => $monthlyOverview,
            'dailyData' => $dailyData, 
            'accounts' => $accounts,
            'filters' => [
                'account_id' => $accountId, 
                'strategy_type' => $strategyType,
                'market_category' => $marketCategory
            ]
        ]);
    }

    // --- METHOD GET DETAILS (FIXED: Parent Logic & Extra Details for Futures) ---
    public function getDetails(Request $request)
    {
        $userId = auth()->id();
        $date = $request->input('date'); 
        $accountId = $request->input('account_id', 'all');
        $strategyType = $request->input('strategy_type', 'all');
        $marketCategory = $request->input('market_category', 'all');

        if (!$date) return response()->json([], 400);

        $trades = collect();

        // 1. FUTURES
        if ($strategyType === 'all' || $strategyType === 'Futures') {
            $q = FuturesTrade::with('tradingAccount')
                ->where(function($query) use ($date) {
                    $query->where(function($q) use ($date) {
                        $q->where('status', 'CLOSED')->whereDate('exit_date', $date);
                    })->orWhere(function($q) use ($date) {
                        $q->where('status', 'OPEN')->whereDate('entry_date', $date);
                    });
                })
                ->whereHas('tradingAccount', function($sq) use ($userId, $marketCategory) {
                    $sq->where('user_id', $userId);
                    if ($marketCategory !== 'all') $sq->where('market_type', $marketCategory);
                });
            
            if ($accountId !== 'all') $q->where('trading_account_id', $accountId);

            $futures = $q->get()->map(function($t) {
                $isOpen = $t->status === 'OPEN';
                return [
                    'id' => 'F-' . $t->id,
                    'is_parent' => true, // TRUE agar bisa di-expand untuk melihat detail notes/fee
                    'time' => $isOpen ? ($t->entry_time ? $t->entry_date . ' ' . $t->entry_time : $t->entry_date) 
                                      : ($t->exit_time ? $t->exit_date . ' ' . $t->exit_time : $t->exit_date),
                    'account_name' => $t->tradingAccount->name ?? '-',
                    'symbol' => $t->symbol,
                    'side' => $t->type,
                    // Pemetaan Type yang Benar
                    'market_type' => $t->tradingAccount->market_type ?? '-', // Contoh: CRYPTO
                    'strategy_type' => 'FUTURES', // Label Strategy
                    'price' => $isOpen ? $t->entry_price : $t->exit_price,
                    'size' => $t->quantity,
                    'pnl' => $t->pnl,
                    'status' => $isOpen ? 'OPEN' : ($t->pnl > 0 ? 'WIN' : ($t->pnl < 0 ? 'LOSS' : 'BE')),
                    
                    // Detail tambahan untuk Dropdown Info Futures
                    'leverage' => $t->leverage,
                    'margin_mode' => $t->margin_mode,
                    'fee' => $t->fee,
                    'entry_notes' => $t->entry_notes,
                    'exit_notes' => $t->exit_notes,
                    'children' => [] // Array kosong karena tidak ada sub-transaksi seperti Spot
                ];
            });
            $trades = $trades->merge($futures);
        }

        // 2. SPOT
        if ($strategyType === 'all' || $strategyType === 'Spot') {
            $q = SpotTrade::with(['tradingAccount', 'spotTransactions'])
                ->where(function($query) use ($date) {
                    $query->whereDate('buy_date', $date)
                          ->orWhereDate('sell_date', $date);
                })
                ->whereHas('tradingAccount', function($sq) use ($userId, $marketCategory) {
                    $sq->where('user_id', $userId);
                    if ($marketCategory !== 'all') $sq->where('market_type', $marketCategory);
                });

            if ($accountId !== 'all') $q->where('trading_account_id', $accountId);

            $spots = $q->get()->map(function($t) use ($date) {
                $isSoldToday = $t->sell_date == $date;
                
                // Map sub-transaksi untuk dropdown Spot
                $children = $t->spotTransactions->map(function($trans) {
                    return [
                        'id' => $trans->id,
                        'date' => $trans->transaction_date,
                        'time' => $trans->transaction_time,
                        'type' => $trans->type, 
                        'price' => $trans->price,
                        'qty' => $trans->quantity,
                        'pnl' => $trans->realized_pnl - $trans->fee
                    ];
                });

                return [
                    'id' => 'S-' . $t->id,
                    'is_parent' => true,
                    'time' => $isSoldToday 
                                ? ($t->sell_time ? $t->sell_date . ' ' . $t->sell_time : $t->sell_date)
                                : ($t->buy_time ? $t->buy_date . ' ' . $t->buy_time : $t->buy_date),
                    'account_name' => $t->tradingAccount->name ?? '-',
                    'symbol' => $t->symbol,
                    'side' => $t->status === 'OPEN' ? 'HOLDING' : ($isSoldToday ? 'SOLD' : 'HOLDING'),
                    // Pemetaan Type yang Benar
                    'market_type' => $t->tradingAccount->market_type ?? '-', // Contoh: STOCK
                    'strategy_type' => 'SPOT', // Label Strategy
                    'price' => $t->price,
                    'size' => $t->quantity,
                    'pnl' => $t->pnl,
                    'status' => $t->status === 'OPEN' ? 'HOLDING' : ($t->pnl > 0 ? 'WIN' : 'LOSS'),
                    'children' => $children
                ];
            });
            $trades = $trades->merge($spots);
        }

        $sortedTrades = $trades->sortByDesc('time')->values();
        return response()->json($sortedTrades);
    }
}