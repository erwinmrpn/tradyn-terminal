<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use App\Models\SpotTransaction;
use App\Models\SpotTrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpotController extends Controller
{
    public function storeTransaction(Request $request, $id)
    {
        // 1. Validasi Input
        $request->validate([
            'type' => 'required|in:BUY,SELL',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric',
            'date' => 'required|date',
            'time' => 'required',
            'realized_pnl' => 'nullable|numeric', // [BARU]
        ]);

        // 2. Gunakan DB Transaction agar aman
        DB::transaction(function () use ($request, $id) {
            
            $trade = SpotTrade::findOrFail($id);

            // [LOGIKA BARU] Hitung PnL Transaksi
            $pnlToStore = null;
            if ($request->type === 'SELL') {
                if ($request->filled('realized_pnl')) {
                    $pnlToStore = $request->realized_pnl;
                } else {
                    // Fallback calculation
                    $pnlToStore = ($request->price - $trade->price) * $request->quantity;
                }
            }

            // A. Simpan Riwayat di Tabel Anak
            SpotTransaction::create([
                'spot_trade_id' => $id,
                'type' => $request->type,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'fee' => $request->fee ?? 0,
                // [FIX] Simpan PnL
                'realized_pnl' => $pnlToStore,
                'transaction_date' => $request->date,
                'transaction_time' => $request->time,
                'notes' => $request->notes,
            ]);

            // B. Update Data Induk (spot_trades)
            if ($request->type === 'BUY') {
                // LOGIKA DCA (Weighted Average)
                $totalCostOld = $trade->price * $trade->quantity;
                $totalCostNew = $request->price * $request->quantity;
                $newQuantity = $trade->quantity + $request->quantity;
                
                // Hindari pembagian dengan nol
                if ($newQuantity > 0) {
                    $newAvgPrice = ($totalCostOld + $totalCostNew) / $newQuantity;
                    
                    $trade->update([
                        'price' => $newAvgPrice, 
                        'quantity' => $newQuantity 
                    ]);
                }

            } else {
                // --- LOGIKA PARTIAL SELL ---
                $newQuantity = $trade->quantity - $request->quantity;
                
                // 1. Cek Status: Jika quantity habis (0 atau sangat kecil), status jadi SOLD
                // Gunakan 0.00000001 untuk menghindari masalah floating point math
                $isSoldOut = $newQuantity <= 0.00000001;
                $status = $isSoldOut ? 'SOLD' : 'OPEN';
                
                // Gunakan pnlToStore (Gross) dikurangi Fee untuk update Induk (Net)
                $pnlThisTransactionNet = ($pnlToStore ?? 0) - ($request->fee ?? 0);

                // 3. Update Akumulasi PnL di Parent
                $currentTotalPnL = $trade->pnl ?? 0;
                $newTotalPnL = $currentTotalPnL + $pnlThisTransaction;

                // 4. Update Parent (spot_trades)
                // Kita tidak update 'sell_price' di parent karena bisa membingungkan jika partial sell.
                // Tapi kita update 'sell_date' agar user tahu kapan terakhir aktivitas jual.
                $trade->update([
                    'quantity' => max(0, $newQuantity),
                    'status' => $status,
                    'pnl' => ($trade->pnl ?? 0) + $pnlThisTransactionNet, 
                    'sell_date' => $request->date, 
                    'sell_time' => $request->time,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Transaction recorded successfully!');
    }
}