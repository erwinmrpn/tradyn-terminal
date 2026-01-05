<script setup lang="ts">
import { computed, ref } from 'vue';

// --- PROPS ---
// Menerima data history trades yang SUDAH CLOSED dari parent
const props = defineProps<{
    trades: any[]; 
}>();

// --- STATE ---
const timeFrame = ref<'TODAY' | 'WEEK' | 'MONTH'>('WEEK');
const expandedIds = ref<Set<number>>(new Set());

// --- HELPERS ---
const toggleExpand = (id: number) => {
    if (expandedIds.value.has(id)) expandedIds.value.delete(id);
    else expandedIds.value.add(id);
};

const getDateTime = (dateStr: string, timeStr: string) => {
    if (!dateStr || !timeStr) return null;
    return new Date(`${dateStr}T${timeStr}`);
};

// Filter Logic (Sederhana)
const filteredTrades = computed(() => {
    return props.trades || []; // Tambahkan logika filter tanggal di sini jika perlu
});

// Metrics Logic
const metrics = computed(() => {
    const trades = filteredTrades.value;
    let netPnL = 0;
    let wins = 0;
    trades.forEach(t => {
        const pnl = parseFloat(t.pnl || 0);
        netPnL += pnl;
        if (pnl > 0) wins++;
    });
    return {
        netPnL,
        totalTrades: trades.length,
        winRate: trades.length > 0 ? (wins / trades.length) * 100 : 0
    };
});

// Formatters
const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
</script>

<template>
    <div class="space-y-8">
        
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-[#5ce1e6]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Spot Performance History
            </h3>
            
            <select v-model="timeFrame" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded-lg px-3 py-2 outline-none focus:border-[#5ce1e6]">
                <option value="TODAY">Today</option>
                <option value="WEEK">This Week</option>
                <option value="MONTH">This Month</option>
            </select>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="summary-card-gradient">
                <div class="text-[10px] text-gray-500 uppercase font-bold mb-2">Net PnL</div>
                <div class="text-2xl font-black" :class="metrics.netPnL >= 0 ? 'text-green-500' : 'text-red-500'">
                    {{ metrics.netPnL >= 0 ? '+' : '' }}{{ formatCurrency(metrics.netPnL) }}
                </div>
            </div>
            <div class="summary-card-gradient">
                <div class="text-[10px] text-gray-500 uppercase font-bold mb-2">Win Rate</div>
                <div class="text-xl font-bold text-white">{{ metrics.winRate.toFixed(0) }}%</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="trade in filteredTrades" :key="trade.id" class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-sm hover:shadow-md transition-all">
                <div class="bg-[#121317] rounded-2xl p-5 h-full flex flex-col justify-between">
                    
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-black text-white">{{ trade.symbol }}</h3>
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 uppercase font-bold">Result</div>
                            <span class="text-xs font-black uppercase" :class="parseFloat(trade.pnl) > 0 ? 'text-green-400' : 'text-red-400'">
                                {{ parseFloat(trade.pnl) > 0 ? 'WIN' : 'LOSS' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end border-t border-[#2d2f36] pt-3">
                         <div class="text-[10px] text-gray-500 uppercase font-bold">Realized PnL</div>
                         <div class="font-bold text-white">{{ formatCurrency(trade.pnl) }}</div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.summary-card-gradient {
  padding: 1.5rem 1rem; border-radius: 0.75rem;
  display: flex; flex-direction: column; justify-content: center; align-items: center;
  border: 2px solid transparent;
  background-image: linear-gradient(#151515, #151515), linear-gradient(90deg, #8c52ff, #5ce1e6);
  background-origin: border-box; background-clip: padding-box, border-box;
}
</style>