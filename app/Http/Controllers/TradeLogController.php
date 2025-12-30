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
        $type = $request->input('type', 'SPOT'); 
        $accountId = $request->input('account_id', 'all');

        // 1. Ambil Daftar Akun
        $accounts = TradingAccount::where('user_id', $userId)
            ->where('strategy_type', $type)
            ->get();

        // 2. Hitung Total Balance
        if ($accountId !== 'all') {
            $totalBalance = $accounts->where('id', $accountId)->sum('balance');
        } else {
            $totalBalance = $accounts->sum('balance');
        }

        // 3. Query Data (PERBAIKAN RELASI DISINI)
        if ($type === 'SPOT') {
            // Gunakan 'tradingAccount' (Sesuai nama function di Model SpotTrade)
            $query = SpotTrade::with('tradingAccount') 
                ->whereHas('tradingAccount', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
                
            if ($accountId !== 'all') {
                $query->where('trading_account_id', $accountId);
            }
            
            // Spot menggunakan kolom 'date'
            $trades = $query->latest('date')->get();
        } else {
            // Gunakan 'tradingAccount' (Sesuai nama function di Model FuturesTrade)
            $query = FuturesTrade::with('tradingAccount') 
                ->whereHas('tradingAccount', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
                
            if ($accountId !== 'all') {
                $query->where('trading_account_id', $accountId);
            }
            
            // Futures menggunakan kolom 'entry_date' (bukan 'date')
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
        $request->validate(['form_type' => 'required|in:SPOT,FUTURES']);

        // === LOGIKA FUTURES ===
        if ($request->form_type === 'FUTURES') {
            
            $validated = $request->validate([
                'trading_account_id' => 'required|exists:trading_accounts,id',
                'date' => 'required|date',
                'type' => 'required|in:LONG,SHORT',
                'symbol' => 'required|string',
                'market_type' => 'required|string',
                'leverage' => 'required|integer|min:1|max:125',
                'margin_mode' => 'required|in:CROSS,ISOLATED',
                'order_type' => 'required|string',
                'price' => 'required|numeric|min:0', 
                'quantity' => 'required|numeric|min:0', 
                'total' => 'required|numeric|min:0', // Margin
                'tp_price' => 'nullable|numeric',
                'sl_price' => 'nullable|numeric',
                'fee' => 'nullable|numeric',
                'notes' => 'nullable|string',
            ]);

            // Opsional: Logic Saldo Futures
            $account = TradingAccount::find($validated['trading_account_id']);
            if ($account->balance < $validated['total']) {
                 return back()->withErrors(['balance' => 'Insufficient futures balance!']);
            }
            $account->decrement('balance', $validated['total']);

            FuturesTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'entry_date' => $validated['date'],
                'type' => $validated['type'],
                'symbol' => strtoupper($validated['symbol']),
                'market_type' => $validated['market_type'],
                'leverage' => $validated['leverage'],
                'margin_mode' => $validated['margin_mode'],
                'order_type' => $validated['order_type'],
                'entry_price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'margin' => $validated['total'],
                'tp_price' => $validated['tp_price'],
                'sl_price' => $validated['sl_price'],
                'open_fee' => $validated['fee'] ?? 0,
                'notes' => $validated['notes'],
                'status' => 'OPEN'
            ]);

            return redirect()->back()->with('success', 'Futures position opened!');
        } 
        
        // === LOGIKA SPOT ===
        else {
            $validated = $request->validate([
                'trading_account_id' => 'required|exists:trading_accounts,id',
                'symbol' => 'required|string|uppercase',
                'market_type' => 'required|string',
                'type' => 'required|in:BUY,SELL',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'fee' => 'nullable|numeric|min:0',
                'date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            $account = TradingAccount::find($validated['trading_account_id']);
            $tradeTotal = $validated['total']; 
            $fee = $validated['fee'] ?? 0;

            if ($validated['type'] === 'BUY') {
                $deduction = $tradeTotal + $fee;
                if ($account->balance < $deduction) {
                    return back()->withErrors(['balance' => 'Insufficient balance!']);
                }
                $account->decrement('balance', $deduction);
            } else {
                $addition = $tradeTotal - $fee;
                $account->increment('balance', $addition);
            }

            SpotTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'symbol' => $validated['symbol'],
                'market_type' => $validated['market_type'],
                'type' => $validated['type'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'total' => $validated['total'],
                'fee' => $fee,
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            return redirect()->back()->with('success', 'Spot trade saved successfully.');
        }
    }
}