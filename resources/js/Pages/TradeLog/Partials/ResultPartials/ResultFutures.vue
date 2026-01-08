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

const getDateTime = (dateStr: string, timeStr: string) => {
    if (!dateStr || !timeStr) return null;
    return new Date(`${dateStr}T${timeStr}`);
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
        if (!t.exit_date) return false;
        const tradeDate = new Date(t.exit_date + 'T' + (t.exit_time || '00:00'));
        return tradeDate >= startDate && tradeDate <= endDate;
    }).sort((a, b) => {
        const timeA = new Date(a.exit_date + 'T' + (a.exit_time || '00:00')).getTime();
        const timeB = new Date(b.exit_date + 'T' + (b.exit_time || '00:00')).getTime();
        return timeB - timeA;
    });
};

// --- LOGIC: CALCULATE METRICS ---
const calculateMetrics = (trades: any[]) => {
    let netPnL = 0;
    let totalInvested = 0; 
    let totalFee = 0;
    let wins = 0;
    let totalRR = 0;
    let rrCount = 0;
    let totalDurationMs = 0;

    trades.forEach(t => {
        const pnl = parseFloat(t.pnl || 0);
        netPnL += pnl;
        if (pnl > 0) wins++;

        const fee = parseFloat(t.fee || 0);
        totalFee += fee;

        // Margin digunakan sebagai cost basis di Futures
        const margin = parseFloat(t.margin || 0);
        totalInvested += margin;

        // R:R Calculation
        if (t.sl_price && parseFloat(t.sl_price) > 0) {
            const risk = Math.abs(parseFloat(t.entry_price) - parseFloat(t.sl_price)) * parseFloat(t.quantity);
            if (risk > 0) {
                totalRR += (pnl / risk);
                rrCount++;
            }
        }

        // Duration
        const start = getDateTime(t.entry_date, t.entry_time);
        const end = getDateTime(t.exit_date, t.exit_time);
        if (start && end) {
            totalDurationMs += (end.getTime() - start.getTime());
        }
    });

    const count = trades.length;
    
    // ROI (Return on Margin)
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
        totalFee,
        avgRR: rrCount > 0 ? (totalRR / rrCount) : 0
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
const getTradeDuration = (trade: any) => {
    const start = getDateTime(trade.entry_date, trade.entry_time);
    const end = getDateTime(trade.exit_date, trade.exit_time);
    if (!start || !end) return '-';
    return formatDurationSmart(end.getTime() - start.getTime());
};
const getRR = (trade: any) => {
    if (!trade.sl_price || !trade.entry_price || !trade.pnl) return '-';
    const risk = Math.abs(parseFloat(trade.entry_price) - parseFloat(trade.sl_price)) * parseFloat(trade.quantity);
    if (risk === 0) return '-';
    const r = parseFloat(trade.pnl) / risk;
    return `1 : ${r.toFixed(1)}`;
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
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
                Futures Result Performance
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
                    <div class="flex justify-between items-start">
                        <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Net PnL</div>
                        <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity">
                            <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center">
                        <h2 class="text-xl font-black tracking-tight" :class="currentMetrics.netPnL >= 0 ? 'text-green-400' : 'text-red-500'">
                            {{ currentMetrics.netPnL >= 0 ? '+' : '' }}{{ formatCurrency(currentMetrics.netPnL) }}
                        </h2>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold flex items-center gap-1 bg-[#1a1b20] border border-[#2d2f36]"
                            :class="currentMetrics.netPnL >= previousMetrics.netPnL ? 'text-green-400' : 'text-red-400'">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" :d="currentMetrics.netPnL >= previousMetrics.netPnL ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'" />
                            </svg>
                            {{ previousMetrics.netPnL === 0 ? '100%' : Math.abs(((currentMetrics.netPnL - previousMetrics.netPnL) / (Math.abs(previousMetrics.netPnL) || 1)) * 100).toFixed(0) + '%' }}
                        </span>
                        <span class="text-[9px] text-gray-500 font-medium whitespace-nowrap">{{ getComparisonLabel() }}</span>
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left relative overflow-hidden">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Avg R:R</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-xl font-black" :class="currentMetrics.avgRR >= 1.5 ? 'text-green-400' : (currentMetrics.avgRR >= 1 ? 'text-yellow-500' : 'text-red-500')">
                            1 : {{ Math.abs(currentMetrics.avgRR).toFixed(1) }}
                        </div>
                    </div>
                    <div class="text-[9px] font-medium text-gray-500 flex items-center gap-1">
                        Risk Reward
                    </div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Trades</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-2xl font-black text-white">{{ currentMetrics.totalTrades }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">Closed Positions</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Win Rate</div>
                    <div class="flex-1 flex flex-col justify-center gap-2">
                        <div class="text-xl font-black" :class="currentMetrics.winRate >= 50 ? 'text-blue-400' : 'text-yellow-500'">
                            {{ currentMetrics.winRate.toFixed(0) }}%
                        </div>
                        <div class="w-full bg-[#1f2128] h-1 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: currentMetrics.winRate + '%' }"></div>
                        </div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium opacity-0">.</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Avg Duration</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-lg font-black text-purple-400">{{ formatDurationSmart(currentMetrics.avgDuration) }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">Hold Time</div>
                </div>
            </div>

            <div class="col-span-1 p-[1px] rounded-xl bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] shadow-lg">
                <div class="bg-[#121317] rounded-xl p-5 h-full flex flex-col justify-between text-left">
                    <div class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Total Fees</div>
                    <div class="flex-1 flex items-center">
                        <div class="text-lg font-black text-white font-mono">{{ formatCurrency(currentMetrics.totalFee) }}</div>
                    </div>
                    <div class="text-[9px] text-gray-600 font-medium">All Trades</div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <div v-for="trade in currentTrades" :key="trade.id" class="relative group">
                
                <div class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_15px_rgba(140,82,255,0.1)] hover:shadow-[0_0_25px_rgba(92,225,230,0.3)] transition-all duration-300">
                    
                    <div class="bg-[#121317] rounded-2xl h-full flex flex-col justify-between overflow-hidden">
                        
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xl font-black text-white tracking-wide">{{ trade.symbol }}</h3>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded text-white uppercase border border-white/20"
                                            :class="trade.type === 'LONG' ? 'bg-green-600' : 'bg-red-600'">
                                            {{ trade.type }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-2">
                                        <span class="bg-[#1f2128] px-1.5 py-0.5 rounded border border-gray-700">{{ trade.leverage }}x {{ trade.margin_mode }}</span>
                                        <span>{{ trade.trading_account?.name }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-300 font-mono">{{ trade.exit_date }}</div>
                                    <div class="text-[10px] text-gray-500">{{ trade.exit_time?.slice(0,5) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-2">
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-bold">Net PnL</div>
                                    <div class="text-2xl font-black tracking-tight" :class="Number(trade.pnl) > 0 ? 'text-green-400' : 'text-red-500'">
                                        {{ Number(trade.pnl) > 0 ? '+' : '' }}{{ formatCurrency(trade.pnl) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] text-gray-500 uppercase font-bold">Result</div>
                                    <span class="inline-block mt-1 px-3 py-1 rounded text-xs font-black tracking-widest uppercase text-white"
                                        :class="Number(trade.pnl) > 0 ? 'bg-green-600 shadow-[0_0_10px_rgba(22,163,74,0.4)]' : 'bg-red-600 shadow-[0_0_10px_rgba(220,38,38,0.4)]'">
                                        {{ Number(trade.pnl) > 0 ? 'WIN' : 'LOSS' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-xs border-t border-[#2d2f36] pt-3 mt-2">
                                <div class="flex flex-col">
                                    <span class="text-[9px] text-gray-500 uppercase font-bold">R:R Ratio</span>
                                    <span class="font-mono text-gray-300 text-sm bg-[#1f2128] px-2 py-0.5 rounded border border-gray-700 mt-1 inline-block">{{ getRR(trade) }}</span>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-[9px] text-gray-500 uppercase font-bold">Duration</span>
                                    <span class="font-mono text-blue-300 text-sm bg-blue-900/20 px-2 py-0.5 rounded border border-blue-500/20 mt-1 inline-block">{{ getTradeDuration(trade) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-show="expandedCards.has(trade.id)" class="bg-[#0a0b0d] p-5 border-t border-[#2d2f36] space-y-4 animate-fade-in-down">
                            
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <div class="text-[9px] text-gray-600 uppercase font-bold">Entry Price</div>
                                    <div class="font-mono text-gray-300">{{ formatCurrency(trade.entry_price) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[9px] text-gray-600 uppercase font-bold">Exit Price</div>
                                    <div class="font-mono text-gray-300">{{ formatCurrency(trade.exit_price) }}</div>
                                </div>

                                <div>
                                    <div class="text-[9px] text-green-600 uppercase font-bold">Take Profit</div>
                                    <div class="font-mono text-green-400">{{ trade.tp_price ? formatCurrency(trade.tp_price) : '-' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[9px] text-red-600 uppercase font-bold">Stop Loss</div>
                                    <div class="font-mono text-red-400">{{ trade.sl_price ? formatCurrency(trade.sl_price) : '-' }}</div>
                                </div>

                                <div>
                                    <div class="text-[9px] text-gray-600 uppercase font-bold">Fees</div>
                                    <div class="font-mono text-yellow-500">{{ formatCurrency(trade.fee) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[9px] text-gray-600 uppercase font-bold">Margin</div>
                                    <div class="font-mono text-gray-300">{{ formatCurrency(trade.margin) }}</div>
                                </div>
                            </div>

                            <div v-if="trade.entry_notes || trade.exit_notes" class="space-y-2 pt-2 border-t border-[#1f2128]">
                                <div v-if="trade.entry_notes">
                                    <div class="text-[9px] text-blue-500 uppercase font-bold">Entry Setup</div>
                                    <p class="text-[10px] text-gray-400 italic leading-relaxed">"{{ trade.entry_notes }}"</p>
                                </div>
                                <div v-if="trade.exit_notes">
                                    <div class="text-[9px] text-yellow-500 uppercase font-bold">Exit / Learning</div>
                                    <p class="text-[10px] text-gray-400 italic leading-relaxed">"{{ trade.exit_notes }}"</p>
                                </div>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <a v-if="trade.entry_screenshot" :href="'/storage/' + trade.entry_screenshot" target="_blank" class="flex-1 text-center text-[10px] py-2 rounded border border-blue-500/30 text-blue-400 hover:bg-blue-500/10 transition-colors font-bold uppercase tracking-wider">Entry Chart</a>
                                <a v-if="trade.exit_screenshot" :href="'/storage/' + trade.exit_screenshot" target="_blank" class="flex-1 text-center text-[10px] py-2 rounded border border-purple-500/30 text-purple-400 hover:bg-purple-500/10 transition-colors font-bold uppercase tracking-wider">Exit Chart</a>
                            </div>
                        </div>

                        <button @click="toggleExpand(trade.id)" 
                            class="w-full py-3 text-xs font-black uppercase tracking-widest text-black bg-gradient-to-r from-[#8c52ff] to-[#5ce1e6] hover:opacity-90 transition-opacity flex items-center justify-center gap-2 group-hover:from-[#9d6bff] group-hover:to-[#7afbff]">
                            {{ expandedCards.has(trade.id) ? 'LESS INFO' : 'MORE INFO' }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="{'rotate-180': expandedCards.has(trade.id)}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>