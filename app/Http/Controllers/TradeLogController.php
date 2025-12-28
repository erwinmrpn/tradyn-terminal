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
            
            // Validasi Input (Tambah Fee)
            $validated = $request->validate([
                'trading_account_id' => 'required|exists:trading_accounts,id',
                'symbol' => 'required|string|uppercase',
                'market_type' => 'required|string',
                'type' => 'required|in:BUY,SELL',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|numeric|min:0',
                'fee' => 'nullable|numeric|min:0', // <--- Validasi Fee
                'date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            $account = TradingAccount::find($validated['trading_account_id']);
            
            // Hitung di memory saja untuk update saldo (tidak disimpan ke trade)
            $calculatedTotal = $validated['price'] * $validated['quantity'];
            $fee = $validated['fee'] ?? 0;

            // Update Saldo Trading Account
            if ($validated['type'] === 'BUY') {
                $deduction = $calculatedTotal + $fee;
                if ($account->balance < $deduction) {
                    return back()->withErrors(['balance' => 'Insufficient balance!']);
                }
                $account->decrement('balance', $deduction);
            } else {
                $addition = $calculatedTotal - $fee;
                $account->increment('balance', $addition);
            }

            // Simpan ke Tabel (TANPA TOTAL VALUE)
            SpotTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'symbol' => $validated['symbol'],
                'market_type' => $validated['market_type'],
                'type' => $validated['type'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'fee' => $fee,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                // 'total_value' => ... (HAPUS BARIS INI)
            ]);

        } else {
            // (Disini nanti tempat logika Simpan Futures)
        }

        return redirect()->back()->with('success', 'Trade recorded successfully.');
    }
}