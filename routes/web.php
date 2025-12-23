<?php

use App\Http\Controllers\TradingAccountController;
use App\Http\Middleware\EnsureTradingAccountSetup;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Route untuk Setup Akun Trading
    Route::get('/setup-account', [TradingAccountController::class, 'create'])->name('trading-account.setup');
    Route::post('/setup-account', [TradingAccountController::class, 'store'])->name('trading-account.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// GROUP KHUSUS UNTUK USER YANG SUDAH LOGIN
Route::middleware(['auth', 'verified'])->group(function () {

    // 1. Route Dashboard (KITA PASANG PENJAGA DI SINI)
    // Jika user belum setup, mereka tidak akan bisa masuk sini dan dilempar ke setup
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(EnsureTradingAccountSetup::class)->name('dashboard'); // <--- Pasang Middleware di sini

    // 2. Route Setup Akun (JANGAN DIPASANG PENJAGA DI SINI, NANTI LOOPING)
    Route::get('/setup-account', [TradingAccountController::class, 'create'])->name('trading-account.setup');
    Route::post('/setup-account', [TradingAccountController::class, 'store'])->name('trading-account.store');

    // 3. Route Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
