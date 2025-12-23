<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTradingAccountSetup
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek apakah user sedang login
        // 2. Cek apakah user BELUM punya data trading_accounts
        // 3. Cek agar tidak looping (jangan redirect kalau user memang sedang di halaman setup)
        if ($request->user() && 
            $request->user()->tradingAccounts()->count() === 0 && 
            !$request->routeIs('trading-account.*')) {
            
            // Kalau belum setup, tendang ke halaman setup
            return redirect()->route('trading-account.setup');
        }

        return $next($request);
    }
}