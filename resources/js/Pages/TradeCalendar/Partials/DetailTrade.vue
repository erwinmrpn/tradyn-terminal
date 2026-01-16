<script setup lang="ts">
import { computed, ref } from 'vue';

interface TransactionChild {
    id: number;
    date: string;
    time: string;
    type: 'BUY' | 'SELL';
    price: number;
    qty: number;
    pnl: number;
}

interface Trade {
    id: string;
    is_parent: boolean;
    time: string;
    account_name: string;
    symbol: string;
    // Update Interface: Pisahkan Market dan Strategy
    market_type: string;   // e.g. CRYPTO
    strategy_type: 'FUTURES' | 'SPOT'; // e.g. SPOT
    side: string;
    price: number;
    size: number;
    pnl: number;
    status: string;
    children?: TransactionChild[]; 
}

const props = defineProps<{
    date: string; 
    trades: Trade[]; 
    onBack: () => void;
}>();

// State untuk menyimpan ID row yang sedang dibuka (expanded)
const expandedRows = ref<Set<string>>(new Set());

const toggleRow = (id: string) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: 'USD',
        minimumFractionDigits: 2, 
    }).format(value);
};

const formatTime = (dateString: string) => {
    if (!dateString) return '-';
    if(dateString.includes(' ')) {
        const [date, time] = dateString.split(' ');
        return time.split(':').slice(0, 2).join(':');
    }
    return '00:00';
};

const formatDateShort = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

// Hitung Summary (Exclude Holding/Open dari Realized PnL)
const dailyTotalPnL = computed(() => {
    return props.trades
        .filter(t => !['OPEN', 'HOLDING'].includes(t.status))
        .reduce((acc, trade) => acc + Number(trade.pnl), 0);
});

const gradientTextStyle = {
    background: 'linear-gradient(to right, #8c52ff, #5ce1e6)',
    '-webkit-background-clip': 'text',
    'background-clip': 'text',
    '-webkit-text-fill-color': 'transparent',
    'color': 'transparent'
};
</script>

