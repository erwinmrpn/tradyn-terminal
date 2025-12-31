<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\TradingAccount;
use App\Models\SpotTrade;     // Model Baru
use App\Models\FuturesTrade;  // Model Baru

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 1. Hitung Total Balance (Dari semua akun user)
        $totalBalance = TradingAccount::where('user_id', $userId)->sum('balance');

        // 2. Hitung Active Positions (Khusus Futures yang statusnya OPEN)
        $activePositions = FuturesTrade::whereHas('tradingAccount', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('status', 'OPEN')->count();

        // 3. Today's PnL (Sementara 0, karena fitur Close Position belum dibuat)
        // Nanti kita akan query ke tabel FuturesTrade yang statusnya CLOSED & tanggal hari ini
        $todaysPnL = 0; 

        // 4. Ambil Recent Activity (Gabungan Spot & Futures)
        
        // Ambil 5 Spot Terakhir
        $recentSpot = SpotTrade::with('tradingAccount')
            ->whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->latest('date')
            ->take(5)
            ->get()
            ->map(function ($trade) {
                $trade->category = 'SPOT'; // Label manual untuk UI
                $trade->display_date = $trade->date; // Normalisasi tanggal
                return $trade;
            });

        // Ambil 5 Futures Terakhir
        $recentFutures = FuturesTrade::with('tradingAccount')
            ->whereHas('tradingAccount', fn($q) => $q->where('user_id', $userId))
            ->latest('entry_date')
            ->take(5)
            ->get()
            ->map(function ($trade) {
                $trade->category = 'FUTURES'; // Label manual untuk UI
                $trade->display_date = $trade->entry_date; // Normalisasi tanggal
                return $trade;
            });

        // Gabungkan dan Urutkan berdasarkan tanggal terbaru
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