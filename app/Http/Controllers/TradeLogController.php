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

        if ($type === 'FUTURES') {
            $query = FuturesTrade::query(); // Ambil semua (Open/Closed/Cancelled)
        } elseif ($type === 'RESULT') {
            $query = FuturesTrade::query()->where('status', 'CLOSED');
        } else {
            $query = SpotTrade::query();
        }

        if ($accountId !== 'all') {
            $query->where('trading_account_id', $accountId);
        } else {
            $query->whereHas('tradingAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $sortField = ($type === 'SPOT') ? 'date' : (($type === 'RESULT') ? 'exit_date' : 'entry_date');
        $trades = $query->with('tradingAccount')->latest($sortField)->get();
        $accounts = $user->tradingAccounts;

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

    public function store(Request $request)
    {
        $request->validate(['form_type' => 'required|in:SPOT,FUTURES']);
        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $request->validate(['screenshot' => 'image|mimes:jpeg,png,jpg,gif|max:5120']);
            $screenshotPath = $request->file('screenshot')->store('screenshots', 'public');
        }

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
                'entry_screenshot' => $screenshotPath, 
                // [UPDATE] Simpan ke entry_notes
                'entry_notes' => $validated['notes'],
                'status' => 'OPEN'
            ]);

            return redirect()->back()->with('success', 'Futures position opened!');
        } else {
            // ... Logic Spot Trade (Tidak berubah, tetap pakai 'notes') ...
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

    public function closePosition(Request $request, $id)
    {
        $trade = FuturesTrade::findOrFail($id);
        if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);

        $validated = $request->validate([
            'exit_date' => 'required|date',
            'exit_price' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'exit_reason' => 'required|string',
            'notes' => 'nullable|string', // Ini nanti masuk ke exit_notes
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
            'exit_price' => $validated['exit_price'],
            'fee' => $validated['fee'],
            'pnl' => $netPnL,
            'exit_reason' => $validated['exit_reason'],
            // [UPDATE] Simpan catatan penutupan ke exit_notes (Entry note aman)
            'exit_notes' => $validated['notes'],
            'exit_screenshot' => $exitScreenshotPath,
        ]);

        return redirect()->back()->with('success', 'Position closed.');
    }

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
            'exit_date' => now(),
            'exit_reason' => 'CANCELLED',
            // [UPDATE] Simpan alasan cancel ke exit_notes
            'exit_notes' => $request->cancellation_note,
        ]);

        return redirect()->back()->with('success', 'Trade cancelled.');
    }

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