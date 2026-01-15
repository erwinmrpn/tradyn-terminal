<?php

namespace App\Http\Controllers;

use App\Models\FuturesTrade;
use App\Models\SpotTransaction;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Tambah Import DB

class TradeCalendarController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        $selectedYear = $request->input('year', Carbon::now()->year);
        $accountId = $request->input('account_id', 'all');
        $marketType = $request->input('market_type', 'all');

        // 1. GET AVAILABLE YEARS
        $futuresYears = FuturesTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->selectRaw('YEAR(exit_date) as year')->distinct();
        $spotYears = SpotTransaction::whereHas('spotTrade.tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->selectRaw('YEAR(transaction_date) as year')->distinct();
        $availableYears = $futuresYears->union($spotYears)->orderBy('year', 'desc')->pluck('year')->filter()->values()->all();

        if (empty($availableYears)) $availableYears = [Carbon::now()->year];
        if (!in_array($selectedYear, $availableYears) && count($availableYears) > 0) $selectedYear = $availableYears[0];

        // 2. PREPARE DAILY DATA QUERY (Untuk dipakai di Monthly & Daily View)
        // Kita ambil semua data harian untuk TAHUN terpilih agar frontend ringan (tidak perlu request ulang per bulan)
        
        $dailyData = [];

        // Query Futures Daily
        if ($marketType === 'all' || $marketType === 'Futures') {
            $q = FuturesTrade::where('status', 'CLOSED')
                ->whereYear('exit_date', $selectedYear)
                ->whereHas('tradingAccount', fn($sq) => $sq->where('user_id', $userId));
            if ($accountId !== 'all') $q->where('trading_account_id', $accountId);
            
            $futuresDaily = $q->selectRaw('DATE(exit_date) as date, COUNT(*) as trades, SUM(pnl) as pnl')
                ->groupBy('date')
                ->get();
            
            foreach ($futuresDaily as $d) {
                if (!isset($dailyData[$d->date])) $dailyData[$d->date] = ['trades' => 0, 'pnl' => 0];
                $dailyData[$d->date]['trades'] += $d->trades;
                $dailyData[$d->date]['pnl'] += $d->pnl;
            }
        }

        // Query Spot Daily
        if ($marketType === 'all' || $marketType === 'Spot') {
            $q = SpotTransaction::where('type', 'SELL')
                ->whereYear('transaction_date', $selectedYear)
                ->whereHas('spotTrade.tradingAccount', fn($sq) => $sq->where('user_id', $userId));
            if ($accountId !== 'all') $q->whereHas('spotTrade', fn($sq) => $sq->where('trading_account_id', $accountId));

            $spotDaily = $q->selectRaw('DATE(transaction_date) as date, COUNT(*) as trades, SUM(realized_pnl - fee) as pnl')
                ->groupBy('date')
                ->get();

            foreach ($spotDaily as $d) {
                if (!isset($dailyData[$d->date])) $dailyData[$d->date] = ['trades' => 0, 'pnl' => 0];
                $dailyData[$d->date]['trades'] += $d->trades;
                $dailyData[$d->date]['pnl'] += $d->pnl;
            }
        }

        // 3. GENERATE MONTHLY OVERVIEW (Dari data harian yang sudah dihitung di atas, biar efisien)
        $monthlyOverview = [];
        for ($month = 1; $month <= 12; $month++) {
            $datePrefix = $selectedYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
            $monthTrades = 0;
            $monthPnl = 0;

            // Loop array dailyData untuk hitung total bulan ini
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
            'selectedYear' => (int)$selectedYear,
            'monthlyOverview' => $monthlyOverview,
            'dailyData' => $dailyData, // <--- INI KUNCI UTAMANYA (Data Harian dikirim ke Vue)
            'accounts' => $accounts,
            'filters' => ['account_id' => $accountId, 'market_type' => $marketType]
        ]);
    }
}