<?php

use App\Http\Controllers\TradingAccountController;
use App\Http\Middleware\EnsureTradingAccountSetup;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\DashboardController; // <--- Import ini

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
    ->middleware(EnsureTradingAccountSetup::class)
    ->name('dashboard');

    // 2. Account Activity Log
    Route::get('/account-activity', [AccountTransactionController::class, 'index'])->name('account.activity');
    Route::post('/account-activity', [AccountTransactionController::class, 'store'])->name('account.activity.store');

    // 3. MENU LAINNYA (Placeholder agar Sidebar tidak Error)
    // Nanti Anda bisa ganti 'Dashboard' dengan 'Portfolio/Index', dll.
    Route::get('/portfolio', function () { return Inertia::render('Dashboard'); })->name('portfolio');
    Route::get('/research', function () { return Inertia::render('Dashboard'); })->name('research');
    Route::get('/signal', function () { return Inertia::render('Dashboard'); })->name('signal');

    // 4. Setup Akun Trading
    Route::get('/setup-account', [TradingAccountController::class, 'create'])->name('trading-account.setup');
    Route::post('/setup-account', [TradingAccountController::class, 'store'])->name('trading-account.store');

    // 5. Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';