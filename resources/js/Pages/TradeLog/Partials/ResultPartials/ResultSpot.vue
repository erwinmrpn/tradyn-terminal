<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    trades: any[];
}>();

// Simple Spot Stats
const stats = computed(() => {
    let totalBuy = 0;
    let totalSell = 0;
    let totalFee = 0;
    let buyCount = 0;
    let sellCount = 0;

    props.trades.forEach(t => {
        if (t.type === 'BUY') {
            totalBuy += parseFloat(t.total);
            buyCount++;
        } else {
            totalSell += parseFloat(t.total);
            sellCount++;
        }
        totalFee += parseFloat(t.fee || 0);
    });

    return { totalBuy, totalSell, totalFee, buyCount, sellCount };
});

const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
</script>

<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Spot Holdings Overview
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-5 shadow-lg flex flex-col items-center">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Total Buy Volume</div>
                <div class="text-2xl font-bold text-white">{{ formatCurrency(stats.totalBuy) }}</div>
                <div class="text-xs text-blue-400 mt-1">{{ stats.buyCount }} txns</div>
            </div>

            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-5 shadow-lg flex flex-col items-center">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Total Sell Volume</div>
                <div class="text-2xl font-bold text-white">{{ formatCurrency(stats.totalSell) }}</div>
                <div class="text-xs text-red-400 mt-1">{{ stats.sellCount }} txns</div>
            </div>

            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-5 shadow-lg flex flex-col items-center">
                <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Total Fees Paid</div>
                <div class="text-2xl font-bold text-yellow-500">{{ formatCurrency(stats.totalFee) }}</div>
            </div>
        </div>
        
        <div class="text-center mt-8 p-6 border border-dashed border-[#2d2f36] rounded-xl">
             <p class="text-gray-500 text-xs">Realized PnL for Spot trading is coming soon.</p>
        </div>
    </div>
</template>