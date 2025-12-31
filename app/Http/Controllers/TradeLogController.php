<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\TradingAccount;
use App\Models\SpotTrade;
use App\Models\FuturesTrade;

class TradeLogController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'SPOT');
        $accountId = $request->input('account_id', 'all');
        $user = Auth::user();

        // Query Data
        if ($type === 'FUTURES') {
            $query = FuturesTrade::query();
        } else {
            $query = SpotTrade::query();
        }

        // Filter Akun
        if ($accountId !== 'all') {
            $query->where('trading_account_id', $accountId);
        } else {
            $query->whereHas('tradingAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $trades = $query->with('tradingAccount')->latest($type === 'SPOT' ? 'date' : 'entry_date')->get();
        $accounts = $user->tradingAccounts;

        $totalBalance = $user->tradingAccounts()
            ->where('strategy_type', $type)
            ->sum('balance');

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
        // 1. Validasi Tipe Form
        $request->validate(['form_type' => 'required|in:SPOT,FUTURES']);

        // 2. LOGIKA UPLOAD SCREENSHOT (Disederhanakan)
        // Kita tidak peduli tipe formnya apa, kalau ada file 'screenshot', kita simpan.
        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            // Validasi file (Gambar, Maks 5MB)
            $request->validate([
                'screenshot' => 'image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);
            
            // Simpan ke folder 'public/screenshots'
            $screenshotPath = $request->file('screenshot')->store('screenshots', 'public');
        }

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
                'total' => 'required|numeric|min:0', 
                'tp_price' => 'nullable|numeric',
                'sl_price' => 'nullable|numeric',
                'notes' => 'nullable|string',
            ]);

            // Cek Saldo
            $account = TradingAccount::find($validated['trading_account_id']);
            if ($account->balance < $validated['total']) {
                 return back()->withErrors(['balance' => 'Insufficient futures balance!']);
            }
            $account->decrement('balance', $validated['total']);

            // Simpan ke Database
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
                // Variabel $screenshotPath pasti terisi jika file ada
                'entry_screenshot' => $screenshotPath, 
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
                // Spot tidak menyimpan screenshot
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            return redirect()->back()->with('success', 'Spot trade saved successfully.');
        }
    }
}