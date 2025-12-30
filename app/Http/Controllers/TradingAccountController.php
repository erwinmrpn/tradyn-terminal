<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TradingAccount;
use Illuminate\Support\Facades\Auth;

class TradingAccountController extends Controller
{
    /**
     * Menampilkan halaman Setup Akun.
     */
    public function create()
    {
        return Inertia::render('TradingAccount/Create');
    }

    /**
     * Menyimpan Akun Baru (Setup Awal).
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|max:5',
            'balance' => 'required|numeric|min:0',
            'exchange' => 'required|string|max:255',
            'strategy_type' => 'required|string|max:50', // SPOT, FUTURES
        ]);

        // 2. Simpan ke Database
        $request->user()->tradingAccounts()->create([
            'name' => $validated['name'],
            'currency' => $validated['currency'],
            'balance' => $validated['balance'],
            'exchange' => $validated['exchange'],
            'strategy_type' => $validated['strategy_type'],
        ]);

        // 3. Redirect ke Dashboard setelah buat akun
        return redirect()->route('dashboard')->with('success', 'Account created!');
    }

    /**
     * (Opsional) Menampilkan daftar akun jika diperlukan nanti.
     */
    public function index()
    {
        $accounts = Auth::user()->tradingAccounts()->latest()->get();
        return Inertia::render('TradingAccount/Index', ['accounts' => $accounts]);
    }

    /**
     * (Opsional) Edit Akun.
     */
    public function edit(TradingAccount $tradingAccount)
    {
        if ($tradingAccount->user_id !== Auth::id()) abort(403);
        return Inertia::render('TradingAccount/Edit', ['account' => $tradingAccount]);
    }

    /**
     * (Opsional) Update Akun.
     */
    public function update(Request $request, TradingAccount $tradingAccount)
    {
        if ($tradingAccount->user_id !== Auth::id()) abort(403);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'exchange' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
        ]);

        $tradingAccount->update($validated);
        return redirect()->route('dashboard')->with('success', 'Account updated!');
    }

    /**
     * (Opsional) Hapus Akun.
     */
    public function destroy(TradingAccount $tradingAccount)
    {
        if ($tradingAccount->user_id !== Auth::id()) abort(403);
        $tradingAccount->delete();
        return redirect()->route('dashboard')->with('success', 'Account deleted.');
    }
}