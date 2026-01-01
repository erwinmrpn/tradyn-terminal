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
        ->middleware(EnsureTradingAccountSetup::class) // Cek akun dulu
        ->name('dashboard');

    // 2. Portfolio 
    Route::get('/Portfolio', [PortfolioController::class, 'index'])->name('portfolio');

    // 3. Account Activity Log
    Route::get('/account-activity', [AccountTransactionController::class, 'index'])->name('account.activity');
    Route::post('/account-activity', [AccountTransactionController::class, 'store'])->name('account.activity.store');

    // 4. Trading Account (FIX: Menggunakan nama 'trading-account.setup')
    // Route ini menangani redirect otomatis setelah register jika belum punya akun
    Route::get('/setup-account', [TradingAccountController::class, 'create'])->name('trading-account.setup');
    Route::post('/setup-account', [TradingAccountController::class, 'store'])->name('trading-account.store');
    
    // (Opsional) Route resource untuk edit/delete akun kedepannya
    Route::resource('trading-accounts', TradingAccountController::class)->except(['create', 'store', 'show']);

    // 5. Trade Calendar
    Route::get('/trade-calendar', function () { 
        return Inertia::render('Dashboard'); 
    })->name('trade.calendar'); 

    // 6. Watchlist Assets
    Route::get('/watchlist', function () { 
        return Inertia::render('Dashboard'); 
    })->name('watchlist');

    // 7. Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 8. Trade Log
    Route::get('/trade-log', [TradeLogController::class, 'index'])->name('trade.log');
    Route::post('/trade-log', [TradeLogController::class, 'store'])->name('trade.log.store');

    // 9. Close Position
    Route::post('/trade-log/close/{id}', [TradeLogController::class, 'closePosition'])->name('trade.log.close');

    // 10. cancel position
    Route::post('/trade-log/cancel/{id}', [TradeLogController::class, 'cancelPosition'])->name('trade.log.cancel');

    // 11. Delete history trade

    Route::delete('/trade-log/{id}', [TradeLogController::class, 'destroy'])->name('trade.log.destroy');

});

require __DIR__.'/auth.php';