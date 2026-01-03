<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    trades: any[];
}>();

const timeFrame = ref<'TODAY' | 'WEEK' | 'MONTH'>('TODAY');
const expandedCards = ref<Set<number>>(new Set());

// --- HELPERS ---
const toggleExpand = (id: number) => {
    if (expandedCards.value.has(id)) {
        expandedCards.value.delete(id);
    } else {
        expandedCards.value.add(id);
    }
};

const getDateTime = (dateStr: string, timeStr: string) => {
    if (!dateStr || !timeStr) return null;
    return new Date(`${dateStr}T${timeStr}`);
};

// [FIX] Helper Date Comparison
const isSameDay = (d1: Date, d2: Date) => {
    return d1.getFullYear() === d2.getFullYear() && 
           d1.getMonth() === d2.getMonth() && 
           d1.getDate() === d2.getDate();
};

const isSameMonth = (d1: Date, d2: Date) => {
    return d1.getFullYear() === d2.getFullYear() && 
           d1.getMonth() === d2.getMonth();
};

// [NEW FIX] Logika Minggu yang Akurat (Senin - Minggu)
const isSameWeek = (date1: Date, date2: Date) => {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    
    // Reset jam agar perbandingan murni tanggal
    d1.setHours(0, 0, 0, 0);
    d2.setHours(0, 0, 0, 0);

    // Atur Minggu (0) menjadi 7, agar urutannya Senin(1) s/d Minggu(7)
    const day1 = d1.getDay() || 7; 
    const day2 = d2.getDay() || 7;

    // Geser kedua tanggal ke hari SENIN terdekat di minggu mereka masing-masing
    d1.setDate(d1.getDate() - day1 + 1);
    d2.setDate(d2.getDate() - day2 + 1);

    // Jika Senin-nya jatuh di tanggal yang sama, berarti satu minggu
    return d1.getTime() === d2.getTime();
};

// --- FILTER & SORT ---
const filteredTrades = computed(() => {
    const now = new Date();
    
    return props.trades
        .filter(t => {
            if (!t.exit_date) return false;
            
            // Konversi exit_date string ke Object Date
            // Asumsi format t.exit_date adalah "YYYY-MM-DD"
            // Kita perlu memastikan jam di-set ke 00:00 untuk komparasi tanggal yang aman
            const tradeDate = new Date(t.exit_date + 'T00:00:00');
            
            if (timeFrame.value === 'TODAY') {
                return isSameDay(tradeDate, now);
            } 
            else if (timeFrame.value === 'WEEK') {
                // Menggunakan logika baru isSameWeek
                return isSameWeek(tradeDate, now);
            } 
            else if (timeFrame.value === 'MONTH') {
                return isSameMonth(tradeDate, now);
            }
            return false;
        })
        .sort((a, b) => {
            // Sort Descending (Terbaru diatas)
            const timeA = new Date(a.exit_date + 'T' + (a.exit_time || '00:00')).getTime();
            const timeB = new Date(b.exit_date + 'T' + (b.exit_time || '00:00')).getTime();
            return timeB - timeA;
        });
});

// --- METRICS ---
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

        const start = getDateTime(t.entry_date, t.entry_time);
        const end = getDateTime(t.exit_date, t.exit_time);
        if (start && end) {
            totalDurationMs += (end.getTime() - start.getTime());
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

// --- FORMATTERS ---
const formatCurrency = (val: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);

const formatDurationSmart = (ms: number) => {
    if (!ms || ms <= 0) return '-';
    const days = Math.floor(ms / (1000 * 60 * 60 * 24));
    const hours = Math.floor((ms % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));
    let result = '';
    if (days > 0) result += `${days}d `;
    if (hours > 0) result += `${hours}h `;
    result += `${minutes}m`;
    return result;
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

// Styling
const pnlClass = computed(() => metrics.value.netPnL > 0 ? 'text-green-500' : (metrics.value.netPnL < 0 ? 'text-red-500' : 'text-gray-400'));
const winRateClass = computed(() => metrics.value.winRate >= 60 ? 'text-green-500' : (metrics.value.winRate >= 50 ? 'text-yellow-500' : 'text-red-500'));
const tradesClass = computed(() => timeFrame.value === 'TODAY' && metrics.value.totalTrades > 5 ? 'text-red-500' : 'text-white');
const rrClass = computed(() => metrics.value.avgRR >= 1.5 ? 'text-green-500' : (metrics.value.avgRR >= 1 ? 'text-yellow-500' : 'text-red-500'));
</script>

<template>
    <div class="space-y-8">
        
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
            <div class="bg-[#1a1b20] border border-[#2d2f36] rounded-xl p-4 flex flex-col justify-center items-center shadow-lg">
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
                <div class="text-xl font-bold text-white text-center text-sm md:text-xl">{{ formatDurationSmart(metrics.avgDurationMs) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <div v-for="trade in filteredTrades" :key="trade.id" class="relative group">
                
                <div class="p-[2px] rounded-2xl bg-gradient-to-br from-[#8c52ff] to-[#5ce1e6] shadow-[0_0_15px_rgba(140,82,255,0.2)] hover:shadow-[0_0_25px_rgba(92,225,230,0.4)] transition-all duration-300">
                    
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

        </div>

    </div>
</template>

<style scoped>
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>