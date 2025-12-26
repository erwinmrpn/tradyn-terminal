<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountTransactionController extends Controller
{
    public function index()
    {
        // Ambil data akun milik user (untuk dropdown)
        $accounts = TradingAccount::where('user_id', auth()->id())
            ->select('id', 'name', 'balance', 'currency')
            ->get();

        // Ambil riwayat transaksi, urutkan dari terbaru
        // Kita gunakan 'with' agar bisa menampilkan nama akun di tabel
        $transactions = AccountTransaction::with('tradingAccount')
            ->whereHas('tradingAccount', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->latest('date')
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'date' => $tx->date,
                    'account_name' => $tx->tradingAccount->name,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'currency' => $tx->tradingAccount->currency,
                ];
            });

        return Inertia::render('AccountActivityLog/Index', [
            'accounts' => $accounts,
            'transactions' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:trading_accounts,id',
            'type' => 'required|in:DEPOSIT,WITHDRAW',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        // Gunakan Database Transaction agar aman
        // Jika insert gagal, update saldo juga dibatalkan
        DB::transaction(function () use ($validated) {
            
            // 1. Simpan Log Transaksi
            AccountTransaction::create([
                'trading_account_id' => $validated['account_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
            ]);

            // 2. Update Saldo Akun Trading (Otomatis)
            $account = TradingAccount::find($validated['account_id']);
            
            if ($validated['type'] === 'DEPOSIT') {
                $account->increment('balance', $validated['amount']);
            } else {
                $account->decrement('balance', $validated['amount']);
            }
        });

        return redirect()->back()->with('success', 'Transaction recorded and balance updated.');
    }
}