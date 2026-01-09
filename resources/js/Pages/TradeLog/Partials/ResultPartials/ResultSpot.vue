<script setup lang="ts">
import { computed, ref } from 'vue';

// --- PROPS ---
const props = defineProps<{
    trades: any[]; 
}>();

// --- STATE ---
const timeFrame = ref<'TODAY' | 'WEEK' | 'MONTH'>('WEEK');
const expandedCards = ref<Set<number>>(new Set());

// --- HELPERS: DATE ---
const getStartOfDay = (date: Date) => { const d = new Date(date); d.setHours(0, 0, 0, 0); return d; };
const getStartOfWeek = (date: Date) => { const d = new Date(date); const day = d.getDay() || 7; if (day !== 1) d.setHours(-24 * (day - 1)); d.setHours(0, 0, 0, 0); return d; };
const getStartOfMonth = (date: Date) => { const d = new Date(date); d.setDate(1); d.setHours(0, 0, 0, 0); return d; };

// --- HELPERS: UI ---
const toggleExpand = (id: number) => {
    if (expandedCards.value.has(id)) expandedCards.value.delete(id);
    else expandedCards.value.add(id);
};

// --- LOGIC: FILTER TRADES ---
const filterTradesByPeriod = (allTrades: any[], period: string, offset: number = 0) => {
    const now = new Date();
    let startDate: Date, endDate: Date;

    if (period === 'TODAY') {
        startDate = getStartOfDay(now);
        startDate.setDate(startDate.getDate() - offset);
        endDate = new Date(startDate);
        endDate.setHours(23, 59, 59, 999);
    } 
    else if (period === 'WEEK') {
        startDate = getStartOfWeek(now);
        startDate.setDate(startDate.getDate() - (offset * 7));
        endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);
        endDate.setHours(23, 59, 59, 999);
    } 
    else { // MONTH
        startDate = getStartOfMonth(now);
        startDate.setMonth(startDate.getMonth() - offset);
        endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 1);
        endDate.setDate(0); 
        endDate.setHours(23, 59, 59, 999);
    }

    return allTrades.filter(t => {
        if (!t.sell_date) return false;
        const tradeDate = new Date(t.sell_date + 'T' + (t.sell_time || '00:00'));
        return tradeDate >= startDate && tradeDate <= endDate;
    }).sort((a, b) => {
        const timeA = new Date(a.sell_date + 'T' + (a.sell_time || '00:00')).getTime();
        const timeB = new Date(b.sell_date + 'T' + (b.sell_time || '00:00')).getTime();
        return timeB - timeA;
    });
};

