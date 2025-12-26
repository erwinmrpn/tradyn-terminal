<?php

use App\Http\Controllers\TradingAccountController;
use App\Http\Middleware\EnsureTradingAccountSetup;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\TradeLogController;

// --- PUBLIC ROUTES ---
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// --- AUTHENTICATED ROUTES ---
Route::middleware(['auth', 'verified'])->group(function () {

    // 1. Dashboard 
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(EnsureTradingAccountSetup::class) // Middleware cek apakah user sudah punya akun
        ->name('dashboard');

    // 2. Portfolio (Halaman Portfolio Real)
    // PENTING: Saya hapus placeholder portfolio yang lama agar tidak bentrok
    Route::get('/Portfolio', [PortfolioController::class, 'index'])->name('portfolio');

    // 3. Account Activity Log
    Route::get('/account-activity', [AccountTransactionController::class, 'index'])->name('account.activity');
    Route::post('/account-activity', [AccountTransactionController::class, 'store'])->name('account.activity.store');

    // 4. Trading Account (Setup & Add New)
    // Route ini dipakai oleh tombol "Add New Account" di Portfolio & Halaman Setup Awal
    Route::get('/trading-account/create', [TradingAccountController::class, 'create'])->name('trading-account.create');
    Route::post('/trading-account', [TradingAccountController::class, 'store'])->name('trading-account.store');

    // 5. Trade Calendar (Dulu Research)
    Route::get('/trade-calendar', function () { 
        // Nanti ganti dengan Controller jika sudah ada fiturnya
        return Inertia::render('Dashboard'); 
    })->name('trade.calendar'); // <--- Nama ini harus SAMA dengan di Sidebar

    // 6. Watchlist Assets (Dulu Signal)
    Route::get('/watchlist', function () { 
        return Inertia::render('Dashboard'); 
    })->name('watchlist');      // <

    // 7. Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 8. Trade Log
    Route::get('/trade-log', [TradeLogController::class, 'index'])->name('trade.log');
});

require __DIR__.'/auth.php';