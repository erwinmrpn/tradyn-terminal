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
        $hasAccount = Auth::user()->tradingAccounts()->exists();

        return Inertia::render('TradingAccount/Create', [
            'isInitialSetup' => !$hasAccount 
        ]);
    }

    /**
     * Menyimpan Akun Baru (Setup Awal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|max:5',
            'balance' => 'required|numeric|min:0',
            'market_type' => 'required|string|in:Crypto,Stock,Commodity',
            'exchange' => 'required|string|max:255',
            'strategy_type' => 'required|string|max:50',
        ]);

        $request->user()->tradingAccounts()->create([
            'name' => $validated['name'],
            'market_type' => $validated['market_type'],
            'currency' => $validated['currency'],
            'balance' => $validated['balance'],
            'exchange' => $validated['exchange'],
            'strategy_type' => $validated['strategy_type'],
        ]);

        // UBAH DISINI: Redirect ke Portfolio setelah buat akun
        return redirect()->route('portfolio')->with('success', 'Account created!');
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
     * Edit Akun.
     */
    public function edit(TradingAccount $tradingAccount)
    {
        if ($tradingAccount->user_id !== Auth::id()) abort(403);
        
        // PERBAIKAN PENTING: 
        // Arahkan ke 'Portfolio/Edit' karena file Edit.vue Anda ada di folder Portfolio
        return Inertia::render('Portfolio/Edit', ['account' => $tradingAccount]);
    }

    /**
     * Update Akun.
     */
    public function update(Request $request, TradingAccount $tradingAccount)
    {
        if ($tradingAccount->user_id !== Auth::id()) abort(403);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'market_type' => 'required|string|in:Crypto,Stock,Commodity',
            'exchange' => 'required|string|max:255',
            'strategy_type' => 'required|string|max:50',
            'currency' => 'required|string|max:5',
            'balance' => 'required|numeric|min:0',
        ]);

        $tradingAccount->update($validated);
        
        // Redirect ke halaman Portfolio agar user melihat perubahannya
        return redirect()->route('portfolio')->with('success', 'Account updated successfully!');
    }

    /**
     * Hapus Akun.
     */
    public function destroy(TradingAccount $tradingAccount)
    {
        if ($tradingAccount->user_id !== Auth::id()) abort(403);
        
        $tradingAccount->delete(); // Ini menghapus data spesifik, bukan semua data.
        
        // PERBAIKAN PENTING: Redirect ke route 'portfolio', bukan 'dashboard'
        return redirect()->route('portfolio')->with('success', 'Account deleted.');
    }
}