// --- LOGIC: CALCULATE METRICS ---
const calculateMetrics = (trades: any[]) => {
    let netPnL = 0;
    let totalInvested = 0; 
    let totalFee = 0;
    let wins = 0;
    let totalDurationMs = 0;

    trades.forEach(t => {
        const pnl = parseFloat(t.pnl || 0);
        netPnL += pnl;
        if (pnl > 0) wins++;

        // Hitung Fee (Initial + Transactions)
        let tradeFee = parseFloat(t.fee || 0);
        if (t.transactions && Array.isArray(t.transactions)) {
            t.transactions.forEach((tx: any) => {
                tradeFee += parseFloat(tx.fee || 0);
            });
        }
        totalFee += tradeFee;

        // Hitung Revenue untuk estimasi Invested
        let revenue = 0;
        if (t.transactions && Array.isArray(t.transactions)) {
            t.transactions.forEach((tx: any) => {
                if (tx.type === 'SELL') {
                    revenue += (parseFloat(tx.price) * parseFloat(tx.quantity));
                }
            });
        }
        // Fallback jika tidak ada detail transaksi (data lama)
        if (revenue === 0) revenue = parseFloat(t.price) * parseFloat(t.quantity);

        const costBasis = revenue - pnl - tradeFee;
        totalInvested += (costBasis > 0 ? costBasis : 0);

        if (t.buy_date && t.sell_date) {
            const start = new Date(t.buy_date + 'T' + t.buy_time).getTime();
            const end = new Date(t.sell_date + 'T' + t.sell_time).getTime();
            totalDurationMs += (end - start);
        }
    });

    const count = trades.length;
    let roi = 0;
    if (count > 0 && totalInvested > 0) {
        roi = (netPnL / totalInvested) * 100;
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

const currentTrades = computed(() => filterTradesByPeriod(props.trades, timeFrame.value, 0));
const previousTrades = computed(() => filterTradesByPeriod(props.trades, timeFrame.value, 1));
const currentMetrics = computed(() => calculateMetrics(currentTrades.value));
const previousMetrics = computed(() => calculateMetrics(previousTrades.value));

// --- FORMATTERS ---
const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
const formatDurationSmart = (ms: number) => {
    if (!ms || ms <= 0) return '-';
    const days = Math.floor(ms / (1000 * 60 * 60 * 24));
    if (days > 30) return Math.floor(days/30) + ' Mo';
    if (days > 0) return days + ' d';
    const hours = Math.floor((ms % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    return hours + ' h';
};
const getComparisonLabel = () => {
    if (timeFrame.value === 'TODAY') return 'vs Yest.';
    if (timeFrame.value === 'WEEK') return 'vs Last Wk.';
    return 'vs Last Mo.';
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
            
            <div class="p-[1px] rounded-lg bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6]">
                <div class="flex bg-[#1a1b20] p-1 rounded-lg h-full">
                    <button @click="timeFrame = 'TODAY'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'TODAY' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Today</button>
                    <button @click="timeFrame = 'WEEK'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'WEEK' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Week</button>
                    <button @click="timeFrame = 'MONTH'" class="px-4 py-1.5 text-[10px] font-bold uppercase rounded transition-all" :class="timeFrame === 'MONTH' ? 'bg-[#2d2f36] text-white shadow' : 'text-gray-500 hover:text-gray-300'">Month</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-stretch">
            
            <div class="col-span-1 relative group p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full relative overflow-hidden flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Net PnL</div>
                    <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="flex-1 flex flex-col justify-center">
                        <div class="text-xl font-black tracking-tight" :class="currentMetrics.netPnL >= 0 ? 'text-green-400' : 'text-red-500'">
                            {{ currentMetrics.netPnL >= 0 ? '+' : '' }}{{ formatCurrency(currentMetrics.netPnL) }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold flex items-center gap-1 bg-[#1a1b20] border border-[#2d2f36]" :class="currentMetrics.netPnL >= previousMetrics.netPnL ? 'text-green-400' : 'text-red-400'">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="currentMetrics.netPnL >= previousMetrics.netPnL ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'" /></svg>
                            {{ previousMetrics.netPnL === 0 ? '100%' : Math.abs(((currentMetrics.netPnL - previousMetrics.netPnL) / (Math.abs(previousMetrics.netPnL) || 1)) * 100).toFixed(0) + '%' }}
                        </span>
                        <span class="text-[9px] text-gray-500 font-medium whitespace-nowrap">{{ getComparisonLabel() }}</span>
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left relative overflow-hidden">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">ROI</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-xl font-black" :class="currentMetrics.roi >= 0 ? 'text-green-400' : 'text-red-500'">
                            {{ currentMetrics.roi >= 0 ? '+' : '' }}{{ currentMetrics.roi.toFixed(2) }}%
                        </div>
                    </div>
                    <div class="text-[9px] font-medium text-gray-500 flex items-center gap-1">
                        <span :class="currentMetrics.roi >= previousMetrics.roi ? 'text-green-500' : 'text-red-500'">{{ currentMetrics.roi >= previousMetrics.roi ? '▲' : '▼' }}</span> from prev.
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Trades</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-2xl font-black text-white">{{ currentMetrics.totalTrades }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">Closed Positions</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Win Rate</div>
                    <div class="flex-1 flex flex-col justify-center gap-2">
                        <div class="text-xl font-black" :class="currentMetrics.winRate >= 50 ? 'text-blue-400' : 'text-yellow-500'">
                            {{ currentMetrics.winRate.toFixed(0) }}%
                        </div>
                        <div class="w-full bg-[#1f2128] h-1 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: currentMetrics.winRate + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Avg Hold</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-lg font-black text-purple-400">{{ formatDurationSmart(currentMetrics.avgDuration) }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">Duration</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Fees</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-lg font-black text-white font-mono">{{ formatCurrency(currentMetrics.totalFee) }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">All Trades</div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="trade in currentTrades" :key="trade.id" class="relative group">
                <div class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-sm hover:shadow-md transition-all">
                    <div class="bg-[#121317] rounded-2xl p-5 h-full flex flex-col justify-between">
                        
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-black text-white">{{ trade.symbol }}</h3>
                                <span class="text-[10px] text-gray-500">{{ trade.sell_date }}</span>
                            </div>
                            <span class="text-xs font-black uppercase px-2 py-0.5 rounded border" 
                                :class="parseFloat(trade.pnl) > 0 ? 'text-green-400 bg-green-500/10 border-green-500/30' : 'text-red-400 bg-red-500/10 border-red-500/30'">
                                {{ parseFloat(trade.pnl) > 0 ? 'WIN' : 'LOSS' }}
                            </span>
                        </div>

                        <div class="flex justify-between items-end border-t border-[#2d2f36] pt-3">
                             <div class="flex flex-col">
                                <span class="text-[9px] text-gray-500 uppercase font-bold">Total PnL</span>
                                <span class="font-bold font-mono text-lg" :class="parseFloat(trade.pnl) > 0 ? 'text-green-400' : 'text-red-400'">
                                    {{ parseFloat(trade.pnl) > 0 ? '+' : '' }}{{ formatCurrency(trade.pnl) }}
                                </span>
                             </div>
                             <div class="flex flex-col items-end">
                                <span class="text-[9px] text-gray-500 uppercase font-bold">Fee</span>
                                <span class="font-mono text-xs text-gray-300">
                                    {{ formatCurrency(
                                        parseFloat(trade.fee || 0) + 
                                        (trade.transactions ? trade.transactions.reduce((sum:number, t:any) => sum + parseFloat(t.fee || 0), 0) : 0)
                                    ) }}
                                </span>
                             </div>
                        </div>

                        <div v-show="expandedCards.has(trade.id)" class="bg-[#0a0b0d] p-5 border-t border-[#2d2f36] space-y-4 animate-fade-in-down mt-4 rounded-xl">
                            
                            <div v-if="trade.buy_notes" class="space-y-1">
                                <div class="text-[9px] text-blue-500 uppercase font-bold">Entry Notes</div>
                                <p class="text-[10px] text-gray-400 italic leading-relaxed">"{{ trade.buy_notes }}"</p>
                            </div>

                            <div v-if="trade.transactions && trade.transactions.length > 0">
                                <div class="text-[9px] text-gray-500 uppercase font-bold mb-2">History</div>
                                <div class="space-y-1">
                                    <div v-for="tx in trade.transactions" :key="tx.id" class="flex justify-between items-center text-[9px] bg-[#1a1b20] p-1.5 rounded border border-[#2d2f36]">
                                        <div class="flex flex-col">
                                            <span class="font-bold" :class="tx.type === 'BUY' ? 'text-emerald-400' : 'text-red-400'">{{ tx.type }}</span>
                                            <span class="text-gray-500">{{ tx.transaction_date }}</span>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-white font-mono">{{ formatCurrency(tx.price) }}</div>
                                            <div class="text-gray-500 font-mono">{{ parseFloat(tx.quantity).toFixed(6) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <button @click="toggleExpand(trade.id)" 
                            class="w-full mt-4 py-2 text-[10px] font-black uppercase tracking-widest text-black bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] hover:opacity-90 transition-opacity flex items-center justify-center gap-2 rounded-lg">
                            {{ expandedCards.has(trade.id) ? 'LESS INFO' : 'MORE INFO' }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-300" :class="{'rotate-180': expandedCards.has(trade.id)}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

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