<template>
    <div class="animate-fade-in-up">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-4">
                <button @click="props.onBack" class="p-2 rounded-lg bg-[#1f2128] hover:bg-[#2d3039] text-gray-400 hover:text-white transition-all border border-[#2d3039]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">
                        {{ new Date(date).toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Trade History Log</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6 bg-[#1f2128] px-6 py-3 rounded-xl border border-[#2d3039]">
                <div class="text-right">
                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Trades</p>
                    <p class="text-lg font-bold text-white">{{ trades.length }}</p>
                </div>
                <div class="h-8 w-px bg-[#2d3039]"></div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Realized PnL</p>
                    <p class="text-xl font-mono font-bold" :class="dailyTotalPnL >= 0 ? 'text-green-400' : 'text-red-400'">
                        {{ formatCurrency(dailyTotalPnL) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-[#121317] border border-[#1f2128] rounded-xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#1f2128] text-gray-500 text-xs uppercase tracking-wider border-b border-[#2d3039]">
                            <th class="px-6 py-4 font-bold w-10"></th> 
                            <th class="px-6 py-4 font-bold">Time</th>
                            <th class="px-6 py-4 font-bold">Account</th>
                            
                            <th class="px-6 py-4 font-bold">Market</th> <th class="px-6 py-4 font-bold">Strategy</th> <th class="px-6 py-4 font-bold">Symbol</th>
                            <th class="px-6 py-4 font-bold">Side</th>
                            <th class="px-6 py-4 font-bold text-right">Price</th>
                            <th class="px-6 py-4 font-bold text-right">Size</th>
                            <th class="px-6 py-4 font-bold text-right">PnL</th>
                            <th class="px-6 py-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#1f2128]">
                        <template v-for="trade in trades" :key="trade.id">
                            <tr class="hover:bg-[#1a1b20] transition-colors group cursor-pointer"
                                @click="trade.is_parent ? toggleRow(trade.id) : null">
                                
                                <td class="px-6 py-4 text-center">
                                    <button v-if="trade.is_parent" class="text-gray-500 hover:text-white transition-colors">
                                        <svg class="w-4 h-4 transform transition-transform duration-200" 
                                            :class="expandedRows.has(trade.id) ? 'rotate-90' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-400 font-mono">
                                    {{ formatTime(trade.time) }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-gray-300 bg-[#1f2128] px-2 py-1 rounded border border-[#2d3039]">
                                        {{ trade.account_name }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm font-bold text-gray-400">
                                    {{ trade.market_type }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider border"
                                        :class="trade.strategy_type === 'SPOT' 
                                            ? 'bg-yellow-500/20 text-yellow-500 border-yellow-500/30' 
                                            : 'bg-purple-500/20 text-purple-400 border-purple-500/30'">
                                        {{ trade.strategy_type }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-white group-hover:text-[#8c52ff] transition-colors">
                                        {{ trade.symbol }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold px-2 py-1 rounded border"
                                        :class="{
                                            'bg-green-500/10 text-green-400 border-green-500/20': ['LONG', 'BUY', 'HOLDING'].includes(trade.side),
                                            'bg-red-500/10 text-red-400 border-red-500/20': ['SHORT', 'SELL', 'SOLD'].includes(trade.side)
                                        }">
                                        {{ trade.side }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-300 font-mono text-right">
                                    {{ new Intl.NumberFormat('en-US').format(trade.price) }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-400 font-mono text-right">
                                    {{ parseFloat(String(trade.size)) }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span v-if="['OPEN', 'HOLDING'].includes(trade.status)" class="text-sm font-bold font-mono text-gray-500">
                                        -
                                    </span>
                                    <span v-else class="text-sm font-bold font-mono" 
                                        :class="trade.pnl >= 0 ? 'text-green-400' : 'text-red-400'">
                                        {{ trade.pnl > 0 ? '+' : '' }}{{ formatCurrency(trade.pnl) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span v-if="trade.status === 'WIN'" class="text-xs font-bold" :style="gradientTextStyle">WIN</span>
                                    <span v-else-if="trade.status === 'LOSS'" class="text-xs font-bold text-red-500">LOSS</span>
                                    <span v-else-if="trade.status === 'BE'" class="text-xs font-bold text-gray-400">BE</span>
                                    <span v-else-if="trade.status === 'OPEN'" class="text-xs font-bold text-blue-400 bg-blue-500/10 px-2 py-1 rounded border border-blue-500/20">OPEN</span>
                                    <span v-else-if="trade.status === 'HOLDING'" class="text-xs font-bold text-yellow-400 bg-yellow-500/10 px-2 py-1 rounded border border-yellow-500/20">HOLDING</span>
                                </td>
                            </tr>

                            <tr v-if="trade.is_parent && expandedRows.has(trade.id)" class="bg-[#0f1013]">
                                <td colspan="11" class="px-6 py-4 border-l-2 border-[#8c52ff]">
                                    <div class="bg-[#1f2128] rounded-lg p-4 border border-[#2d3039]">
                                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-3">Transaction Details (DCA / Partial)</p>
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="text-gray-500 text-xs border-b border-[#2d3039]">
                                                    <th class="py-2 text-left">Date</th>
                                                    <th class="py-2 text-left">Type</th>
                                                    <th class="py-2 text-right">Price</th>
                                                    <th class="py-2 text-right">Qty</th>
                                                    <th class="py-2 text-right">Realized PnL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="child in trade.children" :key="child.id" class="border-b border-[#2d3039]/50 last:border-0">
                                                    <td class="py-2 text-gray-400">{{ formatDateShort(child.date) }} {{ formatTime(child.time) }}</td>
                                                    <td class="py-2">
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-bold"
                                                            :class="child.type === 'BUY' ? 'text-green-400 bg-green-500/10' : 'text-red-400 bg-red-500/10'">
                                                            {{ child.type }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2 text-right text-gray-300 font-mono">{{ new Intl.NumberFormat('en-US').format(child.price) }}</td>
                                                    <td class="py-2 text-right text-gray-400 font-mono">{{ parseFloat(String(child.qty)) }}</td>
                                                    <td class="py-2 text-right font-mono font-bold" 
                                                        :class="child.pnl >= 0 ? 'text-green-400' : 'text-red-400'">
                                                        {{ child.type === 'SELL' ? formatCurrency(child.pnl) : '-' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="trades.length === 0">
                            <td colspan="11" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-sm">No trades recorded on this date.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { 
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
}

@keyframes fadeInUp { 
    from { opacity: 0; transform: translateY(10px); } 
    to { opacity: 1; transform: translateY(0); } 
}
</style>