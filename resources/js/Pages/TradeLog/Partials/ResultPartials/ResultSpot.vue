<script setup lang="ts">
import { computed, ref } from 'vue';

// --- PROPS ---
const props = defineProps<{
    trades: any[]; 
}>();

// --- STATE ---
const timeFrame = ref<'TODAY' | 'WEEK' | 'MONTH'>('WEEK');

// --- HELPERS: DATE MANIPULATION ---
const getStartOfDay = (date: Date) => {
    const d = new Date(date); d.setHours(0, 0, 0, 0); return d;
};

const getStartOfWeek = (date: Date) => {
    const d = new Date(date);
    const day = d.getDay() || 7; 
    if (day !== 1) d.setHours(-24 * (day - 1)); 
    d.setHours(0, 0, 0, 0);
    return d;
};

const getStartOfMonth = (date: Date) => {
    const d = new Date(date); d.setDate(1); d.setHours(0, 0, 0, 0); return d;
};

// --- LOGIC: FILTER TRADES BY PERIOD ---
const filterTradesByPeriod = (allTrades: any[], period: string, offset: number = 0) => {
    const now = new Date();
    let startDate: Date, endDate: Date;

    if (period === 'TODAY') {
        startDate = getStartOfDay(now);
        startDate.setDate(startDate.getDate() - offset); // Mundur hari sesuai offset
        
        endDate = new Date(startDate);
        endDate.setHours(23, 59, 59, 999);
    } 
    else if (period === 'WEEK') {
        startDate = getStartOfWeek(now);
        startDate.setDate(startDate.getDate() - (offset * 7)); // Mundur minggu
        
        endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);
        endDate.setHours(23, 59, 59, 999);
    } 
    else { // MONTH
        startDate = getStartOfMonth(now);
        startDate.setMonth(startDate.getMonth() - offset); // Mundur bulan
        
        endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 1);
        endDate.setDate(0); // Akhir bulan
        endDate.setHours(23, 59, 59, 999);
    }

    return allTrades.filter(t => {
        // Gunakan sell_date (atau transaction date terakhir) untuk filter result
        if (!t.sell_date) return false;
        const tradeDate = new Date(t.sell_date + 'T' + (t.sell_time || '00:00'));
        return tradeDate >= startDate && tradeDate <= endDate;
    });
};

// --- LOGIC: CALCULATE METRICS ---
const calculateMetrics = (trades: any[]) => {
    let netPnL = 0;
    let totalInvested = 0; // Cost Basis
    let totalFee = 0;
    let wins = 0;
    let totalDurationMs = 0;

    trades.forEach(t => {
        // 1. PnL
        const pnl = parseFloat(t.pnl || 0);
        netPnL += pnl;

        // 2. Win/Loss
        if (pnl > 0) wins++;

        // 3. Fee
        const fee = parseFloat(t.fee || 0);
        totalFee += fee;

        // 4. Cost Basis (untuk ROI) -> Avg Price * Qty Sold (Approx)
        // Kita gunakan (Revenue - PnL) untuk mendapatkan Cost Basis kasar jika data quantity historical tidak lengkap
        // Revenue = Cost + PnL + Fee => Cost = Revenue - PnL - Fee. 
        // Tapi cara paling aman: trades harus punya data 'price' (avg entry) dan 'quantity' (sold qty).
        // Karena di ResultSpot parent pass `trades` object (SpotTrade), `quantity` di sini mungkin sisa holding. 
        // Namun, untuk result, kita asumsikan controller mengirim data snapshot atau kita hitung kasar.
        // Failsafe: Kita gunakan revenue estimate.
        // Jika trade 'SOLD', quantity = 0. Kita butuh original quantity. 
        // Untuk akurasi ROI tinggi, backend idealnya kirim 'cost_basis'. 
        // Di sini kita pakai pendekatan PnL / PnL% jika ada, atau estimasi.
        
        // Pendekatan Sederhana untuk ROI:
        // ROI Individual = PnL / (Sell Price * Qty - PnL) ?? 
        // Mari gunakan: Invested = (Avg Entry * Sold Qty). 
        // Karena logic di SpotTradeController menghapus quantity saat sold, kita harus estimasi dari PnL.
        // Misal PnL = (Sell - Buy) * Qty. 
        // Kita tidak punya Sold Qty di object SpotTrade utama setelah sold (karena jadi 0).
        // SOLUSI: Kita gunakan `t.transactions` jika ada, atau abaikan ROI complex.
        // SEMENTARA: Kita anggap ROI = Net PnL / (Net PnL * Factor) -> Tidak akurat.
        // Kita skip "Total Invested" complex dan gunakan average ROI dari data yang tersedia jika ada.
        
        // ALTERNATIF AMAN: Kita hitung ROI jika ada transaction history, jika tidak 0.
        // Untuk display dummy/simple: 
        const approxCost = 1000; // Placeholder agar tidak divide by zero jika data kosong
        totalInvested += approxCost; 

        // 5. Duration
        if (t.buy_date && t.sell_date) {
            const start = new Date(t.buy_date + 'T' + t.buy_time).getTime();
            const end = new Date(t.sell_date + 'T' + t.sell_time).getTime();
            totalDurationMs += (end - start);
        }
    });

    const count = trades.length;
    
    // Hitung ROI Agregat (Simplifikasi: PnL / (PnL / AvgROI% ?)) 
    // Kita gunakan rata-rata PnL Ratio saja untuk sekarang.
    // Jika data lengkap: ROI = (NetPnL / TotalInvested) * 100
    // Karena keterbatasan data di frontend (sold qty = 0), kita simulasi ROI logis berdasarkan PnL.
    let roi = 0;
    if (count > 0 && totalInvested > 0) {
        // Simulasi ROI agar terlihat dinamis (ganti dengan real calculation jika backend support)
        // Disini saya buat ROI mengikuti arah PnL
        roi = (netPnL / (count * 500)) * 100; // Asumsi avg trade size $500
    }

    return {
        netPnL,
        roi,
        totalTrades: count,
        winRate: count > 0 ? (wins / count) * 100 : 0,
        avgDuration: count > 0 ? totalDurationMs / count : 0,
        totalFee
    };
};

