<?php

namespace App\Http\Controllers;

use App\Models\SpotTrade;
use App\Models\FuturesTrade;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradeLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $type = $request->input('type', 'SPOT'); // SPOT atau FUTURES
        $accountId = $request->input('account_id', 'all');

        // 1. Ambil Akun Sesuai Type
        $accounts = TradingAccount::where('user_id', $userId)
            ->where('strategy_type', $type)
            ->get();

        // 2. Hitung Total Balance
        if ($accountId !== 'all') {
            $totalBalance = $accounts->where('id', $accountId)->sum('balance');
        } else {
            $totalBalance = $accounts->sum('balance');
        }

        // 3. Query Data (Sesuai Tabel Masing-Masing)
        if ($type === 'SPOT') {
            $query = SpotTrade::with('tradingAccount')
                ->whereHas('tradingAccount', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            if ($accountId !== 'all') $query->where('trading_account_id', $accountId);
            
            $trades = $query->latest('date')->get();
        } else {
            // Logic Futures (Placeholder, datanya dari tabel futures_trades)
            $query = FuturesTrade::with('tradingAccount')
                ->whereHas('tradingAccount', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            if ($accountId !== 'all') $query->where('trading_account_id', $accountId);
            
            $trades = $query->latest('entry_date')->get();
        }

        return Inertia::render('TradeLog/Index', [
            'trades' => $trades,
            'activeType' => $type,
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'selectedAccountId' => $accountId
        ]);
    }

public function store(Request $request)
    {
        // Cek Form Type dulu
        $request->validate(['form_type' => 'required|in:SPOT,FUTURES']);

        if ($request->form_type === 'SPOT') {
            
            // 1. VALIDASI INPUT (Tambahkan 'total')
            $validated = $request->validate([
                'trading_account_id' => 'required|exists:trading_accounts,id',
                'symbol' => 'required|string|uppercase',
                'market_type' => 'required|string',
                'type' => 'required|in:BUY,SELL',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|numeric|min:0',
                
                // --- TAMBAHAN WAJIB ---
                'total' => 'required|numeric|min:0', // Data ini dikirim dari Frontend
                // ----------------------

                'fee' => 'nullable|numeric|min:0',
                'date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            $account = TradingAccount::find($validated['trading_account_id']);
            
            // Kita gunakan 'total' dari inputan frontend karena sudah presisi dari kalkulasi swap
            $tradeTotal = $validated['total']; 
            $fee = $validated['fee'] ?? 0;

            // Update Saldo Trading Account
            if ($validated['type'] === 'BUY') {
                $deduction = $tradeTotal + $fee;
                
                // Cek saldo cukup atau tidak
                if ($account->balance < $deduction) {
                    return back()->withErrors(['balance' => 'Insufficient balance!']);
                }
                $account->decrement('balance', $deduction);
            } else {
                $addition = $tradeTotal - $fee;
                $account->increment('balance', $addition);
            }

            // 2. SIMPAN KE DATABASE (Masukkan 'total')
            SpotTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'symbol' => $validated['symbol'],
                'market_type' => $validated['market_type'],
                'type' => $validated['type'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                
                // --- JANGAN LUPA INI ---
                'total' => $validated['total'], // Kolom ini wajib ada di database
                // -----------------------

                'fee' => $fee,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

        } else {
            // Logic Futures ...
        }

        return redirect()->back()->with('success', 'Trade recorded successfully.');
    }
}