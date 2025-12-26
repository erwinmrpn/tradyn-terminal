<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TradingAccount;

class TradingAccountController extends Controller
{
    /**
     * Menampilkan halaman form Setup Akun.
     */
    public function create()
    {
        return Inertia::render('TradingAccount/Setup');
    }

    /**
     * Menyimpan data akun baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        // Memastikan semua data yang dikirim user sesuai aturan
        $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|max:5',     // USD, IDR, USDT
            'balance' => 'required|numeric|min:0',
            'exchange' => 'required|string|max:255',   // Nama broker/wallet
            'strategy_type' => 'required|string|max:50', // SPOT, FUTURES
        ]);

        // 2. Simpan ke Database
        // Kita gunakan $request->user()->tradingAccounts() agar otomatis
        // terhubung dengan user ID yang sedang login.
        $request->user()->tradingAccounts()->create([
            'name' => $request->name,
            'currency' => $request->currency,
            'balance' => $request->balance,
            
            // PERBAIKAN UTAMA DISINI:
            // Kita mengambil nilai dari inputan user ($request->exchange)
            // Bukan menulis ulang string validasinya.
            'exchange' => $request->exchange, 
            
            'strategy_type' => $request->strategy_type,
        ]);

        // 3. Redirect ke Dashboard
        return redirect()->route('dashboard');
    }
}