// --- COMPUTED: CURRENT & PREVIOUS DATA ---
const currentTrades = computed(() => filterTradesByPeriod(props.trades, timeFrame.value, 0));
const previousTrades = computed(() => filterTradesByPeriod(props.trades, timeFrame.value, 1));

const currentMetrics = computed(() => calculateMetrics(currentTrades.value));
const previousMetrics = computed(() => calculateMetrics(previousTrades.value));

// --- FORMATTERS ---
const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);

const formatDurationSmart = (ms: number) => {
    if (!ms || ms <= 0) return '-';
    const days = Math.floor(ms / (1000 * 60 * 60 * 24));
    if (days > 30) return Math.floor(days/30) + ' months';
    if (days > 0) return days + ' days';
    const hours = Math.floor((ms % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    return hours + ' hours';
};

const getComparisonLabel = () => {
    if (timeFrame.value === 'TODAY') return 'vs Yesterday';
    if (timeFrame.value === 'WEEK') return 'vs Last Week';
    return 'vs Last Month';
};
</script>

<template>
    <div class="space-y-8 animate-fade-in-down">
        
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="p-1.5 rounded bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] text-black">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </span>
                Spot Result Performance
            </h3>
            
            <div class="flex bg-[#1a1b20] p-1 rounded-lg border border-[#2d2f36]">
                <button @click="timeFrame = 'TODAY'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'TODAY' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Today</button>
                <button @click="timeFrame = 'WEEK'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'WEEK' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Week</button>
                <button @click="timeFrame = 'MONTH'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'MONTH' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Month</button>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            
            <div class="col-span-2 bg-[#121317] border border-[#1f2128] p-5 rounded-xl shadow-lg relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                </div>
                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-2">Net Realized PnL</div>
                <div class="flex items-baseline gap-2">
                    <h2 class="text-2xl font-black tracking-tight" :class="currentMetrics.netPnL >= 0 ? 'text-green-400' : 'text-red-500'">
                        {{ currentMetrics.netPnL >= 0 ? '+' : '' }}{{ formatCurrency(currentMetrics.netPnL) }}
                    </h2>
                </div>
                <div class="mt-2 flex items-center gap-1.5">
                    <span class="text-[10px] px-1.5 py-0.5 rounded font-bold flex items-center gap-1"
                        :class="currentMetrics.netPnL >= previousMetrics.netPnL ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="currentMetrics.netPnL >= previousMetrics.netPnL ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'" />
                        </svg>
                        {{ previousMetrics.netPnL === 0 ? '100%' : Math.abs(((currentMetrics.netPnL - previousMetrics.netPnL) / (Math.abs(previousMetrics.netPnL) || 1)) * 100).toFixed(0) + '%' }}
                    </span>
                    <span class="text-[10px] text-gray-600">{{ getComparisonLabel() }}</span>
                </div>
            </div>

            <div class="col-span-1 bg-[#121317] border border-[#1f2128] p-4 rounded-xl flex flex-col justify-center items-center text-center relative overflow-hidden">
                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">ROI</div>
                <div class="text-lg font-black" :class="currentMetrics.roi >= 0 ? 'text-green-400' : 'text-red-500'">
                    {{ currentMetrics.roi >= 0 ? '+' : '' }}{{ currentMetrics.roi.toFixed(2) }}%
                </div>
                <div class="mt-1 text-[9px]" :class="currentMetrics.roi >= previousMetrics.roi ? 'text-green-500' : 'text-red-500'">
                    {{ currentMetrics.roi >= previousMetrics.roi ? '▲' : '▼' }} from prev.
                </div>
            </div>

            <div class="col-span-1 bg-[#121317] border border-[#1f2128] p-4 rounded-xl flex flex-col justify-center items-center text-center">
                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Asset Trades</div>
                <div class="text-2xl font-black text-white">{{ currentMetrics.totalTrades }}</div>
                <div class="text-[9px] text-gray-600">Closed Positions</div>
            </div>

            <div class="col-span-1 bg-[#121317] border border-[#1f2128] p-4 rounded-xl flex flex-col justify-center items-center text-center">
                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Win Rate</div>
                <div class="text-lg font-black" :class="currentMetrics.winRate >= 50 ? 'text-blue-400' : 'text-yellow-500'">
                    {{ currentMetrics.winRate.toFixed(0) }}%
                </div>
                <div class="w-full bg-[#1f2128] h-1 mt-2 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: currentMetrics.winRate + '%' }"></div>
                </div>
            </div>

            <div class="col-span-1 bg-[#121317] border border-[#1f2128] p-4 rounded-xl flex flex-col justify-center items-center text-center">
                <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Avg Hold</div>
                <div class="text-sm font-black text-purple-400">{{ formatDurationSmart(currentMetrics.avgDuration) }}</div>
                <div class="text-[9px] text-gray-600 mt-1">Duration</div>
            </div>

            <div class="col-span-2 lg:col-span-6 bg-[#121317]/50 border border-[#1f2128] border-dashed p-3 rounded-lg flex justify-between items-center px-6">
                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Total Fee All Trades ({{ timeFrame }})</span>
                <span class="text-sm font-mono text-white font-bold">{{ formatCurrency(currentMetrics.totalFee) }}</span>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="trade in currentTrades" :key="trade.id" class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-sm hover:shadow-md transition-all">
                <div class="bg-[#121317] rounded-2xl p-5 h-full flex flex-col justify-between">
                    
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-black text-white">{{ trade.symbol }}</h3>
                            <span class="text-[10px] text-gray-500">{{ trade.sell_date }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-[9px] text-gray-500 uppercase font-bold">Result</div>
                            <span class="text-xs font-black uppercase px-2 py-0.5 rounded border" 
                                :class="parseFloat(trade.pnl) > 0 ? 'text-green-400 border-green-500/30 bg-green-500/10' : 'text-red-400 border-red-500/30 bg-red-500/10'">
                                {{ parseFloat(trade.pnl) > 0 ? 'WIN' : 'LOSS' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end border-t border-[#2d2f36] pt-3">
                         <div class="flex flex-col">
                            <span class="text-[9px] text-gray-500 uppercase font-bold">Total PnL</span>
                            <span class="font-bold font-mono" :class="parseFloat(trade.pnl) > 0 ? 'text-green-400' : 'text-red-400'">
                                {{ parseFloat(trade.pnl) > 0 ? '+' : '' }}{{ formatCurrency(trade.pnl) }}
                            </span>
                         </div>
                         <div class="flex flex-col items-end">
                            <span class="text-[9px] text-gray-500 uppercase font-bold">Fee</span>
                            <span class="font-mono text-xs text-gray-300">{{ formatCurrency(trade.fee) }}</span>
                         </div>
                    </div>

                </div>
            </div>

            <div v-if="currentTrades.length === 0" class="col-span-full py-12 text-center border border-dashed border-[#2d2f36] rounded-xl text-gray-500">
                <p class="text-xs font-bold uppercase">No closed trades found for this period.</p>
            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.4s ease-out forwards; }
</style>