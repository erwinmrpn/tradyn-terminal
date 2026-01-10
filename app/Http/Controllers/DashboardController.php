<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\TradingAccount;
use App\Models\SpotTrade;
use App\Models\FuturesTrade;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        // 1. Hitung Total Balance
        $totalBalance = TradingAccount::where('user_id', $userId)->sum('balance');

        // 2. Hitung Active Positions
        // Futures: Status OPEN
        $activeFutures = FuturesTrade::whereHas('tradingAccount', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('status', 'OPEN')->count();

        // Spot: Status OPEN (Holding)
        // Kita cek berdasarkan kolom 'status' di spot_trades
        $activeSpot = SpotTrade::whereHas('tradingAccount', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('status', 'OPEN')->count();

        $activePositions = $activeFutures + $activeSpot;

        // 3. Today's PnL (Disesuaikan dengan Database yang Ada)
        
        // A. Futures PnL (Closed Hari Ini)
        // Menggunakan exit_date sesuai migrasi yang ada
        $futuresPnL = FuturesTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->where('status', 'CLOSED')
            ->where('exit_date', $today)
            ->sum('pnl');

        // B. Spot PnL (Sold Hari Ini)
        // KITA KEMBALIKAN KE TABEL INDUK (spot_trades)
        // Karena tabel transaction Anda belum punya kolom pnl, kita ambil dari total PnL trade yang selesai hari ini.
        $spotPnL = SpotTrade::whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->where('sell_date', $today) // Menggunakan sell_date dari tabel spot_trades
            ->sum('pnl');

        $todaysPnL = $futuresPnL + $spotPnL;

        // 4. Ambil Recent Activity
        
        // Ambil 5 Spot Terakhir
        // PERBAIKAN: Mengganti 'date' menjadi 'buy_date' agar tidak error column not found
        $recentSpot = SpotTrade::with('tradingAccount')
            ->whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->latest('buy_date') 
            ->take(5)
            ->get()
            ->map(function ($trade) {
                $trade->category = 'SPOT';
                $trade->display_date = $trade->buy_date; // Mapping untuk sorting di frontend
                return $trade;
            });

        // Ambil 5 Futures Terakhir
        // Menggunakan entry_date (sesuai request Anda untuk tetap pakai date terpisah)
        $recentFutures = FuturesTrade::with('tradingAccount')
            ->whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->latest('entry_date') 
            ->take(5)
            ->get()
            ->map(function ($trade) {
                $trade->category = 'FUTURES';
                $trade->display_date = $trade->entry_date; // Mapping untuk sorting
                return $trade;
            });

        // Gabungkan dan Urutkan
        $recentTrades = $recentSpot->concat($recentFutures)
            ->sortByDesc('display_date')
            ->take(5)
            ->values();

        return Inertia::render('Dashboard', [
            'totalBalance' => $totalBalance,
            'activePositions' => $activePositions,
            'todaysPnL' => $todaysPnL,
            'recentTrades' => $recentTrades
        ]);
    }
}