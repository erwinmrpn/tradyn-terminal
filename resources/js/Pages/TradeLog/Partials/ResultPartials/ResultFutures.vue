<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    trades: any[];
}>();

const timeFrame = ref<'TODAY' | 'WEEK' | 'MONTH'>('TODAY');

// Helpers
const isSameDay = (d1: Date, d2: Date) => d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth() && d1.getDate() === d2.getDate();
const getWeekNumber = (d: Date) => {
    const onejan = new Date(d.getFullYear(), 0, 1);
    return Math.ceil((((d.getTime() - onejan.getTime()) / 86400000) + onejan.getDay() + 1) / 7);
};

// [UPDATE] Filter Logic (Gunakan exit_date saja)
const filteredTrades = computed(() => {
    const now = new Date();
    return props.trades.filter(t => {
        // Karena exit_date cuma string 'YYYY-MM-DD', kita bisa langsung parse
        const tradeDate = new Date(t.exit_date);
        
        if (timeFrame.value === 'TODAY') return isSameDay(tradeDate, now);
        if (timeFrame.value === 'WEEK') return getWeekNumber(tradeDate) === getWeekNumber(now) && tradeDate.getFullYear() === now.getFullYear();
        if (timeFrame.value === 'MONTH') return tradeDate.getMonth() === now.getMonth() && tradeDate.getFullYear() === now.getFullYear();
        return false;
    });
});

// [UPDATE] Helper Combine Date + Time
const getDateTime = (dateStr: string, timeStr: string) => {
    // Format ISO standard: YYYY-MM-DDTHH:mm:ss
    return new Date(`${dateStr}T${timeStr}`);
};

const metrics = computed(() => {
    const trades = filteredTrades.value;
    let netPnL = 0;
    let wins = 0;
    let totalRR = 0;
    let rrCount = 0;
    let totalDurationMs = 0;

    trades.forEach(t => {
        const pnl = parseFloat(t.pnl || 0);
        netPnL += pnl;
        if (pnl > 0) wins++;
        
        if (t.sl_price && parseFloat(t.sl_price) > 0) {
            const risk = Math.abs(parseFloat(t.entry_price) - parseFloat(t.sl_price)) * parseFloat(t.quantity);
            if (risk > 0) {
                totalRR += (pnl / risk);
                rrCount++;
            }
        }

        // [UPDATE] Kalkulasi Durasi dengan Manual Combine
        if (t.entry_date && t.entry_time && t.exit_date && t.exit_time) {
            const entryTime = getDateTime(t.entry_date, t.entry_time).getTime();
            const exitTime = getDateTime(t.exit_date, t.exit_time).getTime();
            totalDurationMs += (exitTime - entryTime);
        }
    });

    const totalTrades = trades.length;
    return {
        netPnL,
        totalTrades,
        winRate: totalTrades > 0 ? (wins / totalTrades) * 100 : 0,
        avgRR: rrCount > 0 ? (totalRR / rrCount) : 0,
        avgDurationMs: totalTrades > 0 ? (totalDurationMs / totalTrades) : 0
    };
});

const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
const formatDuration = (ms: number) => {
    if (ms <= 0) return '-';
    const minutes = Math.floor(ms / 60000);
    const seconds = Math.floor((ms % 60000) / 1000);
    return `${minutes}m ${seconds}s`;
};

// Styling
const pnlClass = computed(() => metrics.value.netPnL > 0 ? 'text-green-500' : (metrics.value.netPnL < 0 ? 'text-red-500' : 'text-gray-400'));
const winRateClass = computed(() => metrics.value.winRate >= 60 ? 'text-green-500' : (metrics.value.winRate >= 50 ? 'text-yellow-500' : 'text-red-500'));
const tradesClass = computed(() => timeFrame.value === 'TODAY' && metrics.value.totalTrades > 5 ? 'text-red-500' : 'text-white');
const rrClass = computed(() => metrics.value.avgRR >= 1.5 ? 'text-green-500' : (metrics.value.avgRR >= 1 ? 'text-yellow-500' : 'text-red-500'));
</script>

<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                Futures Performance
            </h3>
            <select v-model="timeFrame" class="bg-[#1a1b20] border border-[#2d2f36] text-white text-xs rounded-lg p-2 outline-none focus:border-blue-500 cursor-pointer">
                <option value="TODAY">Today</option>
                <option value="WEEK">This Week</option>
                <option value="MONTH">This Month</option>
            </select>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-4 flex flex-col justify-center items-center shadow-lg relative overflow-hidden">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Net PnL</div>
                <div class="text-2xl font-black" :class="pnlClass">{{ metrics.netPnL > 0 ? '+' : '' }}{{ formatCurrency(metrics.netPnL) }}</div>
            </div>

            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-4 flex flex-col justify-center items-center shadow-lg">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Win Rate</div>
                <div class="text-xl font-bold" :class="winRateClass">{{ metrics.winRate.toFixed(0) }}%</div>
            </div>

            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-4 flex flex-col justify-center items-center shadow-lg">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Trades</div>
                <div class="text-xl font-bold" :class="tradesClass">{{ metrics.totalTrades }} <span class="text-xs font-normal text-gray-500">{{ timeFrame.toLowerCase() }}</span></div>
            </div>

            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-4 flex flex-col justify-center items-center shadow-lg">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Avg R:R</div>
                <div class="text-xl font-bold" :class="rrClass">1 : {{ Math.abs(metrics.avgRR).toFixed(1) }}</div>
            </div>

            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-4 flex flex-col justify-center items-center shadow-lg">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Avg Duration</div>
                <div class="text-xl font-bold text-white">{{ formatDuration(metrics.avgDurationMs) }}</div>
            </div>
        </div>
    </div>
</template>