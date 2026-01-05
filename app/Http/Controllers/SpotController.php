<?php

namespace App\Http\Controllers; // [FIX] Namespace disesuaikan

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
        ]);

        // 2. Gunakan DB Transaction agar aman
        DB::transaction(function () use ($request, $id) {
            
            // A. Simpan Riwayat di Tabel Anak
            SpotTransaction::create([
                'spot_trade_id' => $id,
                'type' => $request->type,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'fee' => $request->fee ?? 0,
                'transaction_date' => $request->date,
                'transaction_time' => $request->time,
                'notes' => $request->notes,
            ]);

            // B. Update Data Induk (spot_trades)
            $trade = SpotTrade::findOrFail($id);
            
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
                
                // 2. Hitung Realized PnL untuk transaksi INI SAJA
                // Rumus: (Harga Jual - Harga Avg Entry) * Jumlah yang dijual - Fee
                $pnlThisTransaction = (($request->price - $trade->price) * $request->quantity) - ($request->fee ?? 0);

                // 3. Update Akumulasi PnL di Parent
                $currentTotalPnL = $trade->pnl ?? 0;
                $newTotalPnL = $currentTotalPnL + $pnlThisTransaction;

                // 4. Update Parent (spot_trades)
                // Kita tidak update 'sell_price' di parent karena bisa membingungkan jika partial sell.
                // Tapi kita update 'sell_date' agar user tahu kapan terakhir aktivitas jual.
                $trade->update([
                    'quantity' => max(0, $newQuantity),
                    'status' => $status,
                    'pnl' => $newTotalPnL, // PnL Induk bertambah
                    'sell_date' => $request->date, // Update tanggal aktivitas terakhir
                    'sell_time' => $request->time,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Transaction recorded successfully!');
    }
}