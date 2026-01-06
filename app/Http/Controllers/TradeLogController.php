<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\TradingAccount;
use App\Models\SpotTrade;
use App\Models\FuturesTrade;
use Illuminate\Support\Facades\DB;
use App\Models\SpotTransaction;

class TradeLogController extends Controller
{
    /**
     * 1. Menampilkan Halaman Trade Log
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
            $query = FuturesTrade::query()->where('status', 'CLOSED');
        } else {
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
        $sortField = ($type === 'SPOT') ? 'buy_date' : (($type === 'RESULT') ? 'exit_date' : 'entry_date');
        
        $trades = $query->with('tradingAccount')->latest($sortField)->get();
        $accounts = $user->tradingAccounts;

        // --- HITUNG BALANCE ---
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
                'date' => 'required|date',
                'time' => 'required',
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
                'price' => 'required|numeric|min:0', 
                'quantity' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0', 
                'fee' => 'nullable|numeric|min:0',   
                'target_sell' => 'nullable|numeric',
                'target_buy' => 'nullable|numeric',
                'holding_period' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $account = TradingAccount::find($validated['trading_account_id']);
            
            // Hitung Total Biaya (Investasi + Fee)
            $totalCost = $validated['total'] + ($validated['fee'] ?? 0);

            if ($account->balance < $totalCost) {
                return back()->withErrors(['balance' => 'Insufficient balance (including fee)!']);
            }
            $account->decrement('balance', $totalCost);

            SpotTrade::create([
                'trading_account_id' => $validated['trading_account_id'],
                'symbol' => strtoupper($validated['symbol']),
                'market_type' => $validated['market_type'],
                'status' => 'OPEN',
                'buy_date' => $validated['date'],
                'buy_time' => $validated['time'],
                'price' => $validated['price'],
                'quantity' => $validated['quantity'],
                'fee' => $validated['fee'] ?? 0, 
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
     * 5. Hapus Data Trade (Delete) dengan Rollback Saldo
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->input('type');
        $user = Auth::user();

        return DB::transaction(function () use ($id, $type, $user) {
            if ($type === 'FUTURES') {
                $trade = FuturesTrade::findOrFail($id);
                if ($user->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);
                if ($trade->status === 'OPEN') {
                    TradingAccount::where('id', $trade->trading_account_id)->increment('balance', $trade->margin);
                }
                $trade->delete();
            } else {
                // Eager load transactions untuk akurasi fee
                $trade = SpotTrade::with('transactions')->findOrFail($id);
                if ($user->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) abort(403);

                $account = TradingAccount::find($trade->trading_account_id);

                // Jika status sudah SOLD, saldo sudah akurat, tidak perlu refund.
                // Refund hanya dilakukan jika masih ada aset atau status OPEN.
                if ($trade->status !== 'SOLD') {
                    
                    // 1. Hitung Nilai Aset yang Masih Tertanam (Current Cost Basis)
                    $currentValue = $trade->quantity * $trade->price;

                    // 2. Ambil Fee Awal (dari tabel induk)
                    $initialFee = $trade->fee;

                    // 3. Hitung Semua Fee dari DCA (dari tabel transaksi)
                    $totalDcaFee = $trade->transactions->where('type', 'BUY')->sum('fee');

                    // 4. Ambil Total PnL yang sudah terealisasi (yang sudah masuk ke balance saat sell)
                    $realizedPnL = $trade->pnl ?? 0;

                    // Rumus: Kembalikan modal sisa + semua biaya beli - profit yang sudah diterima
                    $netToRefund = ($currentValue + $initialFee + $totalDcaFee) - $realizedPnL;

                    if ($netToRefund > 0) {
                        $account->increment('balance', $netToRefund);
                    } elseif ($netToRefund < 0) {
                        // Jika ternyata minus (karena profit sangat besar), maka balance dikurangi
                        $account->decrement('balance', abs($netToRefund));
                    }
                }

                $trade->delete();
            }

            return redirect()->back()->with('success', 'History deleted and balance adjusted.');
        });
    }

    /**
     * 6. Handle Transaction (DCA / Partial Sell)
     * Menggantikan fungsi sellSpot lama dengan logika yang lebih canggih.
     */
    public function storeTransaction(Request $request, $id)
    {
        // 1. Validasi Input
        $request->validate([
            'type' => 'required|in:BUY,SELL',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric',
            'date' => 'required|date',
            'time' => 'required',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
        ]);

        // 2. Handle Upload Gambar
        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('screenshots', 'public');
        }

        // 3. Gunakan DB Transaction agar aman
        DB::transaction(function () use ($request, $id, $screenshotPath) {
            
            // A. Simpan Riwayat di Tabel Anak (spot_transactions)
            SpotTransaction::create([
                'spot_trade_id' => $id,
                'type' => $request->type,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'fee' => $request->fee ?? 0,
                'transaction_date' => $request->date,
                'transaction_time' => $request->time,
                'notes' => $request->notes,
                'chart_image' => $screenshotPath, 
            ]);

            // B. Update Data Induk (spot_trades)
            $trade = SpotTrade::findOrFail($id);
            
            if ($request->type === 'BUY') {
                // --- LOGIKA DCA (Weighted Average) ---
                $totalCostOld = $trade->price * $trade->quantity;
                $totalCostNew = $request->price * $request->quantity;
                $newQuantity = $trade->quantity + $request->quantity;
                
                // Hitung Harga Rata-rata Baru
                if ($newQuantity > 0) {
                    $newAvgPrice = ($totalCostOld + $totalCostNew) / $newQuantity;
                    
                    $trade->update([
                        'price' => $newAvgPrice, // Update avg price (HANYA SAAT BUY/DCA)
                        'quantity' => $newQuantity 
                    ]);
                }

                // Kurangi Saldo Akun (Cost + Fee)
                $account = TradingAccount::find($trade->trading_account_id);
                $costDCA = ($request->price * $request->quantity) + ($request->fee ?? 0);
                if ($account) {
                    $account->decrement('balance', $costDCA);
                }

            } else {
                // --- LOGIKA PARTIAL SELL ---
                // Sisa holding tetap pakai avg price yang sama (PRICE TIDAK DI UPDATE DI SINI)
                
                $newQuantity = $trade->quantity - $request->quantity;
                $status = $newQuantity <= 0.00000001 ? 'SOLD' : 'OPEN';
                
                // Hitung Realized PnL (Cost Basis yang terealisasi)
                $revenue = $request->price * $request->quantity;
                $cost = $trade->price * $request->quantity; // Menggunakan Avg Price saat ini
                $pnlThisTransaction = $revenue - $cost - ($request->fee ?? 0);

                // Update DB Induk
                $trade->update([
                    'quantity' => max(0, $newQuantity), // Hanya kurangi quantity
                    'status' => $status,
                    'pnl' => ($trade->pnl ?? 0) + $pnlThisTransaction, // Update total PnL
                    'sell_date' => $request->date, // Update last activity
                    'sell_time' => $request->time,
                ]);

                // Tambah Saldo Akun (Revenue - Fee)
                $account = TradingAccount::find($trade->trading_account_id);
                if ($account) {
                    $account->increment('balance', ($revenue - ($request->fee ?? 0)));
                }
            }
        });
        return redirect()->back()->with('success', 'Transaction recorded successfully!');
    }
}