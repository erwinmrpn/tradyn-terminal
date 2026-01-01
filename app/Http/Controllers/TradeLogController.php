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
     * Menampilkan Halaman Trade Log (Spot, Futures, Result)
     */
    public function index(Request $request)
    {
        // Ambil parameter type dari URL, default 'SPOT'
        $type = $request->input('type', 'SPOT'); 
        $accountId = $request->input('account_id', 'all');
        $user = Auth::user();

        // --- 1. LOGIKA QUERY DATA BERDASARKAN TAB ---
        if ($type === 'FUTURES') {
            // Tab FUTURES utama: Fokus pada posisi yang masih AKTIF (OPEN)
            $query = FuturesTrade::query()->where('status', 'OPEN');
        } 
        elseif ($type === 'RESULT') {
            // Tab RESULT utama: Fokus pada history Futures yang sudah SELESAI (CLOSED)
            $query = FuturesTrade::query()->where('status', 'CLOSED');
        } 
        else {
            // Tab SPOT: Tampilkan semua history spot
            $query = SpotTrade::query();
        }

        // --- 2. FILTER AKUN (Jika user memilih akun tertentu) ---
        if ($accountId !== 'all') {
            $query->where('trading_account_id', $accountId);
        } else {
            // Jika 'all', pastikan hanya mengambil data milik user yang login
            $query->whereHas('tradingAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // --- 3. SORTING DATA ---
        $sortField = ($type === 'SPOT') ? 'date' : (($type === 'RESULT') ? 'exit_date' : 'entry_date');
        
        $trades = $query->with('tradingAccount')
            ->latest($sortField)
            ->get();
            
        $accounts = $user->tradingAccounts;

        // --- [NEW] 4. HITUNG BALANCE TERPISAH (SPOT & FUTURES) ---
        // Kita kirim dua variabel terpisah agar frontend bisa switch otomatis tanpa reload
        $spotBalance = $user->tradingAccounts()->where('strategy_type', 'SPOT')->sum('balance');
        $futuresBalance = $user->tradingAccounts()->where('strategy_type', 'FUTURES')->sum('balance');

        // Untuk kompatibilitas kode lama (jika ada yang masih pakai totalBalance)
        // Jika type RESULT, defaultnya ambil futures balance
        $balanceType = ($type === 'RESULT') ? 'FUTURES' : $type;
        $totalBalance = $user->tradingAccounts()
            ->where('strategy_type', $balanceType)
            ->sum('balance');

        return Inertia::render('TradeLog/Index', [
            'trades' => $trades,
            'activeType' => $type,
            'accounts' => $accounts,
            'totalBalance' => $totalBalance, // Tetap dikirim untuk kompatibilitas
            'spotBalance' => $spotBalance,     // [NEW] Balance Spot Global User
            'futuresBalance' => $futuresBalance, // [NEW] Balance Futures Global User
            'selectedAccountId' => $accountId
        ]);
    }

    /**
     * Menyimpan Transaksi Baru (Spot Trade & Futures Open Position)
     */
    public function store(Request $request)
    {
        // 1. Validasi Tipe Form
        $request->validate(['form_type' => 'required|in:SPOT,FUTURES']);

        // 2. LOGIKA UPLOAD SCREENSHOT (Global)
        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            $request->validate([
                'screenshot' => 'image|mimes:jpeg,png,jpg,gif|max:5120' // Max 5MB
            ]);
            // Simpan ke folder 'public/screenshots'
            $screenshotPath = $request->file('screenshot')->store('screenshots', 'public');
        }

        // === LOGIKA FUTURES (OPEN POSITION) ===
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
                'total' => 'required|numeric|min:0', // Ini adalah Margin Cost
                'tp_price' => 'nullable|numeric',
                'sl_price' => 'nullable|numeric',
                'notes' => 'nullable|string',
            ]);

            // Cek Saldo Akun Futures
            $account = TradingAccount::find($validated['trading_account_id']);
            if ($account->balance < $validated['total']) {
                 return back()->withErrors(['balance' => 'Insufficient futures balance!']);
            }
            
            // Kurangi saldo saat Open Position (Margin digunakan)
            $account->decrement('balance', $validated['total']);

            // Simpan ke Database Futures
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
                'notes' => $validated['notes'],
                'status' => 'OPEN'
            ]);

            return redirect()->back()->with('success', 'Futures position opened!');
        } 
        
        // === LOGIKA SPOT TRADE ===
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

            // Update Saldo Spot
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

            // Simpan ke Database Spot
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

    /**
     * Menyimpan Close Position Futures (Menghitung PnL & Fee Akhir)
     */
    public function closePosition(Request $request, $id)
    {
        // Cari trade berdasarkan ID
        $trade = FuturesTrade::findOrFail($id);
        
        // Security Check: Pastikan trade milik user yang login
        if (Auth::user()->tradingAccounts()->where('id', $trade->trading_account_id)->doesntExist()) {
            abort(403, 'Unauthorized action.');
        }

        // Validasi Input Close
        $validated = $request->validate([
            'exit_date' => 'required|date',
            'exit_price' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'exit_reason' => 'required|string',
            'notes' => 'nullable|string',
            'exit_screenshot' => 'nullable|image|max:5120',
        ]);

        // 1. Handle Screenshot Close (Jika ada)
        $exitScreenshotPath = null;
        if ($request->hasFile('exit_screenshot')) {
            $exitScreenshotPath = $request->file('exit_screenshot')->store('screenshots', 'public');
        }

        // 2. Hitung Gross PnL
        // Rumus: (Exit - Entry) * Qty * Direction
        $pnlGross = 0;
        if ($trade->type === 'LONG') {
            $pnlGross = ($validated['exit_price'] - $trade->entry_price) * $trade->quantity;
        } else { // SHORT
            $pnlGross = ($trade->entry_price - $validated['exit_price']) * $trade->quantity;
        }

        // 3. Hitung Net PnL (Gross - Fee)
        $netPnL = $pnlGross - $validated['fee'];

        // 4. Update Saldo Akun
        // Kembalikan Margin Awal + Net PnL ke saldo akun
        $account = TradingAccount::find($trade->trading_account_id);
        $amountToReturn = $trade->margin + $netPnL;
        
        $account->balance += $amountToReturn;
        $account->save();

        // 5. Update Data Trade di Database
        $trade->update([
            'status' => 'CLOSED',
            'exit_date' => $validated['exit_date'],
            'exit_price' => $validated['exit_price'],
            'fee' => $validated['fee'], // Fee final disimpan di sini
            'pnl' => $netPnL,
            'exit_reason' => $validated['exit_reason'],
            'notes' => $trade->notes . ($validated['notes'] ? "\n[Close]: " . $validated['notes'] : ""),
            'exit_screenshot' => $exitScreenshotPath,
        ]);

        return redirect()->back()->with('success', 'Position closed successfully. PnL: $' . number_format($netPnL, 2));
    }
}