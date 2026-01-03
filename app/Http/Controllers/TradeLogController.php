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
    /**
     * 1. Menampilkan Halaman Trade Log
     * Mengatur data untuk Tab SPOT, FUTURES, dan RESULT
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'SPOT'); 
        $accountId = $request->input('account_id', 'all');
        $user = Auth::user();

        // --- QUERY DATA TRADES ---
        if ($type === 'FUTURES') {
            $query = FuturesTrade::query();
        } elseif ($type === 'RESULT') {
            // Tab RESULT: Hanya menampilkan trade yang sudah selesai (CLOSED)
            // Untuk Futures, status 'CLOSED'. Untuk Spot, status 'SOLD'.
            // Namun, karena Result Section saat ini dipisah per Tab di Frontend,
            // query ini mungkin lebih spesifik ke Futures jika sub-tab defaultnya Futures.
            // Kita biarkan logic ini handle Futures Result dulu.
            $query = FuturesTrade::query()->where('status', 'CLOSED');
        } else {
            // Tab SPOT: Tampilkan semua history spot
            $query = SpotTrade::query();
        }

        // --- FILTER BERDASARKAN AKUN ---
        if ($accountId !== 'all') {
            $query->where('trading_account_id', $accountId);
        } else {
            $query->whereHas('tradingAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // --- SORTING DATA ---
        // Spot: berdasarkan tanggal beli (buy_date)
        // Futures/Result: berdasarkan tanggal entry atau exit
        $sortField = ($type === 'SPOT') ? 'buy_date' : (($type === 'RESULT') ? 'exit_date' : 'entry_date');
        
        $trades = $query->with('tradingAccount')->latest($sortField)->get();
        $accounts = $user->tradingAccounts;

        // --- HITUNG BALANCE TERPISAH ---
        $spotBalance = $user->tradingAccounts()->where('strategy_type', 'SPOT')->sum('balance');
        $futuresBalance = $user->tradingAccounts()->where('strategy_type', 'FUTURES')->sum('balance');
        $balanceType = ($type === 'RESULT') ? 'FUTURES' : $type;
        $totalBalance = $user->tradingAccounts()->where('strategy_type', $balanceType)->sum('balance');

        return Inertia::render('TradeLog/Index', [
            'trades' => $trades,
            'activeType' => $type,
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'spotBalance' => $spotBalance,
            'futuresBalance' => $futuresBalance,
            'selectedAccountId' => $accountId
        ]);
    }

    /**
     * 2. Menyimpan Transaksi Baru (Entry / Buy)
     * Handle Form SPOT (Buy) dan Form FUTURES (Open Position)
     */
    public function store(Request $request)
    {
        $request->validate(['form_type' => 'required|in:SPOT,FUTURES']);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $request->validate(['screenshot' => 'image|mimes:jpeg,png,jpg,gif|max:5120']);
            $screenshotPath = $request->file('screenshot')->store('screenshots', 'public');
        }

        // === LOGIKA FUTURES (OPEN POSITION) ===
        if ($request->form_type === 'FUTURES') {
            
            $validated = $request->validate([
                'trading_account_id' => 'required|exists:trading_accounts,id',
                'date' => 'required|date', // entry_date
                'time' => 'required',      // entry_time
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

            $account = TradingAccount::find($validated['trading_account_id']);
            if ($account->balance < $validated['total']) {
                 return back()->withErrors(['balance' => 'Insufficient futures balance!']);
            }
            $account->decrement('balance', $validated['total']);

            FuturesTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'entry_date' => $validated['date'],
                'entry_time' => $validated['time'],
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
                'entry_screenshot' => $screenshotPath, 
                'entry_notes' => $validated['notes'],
                'status' => 'OPEN'
            ]);

            return redirect()->back()->with('success', 'Futures position opened!');
        } 
        
        // === LOGIKA SPOT (BUY ASSET) ===
        else {
            $validated = $request->validate([
                'trading_account_id' => 'required|exists:trading_accounts,id',
                'symbol' => 'required|string|uppercase',
                'market_type' => 'required|string',
                'date' => 'required|date',
                'time' => 'required',
                'price' => 'required|numeric|min:0', // Buy Price
                'quantity' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0', // Total Invested (USDT)
                'target_sell' => 'nullable|numeric',
                'target_buy' => 'nullable|numeric',
                'holding_period' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $account = TradingAccount::find($validated['trading_account_id']);
            
            // Cek Saldo Spot
            if ($account->balance < $validated['total']) {
                return back()->withErrors(['balance' => 'Insufficient balance!']);
            }
            $account->decrement('balance', $validated['total']);

            SpotTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'symbol' => strtoupper($validated['symbol']),
                'market_type' => $validated['market_type'],
                'status' => 'OPEN',
                'buy_date' => $validated['date'],
                'buy_time' => $validated['time'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'target_sell_price' => $validated['target_sell'],
                'target_buy_price' => $validated['target_buy'],
                'holding_period' => $validated['holding_period'],
                'buy_screenshot' => $screenshotPath,
                'buy_notes' => $validated['notes'],
            ]);

            return redirect()->back()->with('success', 'Spot asset bought!');
        }
    }

    /**
     * 3. Close Position (FUTURES)
     */
    public function closePosition(Request $request, $id)
    {
        $trade = FuturesTrade::findOrFail($id);
        if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);

        $validated = $request->validate([
            'exit_date' => 'required|date',
            'exit_time' => 'required',
            'exit_price' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'exit_reason' => 'required|string',
            'notes' => 'nullable|string',
            'exit_screenshot' => 'nullable|image|max:5120',
        ]);

        $exitScreenshotPath = null;
        if ($request->hasFile('exit_screenshot')) {
            $exitScreenshotPath = $request->file('exit_screenshot')->store('screenshots', 'public');
        }

        $pnlGross = ($trade->type === 'LONG') 
            ? ($validated['exit_price'] - $trade->entry_price) * $trade->quantity
            : ($trade->entry_price - $validated['exit_price']) * $trade->quantity;
        
        $netPnL = $pnlGross - $validated['fee'];

        $account = TradingAccount::find($trade->trading_account_id);
        $account->balance += ($trade->margin + $netPnL);
        $account->save();

        $trade->update([
            'status' => 'CLOSED',
            'exit_date' => $validated['exit_date'],
            'exit_time' => $validated['exit_time'],
            'exit_price' => $validated['exit_price'],
            'fee' => $validated['fee'],
            'pnl' => $netPnL,
            'exit_reason' => $validated['exit_reason'],
            'exit_notes' => $validated['notes'],
            'exit_screenshot' => $exitScreenshotPath,
        ]);

        return redirect()->back()->with('success', 'Position closed.');
    }

    /**
     * 4. Cancel Position (FUTURES)
     */
    public function cancelPosition(Request $request, $id)
    {
        $trade = FuturesTrade::findOrFail($id);
        if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);

        $request->validate(['cancellation_note' => 'required|string|max:500']);

        $account = TradingAccount::find($trade->trading_account_id);
        $account->balance += $trade->margin;
        $account->save();

        $trade->update([
            'status' => 'CANCELLED',
            'pnl' => 0,
            'fee' => 0,
            'exit_price' => $trade->entry_price, 
            'exit_date' => now()->format('Y-m-d'),
            'exit_time' => now()->format('H:i:s'),
            'exit_reason' => 'CANCELLED',
            'exit_notes' => $request->cancellation_note,
        ]);

        return redirect()->back()->with('success', 'Trade cancelled.');
    }

    /**
     * 5. Sell Asset (SPOT) - NEW FUNCTION
     */
    public function sellSpot(Request $request, $id)
    {
        $trade = SpotTrade::findOrFail($id);
        // Security check
        if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'sell_date' => 'required|date',
            'sell_time' => 'required',
            'sell_price' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'sell_screenshot' => 'nullable|image|max:5120',
        ]);

        $sellScreenshotPath = null;
        if ($request->hasFile('sell_screenshot')) {
            $sellScreenshotPath = $request->file('sell_screenshot')->store('screenshots', 'public');
        }

        // Hitung PnL Spot
        // Revenue = (Sell Price * Qty) -> Total uang yang didapat dari penjualan
        // Cost = (Buy Price * Qty) -> Total uang yang dikeluarkan saat beli
        // PnL = Revenue - Cost - Fee
        $revenue = $validated['sell_price'] * $trade->quantity;
        $cost = $trade->price * $trade->quantity; 
        $pnl = $revenue - $cost - $validated['fee'];

        // Kembalikan dana ke saldo
        // Saldo bertambah sebesar Revenue dikurangi Fee (karena fee diambil dari hasil jual biasanya)
        $account = TradingAccount::find($trade->trading_account_id);
        $account->increment('balance', ($revenue - $validated['fee']));

        $trade->update([
            'status' => 'SOLD',
            'sell_date' => $validated['sell_date'],
            'sell_time' => $validated['sell_time'],
            'sell_price' => $validated['sell_price'],
            'fee' => $validated['fee'],
            'pnl' => $pnl,
            'sell_notes' => $validated['notes'],
            'sell_screenshot' => $sellScreenshotPath,
        ]);

        return redirect()->back()->with('success', 'Spot asset sold successfully.');
    }

    /**
     * 6. Hapus Data Trade (Delete)
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->input('type');
        if ($type === 'FUTURES') {
            $trade = FuturesTrade::findOrFail($id);
            if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);
            $trade->delete();
        } else {
            $trade = SpotTrade::findOrFail($id);
            if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);
            $trade->delete();
        }
        return redirect()->back()->with('success', 'Trade deleted.');
    }
}