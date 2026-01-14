<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\TradingAccount;
use App\Models\FuturesTrade;
use App\Models\SpotTrade;
use App\Models\SpotTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class AccountTransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // 1. Ambil Parameter Filter
        $range = $request->input('range', 'all');
        $strategyType = $request->input('strategy_type', 'all');
        $marketType = $request->input('market_type', 'all');
        $accountId = $request->input('account_id', 'all');

        // 2. Ambil Daftar Akun & Hitung Change % Per Timeframe (UPDATE UTAMA DISINI)
        // Kita hitung pct_today, pct_week, dll untuk setiap akun agar Form di Frontend dinamis
        $accountsRaw = TradingAccount::where('user_id', $userId)
            ->select('id', 'name', 'balance', 'currency', 'exchange', 'market_type', 'strategy_type')
            ->get();

        $accounts = $accountsRaw->map(function ($acc) {
            
            // Helper Function untuk hitung perubahan saldo akun spesifik berdasarkan Start Date
            $calculateChange = function($startDate) use ($acc) {
                $startStr = $startDate->format('Y-m-d H:i:s');
                $endStr = Carbon::now()->format('Y-m-d H:i:s');

                // A. Net Flow (Deposit - Withdraw) pada periode ini
                $periodFlow = AccountTransaction::where('trading_account_id', $acc->id)
                    ->whereBetween('date', [$startDate, Carbon::now()]) 
                    ->selectRaw("SUM(CASE WHEN type = 'DEPOSIT' THEN amount ELSE -amount END) as net_flow")
                    ->value('net_flow') ?? 0;

                // B. PnL (Futures + Spot) pada periode ini
                
                // Futures Net PnL
                $futuresPnL = FuturesTrade::where('trading_account_id', $acc->id)
                    ->where('status', 'CLOSED')
                    ->whereBetween(DB::raw("CONCAT(exit_date, ' ', exit_time)"), [$startStr, $endStr])
                    ->sum('pnl');

                // Spot Sell (Realized PnL - Fee)
                $spotSell = SpotTransaction::whereHas('spotTrade', fn($q) => $q->where('trading_account_id', $acc->id))
                    ->where('type', 'SELL')
                    ->whereBetween(DB::raw("CONCAT(transaction_date, ' ', transaction_time)"), [$startStr, $endStr])
                    ->selectRaw('SUM(realized_pnl) as pnl, SUM(fee) as fee')
                    ->first();
                
                // Spot Buy Costs (Fee Beli & DCA - Mengurangi Balance)
                $spotBuyFee = SpotTrade::where('trading_account_id', $acc->id)
                    ->whereBetween(DB::raw("CONCAT(buy_date, ' ', buy_time)"), [$startStr, $endStr])
                    ->sum('fee');
                $spotDcaFee = SpotTransaction::whereHas('spotTrade', fn($q) => $q->where('trading_account_id', $acc->id))
                    ->where('type', 'BUY')
                    ->whereBetween(DB::raw("CONCAT(transaction_date, ' ', transaction_time)"), [$startStr, $endStr])
                    ->sum('fee');

                // Total PnL Periode
                $totalPeriodPnL = $futuresPnL + (($spotSell->pnl ?? 0) - ($spotSell->fee ?? 0)) - ($spotBuyFee + $spotDcaFee);

                // C. Hitung Saldo Awal Periode (Reverse Calculation)
                // Balance Awal = Balance Sekarang - (Flow Periode + PnL Periode)
                $startBalance = $acc->balance - ($periodFlow + $totalPeriodPnL);

                // D. Hitung Persentase
                if ($startBalance != 0) {
                    return round((($acc->balance - $startBalance) / abs($startBalance)) * 100, 2);
                } elseif ($acc->balance > 0 && $startBalance == 0) {
                    return 100;
                }
                return 0;
            };

            // Hitung untuk semua timeframe yang dibutuhkan dropdown form
            $acc->pct_today = $calculateChange(Carbon::today());
            $acc->pct_week = $calculateChange(Carbon::now()->subDays(7));
            $acc->pct_month = $calculateChange(Carbon::now()->subDays(30));
            $acc->pct_year = $calculateChange(Carbon::now()->subYear());

            return $acc;
        });

        // 3. Filter ID Akun (Logic Lama Tetap Dipertahankan)
        $filteredAccountIds = $accounts->filter(function ($acc) use ($strategyType, $marketType, $accountId) {
            $matchStrategy = ($strategyType === 'all' || $acc->strategy_type === $strategyType);
            $matchMarket = ($marketType === 'all' || $acc->market_type === $marketType);
            $matchId = ($accountId === 'all' || (string)$acc->id === (string)$accountId);
            return $matchStrategy && $matchMarket && $matchId;
        })->pluck('id');

        // 4. Hitung Total Balance (Current)
        $totalBalance = $accounts->whereIn('id', $filteredAccountIds)->sum('balance');

        // 5. Tentukan Rentang Waktu (Untuk Filter Global & Perbandingan Balance Global)
        $now = Carbon::now();
        $startDate = null;
        $endDate = $now;
        $comparisonLabel = "vs Previous Period";

        // Variabel untuk menghitung start balance
        $calculateChange = true; 

        switch ($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $comparisonLabel = "vs Yesterday";
                break;
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                $comparisonLabel = "vs Day Before";
                break;
            case 'week':
                $startDate = $now->copy()->subDays(7);
                $comparisonLabel = "vs 7 Days Ago";
                break;
            case 'month':
                $startDate = $now->copy()->subDays(30);
                $comparisonLabel = "vs 30 Days Ago";
                break;
            case 'year':
                $startDate = $now->copy()->subYear();
                $comparisonLabel = "vs 1 Year Ago";
                break;
            case 'all':
            default:
                $startDate = null; 
                $calculateChange = false; // Tidak hitung perubahan untuk All Time
        }

        // 6. Query Dasar Transaksi
        $baseQuery = AccountTransaction::whereIn('trading_account_id', $filteredAccountIds);

        // Helper filter tanggal
        $applyDateFilter = function ($q) use ($startDate, $range, $endDate) {
            if ($startDate) {
                $q->where('date', '>=', $startDate);
            }
            if ($range === 'yesterday') {
                $q->where('date', '<=', $endDate);
            }
            return $q;
        };

        // --- STATISTIK INFLOW & OUTFLOW (Box 2 & 3) ---
        $inflow = $applyDateFilter((clone $baseQuery)->where('type', 'DEPOSIT'))->sum('amount');
        $outflow = $applyDateFilter((clone $baseQuery)->where('type', 'WITHDRAW'))->sum('amount');

        // --- STATISTIK BALANCE CHANGE GLOBAL (Box 1) ---
        $balanceChangePct = 0;

        if ($calculateChange && $startDate) {
            // Kita akan menghitung "Net Flow" dan "PnL" dari $startDate sampai $now
            // Rumus: Balance Awal Periode = Balance Sekarang - (NetFlow Periode + PnL Periode)
            
            // A. Net Flow Periode (Deposit - Withdraw)
            $periodNetFlowQuery = AccountTransaction::whereIn('trading_account_id', $filteredAccountIds)
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $now); // Selalu hitung sampai sekarang untuk dapetin balance awal real

            $periodDeposit = (clone $periodNetFlowQuery)->where('type', 'DEPOSIT')->sum('amount');
            $periodWithdraw = (clone $periodNetFlowQuery)->where('type', 'WITHDRAW')->sum('amount');
            $periodNetFlow = $periodDeposit - $periodWithdraw;

            // B. PnL Periode (Futures + Spot)
            $pnlStartStr = $startDate->format('Y-m-d H:i:s');
            $pnlEndStr = $now->format('Y-m-d H:i:s');

            // Futures Net PnL
            $futuresPnl = FuturesTrade::whereIn('trading_account_id', $filteredAccountIds)
                ->where('status', 'CLOSED')
                ->whereBetween(DB::raw("CONCAT(exit_date, ' ', exit_time)"), [$pnlStartStr, $pnlEndStr])
                ->sum('pnl');

            // Spot Gross PnL - Fee Jual
            $spotSellData = SpotTransaction::whereHas('spotTrade', function ($q) use ($filteredAccountIds) {
                    $q->whereIn('trading_account_id', $filteredAccountIds);
                })
                ->where('type', 'SELL')
                ->whereBetween(DB::raw("CONCAT(transaction_date, ' ', transaction_time)"), [$pnlStartStr, $pnlEndStr])
                ->selectRaw('SUM(realized_pnl) as gross_pnl, SUM(fee) as fee')
                ->first();
            $spotPnl = ($spotSellData->gross_pnl ?? 0) - ($spotSellData->fee ?? 0);

            // Spot Fee Beli & DCA (Mengurangi Balance)
            $spotBuyFee = SpotTrade::whereIn('trading_account_id', $filteredAccountIds)
                ->whereBetween(DB::raw("CONCAT(buy_date, ' ', buy_time)"), [$pnlStartStr, $pnlEndStr])
                ->sum('fee');
            $spotDcaFee = SpotTransaction::whereHas('spotTrade', function ($q) use ($filteredAccountIds) {
                    $q->whereIn('trading_account_id', $filteredAccountIds);
                })
                ->where('type', 'BUY')
                ->whereBetween(DB::raw("CONCAT(transaction_date, ' ', transaction_time)"), [$pnlStartStr, $pnlEndStr])
                ->sum('fee');

            // Total PnL Periode (Net)
            $periodPnL = $futuresPnl + $spotPnl - ($spotBuyFee + $spotDcaFee);

            // C. Hitung Balance Awal Periode & Persentase
            $startBalance = $totalBalance - ($periodNetFlow + $periodPnL);

            if ($startBalance > 0) {
                $balanceChangePct = (($totalBalance - $startBalance) / $startBalance) * 100;
            } elseif ($startBalance == 0 && $totalBalance > 0) {
                $balanceChangePct = 100;
            }
        }

        // 7. Data Tabel Transaksi
        $transactions = $applyDateFilter(clone $baseQuery)
            ->with('tradingAccount')
            ->latest('date')
            ->latest('id')
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'date' => $tx->date,
                    'account_name' => $tx->tradingAccount ? ($tx->tradingAccount->name . ' (' . $tx->tradingAccount->exchange . ')') : 'Unknown',
                    'market_type' => $tx->tradingAccount ? $tx->tradingAccount->market_type : '',
                    'strategy_type' => $tx->tradingAccount ? $tx->tradingAccount->strategy_type : '',
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'currency' => $tx->tradingAccount ? $tx->tradingAccount->currency : 'USD',
                    'notes' => $tx->notes, // [TAMBAHKAN BARIS INI]
                ];
            });

        $availableMarketTypes = $accountsRaw->pluck('market_type')->unique()->values()->all();

        return Inertia::render('AccountActivityLog/Index', [
            'accounts' => $accounts, // Data akun kini berisi pct_today, pct_week, dll
            'transactions' => $transactions,
            'availableMarketTypes' => $availableMarketTypes,
            'filters' => [
                'range' => $range,
                'strategy_type' => $strategyType,
                'market_type' => $marketType,
                'account_id' => $accountId,
            ],
            'stats' => [
                'total_balance' => $totalBalance,
                'balance_change_pct' => round($balanceChangePct, 2), // Global stats tetap jalan
                'inflow' => $inflow,
                'outflow' => $outflow,
                'comparison_label' => $comparisonLabel
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:trading_accounts,id',
            'type' => 'required|in:DEPOSIT,WITHDRAW',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:255', 
        ]);

        DB::transaction(function () use ($validated) {
            AccountTransaction::create([
                'trading_account_id' => $validated['account_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);
            
            $account = TradingAccount::find($validated['account_id']);
            
            if ($validated['type'] === 'DEPOSIT') {
                $account->increment('balance', $validated['amount']);
            } else {
                $account->decrement('balance', $validated['amount']);
            }
        });

        return redirect()->back()->with('success', 'Transaction recorded.');
    }
}