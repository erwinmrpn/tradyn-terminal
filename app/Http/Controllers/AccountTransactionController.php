<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\TradingAccount;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class AccountTransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // 1. Ambil Data Akun
        $accounts = TradingAccount::where('user_id', $userId)
            ->select('id', 'name', 'balance', 'currency')
            ->get();

        // 2. Tentukan Rentang Waktu (Current & Previous)
        $range = $request->input('range', 'all');
        $now = Carbon::now();
        
        $startDate = null;
        $endDate = $now;
        $prevStartDate = null;
        $prevEndDate = null;
        $comparisonLabel = "vs Previous Period"; // Label Default

        // Logika Penentuan Tanggal
        switch ($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $prevStartDate = $now->copy()->subDay()->startOfDay();
                $prevEndDate = $now->copy()->subDay()->endOfDay();
                $comparisonLabel = "vs Yesterday";
                break;
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                $prevStartDate = $now->copy()->subDays(2)->startOfDay();
                $prevEndDate = $now->copy()->subDays(2)->endOfDay();
                $comparisonLabel = "vs Day Before";
                break;
            case 'week': // Last 7 Days
                $startDate = $now->copy()->subDays(7);
                $prevStartDate = $now->copy()->subDays(14);
                $prevEndDate = $now->copy()->subDays(7);
                $comparisonLabel = "vs Previous 7 Days";
                break;
            case 'month': // Last 30 Days
                $startDate = $now->copy()->subDays(30);
                $prevStartDate = $now->copy()->subDays(60);
                $prevEndDate = $now->copy()->subDays(30);
                $comparisonLabel = "vs Previous 30 Days";
                break;
            case 'year': // Last 365 Days
                $startDate = $now->copy()->subYear();
                $prevStartDate = $now->copy()->subYears(2);
                $prevEndDate = $now->copy()->subYear();
                $comparisonLabel = "vs Previous Year";
                break;
            case 'all':
            default:
                // All time tidak ada perbandingan spesifik, kita set null
                $startDate = null; 
        }

        // 3. Helper Query Net Flow
        // Fungsi untuk menghitung (Deposit - Withdraw) dalam rentang tanggal tertentu
        $getNetFlow = function ($start, $end) use ($userId) {
            $q = AccountTransaction::whereHas('tradingAccount', function($query) use ($userId) {
                $query->where('user_id', $userId);
            });
            
            if ($start) {
                $q->where('date', '>=', $start);
            }
            if ($end && $end->ne(Carbon::now())) { // Jika end bukan 'sekarang'
                $q->where('date', '<=', $end);
            }

            return $q->sum(DB::raw("CASE WHEN type = 'DEPOSIT' THEN amount ELSE -amount END"));
        };

        // Hitung Current & Previous Net Flow
        $currentNetFlow = $getNetFlow($startDate, $endDate);
        $previousNetFlow = ($range !== 'all') ? $getNetFlow($prevStartDate, $prevEndDate) : 0;

        // Hitung Persentase Perubahan Net Flow
        $netFlowPct = 0;
        if ($range !== 'all') {
            if ($previousNetFlow != 0) {
                $netFlowPct = (($currentNetFlow - $previousNetFlow) / abs($previousNetFlow)) * 100;
            } elseif ($currentNetFlow != 0) {
                $netFlowPct = 100; // Jika sebelumnya 0 dan sekarang ada nilai
            }
        }

        // --- STATS LAIN (Total Balance & Daily Change) ---
        // (Kode lama tetap dipertahankan untuk kartu pertama)
        $totalBalance = $accounts->sum('balance');
        $todaysPnL = Trade::whereHas('tradingAccount', function ($q) use ($userId) { $q->where('user_id', $userId); })
            ->where('status', 'CLOSED')->whereDate('updated_at', Carbon::today())->sum('pnl');
        $todaysNetFlow = $getNetFlow(Carbon::today(), Carbon::now()); // Reuse helper
        $yesterdayBalance = $totalBalance - ($todaysNetFlow + $todaysPnL);
        $dailyChangePct = 0;
        if ($yesterdayBalance > 0) {
            $dailyChangePct = (($totalBalance - $yesterdayBalance) / $yesterdayBalance) * 100;
        } elseif ($yesterdayBalance == 0 && $totalBalance > 0) { $dailyChangePct = 100; }


        // 4. Ambil Data Transaksi untuk Tabel (Sesuai Filter)
        $query = AccountTransaction::with('tradingAccount')
            ->whereHas('tradingAccount', function($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($range === 'yesterday') {
             $query->where('date', '<=', $endDate);
        }

        $transactions = $query->latest('date')->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'date' => $tx->date,
                    'account_name' => $tx->tradingAccount->name,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'currency' => $tx->tradingAccount->currency,
                ];
            });

        return Inertia::render('AccountActivityLog/Index', [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'filters' => ['range' => $range],
            'stats' => [
                'total_balance' => $totalBalance,
                'daily_change_pct' => round($dailyChangePct, 2),
                
                // DATA BARU UNTUK KARTU NET FLOW
                'net_flow' => $currentNetFlow,
                'net_flow_pct' => round($netFlowPct, 2),
                'comparison_label' => $comparisonLabel
            ]
        ]);
    }

    public function store(Request $request)
    {
        // ... (Kode store TETAP SAMA seperti sebelumnya, tidak perlu diubah) ...
        $validated = $request->validate([
            'account_id' => 'required|exists:trading_accounts,id',
            'type' => 'required|in:DEPOSIT,WITHDRAW',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            AccountTransaction::create([
                'trading_account_id' => $validated['account_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
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